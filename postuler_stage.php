<?php
// Démarre la session PHP.
session_start();

// Inclure le gestionnaire de session pour utiliser les fonctions is_logged_in, get_user_role, get_user_id.
require 'session_manager.php'; 
// Inclure le fichier de connexion à la base de données PDO.
require 'db.php'; 

// Définir les en-têtes pour permettre les requêtes AJAX depuis d'autres origines (si votre frontend est sur un domaine différent)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Seule la méthode POST est autorisée pour l'envoi de données
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json'); // Indique que la réponse sera du JSON

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier si la requête est bien de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode de requête non autorisée.']);
    exit();
}

// Vérifier si l'utilisateur est connecté et est bien un étudiant
if (!is_logged_in() || get_user_role() !== 'student') { // Assurez-vous que 'etudiant' est le rôle correct
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous connecter en tant qu\'étudiant.']);
    exit();
}

// Récupérer l'ID de l'étudiant depuis la session (sécurisé)
$id_etudiant = get_user_id();

// --- MODIFICATION MAJEURE ICI : Récupérer les données du formulaire envoyées via FormData ---
// Les données sont dans $_POST pour les champs texte et $_FILES pour les fichiers.
$id_offre = $_POST['id_offre'] ?? null;

// Valider les données reçues
if (empty($id_offre) || !is_numeric($id_offre)) {
    echo json_encode(['success' => false, 'message' => 'ID de l\'offre invalide ou manquant.']);
    exit();
}

// Vérifier si l'étudiant a déjà postulé à cette offre
try {
    // Utilisation des noms de tables et colonnes de dashboard_etudiant.php
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tb_candidature WHERE id_etudiant = :id_etudiant AND id_stage = :id_offre");
    $stmt_check->bindParam(':id_etudiant', $id_etudiant, PDO::PARAM_INT);
    $stmt_check->bindParam(':id_offre', $id_offre, PDO::PARAM_INT);
    $stmt_check->execute();
    $count = $stmt_check->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà postulé à cette offre.']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur de vérification de candidature existante: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur lors de la vérification de la candidature.']);
    exit();
}

// --- Gestion de l'upload des fichiers (CV et Lettre de Motivation) ---
$upload_dir = 'uploads/'; // Assurez-vous que ce dossier existe et est inscriptible par le serveur web
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Crée le dossier si inexistant
}

$cv_path = null;
$lettre_motivation_path = null;
$errors = [];

// Fonction utilitaire pour gérer l'upload d'un fichier
function handleFileUpload($file_input_name, $upload_dir, $id_etudiant, $id_offre, $pdo) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$file_input_name];
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_ext)) {
            return ['success' => false, 'message' => "Seuls les fichiers PDF sont autorisés pour " . $file_input_name . "."];
        }
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => "La taille du fichier " . $file_input_name . " dépasse la limite de 5MB."];
        }

        // Générer un nom de fichier unique pour éviter les conflits
        // Format: type_etudiantID_offreID_timestamp.pdf
        $unique_file_name = $file_input_name . '_' . $id_etudiant . '_' . $id_offre . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $unique_file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'path' => $target_file];
        } else {
            return ['success' => false, 'message' => "Erreur lors du déplacement du fichier " . $file_input_name . "."];
        }
    } else if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] !== UPLOAD_ERR_NO_FILE) {
        // Gérer les autres erreurs d'upload (taille max, etc.)
        return ['success' => false, 'message' => "Erreur d'upload pour " . $file_input_name . ": Code " . $_FILES[$file_input_name]['error'] . "."];
    }
    return ['success' => false, 'message' => "Le fichier " . $file_input_name . " est manquant."];
}

// Gérer l'upload du CV
$cv_upload_result = handleFileUpload('cv', $upload_dir, $id_etudiant, $id_offre, $pdo);
if ($cv_upload_result['success']) {
    $cv_path = $cv_upload_result['path'];
} else {
    $errors[] = $cv_upload_result['message'];
}

// Gérer l'upload de la Lettre de Motivation
$lm_upload_result = handleFileUpload('lettre_motivation', $upload_dir, $id_etudiant, $id_offre, $pdo);
if ($lm_upload_result['success']) {
    $lettre_motivation_path = $lm_upload_result['path'];
} else {
    $errors[] = $lm_upload_result['message'];
}

// Si des erreurs d'upload sont survenues, renvoyer l'erreur et supprimer les fichiers déjà uploadés
if (!empty($errors)) {
    // Nettoyer les fichiers partiellement uploadés
    if ($cv_path && file_exists($cv_path)) unlink($cv_path);
    if ($lettre_motivation_path && file_exists($lettre_motivation_path)) unlink($lettre_motivation_path);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit();
}

