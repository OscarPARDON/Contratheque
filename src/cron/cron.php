<?php

# Import des modules nécéssaires au fonctionnement
require_once("/var/www/html/contratheque/src/model.php");
require_once("/var/www/html/contratheque/src/controllers/mail_controller.php");

# Fonction qui édite le status des contrats actifs ayant expirés
function check_expired_contracts($cnx){
    try{
    	include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration
    	$contracts = get_new_expired_contracts($cnx); // Récupération des contrats récemment expirés

        // Si des contrats ont expirés recemment, edition de leur status pour chacun d'entre eux
    	if($contracts){
        	foreach($contracts as $contract){

                        # Edition du status du contrat dans la BDD
			updateDb($cnx,["status"=>"expired"],["intern_num"=>$contract["intern_num"]]);

                        # Déplacement du fichier correspondant au contrat du répertoire actif vers le répertoire expiré
			if(file_exists("{$PDF_ROOT}active/". $contract["intern_num"] . ".pdf")){
                		rename("{$PDF_ROOT}active/". $contract["intern_num"] . ".pdf", "{$PDF_ROOT}expired/". $contract["intern_num"] . ".pdf");
            		}
		
		}
    	}

        # Ajout dans les logs : Succès
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_expired_contracts : Success\n", FILE_APPEND | LOCK_EX);
     }
     catch(Exception $e){
        # Ajout dans les logs : Echec, une erreur est survenue
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_expired_contracts : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
     }
 }

# Fonction qui archive les contrats expirés ayant dépassé le delais de conservation
function check_archived_contracts($cnx){
    try{
    	include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration
    	$contracts = get_new_archived_contracts($cnx); // Récupération des contrats expirés ayant dépassé le delais de conservation

        // Si des contrats sont à archiver, déplacement de chacun d'entre eux vers les archives
    	if($contracts){
        	foreach($contracts as $contract){

                        # Reformatage pour convenir à la table d'archive
			unset($contract["status"]);
			unset($contract["contract_followup_date"]);

                        # Déplacement vers la table d'archive et suppression de la table contrat
			insertDb($cnx,$contract,"archived_contracts");
			deleteDb($cnx,["intern_num"=>$contract["intern_num"]],"contracts");

                        # Déplacement du fichier correspondant au contrat du répertoire expiré vers le répertoire archivé
			if(file_exists("{$PDF_ROOT}expired/". $contract["intern_num"] . ".pdf")){
                		rename("{$PDF_ROOT}expired/". $contract["intern_num"] . ".pdf", "{$PDF_ROOT}archived/". $contract["intern_num"] . ".pdf");
            		}
		
		}
    	}

        # Ajout dans les logs : Succès
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_archived_contracts : Success\n", FILE_APPEND | LOCK_EX);
     }
     catch(Exception $e){
        # Ajout dans les logs : Echec, une erreur est survenue
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_archived_contracts : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
     }
 }


# Suppression définitive des contrats supprimés ayant dépassés le delais de conservation
function check_deletion_deadline($cnx){
    try{
    	include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration
    	$contracts = get_new_fully_deleted_contracts($cnx); // Récupération des contrats supprimés ayant dépassés le delais de conservation
    
        // Si des contrats sont à supprimer, suppression définitive de chacuns d'entre eux
    	if($contracts){
        	foreach($contracts as $contract){

                        # Suppression du contrat de la BDD
            		deleteDb($cnx,["intern_num"=>$contract["intern_num"]],"deleted_contracts");

                        # Suppression du document PDF lié au contrat
            		if(file_exists("{$PDF_ROOT}deleted/". $contract["intern_num"] . ".pdf")){
                		unlink("{$PDF_ROOT}deleted/". $contract["intern_num"] . ".pdf");
            		}
        	}
    	}

        // Ajout dans les logs : Succès
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_deletion_deadline : Success\n", FILE_APPEND | LOCK_EX);
    }
    catch(Exception $e){
        # Ajout dans les logs : Echec, une erreur est survenue
	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_deletion_deadline : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
     }
}

# Vérification qu'un fichier ne se trouve pas dans le mauvais répértoire
function check_lost_files($cnx,$intern_num,$repo){

        include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration

        // Recherche d'une correspondance du numéro de contrat dans les tables ne correspondant pas au réprtoire actuel du PDF
        // Si une correspondance est trouvée, le document est déplacé dans le bon répértoire 
        if($repo == "active"){
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"expired"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "expired/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"deleted_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "deleted/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"archived_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "archived/" . $intern_num . ".pdf");
                }
        }
        elseif($repo == "expired"){
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"active"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "active/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"deleted_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "deleted/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"archived_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "archived/" . $intern_num . ".pdf");
                }
        }
        elseif($repo == "deleted"){
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"active"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "active/" . $intern_num . ".pdf");
                }
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"expired"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf", $PDF_ROOT . "expired/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"archived_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "archived/" . $intern_num . ".pdf");
                }
        }
        elseif($repo == "archived"){
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"active"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "active/" . $intern_num . ".pdf");
                }
                if(fetchDb($cnx,["intern_num"=>$intern_num,"status"=>"expired"],["intern_num"])){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf", $PDF_ROOT . "expired/" . $intern_num . ".pdf");
                }
                elseif(fetchDb($cnx,["intern_num"=>$intern_num],["intern_num"],"deleted_contracts")){
                        rename($PDF_ROOT . $repo . "/" . $intern_num . ".pdf",$PDF_ROOT . "deleted/" . $intern_num . ".pdf");
                }
        }

        # Si une correspondance a été trouvée et que la position du document a été corrigée avec succès, retourne 1
	if(!file_exists($PDF_ROOT . $repo . "/" .  $intern_num . ".pdf")){return 1;}

        # Sinon, le document ne correspond à aucun contrat dans la BDD, retourne 0
	else{return 0;}

}

