<?php
// Définit l'en-tête pour permettre les requêtes Cross-Origin (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // GET est suffisant ici
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure le fichier de connexion à la base de données PDO.
// Ce fichier DOIT initialiser la variable $pdo (instance de PDO).
require 'db.php'; 

// Initialiser le tableau de réponse.
$response = ['success' => false, 'data' => [], 'message' => ''];

// --- Vérification critique de la connexion PDO ---
// Assurez-vous que $pdo est une instance valide de PDO. Si la connexion a échoué dans db.php, $pdo ne sera pas défini.
if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500); // Erreur interne du serveur
    $response['message'] = "Erreur interne du serveur: La connexion à la base de données n'a pas pu être établie. Vérifiez db.php et vos logs serveur.";
    echo json_encode($response);
    exit(); // Arrête le script si la connexion est KO.
}

try {
    // Construire la requête SQL de base pour récupérer les propositions.
    // Utiliser le nom de table correct: 'internship_proposals'.
    // Utiliser les noms de colonnes corrects: 'id', 'sujet', 'duree', 'niveau', 'lieu', 'remuneration', 'description', 'company_id', 'created_at'.
    $sql = "SELECT p.*,e.nom_entreprise as nomentreprise FROM proposition_stage p JOIN tb_entreprise e ON p.id_entreprise=e.id_entreprise";
    $params = []; // Tableau pour stocker les paramètres de la requête préparée
    $where_clauses = []; // Tableau pour stocker les clauses WHERE

    // Vérifier si un 'companyId' est fourni dans l'URL.
    // Ce paramètre est utilisé par 'entreprise_dashboard.php' pour filtrer les offres d'une entreprise spécifique.
    if (isset($_GET['companyId']) && !empty($_GET['companyId'])) {
        // Filtrer et valider l'ID de l'entreprise.
        $company_id_filter = filter_input(INPUT_GET, 'companyId', FILTER_VALIDATE_INT);
        
        if ($company_id_filter !== false) {
            // Ajouter la clause WHERE en utilisant un placeholder (?) pour éviter les injections SQL.
            // La colonne dans la DB est 'company_id'.
            $where_clauses[] = "p.id_entreprise = ?"; 
            $params[] = $company_id_filter; // Ajouter l'ID au tableau des paramètres
        } else {
            // Si l'ID d'entreprise est invalide, retourner une erreur et arrêter l'exécution.
            http_response_code(400); // Bad Request
            $response['message'] = "ID d'entreprise fourni est invalide.";
            echo json_encode($response);
            exit(); 
        }
    }
    
    // Concaténer les clauses WHERE si elles existent
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Ajout d'un tri par date de création (du plus récent au plus ancien)
    // Utiliser 'created_at' pour la colonne de date de création.
    $sql .= " ORDER BY p.proposee_le DESC";

    // Préparer la requête SQL.
    $stmt = $pdo->prepare($sql);
    
    // Exécuter la requête avec les paramètres (si des filtres sont appliqués).
    $stmt->execute($params);
    
    // Récupérer toutes les propositions sous forme de tableau associatif.
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater la date 'created_at' au format ISO 8601 pour JavaScript.
   
    $response['success'] = true;
    $response['data'] = $proposals;
    $response['message'] = "Propositions de stage récupérées avec succès.";

} catch (PDOException $e) {
    // En cas d'erreur liée à la base de données (PDO), capturer l'exception et retourner un message d'erreur.
    http_response_code(500); // Erreur interne du serveur
    $response['message'] = "Erreur lors de la récupération des propositions (PDOException): " . $e->getMessage();
    error_log("Erreur PDO get_proposals: " . $e->getMessage()); // Enregistrer l'erreur complète pour le débogage
} catch (Exception $e) {
    // Capter d'autres types d'exceptions inattendues.
    http_response_code(500);
    $response['message'] = "Erreur inattendue lors de la récupération des propositions: " . $e->getMessage();
    error_log("Erreur générale get_proposals: " . $e->getMessage()); // Enregistrer l'erreur
}

// Envoyer la réponse JSON au client.
echo json_encode($response);
exit(); // Terminer le script PHP pour s'assurer qu'aucun autre contenu n'est envoyé.
?>
