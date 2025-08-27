<?php

# Import des dépendences
require_once("src/model.php");
require_once("src/validators/validators.php");

#################################################################################################################################

# Vérification qu'un utilisateur a accès à un service
function check_user_service($cnx,$user_sam,$service_id){
    if(in_array($service_id,array_column(get_user_services_ids($cnx,$user_sam),"service_id"))){
        return 1;
    }
    return 0;
}

# Changement du service actuellement séléctionné par l'utilisateur
function change_service($cnx) {
    // Vérifie si un changement de service est demandé, différent de l’actuel,
    // et que le token CSRF correspond bien
    if (
        isset($_POST["submit-service"]) &&
        isset($_POST["select_service"], $_POST["CSRF"]) &&
        $_POST["select_service"] !== $_SESSION["UserInfo"]["Current_Service"] &&
        $_SESSION["System"]["CSRF"] === $_POST["CSRF"]
    ) {
        $selectedService = $_POST["select_service"];

        // Vérifie si l’utilisateur a le droit de sélectionner ce service
        if ($selectedService === "*" || check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $selectedService)) {
            // Mise à jour du service courant
            $_SESSION["UserInfo"]["Current_Service"] = $selectedService;

            if ($selectedService === "*") {
                $_SESSION["UserInfo"]["Current_Service_Name"] = "Tous les Services";
            } else {
                // Récupère le nom du service en BDD
                $service = fetchDb($cnx, ["id" => $selectedService], ["service_name"], "services");
                $_SESSION["UserInfo"]["Current_Service_Name"] = $service[0]["service_name"] ?? "Service inconnu";
            }
        } else {
            // Tentative non autorisée → destruction de session et suppression du cookie
            session_destroy();
            unset($_COOKIE['UserToken']);
            setcookie('UserToken', '', time() - 3600, '/', '', false, true);
            header("Refresh:0");
            exit;
        }
    }

    // Reset du token CSRF après traitement
    $_SESSION["System"]["CSRF"] = "";

    // Redirection vers la route demandée ou vers l’accueil
    if (!empty($_GET["returnto"])) {
        header("Location: index.php?route=" . urlencode($_GET["returnto"]));
    } else {
        header("Location: index.php");
    }
    exit;
}

# Page de gestion des services
function service_management_page($cnx) {

    # Récupération des services dans la BDD
    $services = fetchDb($cnx,[],NULL,"services");
    $services = escape_array($services);

    # Fénération du token CSRF
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/service_management.php");
}

# Supprimer un service
function delete_service($cnx) {
    // Récupération et validation de l'ID du service à supprimer
    $service_id = $_POST["del_service_id"] ?? "";
    $csrf_token = $_POST["CSRF"] ?? "";
    $error_message = "";
    
    // Vérification des conditions préalables à la suppression
    if (empty($service_id)) {
        $error_message = "ID de service manquant";
    } elseif (!fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
        $error_message = "Service introuvable";
    } elseif ($_SESSION["System"]["CSRF"] !== $csrf_token) {
        $error_message = "Token CSRF invalide";
    } elseif (fetchDb($cnx, ["service_id" => $service_id], ["intern_num"], "contracts")) {
        $error_message = "Tous les contrats doivent être retirés du service avant sa suppression !";
    } elseif ($_SESSION["UserInfo"]["Current_Service"] == $service_id) {
        $error_message = "Vous ne pouvez pas supprimer le service dans lequel vous vous trouvez actuellement !";
    }
    
    // Exécution de la suppression si aucune erreur n'est détectée
    if (empty($error_message)) {
        deleteDb($cnx, ["id" => $service_id], "services");
    }
    
    // Nettoyage du token CSRF pour éviter sa réutilisation
    $_SESSION["System"]["CSRF"] = "";
    
    // Redirection avec ou sans message d'erreur
    $redirect_url = "index.php?route=service_management";
    if (!empty($error_message)) {
        $redirect_url .= "&error=" . urlencode($error_message);
    }
    
    header("Location: $redirect_url");
    exit;
}

