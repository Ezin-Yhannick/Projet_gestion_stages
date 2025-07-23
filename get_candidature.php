<?php
// Démarre la session PHP. Essentiel pour accéder aux informations de l'utilisateur connecté.
session_start();

// Définit l'en-tête pour permettre les requêtes Cross-Origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS"); // Seule la méthode GET est autorisée pour la lecture de données
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8"); // Indique que la réponse sera du JSON

//
// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure le gestionnaire de session pour utiliser les fonctions is_logged_in, get_user_role, get_user_id.
// Assurez-vous que ce fichier existe et contient ces fonctions.
require 'session_manager.php'; 
// Inclure le fichier de connexion à la base de données PDO.
// Ce fichier DOIT initialiser la variable $pdo (instance de PDO).
require 'db.php'; 

// Initialiser le tableau de réponse JSON.
$response = ['success' => false, 'data' => [], 'message' => ''];

// --- Vérification critique de la connexion PDO ---
// Assurez-vous que $pdo est une instance valide de PDO. Si la connexion a échoué dans db.php, $pdo ne sera pas défini.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500); // Erreur interne du serveur
    $response['message'] = "Erreur interne du serveur: La connexion à la base de données n'a pas pu être établie. Vérifiez db.php et vos logs serveur.";
    echo json_encode($response);
    exit(); // Arrête le script si la connexion est KO.
}

// Vérifier si l'utilisateur est connecté et s'il a le rôle d'étudiant.
if (!is_logged_in() || get_user_role() !== 'student') {
    http_response_code(403); // Forbidden (Accès interdit)
    $response['message'] = "Accès non autorisé. Veuillez vous connecter en tant qu'étudiant.";
    echo json_encode($response);
    exit();
}

// Vérifier si la requête est bien une méthode GET.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = "Méthode non autorisée. Seules les requêtes GET sont acceptées.";
    echo json_encode($response);
    exit();
}

// Récupérer l'ID de l'étudiant depuis la session (source de vérité).
$student_id = get_user_id();

// Valider l'ID de l'étudiant.
if (empty($student_id) || !is_numeric($student_id)) {
    http_response_code(400); // Bad Request
    $response['message'] = "ID étudiant invalide ou manquant.";
    echo json_encode($response);
    exit();
}

try {
    // Préparer la requête SQL pour récupérer les candidatures de l'étudiant
    $sql = "
         SELECT 
            tc.id_candidature, 
            tc.date_candidature ,
            tc.statut ,
            ps.id_stage AS internship_id, 
            ps.sujet,
            ps.description,
            ps.duree,
            ps.niveau_requis,
            ps.lieu,
            ps.renumeration,
            e.nom_entreprise AS nomentreprise,
            cs.chemin_pdf AS convention_pdf_path,
            cs.statut_convention AS convention_status,
            tc.date_fin_stage,
            tc.date_debut_stage
        FROM 
            tb_candidature tc
        JOIN 
            proposition_stage ps ON tc.id_stage = ps.id_stage
        JOIN 
            tb_entreprise e ON ps.id_entreprise = e.id_entreprise
        LEFT JOIN 
            conventions_stage cs ON tc.id_candidature = cs.id_candidature
        WHERE 
            tc.id_etudiant = :student_id
        ORDER BY 
            tc.date_candidature DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $applications;
    $response['message'] = "Candidatures récupérées avec succès.";

} catch (PDOException $e) {
    // Gérer les erreurs de base de données.
    http_response_code(500); // Erreur interne du serveur
    $response['message'] = "Erreur de base de données lors de la récupération des candidatures: " . $e->getMessage();
    error_log("Erreur PDO get_applications: " . $e->getMessage()); // Enregistrer l'erreur complète pour le débogage
} catch (Exception $e) {
    // Gérer d'autres exceptions inattendues.
    http_response_code(500);
    $response['message'] = "Erreur inattendue lors de la récupération des candidatures: " . $e->getMessage();
    error_log("Erreur générale get_applications: " . $e->getMessage()); // Enregistrer l'erreur
}
// Envoyer la réponse JSON au client.
echo json_encode($response);
exit(); // Terminer le script PHP.
?>
