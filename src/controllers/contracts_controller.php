<?php 
require_once("src/model.php");
require_once("src/validators/validators.php");
require_once("src/controllers/file_controller.php");
require_once("src/helper/helper.php");

#################################################################################################################################

function statistics_page($cnx) {
    $counters = [];

    // Récupère si l'utilisateur est admin à partir de la table "users"
    $isAdmin = fetchDb($cnx, ["samaccountname" => $_SESSION["UserInfo"]["Username"]], ["is_admin"], "users")[0]["is_admin"];

    // Récupère le service courant de l'utilisateur
    $currentService = $_SESSION["UserInfo"]["Current_Service"];

    // Si l'utilisateur est admin
    if ($isAdmin) {
        // Si l'admin est lié à un service spécifique
        if ($currentService != "*") {
            $counters["active_in_service"] = countDb($cnx, [
                "status" => "active",
                "service_id" => $currentService
            ], "contracts");

            $counters["expired_in_service"] = countDb($cnx, [
                "status" => "expired",
                "service_id" => $currentService
            ], "contracts");

            $counters["inalert_in_service"] = countSoonExpiredContracts($cnx, [
                "service_id" => $currentService
            ]);
        }

        // Données globales (tous services confondus)
        $counters["active_total"] = countDb($cnx, ["status" => "active"], "contracts");
        $counters["expired_total"] = countDb($cnx, ["status" => "expired"], "contracts");
        $counters["inalert_total"] = countSoonExpiredContracts($cnx, []);

    } else {
        // Si utilisateur non-admin et rattaché à un service spécifique
        if ($currentService != "*") {
            $counters["active_in_service"] = countDb($cnx, [
                "status" => "active",
                "service_id" => $currentService
            ], "contracts");

            $counters["expired_in_service"] = countDb($cnx, [
                "status" => "expired",
                "service_id" => $currentService
            ], "contracts");

            $counters["inalert_in_service"] = countSoonExpiredContracts($cnx, [
                "service_id" => $currentService
            ]);
        } else {
            // Utilisateur non-admin mais avec accès à tous les services
            $counters["active_total"] = countDb($cnx, ["status" => "active"], "contracts");
            $counters["expired_total"] = countDb($cnx, ["status" => "expired"], "contracts");
            $counters["inalert_total"] = countSoonExpiredContracts($cnx, []);
        }
    }

    // Récupère les services accessibles par l'utilisateur (avec échappement)
    $services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));

    // Génère un nouveau token CSRF pour sécuriser les futures requêtes
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    // Inclusion des templates (en-tête + page statistiques)
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/statistics_page.php");
}