# Ajout d'un nouveau service
function new_service($cnx) {

    // Cas 1 : soumission du formulaire
    if (isset($_POST["submit_new_service"])) {

        // Vérification du token CSRF
        if (isset($_POST["CSRF"]) && $_SESSION["System"]["CSRF"] === $_POST["CSRF"]) {

            // Reset du CSRF après usage
            $_SESSION["System"]["CSRF"] = "";

            // Normalisation des champs
            $name       = isset($_POST["name"]) ? ucwords(strtolower(trim($_POST["name"]))) : "";
            $short_name = isset($_POST["short_name"]) ? strtoupper(trim($_POST["short_name"])) : "";

            // Validation des champs
            $error = "";
            $valid = true;

            // Vérifie le nom du service
            if (!validateLength($name, 100) || fetchDb($cnx, ["service_name" => $name], ["id"], "services")) {
                $error = "Le nom du service doit être entre 1 et 100 caractères et ne pas déjà exister.";
                $valid = false;
            }

            // Vérifie l’abréviation (2 ou 3 caractères, unique)
            if (!(strlen($short_name) === 2 || strlen($short_name) === 3) || fetchDb($cnx, ["short_name" => $short_name], NULL, "services")) {
                $error = "L'abréviation doit comporter 2 ou 3 caractères et ne pas déjà être utilisée.";
                $valid = false;
            }

            // Si une erreur est détectée → retour au formulaire avec message
            if (!$valid) {
                header(
                    "Location:index.php?route=new_service" .
                    "&error=" . urlencode($error) .
                    "&service_name=" . urlencode($name) .
                    "&shortname=" . urlencode($short_name)
                );
                exit;
            }

            // Insertion du nouveau service
            $service = [
                "service_name" => $name,
                "short_name"   => $short_name,
            ];
            insertDb($cnx, $service, "services");

            // Redirection vers la gestion des services
            header("Location: index.php?route=service_management");
            exit;
        } else {
            // CSRF invalide → accès interdit
            $_SESSION["System"]["CSRF"] = "";
            header("Location:index.php?route=error&error=" . urlencode("Accès Non Autorisé"));
            exit;
        }
    }

    // Cas 2 : affichage du formulaire de création
    else {
        // Génération du token CSRF
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

        // Chargement des templates
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/new_service_form.php");
    }
}

