<?php

# Import des dépendences
require_once("src/model.php");
require_once("src/validators/validators.php");
require_once("src/helper/helper.php");

# Ajout d'un nouvel utilisateur
function new_user($cnx) {
    // Vérification si le formulaire a été soumis
    if (isset($_POST["submit_new_user"])) {
        
        // === VALIDATION CSRF ===
        // Vérification du token CSRF pour éviter les attaques Cross-Site Request Forgery
        if ($_SESSION["System"]["CSRF"] !== $_POST["CSRF"]) {
            $_SESSION["System"]["CSRF"] = "";
            header("Location: index.php?route=error&error=Accès Non Autorisé");
            exit;
        }
        
        // Réinitialisation du token CSRF après utilisation
        $_SESSION["System"]["CSRF"] = "";
        
        // === RÉCUPÉRATION ET NETTOYAGE DES DONNÉES ===
        // Normalisation des données utilisateur avec valeurs par défaut sécurisées
        $samaccountname = isset($_POST["samaccountname"]) ? strtolower(trim($_POST["samaccountname"])) : '';
        $firstname = isset($_POST["firstname"]) ? ucfirst(strtolower(trim($_POST["firstname"]))) : '';
        $lastname = isset($_POST["lastname"]) ? strtoupper(trim($_POST["lastname"])) : '';
        $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
        $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : null;
        
        // Conversion des checkboxes en booléens (plus lisible)
        $isAdmin = isset($_POST["isadmin"]);
        $reminder = isset($_POST["reminder"]);
        $addAllServices = isset($_POST["addallservices"]);
        
        // === VALIDATION DES DONNÉES ===
        $errors = [];
        
        // Validation de l'unicité du nom d'utilisateur
        if (fetchDb($cnx, ["samaccountname" => $samaccountname], null, "users")) {
            $errors[] = "Cet utilisateur existe déjà !";
        }
        
        // Validation des longueurs de champs
        if (!validateLength($samaccountname, 30)) {
            $errors[] = "L'identifiant doit contenir entre 1 et 30 caractères !";
        }
        
        if (!validateLength($firstname, 30)) {
            $errors[] = "Le prénom doit contenir entre 1 et 30 caractères !";
        }
        
        if (!validateLength($lastname, 30)) {
            $errors[] = "Le nom de famille doit contenir entre 1 et 30 caractères !";
        }
        
        // Validation de l'email (longueur et format)
        if (!validateLength($email, 50) || !validateEmailFormat($email)) {
            $errors[] = "L'email fourni est invalide !";
        }
        
        // Validation du téléphone (optionnel)
        if ($phone !== null && !validatePhoneNumberFormat($phone)) {
            $errors[] = "Le numéro de téléphone fourni est invalide !";
        }
        
        // Vérification de l'unicité du nom complet
        if (fetchDb($cnx, ["firstname" => $firstname, "lastname" => $lastname], ["samaccountname"], "users")) {
            $errors[] = "Un utilisateur existant possède déjà ce nom !";
        }
        
        // Vérification de l'unicité de l'email
        if (fetchDb($cnx, ["mail" => $email], ["samaccountname"], "users")) {
            $errors[] = "Un utilisateur utilise déjà cet email !";
        }
        
        // === GESTION DES ERREURS ===
        // Si des erreurs sont détectées, redirection avec conservation des données saisies
        if (!empty($errors)) {
            // Utilisation du premier message d'erreur pour la compatibilité
            $error = $errors[0];
            
            // Construction de l'URL de redirection avec préservation des données
            $params = http_build_query([
                'route' => 'new_user',
                'error' => $error,
                'samname' => $samaccountname,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'phone' => $phone,
                'isadmin' => $isAdmin ? 1 : 0,
                'reminder' => $reminder ? 1 : 0
            ]);
            
            header("Location: index.php?$params");
            exit;
        }
                
        // === CRÉATION DE L'UTILISATEUR ===
        // Préparation des données utilisateur pour insertion en base
        $userData = [
            "samaccountname" => $samaccountname,
            "firstname" => $firstname,
            "lastname" => $lastname,
            "mail" => $email,
            "tel_num" => $phone,
            "is_admin" => $isAdmin ? 1 : 0,
            "contracts_reminders" => $reminder ? 1 : 0,
        ];
        
        // Insertion de l'utilisateur en base de données
        insertDb($cnx, $userData, "users");

	// === GESTION DES SERVICES POUR LES ADMINS ===
        // Si l'utilisateur est admin ET qu'on doit ajouter tous les services
        if ($isAdmin && $addAllServices) {
            $services = fetchDb($cnx, [], ["id"], "services");
            
            // Attribution du rôle Admin sur tous les services
            foreach ($services as $service) {
                insertDb($cnx, [
                    "user_sam" => $samaccountname,
                    "service_id" => $service["id"],
                    "role" => "Admin"
                ], "user_services");
            }
        }
        
        // Redirection vers la page de gestion des utilisateurs après succès
        header("Location: index.php?route=user_management");
        exit;
        
    } else {
        // === AFFICHAGE DU FORMULAIRE ===
        
        // Récupération des services si l'utilisateur a accès à tous les services
        if ($_SESSION["UserInfo"]["Current_Service"] === "*") {
            $services = fetchDb($cnx, [], null, "services");
            $services = escape_array($services);
        }
        
        // Génération d'un nouveau token CSRF pour sécuriser le formulaire
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
        
        // Inclusion des templates pour l'affichage
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/new_user_form.php");
    }
}

