<?php
// Démarre la session PHP
session_start();

// Inclure le gestionnaire de session et le fichier de connexion à la base de données PDO.
require 'session_manager.php'; 
require 'db.php'; 

// Permettre les requêtes Cross-Origin (CORS) - essentiel pour les requêtes AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialiser le tableau de réponse pour le retour JSON.
$response = ['success' => false, 'message' => ''];

// --- Vérification de l'authentification et du rôle de l'entreprise ---
if (!is_logged_in() || get_user_role() !== 'company') {
    $response['message'] = "Accès non autorisé. Vous devez être connecté en tant qu'entreprise pour ajouter une proposition.";
    echo json_encode($response);
    exit();
}

// Récupérer l'ID de l'entreprise depuis la session
$user_id = get_user_id();

// Vérifier si la requête est de type POST (soumission du formulaire).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et filtrer les données du formulaire.
    $sujet = filter_input(INPUT_POST, 'sujet', FILTER_SANITIZE_STRING);
    $duree = filter_input(INPUT_POST, 'duree', FILTER_VALIDATE_INT); 
    $niveau = filter_input(INPUT_POST, 'niveau', FILTER_SANITIZE_STRING);
    $lieu = filter_input(INPUT_POST, 'lieu', FILTER_SANITIZE_STRING);
    $descript = filter_input(INPUT_POST, 'description_stage', FILTER_SANITIZE_STRING);
    
    // --- Traitement et conversion de la rémunération ---
    $remuneration_input = $_POST['remuneration'] ?? ''; // Récupérer l'entrée brute du champ 'remuneration'
    $remuneration = NULL; // Valeur par défaut NULL pour la base de données

    // Tenter d'extraire un nombre décimal (float) de la chaîne, potentiellement avec des symboles de devise.
    // Le regex cherche un nombre (entier ou décimal) au début de la chaîne, suivi optionnellement par un '€'.
    if (preg_match('/^\s*(\d+(\.\d+)?)\s*€?/', $remuneration_input, $matches)) {
        // Si un nombre est trouvé, le convertir en float.
        $remuneration = (float)$matches[1];
    } else if (strtolower(trim($remuneration_input)) === 'non rémunéré' || empty(trim($remuneration_input))) {
        // Si la chaîne est "non rémunéré" (insensible à la casse) ou est vide après nettoyage,
        // stocker NULL dans la base de données.
        $remuneration = NULL;
    } else {
        // Pour tout autre cas (ex: "À discuter", "selon profil"), stocker la chaîne telle quelle après assainissement.
        $remuneration = filter_var($remuneration_input, FILTER_SANITIZE_STRING);
        // S'assurer qu'elle est NULL si elle devient vide après assainissement
        if (empty($remuneration)) {
            $remuneration = NULL;
        }
    }
    // --- Fin du traitement de la rémunération ---

    // --- Validation des données ---
    if (empty($sujet) || empty($duree) || $duree === false) { 
        $response['message'] = "Le sujet et la durée sont obligatoires.";
    }

    // Si aucune erreur de validation initiale, procéder à l'insertion.
    if (empty($response['message'])) {
        try {
            // Insérer la nouvelle proposition de stage dans la table 'internship_proposals'.
            $stmt = $pdo->prepare("INSERT INTO proposition_stage (id_entreprise, sujet, duree, niveau_requis, lieu, renumeration,description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user_id, // Utilisation de l'ID de l'entreprise connecté
                $sujet,
                $duree,
                $niveau,
                $lieu,
                $remuneration, // La valeur traitée (float, string ou NULL)
                $descript
            ]);

            $response['success'] = true;
            $response['message'] = "Proposition de stage ajoutée avec succès !";
            $response['redirect_url'] = 'tableau_de_bord_entreprise.php'; // Redirection vers le tableau de bord de l'entreprise

        } catch (PDOException $e) {
            $response['message'] = "Erreur lors de l'ajout de la proposition: " . $e->getMessage();
            error_log("Erreur PDO create_proposal: " . $e->getMessage()); 
        }
    }
    // Envoyer la réponse JSON au client
    echo json_encode($response);
    exit(); 
}
?>
