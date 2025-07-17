<?php
// Démarre la session PHP.
session_start();

// Inclure le gestionnaire de session et la connexion à la base de données.
// Assurez-vous que ces chemins sont corrects par rapport à l'emplacement de ce script.
require 'session_manager.php';
require 'db.php';

// Message de journalisation au démarrage du script
error_log("download_separate_application_files.php: Script démarré pour la visualisation/téléchargement séparé.");

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    error_log("download_separate_application_files.php: Échec de l'authentification - Utilisateur non connecté.");
    http_response_code(403); // Forbidden
    exit("Accès non autorisé. Veuillez vous connecter.");
}

// Vérifier le rôle de l'utilisateur
$user_role = get_user_role();
// Assurez-vous que 'entreprise' est le rôle exact renvoyé par get_user_role()
if ($user_role !== 'company') { 
    error_log("download_separate_application_files.php: Échec de l'autorisation - Rôle de l'utilisateur ('" . $user_role . "') n'est pas 'entreprise'.");
    http_response_code(403); // Forbidden
    exit("Accès non autorisé. Veuillez vous connecter en tant qu'entreprise.");
}

// Récupérer l'ID de l'entreprise depuis la session
$id_entreprise_connectee = get_user_id();
error_log("download_separate_application_files.php: Utilisateur connecté est une entreprise avec l'ID: " . $id_entreprise_connectee);

// Récupérer l'ID de la candidature et le type de fichier depuis la requête GET
$id_candidature = $_GET['id_candidature'] ?? null;
$file_type = $_GET['type'] ?? null; // 'cv' ou 'lm'
$action = $_GET['action'] ?? 'view'; // 'view' pour visualiser, 'download' pour télécharger

// Valider les paramètres de la requête
if (empty($id_candidature) || !is_numeric($id_candidature)) {
    error_log("download_separate_application_files.php: Paramètre de candidature invalide ou manquant: '" . $id_candidature . "'");
    http_response_code(400); // Bad Request
    exit("Paramètre de candidature invalide ou manquant.");
}

if (!in_array($file_type, ['cv', 'lm'])) {
    error_log("download_separate_application_files.php: Type de fichier invalide ou manquant: '" . ($file_type ?? 'NULL') . "'");
    http_response_code(400); // Bad Request
    exit("Type de fichier invalide. Le type doit être 'cv' ou 'lm'.");
}

if (!in_array($action, ['view', 'download'])) {
    error_log("download_separate_application_files.php: Action invalide: '" . ($action ?? 'NULL') . "'");
    http_response_code(400); // Bad Request
    exit("Action invalide. L'action doit être 'view' ou 'download'.");
}

