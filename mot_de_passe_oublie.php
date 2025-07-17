<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type:json; charset=UTF-8"); // S'assurer que la réponse est toujours JSON
require 'db.php'; 

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // Termine le script après avoir géré les requêtes OPTIONS
}

// Initialiser le tableau de réponse JSON.
$response = ['success' => false, 'message' => ''];


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

// Traitement de la requête POST lorsque le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage de l'adresse e-mail pour éviter les injections et valider le format.
    $email_saisi = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Vérification si l'e-mail est vide ou invalide après la sanitisation.
    if (empty($email_saisi) || !filter_var($email_saisi, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Veuillez entrer une adresse e-mail valide.";
        // Si vous étiez dans un fichier API pur, vous pourriez ajouter: http_response_code(400);
        echo json_encode($response);
        exit();
    }

    try {
        // 1. Vérifier si l'e-mail existe dans la table 'tb_etudiant'
        // Utilisation de requêtes préparées pour prévenir les injections SQL.
        $stmt = $pdo->prepare("SELECT id_etudiant, prenom, nom FROM tb_etudiant WHERE email = ?");
        $stmt->execute([$email_saisi]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // L'étudiant existe, procéder à la génération et l'envoi du jeton

            // 2. Générer un jeton unique et sa date d'expiration
            $token = bin2hex(random_bytes(32)); 
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour')); 

            // 3. Stocker le jeton et son expiration dans la base de données pour l'étudiant trouvé
            $stmt_update = $pdo->prepare("UPDATE tb_etudiant SET jeton = ?, duree_jeton = ? WHERE id_etudiant = ?");
            $stmt_update->execute([$token, $expiry, $student['id_etudiant']]);

            // 4. Construire le lien de réinitialisation qui sera envoyé par e-mail
            // IMPORTANT : Pour un environnement de production, remplacez 'http://localhost/projet_gestion_stages/projet_gestion_stages/'
            // par l'URL de base réelle de votre application (ex: 'https://monapplication.com/').
            $base_url = "http://localhost/projet_gestion_stages/projet_gestion_stages/";
            $reset_link = $base_url . "change_mdp_etudiant.php?jeton=" . $token . "&email=" . urlencode($email_saisi);
            
            // 5. Préparer et envoyer l'e-mail à l'utilisateur
            $student_full_name = htmlspecialchars($student['prenom'] . ' ' . $student['nom']);
            $email_subject = "Réinitialisation de votre mot de passe - Plateforme de Stages";
            $email_body = "
                <html>
                <head>
                    <title>Réinitialisation de mot de passe</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
                        .button { display: inline-block; padding: 10px 20px; margin: 20px 0; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; }
                        .footer { font-size: 0.9em; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <p>Bonjour " . $student_full_name . ",</p>
                        <p>Vous avez demandé à réinitialiser votre mot de passe sur la Plateforme de Stages.</p>
                        <p>Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                        <p><a href='" . $reset_link . "' class='button'>Réinitialiser mon mot de passe</a></p>
                        <p>Ce lien expirera dans 1 heure.</p>
                        <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet e-mail.</p>
                        <p>Cordialement,</p>
                        <p>L'équipe de la Plateforme de Stages</p>
                        <div class='footer'>
                            <p>&copy; 2025 EPAC. Tous droits réservés.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Tente d'envoyer l'e-mail et met à jour la réponse en fonction du succès
            if (sendEmailNotification($email_saisi, $student_full_name, $email_subject, $email_body)) {
                $response['success'] = true;
                $response['message'] = "Un lien de réinitialisation de mot de passe a été envoyé à votre adresse e-mail. Veuillez vérifier votre boîte de réception (et vos spams).";
            } else {
                $response['message'] = "Impossible d'envoyer l'e-mail de réinitialisation. Veuillez réessayer plus tard.";
            }

        } else { 
            // Si aucun étudiant n'est trouvé avec l'e-mail fourni
            // Message générique pour des raisons de sécurité.
            $response['message'] = "Si un compte est associé à cette adresse e-mail, un lien de réinitialisation vous a été envoyé.";
        }
    } catch (PDOException $e) {
        $response['message'] = "Erreur de base de données: " . $e->getMessage();
        error_log("Erreur PDO forgot_password_student: " . $e->getMessage());
        // Si vous étiez dans un fichier API pur, vous pourriez ajouter: http_response_code(500);
    }
    echo json_encode($response);
    exit(); 
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Étudiant</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Styles de base pour la modale (peut être surchargé par style.css) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Assurez-vous qu'il est au-dessus de tout le reste */
            visibility: hidden; /* Caché par défaut */
            opacity: 0;
            transition: visibility 0s, opacity 0.3s ease-in-out;
        }

        .modal-overlay.visible {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px); /* Petite animation au chargement */
            transition: transform 0.3s ease-in-out;
        }

        .modal-overlay.visible .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-message {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 25px;
        }

        .modal-button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            color: white;
            background-color: #2196F3; /* Couleur par défaut */
            transition: background-color 0.3s ease;
        }

        .modal-button:hover {
            opacity: 0.9;
        }

        /* Styles pour les messages de succès/erreur dans la modale */
        .modal-button-error {
            background-color: #f44336 !important; /* Rouge pour erreur */
        }
    </style>