# Modification d'un utilisateur 
function update_user($cnx) {
    // --- Traitement du formulaire de mise à jour utilisateur ---
    if (isset($_POST["submit_edit"])) {

        // Vérification du token CSRF pour éviter les attaques
        if ($_SESSION["System"]["CSRF"] === ($_POST["CSRF"] ?? '')) {
            $_SESSION["System"]["CSRF"] = ""; // Reset du token après usage

            // Récupération et normalisation des champs POST
            $old_samaccountname = $_POST["old_samaccountname"] ?? '';
            $samaccountname     = strtolower(trim($_POST["samaccountname"] ?? ''));
            $firstname          = ucfirst(strtolower(trim($_POST["firstname"] ?? '')));
            $lastname           = strtoupper(trim($_POST["lastname"] ?? ''));
            $email              = trim($_POST["email"] ?? '');
            $phone              = !empty($_POST["telephone"]) ? trim($_POST["telephone"]) : NULL;
            $isAdmin            = isset($_POST["isadmin"]) ? 1 : 0;
            $reminder           = isset($_POST["reminder"]) ? 1 : 0;

            $check = true; // Flag de validation
            $error = "";

            // Vérifie que l'utilisateur existe avant modification
            $existing_user = fetchDb($cnx, ["samaccountname" => $old_samaccountname], NULL, "users");
            if (!$existing_user) {
                $error = "L'utilisateur que vous essayez de modifier n'existe pas !";
                $check = false;
            }

            // Vérifie si le nouvel identifiant est déjà pris par un autre utilisateur
            if ($samaccountname !== $old_samaccountname && fetchDb($cnx, ["samaccountname" => $samaccountname], NULL, "users")) {
                $error = "Un autre utilisateur utilise déjà cet identifiant !";
                $check = false;
            }

            // Récupération des infos actuelles de l’utilisateur
            $updated_user = $existing_user[0] ?? [];

            // Vérifie si un autre utilisateur possède déjà ce nom complet
            if (($updated_user["firstname"] !== $firstname || $updated_user["lastname"] !== $lastname) 
                && fetchDb($cnx, ["firstname" => $firstname, "lastname" => $lastname], ["samaccountname"], "users")) {
                $error = "Un utilisateur existant possède déjà ce nom !";
                $check = false;
            }

            // Vérifie si l'email est unique
            if ($updated_user["mail"] !== $email && fetchDb($cnx, ["mail" => $email], ["samaccountname"], "users")) {
                $error = "Un utilisateur utilise déjà cet email !";
                $check = false;
            }

            // --- Validation des formats ---
            if ($old_samaccountname !== $_SESSION["UserInfo"]["Username"] && !validateLength($samaccountname, 30)) {
                $error = "L'identifiant doit contenir entre 1 et 30 caractères !";
                $check = false;
            }
            if (!validateLength($firstname, 30)) {
                $error = "Le prénom doit contenir entre 1 et 30 caractères !";
                $check = false;
            }
            if (!validateLength($lastname, 30)) {
                $error = "Le nom doit contenir entre 1 et 30 caractères !";
                $check = false;
            }
            if (!validateLength($email, 50) || !validateEmailFormat($email)) {
                $error = "L'email fourni est invalide !";
                $check = false;
            }
            if ($phone !== NULL && !validatePhoneNumberFormat($phone)) {
                $error = "Le numéro de téléphone fourni est invalide !";
                $check = false;
            }

            // --- Gestion des erreurs ---
            if (!$check) {
                header("Location:index.php?route=update_user&userId=" . urlencode($old_samaccountname) . "&error=" . urlencode($error));
                exit;
            }

            // --- Mise à jour des informations ---
            $user = [];
		
	    if($old_samaccountname === $_SESSION["UserInfo"]["Username"]){
		$samaccountname = $old_samaccountname;
	    }


            // Gestion du rôle admin (cas particulier si on enlève/ajoute admin à un autre utilisateur)
            if ($updated_user["is_admin"] && !$isAdmin && $old_samaccountname !== $_SESSION["UserInfo"]["Username"]) {
                updateDb($cnx, ["role" => "User"], ["user_sam" => $old_samaccountname], "user_services");
                $user["is_admin"] = $isAdmin;
            } elseif (!$updated_user["is_admin"] && $isAdmin && $old_samaccountname !== $_SESSION["UserInfo"]["Username"]) {
                updateDb($cnx, ["role" => "Admin"], ["user_sam" => $old_samaccountname], "user_services");
                $user["is_admin"] = $isAdmin;  
            } else {
                // Cas classique : mise à jour des infos personnelles
                $user = [
                    "samaccountname"     => $samaccountname,
                    "lastname"           => $lastname,
                    "firstname"          => $firstname,
                    "mail"               => $email,
                    "tel_num"            => $phone,
                    "contracts_reminders"=> $reminder,
                ];
            }

            // Exécute la mise à jour
            updateDb($cnx, $user, ["samaccountname" => $old_samaccountname], "users");

            // Redirection vers la gestion des utilisateurs
            header("Location:index.php?route=user_management");
            exit;

        } else {
            // CSRF invalide → blocage
            $_SESSION["System"]["CSRF"] = "";
            header("Location:index.php?route=error&error=Accès Non Autorisé");
            exit;
        }
    }

    // --- Affichage du formulaire de mise à jour si un userId est fourni ---
    if (isset($_GET["userId"]) && fetchDb($cnx, ["samaccountname" => $_GET["userId"]], ["samaccountname"], "users")) {
        $updated_user = escape_array(fetchDb($cnx, ["samaccountname" => $_GET["userId"]], NULL, "users"))[0];
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16)); // Génération d’un nouveau token CSRF
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/update_user_form.php");
    } else {
        // Si l'utilisateur n'existe pas → retour à la liste
        header("Location:index.php?route=error&error=Utilisateur Invalide !");
        exit;
    }
}


