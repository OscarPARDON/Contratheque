<?php

function handle_pdf($contract_num, $contract_status = null) {
    // Configuration et préparation des paramètres
    include("/etc/contratheque/conf.php"); // Inclusion anticipée pour $PDF_ROOT
    
    $isUpdate = isset($contract_status);
    $directory = $isUpdate ? $contract_status : "active";
    $filePath = "{$PDF_ROOT}{$directory}/{$contract_num}.pdf";
    
    // Construction de l'URL de redirection selon le contexte
    if ($isUpdate) {
        $redirectRoute = "index.php?route=update_contract&intern_num={$contract_num}";
    } elseif (isset($_POST["previous_contract_num"])) {
        $redirectRoute = "index.php?route=new_contract&intern_num=" . urlencode($_POST["previous_contract_num"]);
    } else {
        $redirectRoute = "index.php?route=new_contract";
    }

    // Règles de validation avec gestion d'erreurs centralisée
    $validationRules = [
        // Validation du nom du contrat
        [
            'condition' => !preg_match('/^[a-zA-Z0-9_-]+$/', $contract_num),
            'error' => "Nom de contrat invalide"
        ],
        // Vérification de l'existence du fichier uploadé
        [
            'condition' => !isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK,
            'error' => "Erreur lors du téléchargement du fichier"
        ],
        // Validation de la taille (2MB maximum)
        [
            'condition' => ($_FILES["pdf_file"]["size"] ?? 0) > 2000000,
            'error' => "Le fichier est trop volumineux (maximum 2MB)"
        ],
        // Validation de l'extension
        [
            'condition' => strtolower(pathinfo($_FILES['pdf_file']['name'] ?? '', PATHINFO_EXTENSION)) !== "pdf",
            'error' => "Le fichier doit être un PDF"
        ]
    ];

    // Vérification de toutes les règles de validation de base
    foreach ($validationRules as $rule) {
        if ($rule['condition']) {
            header("Location: {$redirectRoute}&fileError=" . urlencode($rule['error']));
            exit;
        }
    }

    // Validation avancée du type MIME (nécessite le fichier temporaire)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        header("Location: {$redirectRoute}&fileError=" . urlencode("Erreur système lors de la validation du fichier"));
        exit;
    }
    
    $mimeType = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
    finfo_close($finfo);
    
    if ($mimeType !== 'application/pdf') {
        header("Location: {$redirectRoute}&fileError=" . urlencode("Le fichier n'est pas un PDF valide"));
        exit;
    }

    // Vérification et création du répertoire de destination si nécessaire
    $destinationDir = "{$PDF_ROOT}{$directory}/";
    if (!is_dir($destinationDir)) {
        if (!mkdir($destinationDir, 0755, true)) {
            header("Location: {$redirectRoute}&fileError=" . urlencode("Impossible de créer le répertoire de destination"));
            exit;
        }
    }

    // Gestion de l'ancien fichier en cas de mise à jour
    if ($isUpdate && file_exists($filePath)) {
        if (!unlink($filePath)) {
            header("Location: {$redirectRoute}&fileError=" . urlencode("Impossible de supprimer l'ancien fichier"));
            exit;
        }
    }

    // Déplacement du fichier vers sa destination finale
    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $filePath)) {
        header("Location: {$redirectRoute}&fileError=" . urlencode("Erreur lors de l'enregistrement du fichier sur le serveur"));
        exit;
    }

    // Vérification finale de l'intégrité du fichier déplacé
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        header("Location: {$redirectRoute}&fileError=" . urlencode("Le fichier n'a pas été correctement sauvegardé"));
        exit;
    }

    // Définition des permissions appropriées pour le fichier
    chmod($filePath, 0644);
}


