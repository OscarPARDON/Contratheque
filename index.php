<?php

// Décommenter pour activer l'affichage des erreurs
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

require_once("templates/head.php"); # Chargement de l'entête HTML
session_start(); # Démarrage de la session utilisateur (Amorcage du Cookie)

// En cas d'erreur : renvoi vers la page d'erreur
require_once("src/controllers/login_controller.php");
if(isset($_GET["route"]) && $_GET["route"]=="error"){
    error_page();
    exit;
}

// Vérification de la connexion
if(auth_verif($cnx) != 1){
    login($cnx);
}
else{ // L'utilisateur est connecté, accès autorisé à l'application 

    # Imports des modules de l'application
    require_once("src/controllers/file_controller.php");
    require_once("src/controllers/contracts_controller.php");
    require_once("src/controllers/users_controller.php");
    require_once("src/controllers/services_controller.php");
    require_once("src/controllers/contractors_controller.php");
    require_once("src/controllers/configuration_controller.php");
    require_once("src/controllers/logs_controller.php");
    require_once("src/controllers/mail_controller.php");
    require_once('src/middleware/middleware.php');

    // Vérification de l'expiration de la session utilisateur
    check_session_expiration();

    if(isset($_GET["route"])){ // L'utilisateur demande une page spécifique

        // Vérification que l'utilisateur a bien accès à la page demandée
        RoleVerificationMiddleware($cnx,$_GET["route"]);

        // Routage
        switch($_GET["route"]){
            case "logout":
                logout();
                break;
            case "expired_contracts":
                expired_contracts_page($cnx);
                break;
            case "deleted_contracts":
                deleted_contracts_page($cnx);
                break;
            case "contract_detail":
                contract_detail_page($cnx);
                break;
            case "new_contract":
                new_contract($cnx);
                break;
            case "update_contract":
                update_contract($cnx); 
                break;
            case "delete_contract": 
                delete_contract($cnx);
                break;
            case "recover_contract":
                recover_contract($cnx);
                break;
            case "file_display":
                file_action($cnx,$_GET["contract_num"],$_GET["status"],"display");
                break;
            case "file_download":
                file_action($cnx,$_GET["contract_num"],$_GET["status"],"download");
                break;
            case "user_management":
                user_management_page($cnx);
                break;
            case "new_user":
                new_user($cnx);
                break;
            case "update_user":
                update_user($cnx);
                break;
            case "deactivate_user":
                deactivate_user($cnx);
                break;
            case "delete_user":
                delete_user($cnx);
                break;
            case "update_notes":
                update_note($cnx);
                break;
            case "change_service":
                change_service($cnx);
                break;
            case "recover_user":
                recover_user($cnx);
                break;
            case "change_user_services":
                change_user_services($cnx);
                break;
            case "service_management":
                service_management_page($cnx);
                break;
            case "delete_service":
                delete_service($cnx);
                break;
            case "new_service":
                new_service($cnx);
                break;
            case "update_service":
                update_service($cnx);
                break;
            case "service_users":
                service_users($cnx);
                break;
            case "remove_service_user":
                remove_service_user($cnx);
                break;
            case "add_service_user":
                add_service_user($cnx);
                break;
            case "update_user_role":
                update_service_user_role($cnx);
                break;
            case "contractor_management":
                contractor_management_page($cnx);
                break;
            case "new_contractor":
                new_contractor($cnx);
                break;
            case "edit_contractor":
                edit_contractor($cnx);
                break;
            case "delete_contractor":
                delete_contractor($cnx);
                break;
            case "configuration":
                configuration_page($cnx);
                break;
            case "statistics":
                statistics_page($cnx);
                break;
            case "logs_hub":
                logs_hub();
                break;
            case "login_logs":
                login_logs_page($cnx);
                break;
            case "database_logs":
                db_logs_page($cnx);
                break;
            case "help":
                help_form($cnx);
                break;
            case "help_confirmation":
                help_confirmation_page();
                break;
            case "archived_contracts":
                archived_contracts_page($cnx);
                break;
            case "error":
                error_page();
                break;
            default: // Si la page demandée ne correspond a aucune option : affichage de la page principale
                active_contracts_page($cnx);
                break;
        }
    }
    else{ // L'utilisateur n'a pas demandé de page spécifique
        RoleVerificationMiddleware($cnx,"");
        active_contracts_page($cnx);
    }
}

?>