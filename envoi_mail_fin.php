<?php
// check_and_send_report_reminders.php

// Inclure votre fichier de connexion à la base de données
require_once 'db.php'; // Assurez-vous que ce fichier contient la connexion $pdo

// Configuration de l'e-mail (à adapter avec vos propres paramètres SMTP ou fonction mail)
// Si vous utilisez une bibliothèque comme PHPMailer, incluez-la ici.
// Pour cet exemple, nous utiliserons la fonction mail() de PHP.
function sendReportReminderEmail($studentEmail, $studentName, $internshipSubject) {
    $to = $studentEmail;
    $subject = "Rappel : Dépôt de votre rapport de stage pour " . $internshipSubject;
    $message = "Bonjour " . $studentName . ",\n\n";
    $message .= "Nous vous rappelons que la date de fin de votre stage '" . $internshipSubject . "' est passée. ";
    $message .= "Veuillez déposer votre rapport de stage dès que possible sur la plateforme.\n\n";
    $message .= "Merci de votre coopération.\n\n";
    $message .= "Cordialement,\nVotre équipe de gestion des stages";
    $headers = "From: no-reply@votreplateforme.com\r\n";
    $headers .= "Reply-To: no-reply@votreplateforme.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Pour un environnement de production, utilisez une solution SMTP robuste (PHPMailer, SwiftMailer)
    // et configurez-la correctement. La fonction mail() de PHP est souvent limitée.
    if (mail($to, $subject, $message, $headers)) {
        error_log("Email de rappel envoyé à " . $studentEmail . " pour le stage " . $internshipSubject);
        return true;
    } else {
        error_log("Échec de l'envoi de l'email de rappel à " . $studentEmail . " pour le stage " . $internshipSubject);
        return false;
    }
}

try {
    // Récupérer les stages dont la date de fin est passée, qui sont signés/complétés
    // et pour lesquels le rapport n'a pas encore été soumis ou un rappel n'a pas été envoyé récemment.
    // Pour simplifier, nous allons cibler 'non soumis'.
    $stmt = $pdo->prepare("
        SELECT 
            tc.id_candidature, 
            tc.sujet, 
            te.email AS student_email, 
            te.prenom AS student_prenom, 
            te.nom AS student_nom
        FROM tb_candidature tc
        JOIN tb_etudiant te ON tc.id_etudiant = te.id_etudiant
        WHERE tc.date_fin_stage < CURDATE()
        AND (tc.statut = 'signée' OR tc.statut = 'terminée')
        AND tc.rapport_statut = 'non soumis'
    ");
    $stmt->execute();
    $internships_to_remind = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($internships_to_remind) > 0) {
        error_log("Found " . count($internships_to_remind) . " internships requiring report reminders.");
        foreach ($internships_to_remind as $internship) {
            $studentEmail = $internship['student_email'];
            $studentName = $internship['student_prenom'] . ' ' . $internship['student_nom'];
            $internshipSubject = $internship['sujet'];
            $applicationId = $internship['id_candidature'];

            if (sendReportReminderEmail($studentEmail, $studentName, $internshipSubject)) {
                // Optionnel: Mettre à jour un champ pour indiquer que le rappel a été envoyé
                // Par exemple, ajouter une colonne `last_report_reminder_sent` (DATETIME)
                // ou un statut plus granulaire comme 'rappel_envoye'.
                // Pour cet exemple, nous ne changeons pas le statut du rapport ici,
                // car il ne doit changer qu'à la soumission réelle.
                error_log("Reminder email successfully processed for application ID: " . $applicationId);
            }
        }
    } else {
        error_log("No internships found requiring report reminders today.");
    }

} catch (PDOException $e) {
    error_log("Database error in check_and_send_report_reminders.php: " . $e->getMessage());
} catch (Exception $e) {
    error_log("General error in check_and_send_report_reminders.php: " . $e->getMessage());
}

?>
