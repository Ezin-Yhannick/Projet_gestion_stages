<?php
// get_internships.php - Récupère les stages pour une entreprise donnée

header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
// Assurez-vous que le chemin d'accès est correct par rapport à ce script.
// Par exemple, si db.php est dans le même dossier, 'db.php' est correct.
// Si db.php est dans un dossier parent, utilisez '../db.php'.
require 'db.php';

// L'objet $pdo est maintenant disponible grâce à l'inclusion de db.php

// Récupérer l'ID de l'entreprise (pour l'exemple, via GET, en production cela viendrait d'une session authentifiée)
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;

if (is_null($company_id)) {
    echo json_encode(['success' => false, 'message' => 'ID de l\'entreprise manquant.']);
    exit();
}

try {
    // Requête pour récupérer les stages "acceptés" ou "en cours" pour cette entreprise
    // Nous joignons tb_offre pour obtenir le titre de l'offre et tb_etudiant pour le nom de l'étudiant
    $stmt = $pdo->prepare("
        SELECT
            tc.id_candidature,
            tc.statut,
            tc.date_candidature,
            tc.date_debut_stage,
            tc.date_fin_stage,
            tc.rapport_stage,
            ps.sujet AS titre_offre,
            te.nom AS nom_etudiant,
            te.prenom AS prenom_etudiant
        FROM
            tb_candidature tc
        JOIN
            proposition_stage ps ON tc.id_stage = ps.id_stage
        JOIN
            tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        WHERE
            ps.id_entreprise = :company_id AND (tc.statut = 'acceptée' OR tc.statut = 'signée')
            ORDER BY
            tc.date_candidature DESC, tc.date_debut_stage DESC
       
    ");
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $internships = $stmt->fetchAll();

    echo json_encode(['success' => true, 'internships' => $internships]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des stages: ' . $e->getMessage()]);
}

?>