function new_contract($cnx){
    // Affichage du formulaire si pas de soumission
    if (!isset($_POST["submit_new_contract"])) {
        // Récupération des services utilisateur
        $user_services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
        
        // Récupération des contractants selon les permissions
        if($_SESSION["UserInfo"]["Current_Service"] == "*" && $_SESSION["UserInfo"]["Role"] == "Admin"){
            $contractors = get_all_contractors($cnx, $_SESSION["UserInfo"]["Username"]);
        } else {
            $contractors = fetchDb($cnx, ["service_id" => $_SESSION["UserInfo"]["Current_Service"]], NULL, "contractors");
        }
        $contractors = escape_array($contractors);
        
        // Gestion du pré-chargement des données
        $preload = 0;
        if(isset($_GET["intern_num"])){
            $preloadData = fetchDb($cnx, ["intern_num" => $_GET["intern_num"]], ["contract_name", "contractor_id", "service_id"], "contracts");
            if($preloadData){
                $preload = escape_array($preloadData)[0];
            }
        }
        
        // Génération du token CSRF et affichage du formulaire
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
        require_once("templates/headers/tool_header.php");
        require_once("templates/forms/new_contract_form.php");
        return;
    }
    
    // Vérification du token CSRF
    if ($_SESSION["System"]["CSRF"] !== $_POST["CSRF"]) {
        $_SESSION["System"]["CSRF"] = "";
        header("Location: index.php?route=error&error=Accès Non Autorisé");
        exit;
    }
    
    $_SESSION["System"]["CSRF"] = "";

    // Récupération et préparation des données du contrat
    $contractData = [
        'contract_name' => $_POST["contract_name"] ?? '',
        'contract_start' => $_POST["contract_start"] ?? '',
        'contract_end' => $_POST["contract_end"] ?? '',
        'contract_followup_date' => $_POST["followup_date"] ?? '',
        'previous_contract_num' => str_replace(' ', '', $_POST["previous_contract_num"] ?? ''),
        'contractor_id' => $_POST["contractor"] ?? '',
        'service_id' => $_POST["service"] ?? '',
        'status' => "active",
        'created_by' => $_SESSION["UserInfo"]["Username"],
    ];

    // Génération du numéro interne du contrat
    $serviceShortName = fetchDb($cnx, ["id" => $contractData["service_id"]], ["short_name"], "services")[0]["short_name"];
    $contractData["intern_num"] = date("Y") . "-" . $serviceShortName . "-" . contracts_nbr($cnx, $contractData["service_id"]);

    // Vérification de sécurité pour les utilisateurs non-admin globaux
    if ($_SESSION["UserInfo"]["Current_Service"] != "*" && $contractData["service_id"] != intval($_SESSION["UserInfo"]["Current_Service"])) {
        header("Location:index.php?route=error&error=Opération non autorisée !");
        exit;
    }
    
    // Règles de validation avec gestion d'erreurs optimisée
    $validationErrors = [];
    
    // Validation du nom du contrat
    if (!validateLength($contractData['contract_name'], 50)) {
        $validationErrors[] = "nameError=Le nom renseigné doit se trouver entre 1 et 50 caractères !";
    }

    // Validation des dates
    $dateFields = [$contractData['contract_start'], $contractData['contract_followup_date'], $contractData['contract_end']];
    if (!validateDateFormat($contractData['contract_start']) || !validateDateFormat($contractData['contract_followup_date']) || !validateDateFormat($contractData['contract_end'])) {
        $validationErrors[] = "dateError=Format de date incorrect !";
    } elseif ($contractData['contract_followup_date'] < date("Y-m-d")) {
        $validationErrors[] = "dateError=La date de relance ne peut pas se situer avant la date d'aujourd'hui !";
    } elseif (!($contractData['contract_start'] <= $contractData['contract_followup_date'] && $contractData['contract_followup_date'] <= $contractData['contract_end'])) {
        $validationErrors[] = "dateError=Les dates doivent être ordonnées correctement !";
    }

    // Validation du contractant
    if (!is_numeric($contractData['contractor_id']) || !fetchDb($cnx, ["id" => $contractData['contractor_id']], ["id"], "contractors")) {
        $validationErrors[] = "contractorError=Contractant invalide !";
    } else {
        $contractorService = fetchDb($cnx, ["id" => $contractData["contractor_id"]], ["service_id"], "contractors")[0]["service_id"];
        if ($contractorService != $contractData["service_id"]) {
            $validationErrors[] = "contractorError=Le contractant sélectionné n'est pas issu de ce service !";
        }
    }

    // Validation du service
    if (!is_numeric($contractData['service_id']) || !fetchDb($cnx, ["id" => $contractData['service_id']], ["id"], "services")) {
        $validationErrors[] = "serviceError=Service invalide !";
    } elseif (!check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $contractData["service_id"])) {
        $validationErrors[] = "serviceError=Vous n'avez pas l'autorisation d'ajouter un contrat dans ce service !";
    }

    // Validation du numéro de contrat précédent (si fourni)
    if ($contractData['previous_contract_num']) {
        $prevContractExists = fetchDb($cnx, ["intern_num" => $contractData["previous_contract_num"]]) || 
                             fetchDb($cnx, ["intern_num" => $contractData["previous_contract_num"]], NULL, "deleted_contracts");
        
        if (!validateContractNumberFormat($contractData['previous_contract_num']) || !$prevContractExists) {
            $redirection = $_SERVER['HTTP_REFERER'] . "&prevContractNumError=Le numéro de contrat précédent est incorrect !";
            header("Location: {$redirection}");
            exit;
        }
        
        $prevContractService = fetchDb($cnx, ["intern_num" => $contractData["previous_contract_num"]], ["service_id"], "contracts")[0]["service_id"];
        if ($prevContractService != $contractData["service_id"]) {
            $redirection = $_SERVER['HTTP_REFERER'] . "&prevContractNumError=Vous n'avez pas l'autorisation de reconduire ce contrat !";
            header("Location: {$redirection}");
            exit;
        }
    }
    
    // Redirection en cas d'erreur de validation
    if (!empty($validationErrors)) {
        $errorParams = [
            'route' => 'new_contract',
            'contract_name' => $contractData["contract_name"],
            'contract_start' => $contractData["contract_start"],
            'contract_end' => $contractData["contract_end"],
            'contract_followup' => $contractData["contract_followup_date"],
            'service_id' => $contractData["service_id"],
            'contractor_id' => $contractData["contractor_id"]
        ];
        
        if ($contractData['previous_contract_num']) {
            $errorParams['intern_num'] = $contractData['previous_contract_num'];
        }
        
        $url = "Location: index.php?" . $validationErrors[0] . "&" . http_build_query($errorParams);
        header($url);
        exit;
    }

    // Gestion du fichier PDF si présent
    if (isset($_FILES["pdf_file"]["tmp_name"]) && $_FILES["pdf_file"]["tmp_name"] != NULL) {
        handle_pdf($contractData['intern_num']);
    }
    
    // Insertion en base de données et redirection
    insertDb($cnx, $contractData, "contracts");
    header("Location: index.php");
    exit;
}