# Edition d'un service
function update_service($cnx) {
    // Traitement de la soumission du formulaire de mise à jour
    if (isset($_POST["submit_service_update"])) {
        // Validation du token CSRF
        if ($_SESSION["System"]["CSRF"] !== ($_POST["CSRF"] ?? "")) {
            $_SESSION["System"]["CSRF"] = "";
            header("Location: index.php?route=error&error=" . urlencode("Accès Non Autorisé"));
            exit;
        }
        
        // Nettoyage du token CSRF après vérification
        $_SESSION["System"]["CSRF"] = "";
        
        // Récupération et formatage des données du formulaire
        $service_id = $_POST["id"] ?? "";
        $name = ucwords(strtolower(trim($_POST["name"] ?? "")));
        $short_name = strtoupper(trim($_POST["short_name"] ?? ""));
        $error = "";
        
        // Validation de l'existence du service
        if (!fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
            $error = "Ce service n'existe pas !";
        }
        
        // Validation du nom du service (longueur et unicité)
        if (empty($error)) {
            $existing_service = fetchDb($cnx, ["service_name" => $name], ["id"], "services");
            $existing_service_info = $existing_service[0] ?? null;
            
            if (!validateLength($name, 100) || ($existing_service_info && $existing_service_info["id"] != $service_id)) {
                $error = "Le nom du service doit faire entre 1 et 100 caractères et ne doit pas déjà être utilisé par un autre service";
            }
        }
        
        // Validation de l'abréviation (longueur entre 2-3 caractères et unicité)
        if (empty($error)) {
            $existing_short_service = fetchDb($cnx, ["short_name" => $short_name], ["id"], "services");
            $existing_short_info = $existing_short_service[0] ?? null;
            $short_name_length = strlen($short_name);
            
            if (($short_name_length < 2 || $short_name_length > 3) || 
                ($existing_short_info && $existing_short_info["id"] != $service_id)) {
                $error = "L'abréviation doit être entre 2 et 3 caractères et ne doit pas être déjà utilisé par un autre service.";
            }
        }
        
        // Redirection en cas d'erreur de validation
        if (!empty($error)) {
            header("Location: index.php?route=update_service&serviceId=" . urlencode($service_id) . "&error=" . urlencode($error));
            exit;
        }
        
        // Mise à jour du nom du service dans la session si c'est le service courant
        if ($service_id == $_SESSION["UserInfo"]["Current_Service"]) {
            $_SESSION["UserInfo"]["Current_Service_Name"] = $name;
        }
        
        // Préparation et exécution de la mise à jour en base de données
        $service_data = [
            "service_name" => $name,
            "short_name" => $short_name,
        ];
        updateDb($cnx, $service_data, ["id" => $service_id], "services");
        
        // Redirection vers la page de gestion des services après succès
        header("Location: index.php?route=service_management");
        exit;
    }
    
    // Affichage du formulaire de mise à jour
    else {
        $service_id = $_GET["serviceId"] ?? "";
        
        // Validation de l'ID du service et de son existence
        if (!is_numeric($service_id) || !fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
            header("Location:index.php?route=error&error=Service Invalide !");
            exit;
        }
        
        // Récupération des données du service à modifier
        $updated_service = escape_array(fetchDb($cnx, ["id" => $service_id], null, "services"))[0];
        
        // Récupération des utilisateurs selon les permissions
        $users = fetchDb($cnx, [], null, "users");
        $displayed_users = [];
        $user_role = $_SESSION["UserInfo"]["Role"] ?? "";
        
        // Filtrage des utilisateurs selon le rôle (Admin ou Manager peuvent voir tous les utilisateurs)
        foreach ($users as $user) {
            if ($user_role === "Admin" || $user_role === "Manager") {
                $displayed_users[] = $user;
            }
        }
        
        // Sécurisation des données utilisateur pour l'affichage
        $users = escape_array($displayed_users);
        
        // Génération d'un nouveau token CSRF pour le formulaire
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
        
        // Inclusion des templates pour l'affichage du formulaire
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/update_service_form.php");
    }
}

# Modification du rôle d'un utilisateur dans un service
function update_service_user_role($cnx) {
    // Récupération et validation des données d'entrée
    $user_sam = trim($_POST["user_sam"] ?? "");
    $service_id = $_POST["service_id"] ?? "";
    $role = $_POST["new_role"] ?? "";
    $current_user = $_SESSION["UserInfo"]["Username"] ?? "";
    $error = "";
    
    // Validation de l'existence du service
    if (empty($service_id) || !fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
        header("Location:index.php?route=error&error=Service Invalide !");
        exit;
    }
    
    // Validation de l'utilisateur (existence et appartenance au service)
    if (empty($user_sam)) {
        $error = "Veuillez renseigner un utilisateur valide !";
    } elseif (!fetchDb($cnx, ["samaccountname" => $user_sam], ["samaccountname"], "users")) {
        $error = "Veuillez renseigner un utilisateur valide !";
    } elseif (!fetchDb($cnx, ["user_sam" => $user_sam, "service_id" => $service_id], null, "user_services")) {
        $error = "Veuillez renseigner un utilisateur valide !";
    }
    
    // Validation des permissions de l'utilisateur actuel
    if (empty($error) && get_user_role($cnx, $current_user, $service_id) !== "Admin") {
        $error = "Vous n'avez pas les permissions d'effectuer cette opération !";
    }
    
    // Interdiction de modifier le rôle d'un administrateur
    if (empty($error) && get_user_role($cnx, $user_sam, $service_id) === "Admin") {
        $error = "Vous ne pouvez pas changer le rôle d'un administrateur !";
    }
    
    // Interdiction de modifier son propre rôle
    if (empty($error) && $current_user === $user_sam) {
        $error = "Vous ne pouvez pas modifier votre propre rôle !";
    }
    
    // Validation du nouveau rôle
    if (empty($error)) {
        $valid_roles = ["Manager", "User"];
        if (empty($role) || !in_array($role, $valid_roles, true)) {
            $error = "Le rôle renseigné est invalide !";
        }
    }
    
    // Redirection en cas d'erreur avec message approprié
    if (!empty($error)) {
        $redirect_url = "index.php?route=service_management&serviceId=" . urlencode($service_id) . "&error=" . urlencode($error);
        header("Location: $redirect_url");
        exit;
    }
    
    // Mise à jour du rôle de l'utilisateur dans le service
    $update_data = ["role" => $role];
    $conditions = ["user_sam" => $user_sam, "service_id" => $service_id];
    updateDb($cnx, $update_data, $conditions, "user_services");
    
    // Redirection vers la page des utilisateurs du service après succès
    header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id));
    exit;
}

