<?php

function check_session_expiration() {
    include("/etc/contratheque/conf.php");

    // Initialisation si jamais c'est la première requête
    if (!isset($_SESSION['UserInfo']['LastActivity'])) {
        $_SESSION['UserInfo']['LastActivity'] = time();
        return;
    }

    $inactiveTime = time() - $_SESSION['UserInfo']['LastActivity'];

    if ($inactiveTime > intval($EXPIRATION)) {
        // Plus de 2h d'inactivité => déconnexion
        logout();
    } else {
        // On met à jour la dernière activité
        $_SESSION['UserInfo']['LastActivity'] = time();
    }
}


function RoleVerificationMiddleware($cnx, $route) {
    // Configuration des permissions par rôle - structure hiérarchique
    $permissions = [
        'User' => ['', 'logout', 'expired_contracts', 'deleted_contracts', 'contract_detail', 
                  'file_display', 'file_download', 'change_service', 'statistics', 'help', 
                  'help_confirmation', 'error'],
        'Manager' => ['new_contract', 'update_contract', 'delete_contract', 'recover_contract', 
                     'update_notes', 'change_user_services', 'service_users', 'remove_service_user', 
                     'add_service_user', 'contractor_management', 'new_contractor', 'edit_contractor', 
                     'delete_contractor'],
        'Admin' => ['*'] // Accès total pour les admins
    ];

    // Détermination du service approprié pour récupérer le rôle utilisateur
    $serviceId = $_SESSION["UserInfo"]["Current_Service"];
    
    // Cas spécial : service global "*" avec numéro interne fourni
    // Recherche du service spécifique associé au contrat
    if ($serviceId === "*" && !empty($_POST["intern_num"])) {
        // Recherche d'abord dans les contrats actifs
        $contract = fetchDb($cnx, ["intern_num" => $_POST["intern_num"]], ["service_id"], "contracts");
        
        // Si introuvable, recherche dans les contrats supprimés
        if (empty($contract)) {
            $contract = fetchDb($cnx, ["intern_num" => $_POST["intern_num"]], ["service_id"], "deleted_contracts");
        }
        
        // Utilise le service du contrat trouvé, sinon reste sur le service global
        $serviceId = !empty($contract) ? $contract[0]["service_id"] : $serviceId;
    }

    // Récupération et mise en cache du rôle utilisateur
    $userRole = get_user_role($cnx, $_SESSION["UserInfo"]["Username"], $serviceId);
    if(!$userRole){
	$userRole = "User";
    }
    $_SESSION["UserInfo"]["Role"] = $userRole;

    // Accès complet pour les administrateurs
    if ($userRole === 'Admin') {
        return;
    }

    // Construction de la liste des routes autorisées selon le rôle
    // Les managers héritent des permissions des utilisateurs + leurs permissions spécifiques
    $allowedUrls = ($userRole === 'Manager') 
        ? array_merge($permissions['User'], $permissions['Manager'])
        : ($permissions[$userRole] ?? []); // Utilise les permissions du rôle ou tableau vide si inexistant

    // Vérification d'autorisation et redirection si nécessaire
    if (!in_array($route, $allowedUrls, true)) { // Comparaison stricte pour éviter les faux positifs
        header("Location: index.php");
        exit;
    }
}

?>