function archived_contracts_page($cnx){

    // Récupération des données des contrats archivés
    $contracts = fetch_archives($cnx,[],"archived_contracts");
    $displayed_contracts = escape_array($contracts); // Echapement des données avant affichage pour eviter les injections XSS

    // Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/archived_contracts_page.php");

}

function update_contract($cnx) {
    // Affichage du formulaire si pas de soumission
    if (!isset($_POST["submit_contract_update"])) {
        // Vérification de l'existence du contrat
        if (!isset($_GET['intern_num']) || empty(fetchDb($cnx, ['intern_num' => $_GET["intern_num"]]))) {
            header("Location:index.php?route=error&error=Numéro de contrat manquant ou inconnu !");
            exit;
        }
        
        // Récupération des données du contrat et vérification des permissions
        $contract = escape_array(fetchDb($cnx, ['intern_num' => $_GET["intern_num"]]))[0];
        if (!check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $contract["service_id"])) {
            header("Location:index.php?route=error&error=Accès Non Autorisé");
            exit;
        }
        
        // Récupération des services pour les admins globaux
        if($_SESSION["UserInfo"]["Current_Service"] == "*"){
            $services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
        }
        
        // Récupération des contractants selon les permissions
        if($_SESSION["UserInfo"]["Current_Service"] == "*" && $_SESSION["UserInfo"]["Role"] == "Admin"){
            $contractors = get_all_contractors($cnx, $_SESSION["UserInfo"]["Username"]);
        } else {
            $contractors = fetchDb($cnx, ["service_id" => $_SESSION["UserInfo"]["Current_Service"]], NULL, "contractors");
        }
        $contractors = escape_array($contractors);
        
        // Inclusion du header selon le rôle et génération du token CSRF
        $headerTemplate = ($_SESSION["UserInfo"]["Role"] == "Manager" || $_SESSION["UserInfo"]["Role"] == "Admin") 
            ? "templates/headers/tool_header.php" 
            : "templates/headers/user_header.php";
        require_once($headerTemplate);
        
        $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
        require_once("templates/forms/update_contract_form.php");
        return;
    }
    
    // Vérification du token CSRF
    if ($_SESSION["System"]["CSRF"] !== $_POST["CSRF"]) {
        $_SESSION["System"]["CSRF"] = "";
        header("Location: index.php?route=error&error=Accès Non Autorisé");
        exit;
    }
    
    $_SESSION["System"]["CSRF"] = "";
    
    // Récupération et préparation des données du contrat
    $contractData = [
        'intern_num' => $_POST["intern_num"] ?? '',
        'contract_name' => $_POST["contract_name"] ?? '',
        'contract_start' => $_POST["contract_start"] ?? '',
        'contract_end' => $_POST["contract_end"] ?? '',
        'contract_followup_date' => $_POST["followup_date"] ?? '',
        'contractor_id' => $_POST["contractor"] ?? '',
        'service_id' => $_POST["service"] ?? ''
    ];

    // Vérification de sécurité pour les utilisateurs non-admin globaux
    if ($_SESSION["UserInfo"]["Current_Service"] != "*" && $contractData["service_id"] != intval($_SESSION["UserInfo"]["Current_Service"])) {
        header("Location:index.php?route=error&error=Accès Non Autorisé");
        exit;
    }

    // Vérification de l'existence du contrat et des permissions
    $contractExists = $contractData["intern_num"] && fetchDb($cnx, ["intern_num" => $contractData["intern_num"]]);
    if (!$contractExists) {
        $error = "internNumError=Le numéro de candidature renseigné est incorrect !";
    } else {
        $originalContract = fetchDb($cnx, ["intern_num" => $contractData["intern_num"]])[0];
        $hasPermission = ($originalContract["service_id"] == $_SESSION["UserInfo"]["Current_Service"] || $_SESSION["UserInfo"]["Current_Service"] == "*");
        
        if (!$hasPermission) {
            $error = "internNumError=Le numéro de candidature renseigné est incorrect !";
        }
    }

    // Gestion du changement de service - génération d'un nouveau numéro interne
    if (!isset($error) && $originalContract["service_id"] != $contractData["service_id"]) {
        $serviceShortName = fetchDb($cnx, ["id" => $contractData["service_id"]], ["short_name"], "services")[0]["short_name"];
        $contractData["intern_num"] = date("Y") . "-" . $serviceShortName . "-" . contracts_nbr($cnx, $contractData["service_id"]);
    }

    // Règles de validation avec gestion d'erreurs optimisée
    if (!isset($error)) {
        $validationRules = [
            ['condition' => !validateLength($contractData['contract_name'], 50), 
             'error' => "nameError=Le nom renseigné doit se trouver entre 1 et 50 caract�res !"],
            ['condition' => !validateDateFormat($contractData['contract_start']) || !validateDateFormat($contractData['contract_followup_date']) || !validateDateFormat($contractData['contract_end']), 
             'error' => "dateError=Format de date incorrect !"],
            ['condition' => !($contractData['contract_start'] <= $contractData['contract_followup_date'] && $contractData['contract_followup_date'] <= $contractData['contract_end']), 
             'error' => "dateError=Les dates doivent être ordonnées correctement !"],
            ['condition' => !is_numeric($contractData['contractor_id']) || !fetchDb($cnx, ["id" => $contractData['contractor_id']], ["id"], "contractors"), 
             'error' => "contractorError=Contractant invalide !"],
            ['condition' => !is_numeric($contractData['service_id']) || !fetchDb($cnx, ["id" => $contractData['service_id']], ["id"], "services"), 
             'error' => "serviceError=Service invalide !"]
        ];

        // Validation spécifique du service du contractant
        if (is_numeric($contractData['contractor_id']) && fetchDb($cnx, ["id" => $contractData['contractor_id']], ["id"], "contractors")) {
            $contractorService = fetchDb($cnx, ["id" => $contractData["contractor_id"]], ["service_id"], "contractors")[0]["service_id"];
            if ($contractorService != $contractData["service_id"]) {
                $validationRules[] = ['condition' => true, 'error' => "contractorError=Le contractant sélectionné n'est pas issu de ce service !"];
            }
        }

        // Vérification de toutes les règles de validation
        foreach($validationRules as $rule){
            if($rule['condition']){
                $error = $rule['error'];
                break;
            }
        }
    }
    
    // Redirection en cas d'erreur de validation
    if (isset($error)) {
        header("Location: index.php?route=update_contract&intern_num={$contractData['intern_num']}&" . $error);
        exit;
    }
    
    // Détermination du statut du contrat basé sur la date de fin
    $contractData['status'] = ($contractData['contract_end'] < date("Y-m-d")) ? "expired" : "active";
    
    // Gestion des fichiers PDF - déplacement selon le nouveau statut
    include("/etc/contratheque/conf.php");
    $oldStatus = ($contractData['status'] == "expired") ? "active" : "expired";
    $fromPath = "{$PDF_ROOT}{$oldStatus}/{$contractData['intern_num']}.pdf";
    $toPath = "{$PDF_ROOT}{$contractData['status']}/{$contractData['intern_num']}.pdf";
    
    if (file_exists($fromPath)) {
        rename($fromPath, $toPath);
    }
    // Gestion du nouveau fichier PDF si fourni
    if (isset($_FILES["pdf_file"]["tmp_name"]) && $_FILES["pdf_file"]["tmp_name"] != NULL) {
        handle_pdf($contractData['intern_num'], $contractData['status']);
    }
    // Mise à jour en base de données
    updateDb($cnx, $contractData, ["intern_num" => $originalContract['intern_num']], "contracts");
    
    // Redirection selon le statut final du contrat
    $redirectRoute = ($contractData['status'] == "expired") ? "?route=expired_contracts" : "";
    header("Location: index.php" . $redirectRoute);
    exit;
}