function file_action($cnx, $contract_num, $status, $request) {
    // Validation préalable des paramètres d'entrée
    $validStatuses = ["active", "expired", "deleted", "archived"];
    $validRequests = ["display", "download"];
    
    if (!in_array($status, $validStatuses)) {
        header("Location:index.php?route=error&error=" . urlencode("Statut de contrat invalide"));
        exit;
    }
    
    if (!isset($request) || !in_array($request, $validRequests)) {
        header("Location:index.php?route=error&error=" . urlencode("Requête invalide"));
        exit;
    }
    
    // Validation du format du numéro de contrat
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $contract_num)) {
        header("Location:index.php?route=error&error=" . urlencode("Numéro de contrat invalide"));
        exit;
    }

    // Mapping des statuts vers les tables de base de données
    $tableMapping = [
        "active" => "contracts",
        "expired" => "contracts",
        "deleted" => "deleted_contracts",
        "archived" => "archived_contracts"
    ];
    
    // Récupération du contrat selon son statut
    $tableName = $tableMapping[$status];
    $contractData = fetchDb($cnx, ["intern_num" => $contract_num], NULL, $tableName);
    
    if (empty($contractData)) {
        header("Location:index.php?route=error&error=" . urlencode("Ce contrat n'existe pas"));
        exit;
    }
    
    $contract = $contractData[0];

    // Vérification des permissions utilisateur
    $hasGlobalAccess = ($_SESSION["UserInfo"]["Current_Service"] ?? null) === "*";
    $hasServiceAccess = isset($_SESSION["UserInfo"]["Current_Service"]) && 
                       $contract["service_id"] == $_SESSION["UserInfo"]["Current_Service"];
    
    if (!$hasGlobalAccess && !$hasServiceAccess) {
        header("Location:index.php?route=error&error=" . urlencode("Vous n'avez pas accès à ce document"));
        exit;
    }

    // Construction et validation du chemin du fichier PDF
    include("/etc/contratheque/conf.php");
    $filePath = "{$PDF_ROOT}{$status}/{$contract_num}.pdf";
    
    // Vérifications de sécurité sur le chemin de fichier
    $realPath = realpath($filePath);
    $basePath = realpath($PDF_ROOT . $status);
    
    // Protection contre les attaques de traversée de répertoire
    if (!$realPath || !$basePath || strpos($realPath, $basePath) !== 0) {
        header("Location:index.php?route=error&error=" . urlencode("Accès au fichier refusé"));
        exit;
    }
    
    if (!file_exists($filePath)) {
        header("Location:index.php?route=error&error=" . urlencode("Document du contrat introuvable. Veuillez consulter le service informatique"));
        exit;
    }
    
    // Vérification de la lisibilité du fichier
    if (!is_readable($filePath)) {
        header("Location:index.php?route=error&error=" . urlencode("Document inaccessible. Veuillez consulter le service informatique"));
        exit;
    }

    // Préparation des en-têtes HTTP selon le type de requête
    $contentDisposition = ($request === "display") ? 'inline' : 'attachment';
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    
    // Validation de la taille du fichier
    if ($fileSize === false || $fileSize === 0) {
        header("Location:index.php?route=error&error=" . urlencode("Le document est corrompu ou vide"));
        exit;
    }

    // Nettoyage des buffers et configuration des en-têtes de sécurité
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // En-têtes HTTP pour le téléchargement sécurisé
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $contentDisposition . '; filename="' . $fileName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Protection contre les attaques XSS
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    
    // Envoi du fichier par chunks pour optimiser la mémoire
    $chunkSize = 8192; // 8KB par chunk
    $handle = fopen($filePath, 'rb');
    
    if (!$handle) {
        header("Location:index.php?route=error&error=" . urlencode("Impossible d'ouvrir le document"));
        exit;
    }
    
    // Lecture et envoi du fichier par chunks
    while (!feof($handle) && connection_status() === CONNECTION_NORMAL) {
        echo fread($handle, $chunkSize);
        flush();
    }
    
    fclose($handle);
    exit;
}

?>