# Suppression d'un utilisateur d'un service
function remove_service_user($cnx) {
    // Récupération et validation des données d'entrée
    $user_sam = trim($_POST["user_sam"] ?? "");
    $service_id = $_POST["service_id"] ?? "";
    $csrf_token = $_POST["CSRF"] ?? "";
    $current_user = $_SESSION["UserInfo"]["Username"] ?? "";
    $error = "";
    
    // Validation des données requises et de leur existence en base
    if (empty($user_sam) || empty($service_id)) {
        header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id));
        exit;
    }
    
    // Vérification de l'existence de l'utilisateur
    if (!fetchDb($cnx, ["samaccountname" => $user_sam], ["samaccountname"], "users")) {
        header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id));
        exit;
    }
    
    // Vérification de l'existence du service
    if (!fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
        header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id));
        exit;
    }
    
    // Validation du token CSRF
    if ($_SESSION["System"]["CSRF"] !== $csrf_token) {
        $_SESSION["System"]["CSRF"] = "";
        header("Location: index.php?route=error&error=" . urlencode("Accès Non Autorisé"));
        exit;
    }
    
    // Nettoyage du token CSRF après validation
    $_SESSION["System"]["CSRF"] = "";
    
    // Récupération des rôles pour les validations de permissions
    $current_user_role = get_user_role($cnx, $current_user, $service_id);
    $target_user_role = get_user_role($cnx, $user_sam, $service_id);
    
    // Validation : un Manager ne peut retirer que des Users
    if (!$current_user_role || ($current_user_role === "Manager" && $target_user_role !== "User")) {
        $error = "Vous n'avez pas l'autorisation de retirer cet utilisateur du service !";
    }
    
    // Validation : interdiction de se retirer soi-même du service
    if (empty($error) && $current_user_role != "Admin" && $current_user === $user_sam) {
        $error = "Vous ne pouvez pas vous retirer vous-même du service !";
    }
    
    // Redirection en cas d'erreur de permissions
    if (!empty($error)) {
        $redirect_url = "index.php?route=service_users&serviceId=" . urlencode($service_id) . "&error=" . urlencode($error);
        header("Location: $redirect_url");
        exit;
    }
    
    // Suppression de l'utilisateur du service
    $delete_conditions = [
        "user_sam" => $user_sam,
        "service_id" => $service_id
    ];
    deleteDb($cnx, $delete_conditions, "user_services");
    
    // Redirection vers la page des utilisateurs du service après succès
    header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id));
    exit;
}

