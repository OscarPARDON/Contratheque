<?php

# Import des dépendences
require_once("/var/www/html/contratheque/src/model.php");
require_once("/var/www/html/contratheque/src/validators/validators.php");
require '/var/www/html/contratheque/src/libs/PHPMailer/src/Exception.php';
require '/var/www/html/contratheque/src/libs/PHPMailer/src/PHPMailer.php';
require '/var/www/html/contratheque/src/libs/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;



/**
 * Gère l’envoi du formulaire d’aide ou l’affichage du formulaire si non soumis.
 */
function help_form($cnx) {
    // Cas 1 : le formulaire d’aide a été soumis
    if (isset($_POST["submit_help_request"])) {
        // Récupération des infos utilisateur
        $user = fetchDb(
            $cnx,
            ["samaccountname" => $_SESSION["UserInfo"]["Username"]],
            ["remaining_mails", "lastname", "firstname", "mail"],
            "users"
        )[0];

        $remaining_emails = (int)$user["remaining_mails"];

        // Vérifie si l’utilisateur a encore le droit d’envoyer un email
        if ($remaining_emails > 0) {
            // Récupération et sécurisation des champs saisis
            $title       = isset($_POST["title"]) ? htmlspecialchars($_POST["title"]) : "";
            $description = isset($_POST["description"]) ? htmlspecialchars($_POST["description"]) : "";

            // Ajout du nom complet de l’utilisateur dans la description
            $description .= "\n" . $user["lastname"] . " " . $user["firstname"];

            // Validation de la longueur des champs
            $error = "";
            if (!validateLength($title, 200)) {
                $error = "Le titre renseigné est trop long (1-200 caractères) !";
            } elseif (!validateLength($description, 500)) {
                $error = "La description renseignée est trop longue (1-500 caractères) !";
            }

            // Si une erreur est détectée → redirection avec message
            if ($error !== "") {
                header("Location:index.php?route=error&error=" . urlencode($error));
                exit;
            }

            // Tentative d’envoi d’email
            try {
                include("/etc/contratheque/conf.php");

                $email = new PHPMailer(true);
                $email->SMTPDebug    = SMTP::DEBUG_SERVER; // Debug complet (à désactiver en prod)
                $email->isSMTP();
                $email->Host         = $SMTP_HOST;
                $email->SMTPAuth     = false;
                $email->Port         = $SMTP_PORT;
                $email->SMTPSecure   = false;
                $email->SMTPAutoTLS  = false;

                $email->setFrom($user["mail"]);
                $email->addAddress($GLPI_ADDR);
                $email->Subject = $title;
                $email->Body    = $description;
                $email->send();

                // Mise à jour du compteur d’emails restants
                updateDb(
                    $cnx,
                    ["remaining_mails" => $remaining_emails - 1],
                    ["samaccountname" => $_SESSION["UserInfo"]["Username"]],
                    "users"
                );

                // Redirection vers la confirmation
                header("Location:index.php?route=help_confirmation");
                exit;
            } catch (Exception $e) {
                // Redirection en cas d’échec d’envoi
                header("Location:index.php?route=error&error=" . urlencode($e->getMessage()));
                exit;
            }
        } else {
            // L’utilisateur a épuisé son quota de mails
            header("Location:index.php?route=error&error=" . urlencode("Vous avez atteint votre limite d'email quotidien !"));
            exit;
        }
    }

    // Cas 2 : le formulaire n’a pas encore été soumis → affichage
    else {
        // Détermine l’entête selon le rôle utilisateur
        $role = get_user_role($cnx, $_SESSION["UserInfo"]["Username"], $_SESSION["UserInfo"]["Current_Service"]);

        if ($role === "Manager" || $role === "Admin") {
            require_once("templates/headers/tool_header.php");
        } else {
            require_once("templates/headers/user_header.php");
        }

        // Affichage du formulaire
        require_once("templates/forms/help_form.php");
    }
}

