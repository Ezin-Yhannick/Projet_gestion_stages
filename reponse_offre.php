<?php
// Démarre la session PHP.
session_start();

// Définit l'en-tête pour permettre les requêtes Cross-Origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// --- POUR LE DÉBOGAGE SEULEMENT ---
// Laissez ceci activé pendant le débogage, mais VÉRIFIEZ LES LOGS PHP pour les messages.
// Désactivez-le en production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN DÉBOGAGE ---

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure le gestionnaire de session et le fichier de connexion à la base de données.
// Assurez-vous qu'AUCUN espace ou caractère n'est présent avant <?php dans ces fichiers.
require 'session_manager.php'; 
require 'db.php'; 

// Initialiser le tableau de réponse JSON.
$response = ['success' => false, 'message' => ''];

// Vérification de la connexion PDO.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    $response['message'] = "Erreur interne du serveur: La connexion à la base de données n'a pas pu être établie. Vérifiez db.php et vos logs.";
    echo json_encode($response);
    exit();
}

// Vérifier si l'utilisateur est connecté et a le rôle d'étudiant.
if (!is_logged_in() || get_user_role() !== 'student') {
    http_response_code(403); // Forbidden
    $response['message'] = "Accès non autorisé. Veuillez vous connecter en tant qu'étudiant.";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = "Méthode de requête non autorisée. Seules les requêtes POST sont acceptées.";
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Données JSON invalides reçues. Erreur: ' . json_last_error_msg();
    error_log("student_respond_to_offer: JSON decode error: " . $response['message'] . " Raw input: " . file_get_contents('php://input'));
    echo json_encode($response);
    exit();
}

$application_id = $input['application_id'] ?? null;
$response_type = $input['response_type'] ?? null; // 'accept' ou 'refuse'
$student_id_from_client = $input['student_id'] ?? null; // ID étudiant envoyé par le client

$student_id_from_session = get_user_id(); // ID étudiant de la session (source de vérité)

// Valider les données
if (empty($application_id) || !is_numeric($application_id)) {
    http_response_code(400);
    $response['message'] = "ID de candidature invalide ou manquant.";
    echo json_encode($response);
    exit();
}

if (!in_array($response_type, ['accept', 'refuse'])) {
    http_response_code(400);
    $response['message'] = "Type de réponse invalide.";
    echo json_encode($response);
    exit();
}