try {
    // Requête pour récupérer les chemins des fichiers et vérifier l'autorisation
    $stmt = $pdo->prepare("
        SELECT 
            tc.cv_path,
            tc.lettre_motivation_path,
            ps.id_entreprise,
            te.nom AS student_nom,
            te.prenom AS student_prenom
        FROM 
            tb_candidature tc
        JOIN 
            proposition_stage ps ON tc.id_stage = ps.id_stage
        JOIN
            tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        WHERE 
            tc.id_candidature = :id_candidature
    ");
    $stmt->bindParam(':id_candidature', $id_candidature, PDO::PARAM_INT);
    $stmt->execute();
    $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidature) {
        error_log("download_separate_application_files.php: Candidature non trouvée pour l'ID: " . $id_candidature);
        http_response_code(404); // Not Found
        exit("Candidature non trouvée.");
    }

    error_log("download_separate_application_files.php: Candidature trouvée. ID entreprise de l'offre: " . $candidature['id_entreprise'] . ", ID entreprise connectée (session): " . $id_entreprise_connectee);

    // Vérifier si l'offre de stage associée à cette candidature appartient bien à l'entreprise connectée
    if ($candidature['id_entreprise'] != $id_entreprise_connectee) {
        error_log("download_separate_application_files.php: Accès non autorisé - L'entreprise connectée n'est pas propriétaire de l'offre de stage associée à cette candidature.");
        http_response_code(403); // Forbidden
        exit("Vous n'êtes pas autorisé à accéder à ce fichier.");
    }

    $file_path_db = ''; // Chemin du fichier tel que récupéré de la base de données
    $download_filename_prefix = '';
    $student_full_name_clean = htmlspecialchars(str_replace(' ', '_', $candidature['student_prenom'] . '_' . $candidature['student_nom']));

    // Déterminer le chemin du fichier et le nom de téléchargement
    if ($file_type === 'cv') {
        $file_path_db = $candidature['cv_path'];
        $download_filename_prefix = "CV_" . $student_full_name_clean;
    } elseif ($file_type === 'lm') {
        $file_path_db = $candidature['lettre_motivation_path'];
        $download_filename_prefix = "Lettre_Motivation_" . $student_full_name_clean;
    }

    error_log("download_separate_application_files.php: Chemin du fichier récupéré de la DB pour type '" . $file_type . "': '" . ($file_path_db ?? 'NULL') . "'");
    
    // Tenter de résoudre le chemin absolu
    $file_path = realpath($file_path_db);

    if (!$file_path) {
        error_log("download_separate_application_files.php: ERREUR - realpath() n'a pas pu résoudre le chemin: '" . $file_path_db . "'. Le fichier n'existe probablement pas ou le chemin est invalide.");
        http_response_code(404);
        exit("Fichier " . strtoupper($file_type) . " introuvable ou chemin invalide.");
    }
    
    if (!file_exists($file_path)) {
        error_log("download_separate_application_files.php: ERREUR - Le fichier PDF n'existe pas à l'emplacement: '" . $file_path . "' (chemin original: '" . $file_path_db . "')");
        http_response_code(404); // Not Found
        exit("Fichier " . strtoupper($file_type) . " introuvable sur le serveur.");
    }

    if (!is_readable($file_path)) {
        error_log("download_separate_application_files.php: ERREUR - Le serveur n'a pas les permissions de lecture pour le fichier: '" . $file_path . "'");
        http_response_code(403); // Forbidden
        exit("Accès refusé au fichier " . strtoupper($file_type) . ". Problème de permissions sur le serveur.");
    }

    if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) !== 'pdf') {
        error_log("download_separate_application_files.php: ERREUR - Le fichier n'est pas un PDF: '" . $file_path . "' (extension: " . pathinfo($file_path, PATHINFO_EXTENSION) . ")");
        http_response_code(400); // Bad Request
        exit("Le fichier " . strtoupper($file_type) . " n'est pas au format PDF.");
    }

    $file_size = filesize($file_path);
    if ($file_size === false || $file_size === 0) {
        error_log("download_separate_application_files.php: ERREUR - Le fichier est vide ou la taille n'a pas pu être déterminée: '" . $file_path . "'");
        http_response_code(500); // Internal Server Error
        exit("Le fichier " . strtoupper($file_type) . " est vide ou corrompu.");
    }

    // Définir le nom de fichier pour le téléchargement (utilisé même pour la visualisation pour le nom du fichier)
    $download_filename = $download_filename_prefix . ".pdf";

    error_log("download_separate_application_files.php: Fichier validé: " . $file_path . ", Taille: " . $file_size . " octets, Nom de fichier: " . $download_filename . ", Action: " . $action);

    // Définir les en-têtes pour le fichier PDF
    header("Content-Type: application/pdf");
    header("Content-Length: " . $file_size);
    header("Pragma: no-cache");
    header("Expires: 0");

    // Définir l'en-tête Content-Disposition en fonction de l'action demandée
    if ($action === 'download') {
        header("Content-Disposition: attachment; filename=\"" . $download_filename . "\"");
        error_log("download_separate_application_files.php: Content-Disposition défini sur 'attachment'.");
    } else { // 'view' par défaut
        header("Content-Disposition: inline; filename=\"" . $download_filename . "\"");
        error_log("download_separate_application_files.php: Content-Disposition défini sur 'inline'.");
    }

    // Lire le fichier PDF et l'envoyer au navigateur
    $bytes_sent = @readfile($file_path);

    if ($bytes_sent === false) {
        error_log("download_separate_application_files.php: ERREUR - readfile() a échoué pour le fichier: '" . $file_path . "'");
        exit("Une erreur est survenue lors de l'envoi du fichier.");
    } elseif ($bytes_sent < $file_size) {
        error_log("download_separate_application_files.php: AVERTISSEMENT - readfile() n'a pas envoyé tous les octets. Envoyé: " . $bytes_sent . ", Attendu: " . $file_size . " pour le fichier: '" . $file_path . "'");
    } else {
        error_log("download_separate_application_files.php: Fichier PDF '" . $file_type . "' envoyé au navigateur avec succès.");
    }

    exit();

} catch (PDOException $e) {
    error_log("download_separate_application_files.php: Erreur PDO lors de la récupération des données: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    exit("Une erreur serveur est survenue lors de la récupération des données de la base de données.");
} catch (Exception $e) {
    error_log("download_separate_application_files.php: Erreur inattendue lors de la récupération ou de l'envoi du PDF: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    exit("Une erreur inattendue est survenue: " . $e->getMessage());
}
?>
