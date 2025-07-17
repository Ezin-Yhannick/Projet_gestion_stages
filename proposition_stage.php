<?php
// Démarre la session PHP
session_start();

// Inclure le gestionnaire de session
require 'session_manager.php';
require_once 'db.php'; 

// Rediriger si l'utilisateur n'est pas connecté ou si ce n'est pas une entreprise
if (!is_logged_in() || get_user_role() !== 'company') {
    // Rediriger vers la page de connexion entreprise avec un message d'erreur
    header("Location: login_entreprise.php?status_type=error&status_message=" . urlencode("Accès non autorisé. Veuillez vous connecter en tant qu'entreprise pour ajouter une proposition."));
    exit();
}
// Si l'utilisateur est bien une entreprise, le script continue normalement.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Proposition de Stage</title>
    <!-- Lien vers le fichier CSS global -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles pour la modale */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fond semi-transparent */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Assurez-vous qu'elle est au-dessus du reste */
            visibility: hidden; /* Cachée par défaut */
            opacity: 0; /* Cachée par défaut */
            transition: visibility 0s, opacity 0.3s ease-in-out;
        }

        .modal-overlay.visible {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
            position: relative;
        }

        .modal-header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .modal-message {
            font-size: 16px;
            margin-bottom: 25px;
        }

        .modal-button {
            background-color: #4CAF50; /* Vert par défaut pour succès */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .modal-button.error {
            background-color: #f44336; /* Rouge pour erreur */
        }

        .modal-button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <header class="header header-purple">
        <div class="container header-content">
            <h1 class="header-title">Espace Entreprise</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="tableau_de_bord_entreprise.php" class="nav-link">Tableau de Bord</a>
                <a href="profil_entreprise.php" class="nav-link">Mon Profil</a>
                <a href="logout.php" class="nav-link">Déconnexion</a>
            </nav>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-content">
        <h2 class="page-title">Ajouter une Nouvelle Proposition de Stage</h2>

        <section class="form-section card">
            <form id="stageForm" action="creer_proposition_stage.php" method="POST" class="stage-form">
                <div class="form-group">
                    <label for="sujet" class="form-label">Sujet du Stage:</label>
                    <input type="text" id="sujet" name="sujet" class="form-input purple-focus" placeholder="Ex: Développement d'une application mobile" required>
                </div>
                <div class="form-group">
                    <label for="duree" class="form-label">Durée (en mois):</label>
                    <input type="number" id="duree" name="duree" class="form-input purple-focus" placeholder="Ex: 6" min="1" required>
                </div>
                <div class="form-group">
                    <label for="niveau" class="form-label">Niveau d'études réquis:</label>
                    <input type="text" id="niveau" name="niveau" class="form-input purple-focus" placeholder="Ex: Master en Génie informatique">
                </div>
                <div class="form-group">
                    <label for="lieu" class="form-label">Lieu du stage:</label>
                    <input type="text" id="lieu" name="lieu" class="form-input purple-focus" placeholder="Ex: Cotonou">
                </div>
                <div class="form-group last-group">
                    <label for="remuneration" class="form-label">Rémunération Éventuelle (par mois):</label>
                    <input type="text" id="remuneration" name="remuneration" class="form-input purple-focus" placeholder="Ex: 800€ ou Non rémunéré">
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">Description du stage:</label>
                    <textarea id="description_stage" name="description_stage" class="form-input purple-focus" placeholder="Décrivez ici le but du stage et les tâches principales." rows="5"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-purple">
                        Ajouter la Proposition
                    </button>
                    <a href="entreprise_dashboard.php" class="link-text purple">
                        Annuler
                    </a>
                </div>
            </form>
        </section>
    </main>

    <!-- Pied de page -->
    <footer>
        <p>&copy; 2025 Université [Nom de l'université]. Tous droits réservés.</p>
    </footer>

    <!-- Structure de la modale -->
    <div id="proposalModalOverlay" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalHeader" class="modal-header"></h3>
            <p id="modalMessage" class="modal-message"></p>
            <button id="modalCloseButton" class="modal-button">Fermer</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stageForm = document.getElementById('stageForm');
            const proposalModalOverlay = document.getElementById('proposalModalOverlay');
            const modalHeader = document.getElementById('modalHeader');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseButton = document.getElementById('modalCloseButton');

            let timeoutId; 

            function showModal(header, message, type = 'info', redirectUrl = null) {
                modalHeader.textContent = header;
                modalMessage.innerHTML = message;
                
                modalCloseButton.classList.remove('modal-button-error'); 
                if (type === 'success') {
                    modalCloseButton.style.backgroundColor = '#4CAF50'; 
                    modalCloseButton.textContent = "Continuer";
                } else if (type === 'error') {
                    modalCloseButton.style.backgroundColor = '#f44336'; 
                    modalCloseButton.textContent = "Fermer";
                } else {
                    modalCloseButton.style.backgroundColor = '#2196F3'; 
                    modalCloseButton.textContent = "Fermer";
                }

                proposalModalOverlay.classList.add('visible'); 

                if (timeoutId) {
                    clearTimeout(timeoutId);
                }

                timeoutId = setTimeout(() => {
                    proposalModalOverlay.classList.remove('visible');
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                }, 5000); 

                modalCloseButton.onclick = () => {
                    clearTimeout(timeoutId); 
                    proposalModalOverlay.classList.remove('visible');
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    }
                };
            }

            stageForm.addEventListener('submit', async (event) => {
                event.preventDefault(); 

                const formData = new FormData(stageForm);

                try {
                    const response = await fetch(stageForm.action, {
                        method: 'POST',
                        body: formData, 
                    });

                    const result = await response.json(); 

                    if (result.success) {
                        showModal("Succès", result.message, 'success', result.redirect_url);
                        stageForm.reset(); 
                    } else {
                        showModal("Erreur", result.message, 'error');
                        console.error('Erreur du backend PHP:', result.message);
                    }
                } catch (error) {
                    console.error('Erreur de soumission du formulaire ou problème réseau:', error);
                    showModal('Erreur réseau', 'Une erreur réseau est survenue. Veuillez vérifier la console du navigateur et l\'URL de votre backend PHP.', 'error');
                }
            });
        });
    </script>
</body>
</html>