// Vérification de sécurité: Assurez-vous que la candidature appartient bien à l'étudiant connecté
try {
    // Correction: Inclure les colonnes prenom et nom de tb_etudiant pour l'e-mail
    $stmt_check_ownership = $pdo->prepare("
       SELECT 
            tc.id_etudiant, 
            tc.statut,
            tc.id_stage,
            ps.id_entreprise,
            te.prenom AS student_prenom,
            te.nom AS student_nom
        FROM 
            tb_candidature tc
        JOIN
            tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        JOIN
            proposition_stage ps ON tc.id_stage = ps.id_stage
        WHERE 
            tc.id_candidature = :application_id
    
    ");
    $stmt_check_ownership->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_check_ownership->execute();
    $application_details = $stmt_check_ownership->fetch(PDO::FETCH_ASSOC);

    if (!$application_details || (int)$application_details['id_etudiant'] !== (int)$student_id_from_session) {
        http_response_code(403);
        $response['message'] = "Accès non autorisé à cette candidature.";
        echo json_encode($response);
        exit();
    }

    // Vérifier le statut actuel de la candidature
    // L'étudiant ne peut répondre que si le statut est 'acceptée' par l'entreprise
    if ($application_details['statut'] !== 'acceptée') { // Correction: Utiliser 'acceptée' comme chaîne de caractères
        http_response_code(400);
        $response['message'] = "Cette candidature n'est pas en attente de votre réponse (statut actuel: " . $application_details['statut'] . ").";
        echo json_encode($response);
        exit();
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de la vérification de l'autorisation: " . $e->getMessage();
    error_log("Erreur PDO student_respond_to_offer (ownership check): " . $e->getMessage());
    echo json_encode($response);
    exit();
}

// Déterminer le nouveau statut
$new_status = ($response_type === 'accept') ? 'signée' : 'refusée par étudiant'; // Correction: Utiliser 'refusée par étudiant'

// Récupérer les détails pour l'e-mail de notification à l'entreprise
$company_email = '';
$company_name = '';
$internship_subject = '';
// Utiliser les détails récupérés précédemment pour le nom de l'étudiant
$student_name = htmlspecialchars($application_details['student_prenom'] . ' ' . $application_details['student_nom']);

try {
    $stmt_get_company_details = $pdo->prepare("
        SELECT 
            e.email_entreprise AS company_email,
            e.nom_entreprise AS company_name,
            ps.sujet AS internship_subject
        FROM 
            tb_candidature tc
        JOIN 
            proposition_stage ps ON tc.id_stage = ps.id_stage
        JOIN 
            tb_entreprise e ON ps.id_entreprise = e.id_entreprise
        WHERE 
            tc.id_candidature = :application_id
    ");
    $stmt_get_company_details->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_get_company_details->execute();
    $company_details = $stmt_get_company_details->fetch(PDO::FETCH_ASSOC);

    if ($company_details) {
        $company_email = $company_details['company_email'];
        $company_name = $company_details['company_name'];
        $internship_subject = $company_details['internship_subject'];
    }
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des détails de l'entreprise pour l'e-mail: " . $e->getMessage());
    // Ne pas exit ici, l'update du statut est plus important que l'email si l'email échoue.
}


try {
    $sql = "UPDATE tb_candidature SET statut = :new_status WHERE id_candidature = :application_id AND id_etudiant = :student_id_from_session";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt->bindParam(':student_id_from_session', $student_id_from_session, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = "Votre réponse a été enregistrée avec succès. Statut mis à jour en '" . $new_status . "'.";
               // --- NOUVEAU : Appel à generate_convention.php si l'étudiant accepte ---
               if ($new_status === 'signée') {
                $convention_data = [
                    'application_id' => (int)$application_id,
                    'student_id' => (int)$student_id_from_session,
                    'internship_id' => (int)$application_details['id_stage'],
                    'company_id' => (int)$application_details['id_entreprise']
                ];

                // Utiliser cURL ou file_get_contents pour appeler le script interne
                // ADAPTEZ L'URL CI-DESSOUS À VOTRE ENVIRONNEMENT LARAGON !
                // Ex: 'http://localhost/mon_projet_stage/generate_convention.php'
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode($convention_data),
                        'ignore_errors' => true // Pour lire la réponse même en cas d'erreur HTTP
                    ]
                ]);
                $result_convention_json = @file_get_contents('http://localhost/projet_gestion_stages/projet_gestion_stages/convention.php', false, $context); 
                
                if ($result_convention_json === FALSE) {
                    error_log("Erreur lors de l'appel à generate_convention.php: " . error_get_last()['message']);
                    $response['message'] .= " (Erreur lors de la génération de la convention.)";
                } else {
                    $result_convention = json_decode($result_convention_json, true);
                    if ($result_convention && $result_convention['success']) {
                        $response['message'] .= " (Convention générée : " . $result_convention['pdf_path'] . ")";
                        
            // --- Envoi de l'e-mail de notification à l'entreprise ---
            $to_company = $company_email;
            $subject_company = "";
            $message_body_company = "";
            $headers_company = "From: no-reply@votreuniversite.com\r\n";
            $headers_company .= "MIME-Version: 1.0\r\n";
            $headers_company .= "Content-Type: text/html; charset=UTF-8\r\n";

            if ($response_type === 'accept') {
                $subject_company = "L'étudiant a accepté votre offre de stage !";
                $message_body_company = "
                    <html>
                    <head>
                        <title>Offre de Stage Acceptée</title>
                    </head>
                    <body>
                        <p>Bonjour " . htmlspecialchars($company_name) . ",</p>
                        <p>Nous vous informons que l'étudiant(e) <strong>" . $student_name . "</strong> a <strong>ACCEPTÉ</strong> votre offre de stage pour <strong>'" . htmlspecialchars($internship_subject) . "'</strong>.</p>
                        <p>Veuillez prendre contact avec l'étudiant(e) pour finaliser la convention de stage et les modalités de début.</p>
                        <p>Cordialement,</p>
                        <p>L'équipe de l'Université</p>
                        <p><i>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</i></p>
                    </body>
                    </html>
                ";
            } elseif ($response_type === 'refuse') {
                $subject_company = "L'étudiant a refusé votre offre de stage.";
                $message_body_company = "
                    <html>
                    <head>
                        <title>Offre de Stage Refusée</title>
                    </head>
                    <body>
                        <p>Bonjour " . htmlspecialchars($company_name) . ",</p>
                        <p>Nous vous informons que l'étudiant(e) <strong>" . $student_name . "</strong> a <strong>REFUSÉ</strong> votre offre de stage pour <strong>'" . htmlspecialchars($internship_subject) . "'</strong>.</p>
                        <p>Vous pouvez consulter d'autres candidatures ou modifier votre offre si nécessaire.</p>
                        <p>Cordialement,</p>
                        <p>L'équipe de l'Université</p>
                        <p><i>Ceci est un e-mail automatique, merci de ne pas y répondre directement.</i></p>
                    </body>
                    </html>
                ";
            }

            if (!empty($to_company) && !empty($subject_company) && !empty($message_body_company)) {
                if (mail($to_company, $subject_company, $message_body_company, $headers_company)) {
                    error_log("E-mail de notification à l'entreprise envoyé à " . $to_company . " pour la candidature " . $application_id);
                } else {
                    error_log("Échec de l'envoi de l'e-mail de notification à l'entreprise à " . $to_company . " pour la candidature " . $application_id . ". Vérifiez la configuration de votre serveur mail.");
                }
            } else {
                error_log("Impossible d'envoyer l'e-mail à l'entreprise: informations manquantes (destinataire, sujet ou corps).");
            }
            // --- FIN Envoi de l'e-mail de notification à l'entreprise ---

                    } else {
                        $error_msg = $result_convention['message'] ?? 'Erreur inconnue';
                        error_log("Échec de generate_convention.php: " . $error_msg);
                        $response['message'] .= " (Échec de la génération de la convention: " . $error_msg . ")";
                    }
                }
            }
            // --- FIN NOUVEAU : Appel à generate_convention.php ---

        } else {
            $response['message'] = "Aucune candidature trouvée avec cet ID ou le statut est déjà le même pour cet étudiant.";
        }
    } else {
        $response['message'] = "Échec de l'enregistrement de votre réponse.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de l'enregistrement de votre réponse: " . $e->getMessage();
    error_log("Erreur PDO student_respond_to_offer: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Erreur inattendue lors de l'enregistrement de votre réponse: " . $e->getMessage();
    error_log("Erreur générale student_respond_to_offer: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>
