<?php
// update_internship_dates.php - Permet à l'entreprise de définir les dates de début et de fin de stage
// et envoie un e-mail à l'étudiant.

header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
// Assurez-vous que le chemin d'accès est correct.
require 'db.php';

$response = ['success' => false, 'message' => ''];

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données JSON du corps de la requête
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Vérifier si toutes les données nécessaires sont présentes et valides
    if (
        isset($data['id_candidature']) && is_numeric($data['id_candidature']) &&
        isset($data['date_debut_stage']) && !empty($data['date_debut_stage']) &&
        isset($data['date_fin_stage']) && !empty($data['date_fin_stage'])
    ) {
        $id_candidature = (int)$data['id_candidature'];
        $date_debut_stage = $data['date_debut_stage'];
        $date_fin_stage = $data['date_fin_stage'];

        // Validation du format de date
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_debut_stage) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_fin_stage)) {
            $response['message'] = 'Format de date invalide. Utilisez le format YYYY-MM-DD.';
            echo json_encode($response);
            exit();
        }

        // Vérifier si la date de fin est après la date de début
        if (strtotime($date_fin_stage) < strtotime($date_debut_stage)) {
            $response['message'] = 'La date de fin de stage ne peut pas être antérieure à la date de début.';
            echo json_encode($response);
            exit();
        }

        try {
            // L'objet $pdo est déjà disponible grâce à l'inclusion de db.php

            // 1. Récupérer les informations nécessaires (email de l'étudiant, titre de l'offre)
            $stmt_info = $pdo->prepare("
                SELECT
                    te.email AS student_email,
                    te.nom AS student_nom,
                    te.prenom AS student_prenom,
                    ps.sujet AS offre_titre
                FROM
                    tb_candidature tc
                JOIN
                    tb_etudiant te ON tc.id_etudiant = te.id_etudiant
                JOIN
                    proposition_stage ps ON tc.id_stage = ps.id_stage
                WHERE
                    tc.id_candidature = :id_candidature
            ");
            $stmt_info->bindParam(':id_candidature', $id_candidature, PDO::PARAM_INT);
            $stmt_info->execute();
            $internship_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if (!$internship_info) {
                $response['message'] = 'Candidature non trouvée ou informations manquantes pour l\'envoi de l\'e-mail.';
                echo json_encode($response);
                exit();
            }

            $student_email = $internship_info['student_email'];
            $student_nom = $internship_info['student_nom'];
            $student_prenom = $internship_info['student_prenom'];
            $offre_titre = $internship_info['offre_titre'];

            // 2. Préparer la requête SQL de mise à jour des dates et du statut
            $stmt_update = $pdo->prepare("
                UPDATE tb_candidature
                SET
                    date_debut_stage = :date_debut,
                    date_fin_stage = :date_fin,
                    statut = CASE
                                WHEN statut = 'signée' THEN 'en cours'
                                ELSE statut
                             END
                WHERE id_candidature = :id_candidature
            ");
            $stmt_update->bindParam(':date_debut', $date_debut_stage);
            $stmt_update->bindParam(':date_fin', $date_fin_stage);
            $stmt_update->bindParam(':id_candidature', $id_candidature, PDO::PARAM_INT);

            // Exécuter la requête de mise à jour
            $stmt_update->execute();

            // Vérifier si des lignes ont été affectées
            if ($stmt_update->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Dates de stage mises à jour avec succès.';

                // 3. Envoyer l'e-mail à l'étudiant
                $to = $student_email;
                $subject = "Confirmation des dates de votre stage: " . $offre_titre;
                $message = "Bonjour " . htmlspecialchars($student_prenom) . " " . htmlspecialchars($student_nom) . ",\n\n";
                $message .= "Nous sommes ravis de vous informer que les dates de votre stage pour l'offre '" . htmlspecialchars($offre_titre) . "' ont été définies.\n\n";
                $message .= "Date de début: " . htmlspecialchars($date_debut_stage) . "\n";
                $message .= "Date de fin: " . htmlspecialchars($date_fin_stage) . "\n\n";
                $message .= "Veuillez vous assurer de préparer votre rapport de stage et de le télécharger sur la plateforme une fois le stage terminé.\n\n";
                $message .= "Cordialement,\nL'équipe de [Nom de l'entreprise ou de la plateforme]";

                // En-têtes pour l'e-mail
                $headers = 'From: no-reply@votreplateforme.com' . "\r\n" .
                           'Reply-To: no-reply@votreplateforme.com' . "\r\n" .
                           'X-Mailer: PHP/' . phpversion();

                if (mail($to, $subject, $message, $headers)) {
                    $response['message'] .= ' Un e-mail de confirmation a été envoyé à l\'étudiant.';
                } else {
                    $response['message'] .= ' Erreur lors de l\'envoi de l\'e-mail de confirmation à l\'étudiant.';
                    error_log('Échec de l\'envoi de l\'e-mail à ' . $to . ' pour la candidature ' . $id_candidature);
                }

            } else {
                $response['message'] = 'Aucun stage trouvé avec cet ID ou aucune modification nécessaire.';
            }

        } catch (\PDOException $e) {
            // Gérer les erreurs de base de données spécifiques à cette opération
            $response['message'] = 'Erreur de base de données lors de la mise à jour des dates: ' . $e->getMessage();
            error_log('Erreur PDO dans update_internship_dates.php: ' . $e->getMessage());
        } catch (Exception $e) {
            // Gérer d'autres exceptions inattendues
            $response['message'] = 'Erreur inattendue: ' . $e->getMessage();
            error_log('Erreur inattendue dans update_internship_dates.php: ' . $e->getMessage());
        }
    } else {
        $response['message'] = 'Données manquantes ou invalides (id_candidature, date_debut_stage, date_fin_stage).';
    }
} else {
    $response['message'] = 'Méthode de requête non autorisée. Seules les requêtes POST sont acceptées.';
}

echo json_encode($response);
?>
