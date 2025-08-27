<?php

function contractor_management_page($cnx){
    // Récupération des contractants
    if($_SESSION["UserInfo"]["Current_Service"] == "*" && $_SESSION["UserInfo"]["Role"] == "Admin"){ // Si l'utilisateur est un administrateur et qu'il souhaite obtenir les resultats de tous les services
        $contractors = get_all_contractors($cnx,$_SESSION["UserInfo"]["Username"]);
    }
    else{ // Si l'utilisateur est un utilisateur landbda
        $contractors = fetchDb($cnx,["service_id"=>$_SESSION["UserInfo"]["Current_Service"]],NULL,"contractors");
    }
    $contractors = escape_array($contractors); # Echapement des données avant affichage pour eviter les injections XSS

    // Récupération des services
    $services = get_user_services($cnx,$_SESSION["UserInfo"]["Username"]);
    $services = escape_array($services); # Echapement des données avant affichage pour eviter les injections XSS
    $service_count = count($services);

    # Affiche de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/contractor_management_page.php");

}

function new_contractor($cnx){
    // Traitement de la soumission du formulaire
    if(isset($_POST["submit_new_contractor"])){
        // Vérification du token CSRF pour la sécurité
        if($_SESSION["System"]["CSRF"] !== $_POST["CSRF"]){
            $_SESSION["System"]["CSRF"] = "";
            header("Location:index.php?route=error&error=Accès Non Autorisé");
            exit;
        }

        // Récupération et nettoyage des données du formulaire
        $contractorData = [
            'ref' => $_POST["ref"] ?? "",
            'name' => ucfirst(strtolower($_POST["name"] ?? "")),
            'email' => strtolower($_POST["email"] ?? ""),
            'phone_num' => $_POST["telephone"] ?? "",
            'service_id' => $_POST["service_id"] ?? NULL
        ];

        // Validation des données avec gestion des erreurs optimisée
        $validationRules = [
            ['field' => 'ref', 'condition' => !validateLength($contractorData['ref'], 30), 
             'error' => "La référence doit être entre 1 et 30 caractères !"],
            ['field' => 'name', 'condition' => !validateLength($contractorData['name'], 100), 
             'error' => "Le nom du contractant doit être entre 1 et 100 caractères !"],
            ['field' => 'email', 'condition' => !(validateLength($contractorData['email'], 100) && validateEmailFormat($contractorData['email'])), 
             'error' => "L'email renseigné ne respecte pas le format requis !"],
            ['field' => 'phone_num', 'condition' => !($contractorData['phone_num'] && validatePhoneNumberFormat($contractorData['phone_num'])), 
             'error' => "Le numéro de téléphone ne respecte pas le format requis !"]
        ];

        // Validation spécifique pour les admins globaux
        if($_SESSION["UserInfo"]["Current_Service"] == "*" && (!$contractorData['service_id'] || !fetchDb($cnx, ["id" => $contractorData['service_id']], ["id"], "services"))){
            $validationRules[] = ['field' => 'service_id', 'condition' => true, 'error' => "Le service est invalide !"];
        }

        // Validation des permissions de service
        if($contractorData['service_id'] && !fetchDb($cnx, ["user_sam" => $_SESSION["UserInfo"]["Username"], "service_id" => $contractorData['service_id'], "role" => "Admin"], ["id"], "user_services")){
            $validationRules[] = ['field' => 'service_id', 'condition' => true, 'error' => "Vous ne pouvez pas créer de contractant dans ce service !"];
        }

        // Vérification de toutes les règles de validation
        foreach($validationRules as $rule){
            if($rule['condition']){
                $urlParams = http_build_query([
                    'route' => 'new_contractor',
                    'error' => $rule['error'],
                    'contractor_name' => $contractorData['name'],
                    'contractor_ref' => $contractorData['ref'],
                    'contractor_mail' => $contractorData['email'],
                    'contractor_tel' => $contractorData['phone_num'],
                    'contractor_service' => $contractorData['service_id']
                ]);
                header("Location:index.php?" . $urlParams);
                exit;
            }
        }

        // Préparation des données pour l'insertion en base
        $contractor = [
            "contractor_ref" => $contractorData['ref'],
            "contractor_name" => $contractorData['name'],
            "contractor_mail" => $contractorData['email'],
            "contractor_tel" => $contractorData['phone_num'],
            "service_id" => ($_SESSION["UserInfo"]["Current_Service"] == "*" && $_SESSION["UserInfo"]["Role"] == "Admin") 
                ? $contractorData['service_id'] 
                : $_SESSION["UserInfo"]["Current_Service"]
        ];

        // Insertion en base de données
        insertDb($cnx, $contractor, "contractors");

        // Construction de l'URL de redirection
        if(isset($_GET["returnto"])){
            $redirectParams = ['route' => $_GET["returnto"]];
            
            // Paramètres optionnels à conserver lors de la redirection
            $optionalParams = ['intern_num', 'contract_name', 'contract_followup', 'contract_start', 'contract_end', 'service_id'];
            foreach($optionalParams as $param){
                if(isset($_POST[$param]) && $_POST[$param] !== ''){
                    $redirectParams[$param] = $_POST[$param];
                }
            }
            
            header("Location:index.php?" . http_build_query($redirectParams));
        } else {
            header("Location:index.php?route=contractor_management");
        }
        exit;
    }

    // Affichage du formulaire - génération du token CSRF
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
    
    // Chargement des services pour les admins globaux
    if($_SESSION["UserInfo"]["Role"] == "Admin" && $_SESSION["UserInfo"]["Current_Service"] == "*"){
        $services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
    }
    
    // Inclusion des templates
    require_once("templates/headers/tool_header.php");
    require_once("templates/forms/new_contractor_form.php");
}