function help_confirmation_page(){
    // Détermine l’entête selon le rôle utilisateur
    if($_SESSION["UserInfo"]["Role"] == "Manager" || $_SESSION["UserInfo"]["Role"] == "Admin"){
        require_once("templates/headers/tool_header.php");
    }
   else{
        require_once("templates/headers/user_header.php");
   }

   # Affichage de la page
    require_once("templates/bodies/help_confirmation_page.php");
}


/**
 * Envoie un rappel par email aux utilisateurs ayant des contrats proches de l’expiration.
 */
function send_reminder($cnx) {
    // Récupère les utilisateurs devant recevoir un rappel
    $targets = get_reminded_users($cnx);

    foreach ($targets as $target) {
        // Récupère les services contrôlés par l’utilisateur
        $target_services = get_user_controlled_services($cnx, $target["user_sam"]);
        $contracts = [];

        if ($target_services) {
            // Regroupe tous les contrats proches d’expiration pour ses services
            foreach ($target_services as $service) {
                $contracts = array_merge(
                    $contracts,
                    getSoonExpiredContracts($cnx, ["service_id" => $service["id"]])
                );
            }

            // Si des contrats sont trouvés, préparer et envoyer l’email
            if (!empty($contracts)) {
                $title = "Vous avez " . count($contracts) . " contrat(s) sur le point d'expirer !";

                // Construction du corps HTML du mail
                $body  = "<p>Attention, les contrats suivants sont sur le point d'expirer.<br>";
                $body .= "Veuillez procéder aux démarches nécessaires pour les renouveler :</p>";
                $body .= "<ul>";

                foreach ($contracts as $contract) {
                    // Formatage de la date en français
                    $date = new DateTime($contract["contract_end"]);
                    $formatter = new IntlDateFormatter(
                        'fr_FR',
                        IntlDateFormatter::LONG,
                        IntlDateFormatter::NONE,
                        null,
                        null,
                        'd MMMM yyyy'
                    );

                    // Ajout du contrat dans la liste HTML
                    $body .= "<li><a href='http://intranet/contratheque/index.php?route=contract_detail&intern_num="
                        . htmlspecialchars($contract["intern_num"], ENT_QUOTES, 'UTF-8')
                        . "'>"
                        . htmlspecialchars($contract["intern_num"], ENT_QUOTES, 'UTF-8')
                        . " : Expiration le " . $formatter->format($date)
                        . "</a></li>";
                }
                $body .= "</ul>";

                // Tentative d’envoi de l’email
                try {
                    include("/etc/contratheque/conf.php");

                    $email = new PHPMailer(true);
                    $email->SMTPDebug   = SMTP::DEBUG_OFF; // désactiver en production
                    $email->isSMTP();
                    $email->CharSet     = 'UTF-8';
                    $email->Host        = $SMTP_HOST;
                    $email->SMTPAuth    = false;
                    $email->Port        = $SMTP_PORT;
                    $email->SMTPSecure  = false;
                    $email->SMTPAutoTLS = false;

                    $email->setFrom("contratheque@mutualite70.fr", "Contrathèque");
                    $email->addAddress($target["mail"]);
                    $email->Subject = $title;
                    $email->Body    = $body;
                    $email->isHTML(true);

                    $email->send();
                } catch (Exception $e) {
                    // Log de l’erreur si l’email échoue
                    file_put_contents(
                        "/var/www/html/contratheque/src/cron/log.txt",
                        "Send_reminder ERROR (" . $target["mail"] . ") : " . $e->getMessage() . "\n",
                        FILE_APPEND | LOCK_EX
                    );
                    return 0;
                }
            }
        }
    }

    // Log de succès si aucun problème
    file_put_contents(
        "/var/www/html/contratheque/src/cron/log.txt",
        "Send_reminder : Success \n",
        FILE_APPEND | LOCK_EX
    );
    return 1;
}


?>