// Préparer la requête SQL pour insérer la candidature
// Assurez-vous que les noms de colonnes correspondent à votre table `candidatures`
$sql = "INSERT INTO tb_candidature (id_etudiant, id_stage, cv_path, lettre_motivation_path, date_candidature, statut) 
        VALUES (:id_etudiant, :id_offre, :cv_path, :lettre_motivation_path, NOW(), 'en attente')"; // Statut initial 'En attente'

try {
    $stmt = $pdo->prepare($sql);

    // Lier les paramètres
    $stmt->bindParam(':id_etudiant', $id_etudiant, PDO::PARAM_INT);
    $stmt->bindParam(':id_offre', $id_offre, PDO::PARAM_INT);
    $stmt->bindParam(':cv_path', $cv_path, PDO::PARAM_STR);
    $stmt->bindParam(':lettre_motivation_path', $lettre_motivation_path, PDO::PARAM_STR);

    // Exécuter la requête
    if ($stmt->execute()) {
        // --- Récupérer les informations pour l'e-mail de confirmation ---
        $student_email = '';
        $student_name = '';
        $internship_title = ''; // Renommé pour correspondre à 'titre' dans offres_stage
        $company_name = '';

        try {
            // Utilisation des noms de tables et colonnes de dashboard_etudiant.php
            $stmt_get_details = $pdo->prepare("
                SELECT 
                    te.email AS student_email,
                    te.prenom AS student_prenom,
                    te.nom AS student_nom,
                    ps.sujet AS internship_title,
                    e.nom_entreprise AS company_name
                FROM 
                    tb_etudiant te
                JOIN 
                    tb_candidature tc ON te.id_etudiant = tc.id_etudiant
                JOIN 
                    proposition_stage ps ON tc.id_stage = ps.id_stage
                JOIN 
                    tb_entreprise e ON ps.id_entreprise = e.id_entreprise
                WHERE 
                    te.id_etudiant = :id_etudiant AND ps.id_stage = :id_offre
                LIMIT 1
            ");
            $stmt_get_details->bindParam(':id_etudiant', $id_etudiant, PDO::PARAM_INT);
            $stmt_get_details->bindParam(':id_offre', $id_offre, PDO::PARAM_INT);
            $stmt_get_details->execute();
            $details = $stmt_get_details->fetch(PDO::FETCH_ASSOC);

            if ($details) {
                $student_email = $details['student_email'];
                $student_name = $details['student_prenom'] . ' ' . $details['student_nom'];
                $internship_title = $details['internship_title'];
                $company_name = $details['company_name'];
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la récupération des détails pour l'e-mail de confirmation: " . $e->getMessage());
        }

        // --- Envoi de l'e-mail de confirmation ---
        $to = $student_email;
        $subject = "Confirmation de votre Candidature de Stage";
        $message_body = "
            <html>
            <head>
                <title>Confirmation de Candidature</title>
            </head>
            <body>
                <p>Bonjour " . htmlspecialchars($student_name) . ",</p>
                <p>Nous confirmons la bonne réception de votre candidature pour le stage <strong>'" . htmlspecialchars($internship_title) . "'</strong> chez <strong>" . htmlspecialchars($company_name) . "</strong>.</p>
                <p>Votre candidature est actuellement en cours d'examen. Vous recevrez une notification dès que son statut changera.</p>
                <p>Cordialement,</p>
                <p>L'équipe de l'Université</p>
                <p><i>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</i></p>
            </body>
            </html>
        ";
        $headers = "From: no-reply@votreuniversite.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        if (!empty($to) && !empty($subject) && !empty($message_body)) {
            // La fonction mail() de PHP nécessite un serveur mail configuré (ex: Sendmail, Postfix, ou un service SMTP)
            // Si vous êtes en développement local sans serveur mail, cette fonction ne fonctionnera pas.
            // Pour un environnement de production, assurez-vous que votre serveur est configuré pour envoyer des e-mails.
            if (mail($to, $subject, $message_body, $headers)) {
                error_log("E-mail de confirmation de candidature envoyé à " . $to . " pour le stage " . $id_offre);
            } else {
                error_log("Échec de l'envoi de l'e-mail de confirmation à " . $to . " pour le stage " . $id_offre);
            }
        } else {
            error_log("Impossible d'envoyer l'e-mail de confirmation: informations manquantes.");
        }
        // --- FIN Envoi de l'e-mail de confirmation ---

        echo json_encode(['success' => true, 'message' => 'Votre candidature a été envoyée avec succès et un e-mail de confirmation vous a été envoyé !']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Échec de l\'envoi de la candidature.']);
    }
} catch (PDOException $e) {
    // Enregistrer l'erreur pour le débogage (ne pas afficher directement à l'utilisateur pour des raisons de sécurité)
    error_log("Erreur PDO lors de l'envoi de candidature: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi de votre candidature. Veuillez réessayer plus tard.']);
}
?>