# Récupération du rôle d'un utilisateur
function get_user_role($cnx, $user_sam, $service_id) {
    // Vérifie si l'utilisateur est administrateur global
    $user_data = fetchDb($cnx, ["samaccountname" => $user_sam], ["is_admin"], "users");
    
    // Si l'utilisateur existe et est admin → renvoie "Admin"
    if (!empty($user_data) && !empty($user_data[0]["is_admin"])) {
        return "Admin";
    }

    // Si on passe "*" en service_id, on considère l'utilisateur comme simple "User"
    if ($service_id === "*") {
        return "User";
    }

    // Recherche le rôle spécifique de l'utilisateur dans un service donné
    $result = fetchDb($cnx, ["user_sam" => $user_sam, "service_id" => $service_id], ["role"], "user_services");

    // Si un rôle est trouvé → renvoie ce rôle, sinon renvoie 0
    return (!empty($result) && !empty($result[0]["role"])) ? $result[0]["role"] : 0;
}


# Désactivation d'un utilisateur
function deactivate_user($cnx) {
    // Vérifie si le formulaire de désactivation a bien été soumis
    if (isset($_POST["submit_deactivation"]) && ($_SESSION["System"]["CSRF"] ?? '') === ($_POST["CSRF"] ?? '')) {
        
        // Récupère le nom d'utilisateur à désactiver (sécurité : valeur par défaut = "")
        $username = trim($_POST["deactivate_user_id"] ?? "");

        // Vérifie que l'utilisateur existe dans la base
        $user_exists = fetchDb($cnx, ["samaccountname" => $username], ["samaccountname"], "users");

        if (!empty($user_exists)) {
            // Empêche un utilisateur de se désactiver lui-même
            if ($_SESSION["UserInfo"]["Username"] !== $username) {
                // Mise à jour de l'utilisateur → active = 0
                updateDb($cnx, ["active" => 0], ["samaccountname" => $username], "users");
            }
        }
    }

    // Réinitialisation du CSRF token pour éviter une réutilisation
    $_SESSION["System"]["CSRF"] = "";

    // Redirection vers la page de gestion des utilisateurs
    header("Location: index.php?route=user_management");
    exit;
}

