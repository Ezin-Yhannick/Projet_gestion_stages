<?php
// Démarre la session PHP.
session_start();

// Définit l'en-tête pour permettre les requêtes Cross-Origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure le gestionnaire de session et le fichier de connexion à la base de données.
require 'session_manager.php'; 
require 'db.php'; 

// Initialiser le tableau de réponse JSON.
$response = ['success' => false, 'message' => ''];

// Vérification de la connexion PDO.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    $response['message'] = "Erreur interne du serveur: La connexion à la base de données n'a pas pu être établie.";
    echo json_encode($response);
    exit();
}

// Vérifier si l'utilisateur est connecté et a le rôle d'entreprise.
if (!is_logged_in() || get_user_role() !== 'company') {
    http_response_code(403); // Forbidden
    $response['message'] = "Accès non autorisé. Veuillez vous connecter en tant qu'entreprise.";
    echo json_encode($response);
    exit();
}

// Vérifier si la requête est bien de type POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = "Méthode de requête non autorisée. Seules les requêtes POST sont acceptées.";
    echo json_encode($response);
    exit();
}

// Lisez le corps brut de la requête POST (JSON).
$input_json = file_get_contents('php://input');
$input = json_decode($input_json, true);

// Vérifier si le décodage JSON a réussi.
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Données JSON invalides reçues. Erreur: ' . json_last_error_msg();
    error_log("update_application_status: JSON decode error: " . $response['message'] . " Raw input: " . $input_json);
    echo json_encode($response);
    exit();
}

// Récupérer les données de la requête.
$application_id = $input['application_id'] ?? null;
$new_status = $input['new_status'] ?? null;
$company_id_from_client = $input['company_id'] ?? null; // ID de l'entreprise envoyé par le client

// Récupérer l'ID de l'entreprise depuis la session (source de vérité).
$company_id_from_session = get_user_id();

// Valider les données.
if (empty($application_id) || !is_numeric($application_id)) {
    http_response_code(400);
    $response['message'] = "ID de candidature invalide ou manquant.";
    echo json_encode($response);
    exit();
}

// Liste des statuts autorisés.
$allowed_statuses = ['acceptée', 'refusée', 'en attente', 'complétée', 'signée'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    $response['message'] = "Statut invalide. Statuts autorisés: " . implode(', ', $allowed_statuses);
    echo json_encode($response);
    exit();
}