function delete_contract($cnx) {
    // Vérification de la soumission et du token CSRF
    if (!isset($_POST["submit_deletion"]) || $_POST["CSRF"] !== $_SESSION["System"]["CSRF"]) {
        header("Location:index.php?route=error&error=Requête erronée !");
        exit;
    }

    // Inclusion de la configuration pour les chemins PDF
    include("/etc/contratheque/conf.php");

    // Récupération et validation des données du formulaire
    $contractNum = $_POST["del_contract_num"] ?? null;
    if (!$contractNum) {
        header("Location:index.php?route=error&error=Numéro de contrat manquant !");
        exit;
    }

    // Validation de la raison de suppression
    $validReasons = ["Contrat Erroné", "Contrat Obsolète", "Duplicata", "Autre"];
    $reason = $_POST["reason"] ?? '';
    if (!in_array($reason, $validReasons)) {
        header("Location:index.php?route=error&error=La raison de la suppression est invalide !");
        exit;
    }

    // Récupération et validation du contrat
    $contractData = fetchDb($cnx, ['intern_num' => $contractNum]);
    if (empty($contractData)) {
        header("Location:index.php?route=error&error=Le numéro de contrat est invalide !");
        exit;
    }
    
    $contract = $contractData[0];
    
    // Vérification des permissions utilisateur sur le service du contrat
    if (!check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $contract["service_id"])) {
        header("Location:index.php?route=error&error=Accès Non Autorisé !");
        exit;
    }

    // Sauvegarde du statut pour la redirection et préparation des données
    $originalStatus = $contract["status"];
    
    // Préparation des données pour la table deleted_contracts
    $deletedContractData = $contract;
    unset($deletedContractData["status"]); // Suppression du statut
    unset($deletedContractData["created_by"]); // Reformatage (created_by => deleted_by)
    $deletedContractData["reason"] = $reason;
    $deletedContractData["deletion_date"] = date("Y-m-d");
    $deletedContractData["deleted_by"] = $_SESSION["UserInfo"]["Username"]; // Ajout de qui a supprimé

    // Insertion dans la table des contrats supprimés
    insertDb($cnx, $deletedContractData, 'deleted_contracts');
    
    // Suppression du contrat original
    deleteDb($cnx, ["intern_num" => $contractNum], "contracts");

    // Gestion du déplacement du fichier PDF
    $sourceDir = ($originalStatus === 'expired') ? "expired" : "active";
    $sourcePath = "{$PDF_ROOT}{$sourceDir}/{$contractNum}.pdf";
    $destPath = "{$PDF_ROOT}deleted/{$contractNum}.pdf";
    
    // Déplacement du fichier PDF s'il existe
    if (file_exists($sourcePath)) {
        // Vérification que le répertoire de destination existe
        $deletedDir = "{$PDF_ROOT}deleted/";
        if (!is_dir($deletedDir)) {
            mkdir($deletedDir, 0755, true);
        }
        
        rename($sourcePath, $destPath);
    }

    // Nettoyage du token CSRF et redirection
    $_SESSION["System"]["CSRF"] = "";
    
    // Redirection vers la page appropriée selon le statut original
    $redirectRoute = ($originalStatus === "expired") ? "?route=expired_contracts" : "";
    header("Location: index.php" . $redirectRoute);
    exit;
}

