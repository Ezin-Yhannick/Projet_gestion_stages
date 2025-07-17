<?php
// archive_internship.php

header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
// Assurez-vous que le chemin d'accès est correct par rapport à archive_internship.php
require_once 'db.php'; // Ou '../db.php' si db.php est dans le dossier parent, etc.

$response = ['success' => false, 'message' => ''];

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données JSON du corps de la requête
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Vérifier si l'ID de candidature est présent
    if (isset($data['id_candidature']) && is_numeric($data['id_candidature'])) {
        $id_candidature = $data['id_candidature'];

        try {
            // Utiliser l'objet PDO fourni par db.php
            // Assurez-vous que votre db.php retourne ou rend disponible une variable $pdo
            // Par exemple, si db.php contient :
            // try { $pdo = new PDO(...); } catch (PDOException $e) { die(...); }
            // alors $pdo sera disponible ici.

            // Préparer la requête SQL de mise à jour
            // Assurez-vous que le nom de votre table et de la colonne de statut sont corrects
            $stmt = $pdo->prepare("UPDATE tb_candidature SET statut = 'Archivé' WHERE id_candidature = :id_candidature");
            $stmt->bindParam(':id_candidature', $id_candidature, PDO::PARAM_INT);

            // Exécuter la requête
            $stmt->execute();

            // Vérifier si des lignes ont été affectées (si le stage a été trouvé et mis à jour)
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Stage archivé avec succès.';
            } else {
                $response['message'] = 'Aucun stage trouvé avec cet ID ou le stage est déjà archivé.';
            }

        } catch (PDOException $e) {
            // Gérer les erreurs de base de données
            $response['message'] = 'Erreur de base de données: ' . $e->getMessage();
            error_log('Erreur PDO dans archive_internship.php: ' . $e->getMessage());
        } catch (Exception $e) {
            // Gérer d'autres exceptions
            $response['message'] = 'Erreur inattendue: ' . $e->getMessage();
            error_log('Erreur inattendue dans archive_internship.php: ' . $e->getMessage());
        }
    } else {
        $response['message'] = 'ID de candidature manquant ou invalide.';
    }
} else {
    $response['message'] = 'Méthode de requête non autorisée. Seules les requêtes POST sont acceptées.';
}

echo json_encode($response);
?>
