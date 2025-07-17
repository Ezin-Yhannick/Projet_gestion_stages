<?php
// Démarre la session PHP.
session_start();
// Inclure le gestionnaire de session si nécessaire.
require 'session_manager.php'; 

// Récupérer le jeton de l'URL
$token = $_GET['jeton'] ?? ''
;

// Si le jeton est vide, rediriger ou afficher une erreur
if (empty($token)) {
    // Rediriger vers la page de mot de passe oublié avec un message d'erreur
    header("Location: mot_de_passe_oublie.php?status_type=error&status_message=" . urlencode("Jeton de réinitialisation manquant ou invalide."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le Mot de Passe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Réinitialiser le Mot de Passe</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="login_etudiant.php" class="nav-link">Connexion</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="form-container">
            <h2>Définir un Nouveau Mot de Passe</h2>
            <form id="resetPasswordForm">
                <!-- Champ caché pour le jeton de réinitialisation -->
                <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="new_password">Nouveau Mot de Passe:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirmer le Nouveau Mot de Passe:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8">
                </div>
                <button type="submit" class="submit-button">Réinitialiser le Mot de Passe</button>
            </form>
        </div>
    </main>

    <!-- Modale générique pour les messages (erreurs, succès, info) -->
    <div id="messageModal" class="modal-message">
        <div class="modal-message-content">
            <span class="close-button">&times;</span>
            <h3 id="messageModalTitle" class="message-title"></h3>
            <p id="messageModalText" class="message-text"></p>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Université [Nom de l'université]. Tous droits réservés.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            const messageModal = document.getElementById('messageModal');
            const messageModalContent = messageModal.querySelector('.modal-message-content'); 
            const messageModalTitle = document.getElementById('messageModalTitle');
            const messageModalText = document.getElementById('messageModalText');
            const messageModalCloseButton = messageModal.querySelector('.close-button');

            function showMessageBox(message, type = 'info') {
                messageModalContent.classList.remove('info', 'success', 'error');
                messageModalContent.classList.add(type);
                messageModalTitle.textContent = type.toUpperCase();
                messageModalText.textContent = message;
                messageModal.style.display = 'flex';

                if (type !== 'error') { 
                    setTimeout(() => {
                        messageModal.style.display = 'none';
                    }, 5000); 
                }
            }

            messageModalCloseButton.addEventListener('click', () => {
                messageModal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === messageModal) {
                    messageModal.style.display = 'none';
                }
            });

            resetPasswordForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const token = document.getElementById('token').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmNewPassword = document.getElementById('confirm_new_password').value;

                // Validation côté client
                if (newPassword !== confirmNewPassword) {
                    showMessageBox('Les nouveaux mots de passe ne correspondent pas.', 'error');
                    return;
                }
                if (newPassword.length < 8) {
                    showMessageBox('Le nouveau mot de passe doit contenir au moins 8 caractères.', 'error');
                    return;
                }

                const payload = {
                    token: token,
                    new_password: newPassword
                };

                try {
                    const response = await fetch('process_reset_password.php', { // Nom du script PHP pour le traitement
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessageBox(result.message + ' Vous pouvez maintenant vous connecter.', 'success');
                        resetPasswordForm.reset(); // Réinitialiser le formulaire
                        // Optionnel: Rediriger vers la page de connexion après un court délai
                        setTimeout(() => {
                            window.location.href = 'login_etudiant.php'; 
                        }, 3000);
                    } else {
                        showMessageBox(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Erreur lors de la réinitialisation du mot de passe:', error);
                    showMessageBox('Une erreur est survenue lors de la réinitialisation. Veuillez réessayer.', 'error');
                }
            });
        });
    </script>
</body>
</html>