function recover_contract($cnx) {
    // Vérifie que le formulaire a été soumis et que le jeton CSRF est valide
    if (isset($_POST["submit_recover"]) && isset($_POST["CSRF"]) && $_POST["CSRF"] === ($_SESSION["System"]["CSRF"] ?? null)) {
        
        include "/etc/contratheque/conf.php"; // Charge la config (ex. : $PDF_ROOT, paramètres DB, etc.)

        // Récupération sécurisée du numéro de contrat, sinon redirection
        $contract_num = $_POST["recover_contract_num"] ?? null;
        if (!$contract_num) {
            header("Location:index.php?route=error&error=Numéro de contrat manquant !");
            exit;
        }

        // Récupère le contrat supprimé dans la base (une seule requête pour tout récupérer)
        $contract_data = fetchDb($cnx, ['intern_num' => $contract_num], null, "deleted_contracts")[0] ?? null;

        // Si aucun contrat trouvé ou si l'utilisateur n'a pas les droits sur ce service → sortie
        if (!$contract_data || !check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $contract_data["service_id"])) {
	        header("Location:index.php?route=deleted_contracts");
            exit;
        }

        // Supprime les champs inutiles à la restauration
        unset($contract_data["reason"], $contract_data["deletion_date"], $contract_data["deleted_by"]);

        // Détermine le statut du contrat (expiré ou actif) en fonction de la date de fin
        $contract_data["status"] = ($contract_data["contract_end"] < date("Y-m-d")) ? "expired" : "active";

	$contract_data["created_by"] = $_SESSION["UserInfo"]["Username"];

        // Insère le contrat restauré dans la table active
        insertDb($cnx, $contract_data);

        // Supprime l'entrée de la table des contrats supprimés
        deleteDb($cnx, ["intern_num" => $contract_data["intern_num"]], "deleted_contracts");

        // Déplace le PDF associé dans le bon dossier en fonction du statut
        $old_path = "{$PDF_ROOT}deleted/{$contract_num}.pdf";
        if (is_file($old_path)) {
            $new_path = "{$PDF_ROOT}{$contract_data["status"]}/{$contract_num}.pdf";
            rename($old_path, $new_path);
        }
    }

    // Réinitialise le jeton CSRF pour éviter la réutilisation
    $_SESSION["System"]["CSRF"] = "";

    // Redirige vers la liste des contrats supprimés
    header("Location: index.php?route=deleted_contracts");
    exit;
}