function edit_contractor($cnx){
    // Vérification de la soumission du formulaire et du token CSRF
    if(!isset($_POST["submit_edit"]) || $_SESSION["System"]["CSRF"] !== $_POST["CSRF"]){
        $_SESSION["System"]["CSRF"] = "";
        header("Location:index.php?route=error&error=Requête erronée !");
        exit;
    }

    // Récupération et nettoyage des données du formulaire
    $contractorData = [
        'id' => $_POST["id"] ?? "",
        'ref' => $_POST["ref"] ?? "",
        'name' => ucfirst(strtolower($_POST["name"] ?? "")),
        'email' => strtolower($_POST["email"] ?? ""),
        'phone_num' => $_POST["telephone"] ?? ""
    ];

    // Vérification préalable de l'existence du contractant pour les permissions
    $contractorExists = $contractorData['id'] && is_numeric($contractorData['id']) && fetchDb($cnx, ["id" => $contractorData['id']], ["id"], "contractors");
    
    if($contractorExists){
        // Récupération du service_id et vérification des permissions en une seule requête optimisée
        $service_id = fetchDb($cnx, ["id" => $contractorData['id']], ["service_id"], "contractors")[0]["service_id"];
        $userRole = fetchDb($cnx, ["user_sam" => $_SESSION["UserInfo"]["Username"], "service_id" => $service_id], ["role"], "user_services");
        $hasPermission = $userRole && in_array($userRole[0]["role"], ["Admin", "Manager"]);
    } else {
        $hasPermission = false;
    }

    // Règles de validation avec gestion d'erreurs optimisée
    $validationRules = [
        ['condition' => !$contractorExists, 
         'error' => "Contractant inconnu !"],
        ['condition' => !$hasPermission, 
         'error' => "Vous n'avez pas l'autorisation de modifier ce contractant !"],
        ['condition' => !validateLength($contractorData['ref'], 30), 
         'error' => "La référence renseignée doit être entre 1 et 30 caractères !"],
        ['condition' => !validateLength($contractorData['name'], 100), 
         'error' => "Le nom du contractant doit être entre 1 et 100 caractères !"],
        ['condition' => !(validateLength($contractorData['email'], 100) && validateEmailFormat($contractorData['email'])), 
         'error' => "L'email renseigné ne respecte pas le format requis !"],
        ['condition' => !($contractorData['phone_num'] && validatePhoneNumberFormat($contractorData['phone_num'])), 
         'error' => "Le numéro de téléphone renseigné ne respecte pas le format requis !"]
    ];

    // Vérification de toutes les règles de validation
    foreach($validationRules as $rule){
        if($rule['condition']){
            $_SESSION["System"]["CSRF"] = "";
            header("Location:index.php?route=contractor_management&error=" . urlencode($rule['error']));
            exit;
        }
    }

    // Préparation des données pour la mise à jour
    $updateData = [
        "contractor_ref" => $contractorData['ref'],
        "contractor_name" => $contractorData['name'],
        "contractor_mail" => $contractorData['email'],
        "contractor_tel" => $contractorData['phone_num']
    ];

    // Mise à jour en base de données
    updateDb($cnx, $updateData, ["id" => $contractorData['id']], "contractors");

    // Nettoyage du token CSRF et redirection
    $_SESSION["System"]["CSRF"] = "";
    header("Location:index.php?route=contractor_management");
    exit;
}


function delete_contractor($cnx) {
    // Vérifie que la suppression a été demandée et que le token CSRF est valide
    if (isset($_POST["submit_deletion"]) && $_SESSION["System"]["CSRF"] == $_POST["CSRF"]) {

        // Récupère l'ID du contractant à supprimer, vide si non défini
        $id = $_POST["contractor_id"] ?? "";

        if ($id) {
            // Initialisation du drapeau de validation (non utilisé ici)
            $check = 1;

            // Récupère le contractant ciblé pour vérifier son appartenance au service courant
            $contractor = fetchDb($cnx, ["id" => $id], ["service_id"], "contractors");

            // Vérifie que le contractant existe et que l'utilisateur y a accès (même service ou admin)
            if (!($contractor && (
                    $contractor[0]["service_id"] == $_SESSION["UserInfo"]["Current_Service"] ||
                    ($_SESSION["UserInfo"]["Current_Service"] == "*" && in_array($contractor[0]["service_id"],array_column(get_user_services_ids($cnx,$_SESSION["UserInfo"]["Username"]),"service_id")))
                ))) {
                $error = "Contractant Invalide !";
            }

            // Vérifie si le contractant a encore des contrats actifs
            if (fetchDb($cnx, ["contractor_id" => $id, "status" => "active"], ["intern_num"], "contracts")) {
                $error = "Veuillez traiter les contrats liés au contractant avant de le supprimer !";
            }

            // Si une erreur a été détectée, redirige avec message d'erreur
            if (isset($error)) {
                header("Location:index.php?route=contractor_management&error=" . urlencode($error));
                exit;
            }

            // Suppression du contractant si aucune erreur
            deleteDb($cnx, ["id" => $id], "contractors");
        }
    }

    // Réinitialise le token CSRF
    $_SESSION["System"]["CSRF"] = "";

    // Redirige vers la page de gestion des contractants
    header("Location:index.php?route=contractor_management");
}


?>