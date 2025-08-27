<?php
require_once("/var/www/html/contratheque/src/model.php"); #Appel du modèle
# Controlleur de Connexion :

# Fonction qui vérifie si l'utilisateur est inscrit lorsqu'il se connecte, elle créé un compte automatiquemment si il n'est pas créé
function is_registered($cnx, $ldap_cnx, $samaccountname){
    include("/etc/contratheque/conf.php"); #Fichier de configuration

    if(fetchDb($cnx,["samaccountname"=>$samaccountname],NULL,"users")){ #Si l'utilisateur est dans la base de données ...
        return 1; #Fin de la fonction
    } 
    else {
        #Sinon l'utilisateur est ajouté à partir des données de son compte AD
        $search_filter = "(sAMAccountName=" . $samaccountname . ")"; #Filtre de recherche
        $search = ldap_search($ldap_cnx, $SEARCH_BASE, $search_filter); #Récupération des données de l'utilisateur dans l'AD
        $entries = ldap_get_entries($ldap_cnx, $search); #Extraction des données
        $lastname=$entries[0]["sn"][0]; #Nom de famille de l'utilisateur
        $firstname=$entries[0]["givenname"][0]; #Prenom de l'utilisateur
        $mail=$entries[0]["mail"][0]; #Mail de l'utilisateur
        $user = [
            "samaccountname"=>$samaccountname,
            "lastname"=>$lastname,
            "firstname"=>$firstname,
            "mail"=>$mail,
        ];
        insertDb($cnx, $user, "users"); #Ajout de l'utilisateur dans la BDD
        return 0; #L'utilisateur n'etait pas dans la BDD mais il y a été ajouté
    }
    
}

/**
 * Vérifie si un utilisateur peut encore tenter de se connecter
 * en fonction de ses logs récents et d’un seuil maximum.
 */
function login_logs_check($cnx) {
    // Inclusion de la configuration (contient $MAX_TRIES notamment)
    include("/etc/contratheque/conf.php");

    // Récupération des logs de connexion de l’utilisateur courant (IP client)
    $user_login_log = get_login_log($cnx, get_client_ip());

    // Si aucun log ou moins de $MAX_TRIES, on autorise directement
    if (empty($user_login_log) || count($user_login_log) < $MAX_TRIES) {
        return true;
    }

    // Vérifie s’il y a au moins une connexion réussie
    // (si oui, on considère qu’il n’est pas bloqué)
    foreach ($user_login_log as $line) {
        if (!empty($line["result"]) && $line["result"] === "Success") {
            return true;
        }
    }

    // Sinon, toutes les tentatives ont échoué et dépassent le seuil → on bloque
    return false;
}

function auth_verif($cnx) {
    // Vérifie si le token de session et celui du cookie existent et correspondent
    if (
        !isset($_SESSION["UserInfo"]["Token"]) || 
        !isset($_COOKIE['UserToken']) || 
        $_SESSION["UserInfo"]["Token"] !== $_COOKIE['UserToken']
    ) {
        // tokens absents ou différents → suspicion de tentative d’accès non valide

        // Vérifie si l’utilisateur est déjà bloqué ou a dépassé les tentatives
        if (!login_logs_check($cnx)) {
            // Insère un log de type "Blocked"
            insertDb($cnx, [
                "remote_address" => get_client_ip(),
                "user_sam"       => "Unknown",  // identifiant inconnu car non authentifié
                "result"         => "Blocked"
            ], "login_logs");

            // Charge la page de blocage et arrête immédiatement le script
            require_once("templates/error/blocked.html");
            exit;
        }

        // Pas encore bloqué, Echec d’authentification)
        return 0;
    }

    // Authentification réussie
    return 1;
}

function get_client_ip() {
    // Vérifie si l'adresse IP vient d'une connexion partagée (rare mais possible)
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Vérifie si l'IP est passée via un proxy ou un load balancer
    // Dans ce cas, HTTP_X_FORWARDED_FOR peut contenir plusieurs IPs séparées par des virgules
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Sépare les IPs, supprime les espaces, et prend la première (IP d'origine)
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }

    // Sinon, utilise l'adresse IP directe fournie par le serveur web
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    // Si aucune IP n'est détectée
    return "Unknown";
}