function update_note($cnx) {
    // Vérifie que le formulaire est soumis et que le jeton CSRF est valide
    if (isset($_POST["submit_notes"]) && isset($_POST["CSRF"]) && $_POST["CSRF"] === ($_SESSION["System"]["CSRF"] ?? null)) {

        // Invalide le CSRF pour éviter toute réutilisation
        $_SESSION["System"]["CSRF"] = "";

        // Récupère le numéro interne du contrat
        $contract_id = $_POST["intern_num"] ?? null;
        if ($contract_id) {

            // Détermine dans quelle table chercher le contrat
            // 1. Dans "contracts"
            // 2. Sinon dans "deleted_contracts"
            // 3. Sinon redirection
            if (fetchDb($cnx, ["intern_num" => $contract_id], null, "contracts")) {
                $table = "contracts";
            } elseif (fetchDb($cnx, ["intern_num" => $contract_id], null, "deleted_contracts")) {
                $table = "deleted_contracts";
            } else {
                header("Location:index.php?route=error&error=Numéro de contrat manquant ou invalide !");
                exit;
            }

            // Récupère les données du contrat
            $contract = fetchDb($cnx, ["intern_num" => $contract_id], null, $table)[0] ?? null;
            if (!$contract) {
                header("Location:index.php?route=error&error=Numéro de contrat invalide !");
                exit;
            }

            // Vérifie que l'utilisateur a accès au service lié à ce contrat
            if (!check_user_service($cnx, $_SESSION["UserInfo"]["Username"], $contract["service_id"])) {
                header("Location:index.php?route=error&error=Accès Non Autorisé !");
                exit;
            }

            // Vérifie la longueur et met à jour les notes
            if (validateLength($_POST["contract_notes"], 300)) {
                // Remplace les guillemets doubles par des simples pour éviter certains problèmes d'affichage/SQL
                $contract["contract_notes"] = str_replace('"', "'", $_POST["contract_notes"]);
                updateDb($cnx, $contract, ['intern_num' => $contract["intern_num"]], $table);
            }
        }
    }

    // Redirection après traitement
    $redirect = $_GET["returnto"] ?? "index.php";
    $extra = ($redirect === "contract_detail" && isset($contract_id)) ? "&intern_num=$contract_id" : "";
    header("Location: index.php?route=$redirect$extra");
    exit;
}

