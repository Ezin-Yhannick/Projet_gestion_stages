<?php
// upload_rapport.php

// Inclure votre fichier de connexion à la base de données
require_once 'db.php'; // Assurez-vous que ce fichier contient la connexion $pdo

// Vérifiez si la requête est de type POST et si un fichier a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['rapport_stage'])) {
    // Récupérer l'ID de la candidature (stage)
    // Il est CRUCIAL de s'assurer que l'ID de candidature est valide et appartient à l'étudiant connecté
    // Pour cet exemple, nous supposons que l'ID est passé via un champ caché dans le formulaire.
    // En production, vous devriez vérifier l'authentification de l'étudiant et son association avec l'ID de candidature.
    $applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

    if ($applicationId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de candidature invalide.']);
        exit;
    }

    $file = $_FILES['rapport_stage'];

    // Informations sur le fichier
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // Extensions autorisées (PDF, DOCX)
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx'];

    if (!in_array($fileExt, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls les PDF, DOC et DOCX sont acceptés.']);
        exit;
    }

    // Taille maximale du fichier (ex: 10 Mo)
    if ($fileSize > 10 * 1024 * 1024) { // 10 MB
        echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (max 10 Mo).']);
        exit;
    }

    // Vérifier les erreurs d'upload
    if ($fileError !== 0) {
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors du téléchargement du fichier. Code: ' . $fileError]);
        exit;
    }

    // Créer un nom de fichier unique pour éviter les conflits
    $newFileName = uniqid('rapport_') . '.' . $fileExt;
    $uploadDir = 'uploads/rapports_stage/'; // Chemin où stocker les rapports (doit exister et être accessible en écriture)

    // Assurez-vous que le répertoire d'upload existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = $uploadDir . $newFileName;

    // Déplacer le fichier temporaire vers le répertoire de destination
    if (move_uploaded_file($fileTmpName, $filePath)) {
        try {
            // Mettre à jour la base de données
            $stmt = $pdo->prepare("
                UPDATE tb_candidature
                SET rapport_url = ?, rapport_statut = 'soumis'
                WHERE id_candidature = ?
            ");
            $stmt->execute([$filePath, $applicationId]);

            // Envoyer une notification à l'entreprise (optionnel, mais recommandé)
            // Vous pouvez récupérer l'ID de l'entreprise et son email pour envoyer une notification.
            // ... (logique d'envoi de notification à l'entreprise) ...

            echo json_encode(['success' => true, 'message' => 'Rapport de stage déposé avec succès !']);
        } catch (PDOException $e) {
            error_log("Database error in upload_rapport.php: " . $e->getMessage());
            // Supprimer le fichier si la mise à jour DB échoue
            unlink($filePath); 
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du rapport en base de données.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Échec du déplacement du fichier téléchargé.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide ou aucun fichier soumis.']);
}
?>
