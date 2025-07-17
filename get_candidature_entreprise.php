<?php
// Démarre la session PHP.
session_start();

// Inclure le gestionnaire de session et la connexion à la base de données.
require 'session_manager.php'; 
require 'db.php'; 

// Définir les en-têtes pour permettre les requêtes AJAX et spécifier le type de contenu JSON.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS (pré-vol CORS).
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier si l'utilisateur est connecté et s'il a le rôle 'company'.
if (!is_logged_in() || get_user_role() !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé. Veuillez vous connecter en tant qu\'entreprise.']);
    exit();
}

// Récupérer l'ID de l'entreprise depuis la session.
$company_id = get_user_id();

if (empty($company_id) || !is_numeric($company_id)) {
    http_response_code(400); // Bad Request
    $response['message'] = "ID étudiant invalide ou manquant.";
    echo json_encode($response);
    exit();
}

// Initialiser le tableau de données.
$applications = [];
$response = ['success' => false, 'message' => ''];

try {
    // Préparer la requête SQL pour récupérer les candidatures.
    // Nous joignons les tables tb_candidature, tb_stage et tb_etudiant pour obtenir toutes les informations nécessaires.
    // Assurez-vous que les noms de tables et de colonnes correspondent à votre base de données.
    $stmt = $pdo->prepare("
        SELECT 
            tc.id_candidature AS application_id,
            tc.date_candidature AS applied_at,
            tc.statut AS application_status,
            ps.sujet,
            te.nom AS student_nom,
            te.prenom AS student_prenom,
            te.email AS student_email
        FROM 
            tb_candidature tc
        JOIN 
            proposition_stage ps ON tc.id_stage = ps.id_stage
        JOIN 
            tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        WHERE 
            ps.id_entreprise = :company_id
        ORDER BY 
            tc.date_candidature DESC
    ");
    
    // Lier l'ID de l'entreprise.
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Récupérer toutes les candidatures.
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $applications;

} catch (PDOException $e) {
    // Enregistrer l'erreur pour le débogage (ne pas afficher directement à l'utilisateur).
    error_log("Erreur PDO dans get_company_applications.php: " . $e->getMessage());
    $response['message'] = 'Erreur serveur lors de la récupération des candidatures.';
}

// Renvoyer la réponse JSON.
echo json_encode($response);
exit();
?>