# Affichage de la vue : "contrats supprimés"
function deleted_contracts_page($cnx) {
    // Détermine les critères de recherche des contrats supprimés
    if($_SESSION["UserInfo"]["Current_Service"] == "*"){
        $displayed_contracts = [];
	$services_ids = array_column(get_user_services_ids($cnx,$_SESSION["UserInfo"]["Username"]),"service_id");
	foreach($services_ids as $id){
		$contracts = fetch_contracts($cnx,["service_id"=>$id],"deleted_contracts");
		$displayed_contracts = array_merge($displayed_contracts, $contracts);
	}
    }
    else{
        $displayed_contracts = fetch_contracts($cnx, ["service_id"=>$_SESSION["UserInfo"]["Current_Service"]], "deleted_contracts");
    }
    $displayed_contracts = escape_array($displayed_contracts);

    // Génère un nouveau jeton CSRF pour les actions de la page
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    // Définit le nom de la table pour un usage ultérieur
    $table = "deleted_contracts";

    // Récupère et sécurise la liste des services auxquels l'utilisateur a accès
    $user_services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
    $service_count = count($user_services);

    // Inclut le bon en-tête selon le rôle de l'utilisateur
    if (in_array($_SESSION["UserInfo"]["Role"], ["Admin", "Manager"])) {
        require_once "templates/headers/tool_header.php";
    } else {
        require_once "templates/headers/user_header.php";
    }

    // Inclut le corps principal de la page
    require_once "templates/bodies/contract_page.php";
}

function contract_detail_page($cnx) {
    // Vérifie la présence du numéro interne dans l'URL
    if (!isset($_GET["intern_num"])) {
        header("Location:index.php?route=error&error=Numéro de contrat manquant !");
        exit;
    }

    $intern_num = $_GET["intern_num"];
    $contract   = null;
    $status     = null;

    // Cherche d'abord dans les contrats actifs
    if (!empty(fetchDb($cnx, ['intern_num' => $intern_num], null, "contracts"))) {
        $contract = fetch_contracts($cnx, ['intern_num' => $intern_num], "contracts");
        $status   = $contract[0]["status"];
    }
    // Sinon cherche dans les contrats supprimés
    elseif (!empty(fetchDb($cnx, ['intern_num' => $intern_num], null, "deleted_contracts"))) {
        $contract = fetch_contracts($cnx, ['intern_num' => $intern_num], "deleted_contracts");
        $status   = "deleted";
    }
    // Aucun contrat trouvé → redirection
    else {
        header("Location:index.php?route=error&error=Numéro de contrat invalide !");
        exit;
    }

    // Sécurise les données et ne garde que la première entrée
    $contract = escape_array($contract)[0];

    // Détermine le rôle effectif de l'utilisateur pour ce contrat
    $role = $_SESSION["UserInfo"]["Role"];
    if ($_SESSION["UserInfo"]["Current_Service"] === "*" && $role === "User") {
        $user_role_data = fetchDb(
            $cnx,
            ["user_sam" => $_SESSION["UserInfo"]["Username"], "service_id" => $contract["service_id"]],
            ["role"],
            "user_services"
        )[0] ?? null;

        if ($user_role_data) {
            $role = $user_role_data["role"];
        }
    }

    // Génère un nouveau jeton CSRF
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    // Charge le bon en-tête selon le rôle et le service courant
    if (($_SESSION["UserInfo"]["Current_Service"] !== "*" && $role === "Manager") || $role === "Admin") {
        require_once "templates/headers/tool_header.php";
    } else {
        require_once "templates/headers/user_header.php";
    }

    // Charge le corps de la page
    require_once "templates/bodies/contract_detail.php";
}