# Ajout d'un utilisateur dans un service
function add_service_user($cnx) {
    // Traitement de la soumission du formulaire (méthode POST)
    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        // Récupération et validation des données d'entrée
        $service_id = $_POST["service_id"] ?? "";
        $user_sam = trim($_POST["user_sam"] ?? "");
        $requested_role = $_POST["role"] ?? "";
        $current_user = $_SESSION["UserInfo"]["Username"] ?? "";
        $error = "";
        
        // Validation de l'existence et validité du service
        if (empty($service_id) || !fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
            $error = "Le service sélectionné est invalide !";
        }
        
        // Validation de l'existence et validité de l'utilisateur
        if (empty($error) && (empty($user_sam) || !fetchDb($cnx, ["samaccountname" => $user_sam], ["samaccountname"], "users"))) {
            $error = "L'utilisateur sélectionné est invalide !";
        }
        
        // Vérification que l'utilisateur n'est pas déjà dans ce service
        if (empty($error) && fetchDb($cnx, ["user_sam" => $user_sam, "service_id" => $service_id], ["id"], "user_services")) {
            $error = "L'utilisateur est déjà présent dans ce service !";
        }
	        
        // Détermination du rôle à attribuer
        $role = "";
        if (empty($error)) {
            // Récupération des informations sur l'utilisateur pour vérifier s'il est admin système
            $user_info = fetchDb($cnx, ["samaccountname" => $user_sam], ["is_admin"], "users");
            $is_system_admin = $user_info[0]["is_admin"] ?? false;
            
            // Si l'utilisateur est admin système, il devient automatiquement Admin du service
            if ($is_system_admin) {
                $role = "Admin";
            } else {
                // Récupération du rôle de l'utilisateur actuel dans le service
                $current_user_role = get_user_role($cnx, $current_user, $service_id);
                
                // Si l'utilisateur actuel est Manager, il ne peut ajouter que des Users
		if (!$current_user_role){
		    header("Location:index.php?route=error&error=Accès Non Autorisé !");
		    exit;
		}
                elseif ($current_user_role === "Manager") {
                    $role = "User";
                } else {
                    // Sinon, validation du rôle demandé (Admin peut attribuer User ou Manager)
                    $valid_roles = ["User", "Manager"];
                    if (empty($requested_role) || !in_array($requested_role, $valid_roles, true)) {
                        $error = "Le rôle renseigné est invalide !";
                    } else {
                        $role = $requested_role;
                    }
                }
            }
        }
        
        // Redirection en cas d'erreur avec message approprié
        if (!empty($error)) {
            $referer = $_SERVER['HTTP_REFERER'] ?? "index.php?route=service_users&serviceId=" . urlencode($service_id);
            $redirect_url = $referer . "&error=" . urlencode($error);
            header("Location: $redirect_url");
            exit;
        }
        
        // Insertion de l'utilisateur dans le service avec le rôle déterminé
        $insert_data = [
            "user_sam" => $user_sam,
            "service_id" => $service_id,
            "role" => $role
        ];
        insertDb($cnx, $insert_data, "user_services");
        
        // Redirection vers la page des utilisateurs du service avec message de succès
        header("Location: index.php?route=service_users&serviceId=" . urlencode($service_id) . "&success=1");
        exit;
    }
    
    // Affichage du formulaire d'ajout d'utilisateur (méthode GET)
    else {
        $service_id = $_GET["serviceId"] ?? "";
        
        // Validation de l'existence du service pour l'affichage du formulaire
        if (empty($service_id) || !fetchDb($cnx, ["id" => $service_id], ["id"], "services")) {
            header("Location:index.php?route=error&error=Service Invalide !");
            exit;
        }
        
        // Génération d'un nouveau token CSRF pour sécuriser le formulaire
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
        
        // Récupération de la liste des utilisateurs non encore dans ce service
        $users = fetch_unselected_users($cnx, $service_id);
        
        // Sécurisation des données utilisateur pour l'affichage
        $users = escape_array($users);
        
        // Inclusion des templates pour l'affichage du formulaire
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/add_service_user.php");
    }
}

# Page de gestion de l'accès aux utilisateurs dans un service
function service_users($cnx){

    # Vérification que le service renseigné est valide et existant
    if(!(isset($_GET["serviceId"]) && is_numeric($_GET["serviceId"]) && fetchDb($cnx,["id"=>$_GET["serviceId"]],"id","services"))){
        header("Location:index.php?route=error&error=Service Invalide !");
    }

    # Récupération du role de l'utilisateur actuel et des utilisateurs du service dans la BDD
    $role = get_user_role($cnx,$_SESSION["UserInfo"]["Username"],$_GET["serviceId"]);
    if(!$role){
	    header("Location:index.php?route=error&error=Accès Non Autorisé !");
    }
    $users = fetch_service_users($cnx,["service_id"=>$_GET["serviceId"]]);
    $users = escape_array($users);
    
    # Génération du Token CSRF
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/forms/service_users_page.php");

}


?>