// Vérification de sécurité: Assurez-vous que l'entreprise qui tente de modifier la candidature
// est bien l'entreprise propriétaire de l'offre de stage associée à cette candidature.
try {
    $stmt_check_ownership = $pdo->prepare("
        SELECT ps.id_entreprise
        FROM tb_candidature tc
        JOIN proposition_stage ps ON tc.id_stage = ps.id_stage
        WHERE tc.id_candidature = :application_id
    ");
    $stmt_check_ownership->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_check_ownership->execute();
    $owner_company_id = $stmt_check_ownership->fetchColumn();

    if (!$owner_company_id || (int)$owner_company_id !== (int)$company_id_from_session) {
        http_response_code(403); // Forbidden
        $response['message'] = "Accès non autorisé à cette candidature. Vous n'êtes pas l'entreprise propriétaire de l'offre.";
        echo json_encode($response);
        exit();
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de la vérification de l'autorisation.";
    error_log("Erreur PDO update_application_status (ownership check): " . $e->getMessage());
    echo json_encode($response);
    exit();
}

// --- NOUVEAU : Récupérer les informations de la candidature, de l'étudiant et de l'entreprise avant la mise à jour ---
// Cela est nécessaire pour l'envoi de l'e-mail.
$student_email = '';
$student_name = '';
$internship_subject = '';
$company_name = '';

try {
    $stmt_get_details = $pdo->prepare("
        SELECT 
ps.id_entreprise,
tc.id_etudiant, -- Ajouté pour la génération de convention
tc.id_stage, -- Ajouté pour la génération de convention
tc.statut AS current_status,
te.email AS student_email,
te.prenom AS student_prenom,
te.nom AS student_nom,
ps.sujet AS internship_subject,
e.nom_entreprise AS company_name
FROM tb_candidature tc
JOIN proposition_stage ps ON tc.id_stage = ps.id_stage
JOIN tb_etudiant te ON tc.id_etudiant = te.id_etudiant
JOIN tb_entreprise e ON ps.id_entreprise = e.id_entreprise
WHERE tc.id_candidature = :application_id
    ");
    $stmt_get_details->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_get_details->execute();
    $details = $stmt_get_details->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        $student_email = $details['student_email'];
        $student_name = $details['student_prenom'] . ' ' . $details['student_nom'];
        $internship_subject = $details['internship_subject'];
        $company_name = $details['company_name'];
        $current_status = $details['current_status'];

        // Empêcher la mise à jour si le statut est déjà accepté ou refusé
        if ($current_status === 'acceptée' || $current_status === 'refusée') {
            $response['message'] = "La candidature a déjà été traitée (statut actuel: " . $current_status . "). Impossible de la modifier.";
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = "Détails de la candidature introuvables.";
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de la récupération des détails de la candidature: " . $e->getMessage();
    error_log("Erreur PDO update_application_status (get details): " . $e->getMessage());
    echo json_encode($response);
    exit();
}


try {
    // Préparer la requête SQL pour mettre à jour le statut de la candidature.
    $sql = "UPDATE tb_candidature SET statut = :new_status WHERE id_candidature = :application_id";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = "Statut de la candidature mis à jour avec succès en '" . $new_status . "'.";
            // --- NOUVEAU : Appel à generate_convention.php si le statut est 'signée' ---
            // Note: Normalement, le statut 'signée' est mis par l'étudiant après acceptation de l'offre.
            // Si l'entreprise met directement en 'signée' (moins courant), la convention est aussi générée.
            if ($new_status === 'signée') {
                $convention_data = [
                    'application_id' => (int)$application_id,
                    'student_id' => (int)$student_id_for_convention,
                    'internship_id' => (int)$internship_id_for_convention,
                    'company_id' => (int)$company_id_from_session
                ];

                // ADAPTEZ L'URL CI-DESSOUS À VOTRE ENVIRONNEMENT LARAGON !
                // Ex: 'http://localhost/mon_projet_stage/generate_convention.php'
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode($convention_data),
                        'ignore_errors' => true
                    ]
                ]);
                $result_convention_json = @file_get_contents('http://localhost/projet_gestion_stages/projet_gestion_stages/convention.php', false, $context); 
                
                if ($result_convention_json === FALSE) {
                    error_log("Erreur lors de l'appel à generate_convention.php depuis update_application_status.php: " . error_get_last()['message']);
                    $response['message'] .= " (Erreur lors de la génération de la convention.)";
                } else {
                    $result_convention = json_decode($result_convention_json, true);
                    if ($result_convention && $result_convention['success']) {
                        $response['message'] .= " (Convention générée : " . $result_convention['pdf_path'] . ")";
                    } else {
                        $error_msg = $result_convention['message'] ?? 'Erreur inconnue';
                        error_log("Échec de generate_convention.php depuis update_application_status.php: " . $error_msg);
                        $response['message'] .= " (Échec de la génération de la convention: " . $error_msg . ")";
                    }
                }
            }
            // --- FIN NOUVEAU : Appel à generate_convention.php ---

            // --- NOUVEAU : Envoi de l'e-mail de notification ---
            $to = $student_email;
            $subject = "";
            $message_body = "";
            $headers = "From: no-reply@votreuniversite.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            if ($new_status === 'acceptée') {
                $subject = "Candidature Acceptée pour le Stage " . $internship_subject;
                $message_body = "
                    <html>
                    <head>
                        <title>Candidature Acceptée</title>
                    </head>
                    <body>
                        <p>Bonjour " . htmlspecialchars($student_name) . ",</p>
                        <p>Nous avons le plaisir de vous informer que votre candidature pour le stage <strong>'" . htmlspecialchars($internship_subject) . "'</strong> chez <strong>" . htmlspecialchars($company_name) . "</strong> a été <strong>ACCEPTÉE</strong> !</p>
                        <p>L'entreprise vous contactera prochainement pour les prochaines étapes (entretien, convention de stage, etc.).</p>
                        <p>Félicitations !</p>
                        <p>Cordialement,</p>
                        <p>L'équipe de la Plateforme de Gestion de Stages</p>
                        <p><i>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</i></p>
                    </body>
                    </html>
                ";
            } elseif ($new_status === 'refusée') {
                $subject = "Mise à jour de votre Candidature pour le Stage " . $internship_subject;
                $message_body = "
                    <html>
                    <head>
                        <title>Mise à jour de Candidature</title>
                    </head>
                    <body>
                        <p>Bonjour " . htmlspecialchars($student_name) . ",</p>
                        <p>Nous vous informons que votre candidature pour le stage <strong>'" . htmlspecialchars($internship_subject) . "'</strong> chez <strong>" . htmlspecialchars($company_name) . "</strong> a été <strong>REFUSÉE</strong>.</p>
                        <p>Nous comprenons que cette nouvelle puisse être décevante. Nous vous encourageons à continuer à explorer d'autres opportunités sur notre plateforme.</p>
                        <p>Cordialement,</p>
                        <p>L'équipe de la Plateforme de Gestion de Stages </p>
                        <p><i>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</i></p>
                    </body>
                    </html>
                ";
            }

            // Tenter d'envoyer l'e-mail
            if (!empty($to) && !empty($subject) && !empty($message_body)) {
                if (mail($to, $subject, $message_body, $headers)) {
                    error_log("E-mail de notification envoyé à " . $to . " pour la candidature " . $application_id);
                } else {
                    error_log("Échec de l'envoi de l'e-mail de notification à " . $to . " pour la candidature " . $application_id);
                    // Vous pouvez choisir d'ajouter un message à la réponse JSON si l'envoi d'e-mail est critique
                    // $response['message'] .= " (Mais l'e-mail de notification n'a pas pu être envoyé.)";
                }
            } else {
                error_log("Impossible d'envoyer l'e-mail: informations manquantes (destinataire, sujet ou corps).");
            }
            // --- FIN NOUVEAU : Envoi de l'e-mail de notification ---

        } else {
            $response['message'] = "Aucune candidature trouvée avec cet ID ou le statut est déjà le même.";
        }
    } else {
        $response['message'] = "Échec de la mise à jour du statut de la candidature.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de la mise à jour du statut: " . $e->getMessage();
    error_log("Erreur PDO update_application_status: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Erreur inattendue lors de la mise à jour du statut: " . $e->getMessage();
    error_log("Erreur générale update_application_status: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>
