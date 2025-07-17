<?php
// Démarre la session PHP.
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure l'autoloader de Composer. C'est essentiel pour charger Dompdf.
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require 'session_manager.php'; 
require 'db.php'; 

$response = ['success' => false, 'message' => ''];

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    $response['message'] = "Erreur interne du serveur: La connexion à la base de données n'a pas pu être établie.";
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = "Méthode non autorisée. Seules les requêtes POST sont acceptées.";
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Données JSON invalides reçues. Erreur: ' . json_last_error_msg();
    error_log("generate_convention: JSON decode error: " . $response['message'] . " Raw input: " . file_get_contents('php://input'));
    echo json_encode($response);
    exit();
}

$application_id = $input['application_id'] ?? null;
$student_id = $input['student_id'] ?? null;
$internship_id = $input['internship_id'] ?? null; // C'est l'ID du stage dans proposition_stage
$company_id = $input['company_id'] ?? null;

// Valider les données
if (empty($application_id) || !is_numeric($application_id) ||
    empty($student_id) || !is_numeric($student_id) ||
    empty($internship_id) || !is_numeric($internship_id) ||
    empty($company_id) || !is_numeric($company_id)) {
    http_response_code(400);
    $response['message'] = "Données d'entrée incomplètes ou invalides pour la génération de convention.";
    echo json_encode($response);
    exit();
}