</head>
<body class="login-page-body">
<header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Plateforme de Gestion des Stages</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
            </nav>
        </div>
    </header>

    <main class="main-content login-content">
        <h2 class="login-form-title">Mot de passe oublié ?</h2>
        <section class="login-form-card">
            <p class="text-center text-gray-600 mb-6">
                Entrez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.
            </p>
            <form id="forgotPasswordForm" action="" method="POST" class="stage-form">
                <div class="login-form-group">
                    <label for="email" class="login-form-label">Adresse E-mail:</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="******@gmail.com" required>
                </div>
                <div class="login-form-actions mt-6">
                    <button type="submit" class="btn btn-primary form-button-full-width">
                        Envoyer le lien de réinitialisation
                    </button>
                    <p class="login-signup-text">
                        <a href="login_etudiant.php" class="link-text">Retour à la connexion</a>
                    </p>
                </div>
            </form>
        </section>
    </main>

    <div id="forgotPasswordModalOverlay" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalHeader" class="modal-header"></h3>
            <p id="modalMessage" class="modal-message"></p>
            <button id="modalCloseButton" class="modal-button">Fermer</button>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 EPAC. Tous droits réservés.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Récupération des éléments du DOM pour le formulaire et la modale
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            const modalOverlay = document.getElementById('forgotPasswordModalOverlay');
            const modalHeader = document.getElementById('modalHeader');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseButton = document.getElementById('modalCloseButton');

            let timeoutId; // Variable pour stocker l'ID du timeout pour la fermeture automatique de la modale

            /**
             * Fonction pour afficher la modale avec un message personnalisé et un style de bouton.
             * @param {string} header - Le titre de la modale.
             * @param {string} message - Le message à afficher dans la modale (peut contenir du HTML).
             * @param {string} [type='info'] - Le type de message ('success', 'error', 'info') pour styliser le bouton.
             * @param {string|null} [redirectUrl=null] - URL de redirection après fermeture de la modale ou timeout.
             */
            function showModal(header, message, type = 'info', redirectUrl = null) {
                // Met à jour le contenu de la modale
                modalHeader.textContent = header;
                modalMessage.innerHTML = message;
                
                // Réinitialise et applique le style du bouton en fonction du type de message
                modalCloseButton.classList.remove('modal-button-error'); 
                if (type === 'success') {
                    modalCloseButton.style.backgroundColor = '#4CAF50'; // Vert pour succès
                    modalCloseButton.textContent = "OK";
                } else if (type === 'error') {
                    modalCloseButton.style.backgroundColor = '#f44336'; // Rouge pour erreur
                    modalCloseButton.textContent = "Fermer";
                } else {
                    modalCloseButton.style.backgroundColor = '#2196F3'; // Bleu par défaut (info)
                    modalCloseButton.textContent = "Fermer";
                }

                modalOverlay.classList.add('visible'); // Rend la modale visible

                // Efface tout timeout précédent pour éviter des fermetures/redirections inattendues
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }

                // Définit un nouveau timeout pour masquer la modale automatiquement après 5 secondes
                timeoutId = setTimeout(() => {
                    modalOverlay.classList.remove('visible');
                    if (redirectUrl) {
                        window.location.href = redirectUrl; // Redirige si une URL est spécifiée
                    }
                }, 5000); // 5000 millisecondes = 5 secondes

                // Gère le clic sur le bouton de fermeture de la modale
                modalCloseButton.onclick = () => {
                    clearTimeout(timeoutId); // Efface le timeout pour éviter la redirection automatique
                    modalOverlay.classList.remove('visible'); // Masque la modale
                    if (redirectUrl) {
                        window.location.href = redirectUrl; // Redirige si une URL est spécifiée
                    }
                };
            }

            // Écouteur d'événement pour la soumission du formulaire
            forgotPasswordForm.addEventListener('submit', async (event) => {
                event.preventDefault(); // Empêche le rechargement par défaut de la page
                const formData = new FormData(forgotPasswordForm); // Récupère les données du formulaire
                
                try {
                    // Envoie les données du formulaire au script PHP actuel via Fetch API
                    // L'action du formulaire est vide, donc la requête sera envoyée au même fichier.
                    const response = await fetch(forgotPasswordForm.action, {
                        method: 'POST',
                        body: formData
                    });

                    // Récupère le type de contenu de la réponse du serveur
                    const contentType = response.headers.get("content-type");

                    // Vérifie si la réponse est de type JSON.
                    // Note: Étant donné que le PHP utilise "Content-Type: json;",
                    // nous utilisons indexOf("json") pour la détection. Idéalement, ce serait "application/json".
                    if (contentType && contentType.indexOf("json") !== -1) {
                        const result = await response.json(); // Parse la réponse JSON
                        if (result.success) {
                            showModal("Succès", result.message, 'success'); // Affiche la modale de succès
                            forgotPasswordForm.reset(); // Réinitialise le formulaire après succès
                        } else {
                            showModal("Erreur", result.message, 'error'); // Affiche la modale d'erreur
                        }
                    } else {
                        // Gère les réponses non-JSON (ex: erreurs PHP non capturées ou sortie inattendue)
                        // Cela se produit souvent si des "headers already sent" se produisent côté PHP.
                        const errorText = await response.text();
                        console.error('Réponse non JSON du serveur:', errorText);
                        showModal(
                            'Erreur Serveur', 
                            'Une erreur inattendue est survenue. Le serveur a renvoyé une réponse non-JSON. ' +
                            'Ceci est souvent dû à des erreurs PHP affichées avant la réponse JSON. ' +
                            'Vérifiez les logs PHP de votre serveur ou la console du navigateur pour le message brut ci-dessous.<br><br>' +
                            'Message brut du serveur: <pre style="white-space: pre-wrap; word-break: break-all; max-height: 150px; overflow-y: auto; background-color: #eee; padding: 10px; border-radius: 5px;">' + 
                            (errorText || 'Aucun message.') + 
                            '</pre>', 
                            'error'
                        );
                    }
                } catch (error) {
                    // Gère les erreurs réseau (ex: serveur injoignable, pas de connexion internet)
                    console.error('Erreur de soumission du formulaire ou problème réseau:', error);
                    showModal('Erreur réseau', 'Une erreur réseau est survenue. Veuillez réessayer.', 'error');
                }
            });
        });
    </script>
</body>
</html>