function active_contracts_page($cnx) {
    // Prépare les critères de sélection : statut actif, et éventuellement filtré par service
    if($_SESSION["UserInfo"]["Current_Service"] == "*"){
        $displayed_contracts = [];
	    $services_ids = array_column(get_user_services_ids($cnx,$_SESSION["UserInfo"]["Username"]),"service_id");
	foreach($services_ids as $id){
		$contracts = fetch_contracts($cnx,["service_id"=>$id,"status"=>"active"],"contracts");
		$displayed_contracts = array_merge($displayed_contracts, $contracts);
	}
    }
    else{
        $displayed_contracts = fetch_contracts($cnx, ["service_id"=>$_SESSION["UserInfo"]["Current_Service"],"status"=>"active"], "contracts");
    }
    $displayed_contracts = escape_array($displayed_contracts);

    // Génère un jeton CSRF pour les actions de la page
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    // Nom de la table utilisée dans cette page
    $table = "contracts";

    // Récupère et sécurise la liste des services auxquels l'utilisateur a accès
    $user_services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
    $service_count = count($user_services);

    // Choisit l'en-tête à inclure selon le rôle de l'utilisateur
    if (in_array($_SESSION["UserInfo"]["Role"], ["Admin", "Manager"])) {
        require_once "templates/headers/tool_header.php";
    } else {
        require_once "templates/headers/user_header.php";
    }

    // Inclut le corps principal de la page
    require_once "templates/bodies/contract_page.php";
}

# Affichage de la vue : "contrats expirés"
function expired_contracts_page($cnx) {
    // Prépare les critères de recherche : statut "expired"
    if($_SESSION["UserInfo"]["Current_Service"] == "*"){
        $displayed_contracts = [];
	    $services_ids = array_column(get_user_services_ids($cnx,$_SESSION["UserInfo"]["Username"]),"service_id");
	foreach($services_ids as $id){
		$contracts = fetch_contracts($cnx,["service_id"=>$id,"status"=>"expired"],"contracts");
		$displayed_contracts = array_merge($displayed_contracts, $contracts);
	}
    }
    else{
        $displayed_contracts = fetch_contracts($cnx, ["service_id"=>$_SESSION["UserInfo"]["Current_Service"],"status"=>"expired"], "contracts");
    }
    $displayed_contracts = escape_array($displayed_contracts);
    // Génère un jeton CSRF pour les actions de cette page
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));

    // Nom de la table (ici toujours "contracts")
    $table = "contracts";

    // Récupère et sécurise la liste des services accessibles à l'utilisateur
    $user_services = escape_array(get_user_services($cnx, $_SESSION["UserInfo"]["Username"]));
    $service_count = count($user_services);

    // Choisit le bon en-tête à inclure en fonction du rôle
    if (in_array($_SESSION["UserInfo"]["Role"], ["Admin", "Manager"])) {
        require_once "templates/headers/tool_header.php";
    } else {
        require_once "templates/headers/user_header.php";
    }

    // Inclut le corps principal de la page
    require_once "templates/bodies/contract_page.php";
}



?>