# Suppression d'un utilisateur
function delete_user($cnx) {
    // Vérifie que le formulaire a été soumis et que le CSRF est valide
    if (isset($_POST["submit_deletion"]) && ($_SESSION["System"]["CSRF"] ?? '') === ($_POST["CSRF"] ?? '')) {

        // Récupère l'identifiant de l'utilisateur à supprimer (sécurité : trim + valeur par défaut vide)
        $username = trim($_POST["del_user_id"] ?? "");

        // Vérifie si l'utilisateur existe dans la base
        $user_exists = fetchDb($cnx, ["samaccountname" => $username], ["samaccountname"], "users");

        if (!empty($user_exists)) {
            // On interdit à l'utilisateur de se supprimer lui-même
            if ($username !== $_SESSION["UserInfo"]["Username"]) {
                // Suppression de l'utilisateur dans la base
                deleteDb($cnx, ["samaccountname" => $username], "users");
            }
        }
    }

    // Réinitialisation du token CSRF pour éviter toute réutilisation
    $_SESSION["System"]["CSRF"] = "";

    // Redirection vers la gestion des utilisateurs
    header("Location: index.php?route=user_management");
    exit;
}

# Récupération d'un utilisateur désactivé
function recover_user($cnx) {
    // Vérifie que le formulaire de restauration a bien été soumis
    // et que le token CSRF est valide
    if (isset($_POST["submit_recover"]) && ($_SESSION["System"]["CSRF"] ?? '') === ($_POST["CSRF"] ?? '')) {
        
        // Récupère le nom d'utilisateur à réactiver (trim + valeur par défaut vide pour sécurité)
        $username = trim($_POST["recover_username"] ?? "");

        // Vérifie si l'utilisateur existe bien dans la base
        $user_exists = fetchDb($cnx, ["samaccountname" => $username], ["samaccountname"], "users");

        if (!empty($user_exists)) {
            // On interdit à un utilisateur de se réactiver lui-même
            if ($_SESSION["UserInfo"]["Username"] !== $username) {
                // Réactivation du compte (active = 1)
                updateDb($cnx, ["active" => 1], ["samaccountname" => $username], "users");
            }
        }
    }

    // Réinitialisation systématique du CSRF token pour éviter une réutilisation
    $_SESSION["System"]["CSRF"] = "";

    // Redirection vers la gestion des utilisateurs
    header("Location: index.php?route=user_management");
    exit;
}

# Page de gestion des utilisateurs
function user_management_page($cnx) {

    # Récupération des utilisateurs dans la BDD
    $users = fetchDb($cnx, [], NULL, "users");
    $displayed_users = escape_array($users);

    # Récupération des services auxquels l'utilisateur actuel a accès
    $user_services = get_user_services($cnx,$_SESSION["UserInfo"]["Username"]);
    $filtered_services = escape_array($user_services);
    $service_count = count($filtered_services);
    
    # Génration du token CSRF
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/user_management.php");
}


?>