# Nettoyage des documents ne correspondant à aucun contrat de la BDD
function check_unlinked_files($cnx) {
    include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration
    try {

        // Association répertoire → table
        $repositoryTableMap = [
            "active"   => "contracts",
            "expired"  => "contracts",
            "deleted"  => "deleted_contracts",
            "archived" => "archived_contracts"
        ];

        // Pour chaque répertoire, récupération des noms des PDF présent et vérification qu'ils correspondent bien à des contrats dans la table de la BDD correspondante
        foreach ($repositoryTableMap as $repo => $table) {
            
            // Création et vérification du chemin vers le répertoire de stockage des fichiers
            $path = $PDF_ROOT . $repo . "/";
            if (!is_dir($path)) {
                continue;
            }

            # Récupération des fichiers du répértoire
            $files = array_diff(scandir($path), ['.', '..']);

            # Pour chacun d'entre eux, vérification d'une correspondance dans la BDD, sinon vérification que le fichier n'est pas égaré, sinon suppression
            foreach ($files as $file) {
                $fullPath = $path . $file;

                # Passe si l'element n'est pas un fichier
                if (!is_file($fullPath)) {
                    continue;
                }

                // Récupération du numéro de contrat (nom du fichier)
                $filename = pathinfo($file, PATHINFO_FILENAME);

                // Vérifie si le fichier correspond à un contrat de la base
		if($table == "contracts"){$result = fetchDb($cnx, ["intern_num" => $filename], ["intern_num","status"], $table);}
                else{$result = fetchDb($cnx, ["intern_num" => $filename], ["intern_num"], $table);}

                // Si aucune correspondance dans la table, une vérification de correspondance dans les autres tables est faite, si aucune correspondance n'est trouvée le fichier est supprimé
                if (empty($result) || (isset($result[0]["status"]) && $result[0]["status"] != $repo)) {
		    if(!check_lost_files($cnx,$filename,$repo)){unlink($fullPath);}
                }
            }
        }

        // Ajout dans les logs : Succès
        file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_unlinked_files : Success\n", FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        # Ajout dans les logs : Echec, une erreur est survenue
        file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Check_unlinked_files : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Réinitialisation de la limite de mails des utilisateurs ayant entamé leur quota de ticket quotidien
function reset_email_limit($cnx){
    try{
        include("/etc/contratheque/conf.php"); // Chargement du fichier de configuration
        $users = get_users_resetable_email_limit($cnx,$EMAIL_LIMITATION); // Récupération des utilisateurs avec un quota de mail entamé

        // Si des utilisateurs ont entamé leur quota de mail, celui-ci est réinitialisé à la valeur configurée pour chacun d'entre eux
        if($users){
                foreach($users as $user){

                        // Réinitialisation de la valeur "emails restants" dans le BDD
                        updateDb($cnx,["remaining_mails"=>$EMAIL_LIMITATION],["samaccountname"=>$user["samaccountname"]],"users");
                }
        }

        // Ajout dans les logs : Succès
        file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Reset_email_limit : Success\n", FILE_APPEND | LOCK_EX);
     }
     catch(Exception $e){
        // Ajout dans les logs : Echec, une erreur est survenue
        file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Reset_email_limit : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
     }
 }

// Maintenance des tables de logs (Ne garde que 200 Lignes)
function clean_logs($cnx){
	try{
		trim_login_logs($cnx);
		trim_db_logs($cnx);
		// Ajout dans les logs : Succès
        	file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Clean logs : Success\n", FILE_APPEND | LOCK_EX);

	}
	catch(Exception $e){
        // Ajout dans les logs : Echec, une erreur est survenue
        file_put_contents("/var/www/html/contratheque/src/cron/log.txt", "Clean logs : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
     }

}


##########################################################################################################################################################
# Execution

# Intialisation des logs
file_put_contents("/var/www/html/contratheque/src/cron/log.txt", date("Y-m-d") . ": \n", FILE_APPEND | LOCK_EX);

# Execution des fonctions
check_expired_contracts($cnx);
check_deletion_deadline($cnx);
check_archived_contracts($cnx);
check_unlinked_files($cnx);
reset_email_limit($cnx);
send_reminder($cnx);
clean_logs($cnx);

?>