/**
 * Gère le processus d’authentification de l’utilisateur.
 */
function login($cnx) {
    include("/etc/contratheque/conf.php");

    // Initialisation du nombre d’essais restants si non défini
    if (!isset($_SESSION["System"]["tries"])) {
        $_SESSION["System"]["tries"] = $MAX_TRIES;
    }

    // Vérifie si le formulaire de connexion est soumis avec username et password
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {

        // Nettoyage du nom d'utilisateur (seules les lettres sont conservées)
        $username = preg_replace('/[^a-zA-Z]/', '', $_POST["username"]);
        $password = $_POST["password"];

        // Préparation de la connexion LDAP
        $ldap_cnx = ldap_connect("ldap:" . $LDAPSRV_ADDRESS);
        ldap_set_option($ldap_cnx, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Vérifie si l'utilisateur existe et est actif (si déjà enregistré en BDD)
        $user = fetchDb($cnx, ["samaccountname" => $username], ["active"], "users");

        // Vérifie les conditions d'authentification
        $validAuth = (
            ldap_bind($ldap_cnx, $username . $DOMAIN, $password) &&
            (!$user || $user[0]["active"] == 1) &&
            isset($_SESSION["System"]["CSRF"], $_POST["CSRF"]) &&
            $_SESSION["System"]["CSRF"] === $_POST["CSRF"]
        );

        if ($validAuth) {
            // Reset des sécurités
            $_SESSION["System"]["CSRF"] = "";
            $_SESSION["System"]["tries"] = $MAX_TRIES;

            // Vérifie l’inscription de l’utilisateur en BDD (sinon inscription auto)
            is_registered($cnx, $ldap_cnx, $username);
            $user = fetchDb($cnx, ["samaccountname" => $username], NULL, "users")[0];

            // Création et enregistrement d’un token sécurisé
            $token = bin2hex(random_bytes(32));
            setcookie('UserToken', $token, [
                'expires'  => time() + (86400 * 30), // 30 jours
                'path'     => '/',
                'domain'   => '', // à adapter si domaine spécifique
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            $_SESSION["UserInfo"]["Token"]         = $token;
            $_SESSION["UserInfo"]["TokenCreation"] = time();
            $_SESSION["UserInfo"]["Username"]      = $username;

            // Attribution du service utilisateur
            $user_services = fetchDb($cnx, ["user_sam" => $username], ["service_id"], "user_services");
            if (!$user_services) {
                $_SESSION["UserInfo"]["Current_Service"]      = "";
                $_SESSION["UserInfo"]["Current_Service_Name"] = "Aucun Service Attribué";
            } else {
                $service_id = $user_services[0]["service_id"];
                $_SESSION["UserInfo"]["Current_Service"]      = $service_id;
                $_SESSION["UserInfo"]["Current_Service_Name"] = fetchDb(
                    $cnx, ["id" => $service_id], ["service_name"], "services"
                )[0]["service_name"];
            }

            // Log succès
            loginLog($cnx, [
                "remote_address" => get_client_ip(),
                "user_sam"       => $username,
                "result"         => "Success"
            ], "login_logs");

            // Régénère l’ID de session pour éviter les attaques fixation de session
            session_regenerate_id(true);

            // Redirection vers l’accueil
            header("Location: index.php");
            exit;
        }

        // Cas échec de connexion
        $_SESSION["System"]["CSRF"] = "";
        $_SESSION["System"]["tries"]--;

        loginLog($cnx, [
            "remote_address" => get_client_ip(),
            "user_sam"       => $username,
            "result"         => "Failure"
        ], "login_logs");

        header("Location: index.php");
        exit;
    }

    // Si le formulaire n’a pas encore été soumis → génération d’un CSRF token
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
    require_once("templates/forms/login_page.php");
}


function logout(){
    # Fin de la session
    session_destroy(); # Destruction de la session
    $_SESSION = ""; # Effacement des données de session
    setcookie("UserToken", "", time()-3600); # Effacement du token de connexion dans le navigateur

    header("Location:index.php"); # Redirection sur le routeur
}

function error_page(){ 
    # Affichage de la page d'erreur
    require_once("templates/error/error.php");
}

?>