try {
    // Vérifier si une convention existe déjà pour cette application
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM conventions_stage WHERE id_candidature = :application_id");
    $stmt_check->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_check->execute();
    if ($stmt_check->fetchColumn() > 0) {
        $response['success'] = true;
        $response['message'] = "Une convention existe déjà pour cette candidature. Aucune nouvelle génération nécessaire.";
        echo json_encode($response);
        exit();
    }

    // Récupérer toutes les données nécessaires pour la convention
    // CORRECTION ICI : Revenir à l'utilisation de ps.id_stage pour la jointure
    // Assurez-vous que les colonnes 'telephone' et 'adresse' existent bien dans vos tables.
    $stmt_data = $pdo->prepare("
        SELECT 
            tc.date_candidature AS applied_at,
            ps.sujet, ps.description, ps.duree, ps.niveau_requis, ps.lieu, ps.renumeration,
            te.prenom AS student_prenom, te.nom AS student_nom, te.email AS student_email, 
            e.nom_entreprise AS company_name, e.email_entreprise AS company_email
        FROM 
            tb_candidature tc
        JOIN 
            proposition_stage ps ON tc.id_stage = ps.id_stage 
        JOIN 
            tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        JOIN 
            tb_entreprise e ON ps.id_entreprise = e.id_entreprise
        WHERE 
            tc.id_candidature = :application_id
    ");
    $stmt_data->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt_data->execute();
    $convention_data = $stmt_data->fetch(PDO::FETCH_ASSOC);

    if (!$convention_data) {
        http_response_code(404);
        $response['message'] = "Détails de la candidature introuvables pour générer la convention.";
        echo json_encode($response);
        exit();
    }

    // --- Construction du contenu HTML de la convention ---
    $html_content = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Convention de Stage - ' . htmlspecialchars($convention_data['sujet']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; font-size: 12px; }
            h1, h2 { color: #2c3e50; text-align: center; }
            h1 { font-size: 24px; margin-bottom: 20px; }
            h2 { font-size: 18px; margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            p { line-height: 1.6; margin-bottom: 10px; }
            .section { margin-bottom: 20px; padding: 15px; border: 1px solid #f0f0f0; border-radius: 8px; background-color: #f9f9f9; }
            .label { font-weight: bold; color: #333; }
            /* Styles pour le bloc de signature */
            .signature-block { 
                margin-top: 50px; 
                text-align: center; /* Centre le contenu global du bloc */
            }
            .signature-row {
                display: flex;
                justify-content: space-around;
                margin-bottom: 30px; /* Espace entre les deux lignes de signatures */
            }
            .signature-item { 
                width: 45%; /* Ajusté pour laisser de l\'espace entre les deux items sur la même ligne */
                border-top: 1px dashed #ccc; 
                padding-top: 10px; 
                text-align: center; /* S\'assure que le texte est centré dans chaque bloc de signature */
            }
            .signature-single-item-wrapper {
                width: 45%; /* Largeur du conteneur pour le dernier élément */
                margin: 0 auto; /* Centre le conteneur du dernier élément */
            }
            .footer { text-align: center; margin-top: 50px; font-size: 10px; color: #777; }
        </style>
    </head>
    <body>
        <h1>CONVENTION DE STAGE</h1>
        <p style="text-align: center;">Numéro de Candidature: <strong>' . htmlspecialchars($application_id) . '</strong></p>
        <p style="text-align: center;">Date de Génération: <strong>' . date('d/m/Y H:i:s') . '</strong></p>

        <h2>Informations sur l\'Étudiant</h2>
        <div class="section">
            <p><span class="label">Nom:</span> ' . htmlspecialchars($convention_data['student_nom']) . '</p>
            <p><span class="label">Prénom:</span> ' . htmlspecialchars($convention_data['student_prenom']) . '</p>
            <p><span class="label">Email:</span> ' . htmlspecialchars($convention_data['student_email']) . '</p>
        </div>

        <h2>Informations sur l\'Entreprise d\'Accueil</h2>
        <div class="section">
            <p><span class="label">Nom de l\'Entreprise:</span> ' . htmlspecialchars($convention_data['company_name']) . '</p>
            <p><span class="label">Email:</span> ' . htmlspecialchars($convention_data['company_email']) . '</p>
        </div>

        <h2>Détails du Stage</h2>
        <div class="section">
            <p><span class="label">Sujet du Stage:</span> ' . htmlspecialchars($convention_data['sujet']) . '</p>
            <p><span class="label">Description:</span> ' . nl2br(htmlspecialchars($convention_data['description'])) . '</p>
            <p><span class="label">Durée:</span> ' . htmlspecialchars($convention_data['duree']) . ' mois</p>
            <p><span class="label">Niveau Requis:</span> ' . htmlspecialchars($convention_data['niveau_requis']) . '</p>
            <p><span class="label">Lieu du Stage:</span> ' . htmlspecialchars($convention_data['lieu']) . '</p>
            <p><span class="label">Rémunération:</span> ' . htmlspecialchars($convention_data['renumeration']) . '</p>
            <p><span class="label">Date de Candidature Initiale:</span> ' . date('d/m/Y', strtotime($convention_data['applied_at'])) . '</p>
        </div>

        <p style="margin-top: 30px;">La présente convention régit les modalités du stage mentionné ci-dessus, conformément aux lois et règlements en vigueur. Elle prend effet à la date de signature par toutes les parties.</p>

        <div class="signature-block">
            <div class="signature-row">
                <div class="signature-item">
                    <p>L\'Étudiant(e)</p>
                    <p>(Signature et Date)</p>
                </div>
                <div class="signature-item">
                    <p>L\'Entreprise</p>
                    <p>(Signature et Cachet, Date)</p>
                </div>
            </div>
            <div class="signature-single-item-wrapper">
                <div class="signature-item" style="width: 100%;">
                    <p>L\'Enseignant Référent</p>
                    <p>(Signature et Date)</p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Ce document est généré automatiquement par le système de gestion des stages de l\'Université.</p>
            <p>Veuillez imprimer, signer et retourner les exemplaires à toutes les parties.</p>
        </div>
    </body>
    </html>';

    // --- Configuration et génération du PDF avec Dompdf ---
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'portrait'); 
    $dompdf->render(); 

    // Définir le chemin de sauvegarde du PDF
    $file_name = "convention_" . $application_id . "_" . date('YmdHis') . ".pdf";
    $upload_dir = __DIR__ . '/conventions/'; 

    // Créer le dossier si n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true); 
    }

    $full_pdf_path = $upload_dir . $file_name;
    $web_pdf_path = 'conventions/' . $file_name; 

    // Sauvegarder le PDF sur le serveur
    file_put_contents($full_pdf_path, $dompdf->output());

    // Insérer la nouvelle convention dans la base de données
    $sql = "INSERT INTO conventions_stage (id_candidature, id_etudiant, id_stage, id_entreprise, chemin_pdf, statut_convention) 
            VALUES (:application_id, :student_id, :internship_id, :company_id, :chemin_pdf, 'générée')";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':internship_id', $internship_id, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':chemin_pdf', $web_pdf_path, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Convention de stage générée et enregistrée avec succès.";
        $response['convention_id'] = $pdo->lastInsertId();
        $response['pdf_path'] = $web_pdf_path; 
    } else {
        $response['message'] = "Échec de l'enregistrement de la convention de stage dans la base de données.";
        if (file_exists($full_pdf_path)) {
            unlink($full_pdf_path);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Erreur de base de données lors de la génération de la convention: " . $e->getMessage();
    error_log("Erreur PDO generate_convention: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = "Erreur inattendue lors de la génération de la convention: " . $e->getMessage();
    error_log("Erreur générale generate_convention: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>
