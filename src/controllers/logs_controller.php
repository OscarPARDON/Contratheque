<?php

# Menu d'accès aux logs
function logs_hub(){

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/logs_hub.php");
}

# Tableau des logs de la base de donnée
function db_logs_page($cnx){

    # Récupération des logs dans la BDD
    $logs = fetchDb($cnx,[],NULL,"db_log");
    $logs = escape_array($logs);

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/db_logs_page.php");
}

# Tableau des logs de connexion
function login_logs_page($cnx){

    # Récupération des logs dans la BDD
    $logs = fetchDb($cnx,[],NULL,"login_logs");
    $logs = escape_array($logs);

    # Affichage de la page
    require_once("templates/headers/tool_header.php");
    require_once("templates/bodies/login_logs_page.php");
}

?>