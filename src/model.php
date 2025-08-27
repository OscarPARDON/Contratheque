<?php

###########################################################################################################
# Connexion à la BDD

//Récupération des informations pour la connexion à la BDD
include("/etc/contratheque/conf.php");
require_once("/var/www/html/contratheque/src/helper/helper.php");

// Tentative de connexion à la BDD
try {
    $cnx = new PDO("mysql:host=$HOSTNAME;dbname=$DB_NAME","$USERNAME","$DB_PASSWORD");
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Affichage du message d'erreur si la connexion est impossible
catch (PDOException $e){
    header("Location:index.php?route=error&error=" . $e->getMessage());
    die();
}

#########################################################################################################
# Utilisées exclusivement par CRON

#Récupération de contrats actif ayant expirés
function get_new_expired_contracts($cnx){
    $current_date = date("Y-m-d");
    try {
        $query = $cnx->prepare("SELECT * FROM contracts WHERE status = 'active' AND contract_end < :currentdate");
        $query->bindParam(':currentdate', $current_date, PDO::PARAM_STR);
        $query->execute();
    }
    catch (Exception $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
    return $query->fetchall();
}

# Récupération des contrats expirés ayant dépassés le delais d'archivation
function get_new_archived_contracts($cnx) {
    $archivation_date = (new DateTime())->modify('-5 years')->format('Y-m-d');
    
    try {
        $query = $cnx->prepare("SELECT * FROM contracts WHERE contract_end < :archivationDate");
        $query->bindParam(':archivationDate', $archivation_date, PDO::PARAM_STR);
        $query->execute();
    } catch (Exception $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
    
    return $query->fetchAll(PDO::FETCH_ASSOC);
}


# Récupération des contrats supprimés ayant dépassés le delais de conservation
function get_new_fully_deleted_contracts($cnx){
    include("/etc/contratheque/conf.php");

    $current_date = date("Y-m-d"); #Récupération de la date du jour
    $keeping_duration_tab = explode('-',$KEEPING_DURATION); #Décomposition de la durée de conservation
    $current_date_tab = explode('-',$current_date); #Décomposition de la date
    $deadline_tab = [[],[],[]]; #Création de la variable qui contiendra date de vérification décomposée

    #Année
    $deadline_tab[0] = intval($current_date_tab[0]) - intval($keeping_duration_tab[0]); #Calcul de l'année de vérification
    #Mois
    $deadline_tab[1] = $current_date_tab[1] - $keeping_duration_tab[1]; #Calcul du mois de vérification
    #Jour
    $deadline_tab[2] = $current_date_tab[2]-$keeping_duration_tab[2]; #Calcul du jour de vérification

    if($deadline_tab[2] <= 0){ #Si la valeur issue de la soustraction est inférieure ou égale à 0
        $deadline_tab[2] = $deadline_tab[2]+30; #On remet le numéro du jour au debut
        $deadline_tab[1] = $deadline_tab[1] -1; #Et on soustrait 1 mois à la date de vérification
    }
    if($deadline_tab[1] <= 0){ #Si la valeur apres soustraction est inférieure à 0
        $deadline_tab[1] = $deadline_tab[1]+12; #On remet le numéro du mois au début
        $deadline_tab[0] = $deadline_tab[0] -1; #On soustrait une année à la date d'échéance
    }

    $deadline = implode('-',$deadline_tab); #Recomposition de la date d'échéance sous format Y-m-d

    try {
        $query = $cnx->prepare("SELECT * FROM deleted_contracts WHERE deletion_date < DATE :deadline"); #Récupération des contrats ayant leur date de supression inférieur à la date de vérification
        $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
        $query->execute();
    }
    catch (Exception $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
    return $query->fetchall();
}

# Récupération des utilisateurs ayant activé le rappel de contrat
function get_reminded_users($cnx){
    try{
        $query = "SELECT DISTINCT us.user_sam, u.mail FROM user_services us JOIN users u ON us.user_sam = u.samaccountname WHERE us.role IN ('Admin','Manager') AND u.contracts_reminders = 1";
        $request = $cnx->prepare($query);
        $request->execute([]);
        return $request->fetchAll(PDO::FETCH_ASSOC);

    }
    catch(PDOException $e){
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des utilisateurs ayant utilisé la fonction de ticketing
function get_users_resetable_email_limit($cnx,$email_limit){
    try {
          
        $query = "SELECT samaccountname FROM users WHERE remaining_mails != ?";
        $stmt = $cnx->prepare($query);
        $stmt->execute([$email_limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Compteur du nombre de contrats en alerte de renouvellement
function countSoonExpiredContracts($cnx, $conditions = []) {
    try {
        
        $query = "SELECT COUNT(*) AS count FROM contracts WHERE status = 'active' AND contract_followup_date < CURDATE()";
        $params = [];

        if (!empty($conditions)) {
            $query .= " AND ". implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }

        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)["count"];
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des contrats en alerte de renouvellement
function getSoonExpiredContracts($cnx, $conditions = []) {
    try {
        
        $query = "SELECT * FROM contracts WHERE contract_followup_date < CURDATE() AND status = 'active'";
        $params = [];

        if (!empty($conditions)) {
            $query .= " AND ". implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }

        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

// D�coupe la table de logs de la base de donn�es pour ne garder que les 200 derni�res entr�es
function trim_db_logs($cnx){
      try {
        
        $query = "DELETE FROM db_log WHERE id < (SELECT MIN(id) FROM (SELECT id FROM db_log ORDER BY datetime DESC LIMIT 200) AS t)";
        $stmt = $cnx->prepare($query);
        $stmt->execute();        
        
        return 1;
      } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
    }

}

// D�coupe la table de logs de connexion pour ne garder que les 200 derni�res entr�es
function trim_login_logs($cnx){
      try {
        $query = "DELETE FROM login_logs WHERE id < (SELECT MIN(id) FROM (SELECT id FROM login_logs ORDER BY datetime DESC LIMIT 200) AS t)";
        $stmt = $cnx->prepare($query);
        $stmt->execute();
        
        return 1;
      } catch (PDOException $e) {
        return 0;
    }

}

###############################################################################################################################################################
# Fonctions générales

# Récupération des données d'une table dans la BDD 
function fetchDb($cnx, $conditions = [], $fields = null, $table="contracts") {
    try {
        $selectFields = $fields && is_array($fields) ? implode(', ', $fields) : '*';
        
        $query = "SELECT $selectFields FROM $table";
        $params = [];
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }
        
        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Compteur de résultats dans une table de la BDD
function countDb($cnx, $conditions = [], $table="contracts") {
    try {
        
        $query = "SELECT COUNT(*) AS count FROM $table";
        $params = [];
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(function ($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }
        
        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)["count"];
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

// Ajouter d'une entrée dans une table de la BDD
function insertDb($cnx,$data,$table="contracts"){
    try {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        
        $stmt = $cnx->prepare($query);
        write_db_log($cnx,"Insertion",$table);
        return $stmt->execute($data);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

// Edition d'une entrée dans une table de la BDD
function updateDb($cnx, $data, $conditions, $table = "contracts") {
    try {
        $setClause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($key) => "$key = :cond_$key", array_keys($conditions)));
        
        $query = "UPDATE $table SET $setClause WHERE $whereClause";
        
        $params = array_merge($data, array_combine(
            array_map(fn($key) => "cond_$key", array_keys($conditions)),
            array_values($conditions)
        ));
        
        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        write_db_log($cnx,"Update",$table);
        return $stmt->rowCount(); // Retourne le nombre de lignes affectées
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Suppression d'une entrée dans la BDD
function deleteDb($cnx, $conditions, $table = "contracts") {
    try {
        $whereClause = implode(' AND ', array_map(fn($key) => "$key = :$key", array_keys($conditions)));
        
        $query = "DELETE FROM $table WHERE $whereClause";
        
        $stmt = $cnx->prepare($query);
        $stmt->execute($conditions);
        
        write_db_log($cnx,"Deletion",$table);
        return $stmt->rowCount(); // Retourne le nombre de lignes supprimées
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

###############################################################################################################################################################
# Fonctions contrats

# Récupération du numéro du dernier contrat d'un service
function contracts_nbr($cnx, $service_id){
    try {
        $nbr = $cnx->prepare("SELECT MAX(intern_num) AS max_intern_num FROM (SELECT intern_num FROM contracts WHERE service_id = :id UNION ALL SELECT intern_num FROM deleted_contracts WHERE service_id = :id) AS all_intern_nums");
        $nbr->bindParam(':id', $service_id, PDO::PARAM_STR);
        $nbr->execute();
        $nbr = $nbr->fetch();
        if($nbr[0] == NULL){
            $nbr = "001";
        }
        else{
            $nbr = explode('-',$nbr[0]);
            $nbr = end($nbr);
            if($nbr < 9){
                $nbr = "00" . strval($nbr+1);
            }
            elseif($nbr < 99){
                $nbr = "0" . strval($nbr+1);
            }
            else{
                $nbr = strval($nbr+1);
            }
        }
    } 
    catch (Exception $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
    
    return $nbr;
}

# Récupération des contrats entiers dans la BDD
function fetch_contracts($cnx, $conditions, $table){
    try {

	if($table === "deleted_contracts"){
	    $query = "SELECT * FROM $table t LEFT JOIN contractors c ON t.contractor_id = c.id JOIN services s ON s.id = t.service_id LEFT JOIN users u ON t.deleted_by = u.samaccountname";
	}
	else{
            $query = "SELECT * FROM $table t LEFT JOIN contractors c ON t.contractor_id = c.id JOIN services s ON s.id = t.service_id LEFT JOIN users u ON t.created_by = u.samaccountname";
	}
        
        $params = [];
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(function ($key) {
                return "t.$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }
        
        $stmt = $cnx->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des contrats archivés dans la BDD
function fetch_archives($cnx){
    try {
        $query = "SELECT * FROM archived_contracts ac JOIN contractors c ON ac.contractor_id = c.id JOIN services s ON s.id = ac.service_id";
        $stmt = $cnx->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

###############################################################################################################################################################
# Fonctions services

# Récupération des utilisateurs qui ne sont pas déjà dans un service
function fetch_unselected_users($cnx,$service_id){
    try {

        $query = "SELECT * FROM users WHERE samaccountname NOT IN (SELECT user_sam FROM user_services WHERE service_id = ?)";

        $stmt = $cnx->prepare($query);
        $stmt->execute([$service_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . urlencode($e->getMessage()));
        exit;
    }
}

# Récupération des utilisateurs d'un service
function fetch_service_users($cnx, $conditions) {
    try {
        $query = "
            SELECT u.*, us.role
            FROM users u
            INNER JOIN user_services us ON u.samaccountname = us.user_sam
        ";

        $params = [];

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(function ($key) {
                return "us.$key = :$key";
            }, array_keys($conditions)));
            $params = $conditions;
        }

        $stmt = $cnx->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . urlencode($e->getMessage()));
        exit;
    }
}

###############################################################################################################################################################
# Fonctions utilisateurs

# Récupération des IDs des services auxquels un utilisateur a accès
function get_user_services_ids($cnx,$user_sam){
    try{
        $query = $cnx->prepare("SELECT service_id FROM user_services WHERE user_sam = ?");
        $query->execute([$user_sam]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des services dans lequel l'utilisateur a un rôle de Responsable ou d'Administrateur
function get_user_controlled_services($cnx,$user_sam){
    try{
        $query = $cnx->prepare("SELECT s.* FROM services s JOIN user_services us ON us.service_id = s.id WHERE us.user_sam = ? AND us.role IN ('Admin','Manager')");
        $query->execute([$user_sam]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des services auxquels un utilisateur a accès
function get_user_services($cnx,$user_sam){
    try{
        $query = $cnx->prepare("SELECT * FROM user_services us JOIN services s ON us.service_id = s.id WHERE us.user_sam = ?");
        $query->execute([$user_sam]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération de tous les contractants auxquels un utilisateur a accès
function get_all_contractors($cnx,$user_sam){
    try {

        $query = "SELECT * FROM contractors WHERE service_id IN (SELECT service_id FROM user_services WHERE user_sam = ?)";

        $stmt = $cnx->prepare($query);
        $stmt->execute([$user_sam]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . urlencode($e->getMessage()));
        exit;
    }
}

###############################################################################################################################################################
# Fonctions logs

# Récupération des logs de connexion dans la BDD
function get_login_log($cnx, $remote_address){
    try{
        include("/etc/contratheque/conf.php");
        $query = "SELECT result
        FROM login_logs
        WHERE remote_address = ?
        ORDER BY datetime DESC
        LIMIT $MAX_TRIES";
        $request = $cnx->prepare($query);
        $request->execute([$remote_address]);
        return $request->fetchAll(PDO::FETCH_ASSOC);

    }
    catch(PDOException $e){
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Ajout d'une nouvelle entrée dans les logs de la BDD
function write_db_log($cnx, $operation, $table){
    require_once("/var/www/html/contratheque/src/controllers/login_controller.php"); # Importation du login controller pour avoir accès à la fonction get_client_ip en cas d'utilisation par la tâche cron
    $username = $_SESSION["UserInfo"]["Username"] ?? "Server";
    try{
        $insertion = $cnx->prepare("INSERT INTO db_log (remote_address,user_sam,db_table,operation) VALUES (?,?,?,?)");
        $insertion->execute([get_client_ip(),$username,$table,$operation]);
    }
    catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

# Récupération des logs de connexion
function loginLog($cnx,$log){
    try {
        $fields = implode(', ', array_keys($log));
        $placeholders = ':' . implode(', :', array_keys($log));
        $query = "INSERT INTO login_logs ($fields) VALUES ($placeholders)";
        
        $stmt = $cnx->prepare($query);
        return $stmt->execute($log);
    } catch (PDOException $e) {
        header("Location:index.php?route=error&error=" . $e->getMessage());
        exit;
    }
}

?>
