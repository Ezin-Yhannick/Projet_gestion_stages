<?php 
function sendEmailNotification($to_email, $to_name, $subject, $message_body) {
    $entete = "MIME-Version: 1.0" . "\r\n";
    $entete .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $entete  .= "From: kpatagnon@gmail.com" . "\r\n"; // REMPLACEZ CETTE ADRESSE PAR VOTRE ADRESSE D'EXPÉDITION RÉELLE
    $entete  .= "Reply-To: kpatagnon@gmail.com" . "\r\n";

    if (mail($to_email, $subject, $message_body, $entete)) {
        error_log("Email sent successfully to: " . $to_email);
        return true;
    } else {
        error_log("Failed to send email to: " . $to_email);
        return false;
    }
}

?>