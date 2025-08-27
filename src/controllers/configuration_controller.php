<?php

function configuration_page($cnx){
    // Chemin vers le fichier de configuration
    $filePath = "/etc/contratheque/conf.php";
    
    // Si le formulaire des paramètres est soumis ...
    if(isset($_POST["edit_configuration"])){

        // Vérification CSRF
        if($_POST["CSRF"] !== $_SESSION["System"]["CSRF"]){
            $_SESSION["System"]["CSRF"] = "";
            header("Location:index.php?route=error&error=Accès Non Autorisé");
            exit;
        }
        
        $_SESSION["System"]["CSRF"] = "";
        
        // Configuration des variables avec valeurs par défaut
        $configVars = [
            "HOSTNAME" => $_POST["hostname"] ?? "",
            "DB_NAME" => $_POST["dbname"] ?? "",  
            "DB_PASSWORD" => $_POST["dbpassword"] ?? "",
            "USERNAME" => $_POST["username"] ?? "",
            "LDAPSRV_ADDRESS" => $_POST["ldapsrvaddress"] ?? "",
            "DOMAIN" => $_POST["domain"] ?? "",
            "SEARCH_BASE" => $_POST["searchbase"] ?? "",
            "PDF_ROOT" => $_POST["pdfroot"] ?? "",
            "MAX_TRIES" => $_POST["maxtries"] ?? "",
            "EXPIRATION" => $_POST["expiration"] ?? "",
            "KEEPING_DURATION" => $_POST["keepingduration"] ?? "",
            "EMAIL_LIMITATION" => $_POST["email_limit"] ?? "",
            "SMTP_HOST" => $_POST["smtphost"] ?? "",
            "SMTP_ADDR" => $_POST["mailingaddr"] ?? "",
            "SMTP_PORT" => $_POST["smtpport"] ?? "",
            "GLPI_ADDR" => $_POST["glpiaddr"] ?? "",
        ];

        // Lecture et mise à jour du fichier en une seule opération
        $content = file_get_contents($filePath);
        $patterns = [];
        $replacements = [];
        
        foreach ($configVars as $key => $value) {
            $patterns[] = '/(\$' . preg_quote($key, '/') . '\s*=\s*)(?:".*?"|\'.*?\');/';
            $replacements[] = '$1"' . addslashes($value) . '";';
        }
        
        $content = preg_replace($patterns, $replacements, $content);

        // Modification du fichier avec les nouvelles valeurs
        file_put_contents($filePath, $content);
        
        // Retour à la page de configuration et affichage du message de succès
        header("Location: index.php?route=configuration&success=1");
        exit;
    }

    // Affichage de la page de configuration
    clearstatcache(true, $filePath);
    include($filePath);
    $_SESSION["System"]["CSRF"] = bin2hex(random_bytes(16));
    require_once("templates/headers/tool_header.php");
    require_once("templates/forms/configuration_page.php");
}


?>