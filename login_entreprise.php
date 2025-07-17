<?php

session_start();
require 'session_manager.php'; 
require 'db.php'; 

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email_saisi = filter_input(INPUT_POST, 'email_company', FILTER_SANITIZE_EMAIL); 
    $mot_de_passe_saisi = $_POST['password_company']; 

    // --- Validation simple des données ---
    if (empty($email_saisi) || empty($mot_de_passe_saisi)) {
        $response['message'] = "Veuillez remplir tous les champs.";
    } else { // Poursuivre uniquement si les champs ne sont pas vides
        try {
            // PRUDENCE : Le nom de la table et les noms des colonnes sont tirés de votre code fourni.
            // Assurez-vous que votre base de données contient bien une table 'tb_etudiant'
            // avec les colonnes 'id_etudiant', 'email', 'mot_de_passe' (haché), et 'nom_utilisateur'.
            $stmt = $pdo->prepare("SELECT * FROM tb_entreprise WHERE email_entreprise = ?");
            $stmt->execute([$email_saisi]);
            $entreprise = $stmt->fetch(PDO::FETCH_ASSOC); // Récupérer le résultat sous forme de tableau associatif.

            if ($entreprise) {  
                // Si un étudiant est trouvé avec cet e-mail, vérifier le mot de passe haché.
                // PRUDENCE : 'mot_de_passe' est la colonne dans votre DB qui contient le HASH.
                if (password_verify($mot_de_passe_saisi, $entreprise['mot_de_passe'])) {
                    // Mot de passe correct : la connexion est réussie !
                    // Stocker les informations de l'utilisateur dans la session PHP.
                    // PRUDENCE : Utiliser 'id_etudiant' et 'nom_utilisateur' de votre DB.
                    $_SESSION['user_id'] = $entreprise['id_entreprise'];          // ID unique de l'étudiant
                    $_SESSION['company_email'] = $entreprise['email_entreprise'];            // E-mail de l'étudiant
                    $_SESSION['username'] = $entreprise['nom_entreprise'];    // Nom d'utilisateur de l'étudiant
                    $_SESSION['user_role'] = 'company';                      

                    $response['success'] = true;
                    $response['message'] = "Connexion réussie ! Bon retour! " . htmlspecialchars($entreprise['nom_entreprise']) . ".";
                    // Redirection vers le tableau de bord étudiant (fichier PHP, car il vérifie la session).
                    $response['redirect_url'] = 'tableau_de_bord_entreprise.php'; 
                } else {
                    // Mot de passe incorrect.
                    $response['message'] = "Adresse e-mail ou mot de passe incorrect.";
                }
            } else {
                // E-mail non trouvé dans la base de données.
                $response['message'] = "Adresse e-mail ou mot de passe incorrect.";
            }

        } catch (Exception $e) {
            // Capturer et gérer toute erreur liée à la base de données (PDOException).
            $response['message'] = "Erreur de connexion à la base de données: " . $e->getMessage();
            // Enregistrer l'erreur dans les logs du serveur pour le débogage.
            error_log("Erreur PDO lors de la connexion étudiant: " . $e->getMessage());
        }
    }
    // Envoyer la réponse JSON au client (navigateur).
    echo json_encode($response);
    exit(); // Terminer l'exécution du script PHP après l'envoi de la réponse JSON.
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Page de connexion/inscription </title>
    <link rel="stylesheet" href="style.css">
</head>
    <style>
        /* Styles pour la modale - Assurez-vous que ces styles sont dans global_styles.css ou ici */
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
<body class="login-page-body">
<header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Plateforme de Gestion des Stages</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
            </nav>
        </div>
    </header>
    <main class="contenu login">
        <h2 class="login-form-title">Connexion Entreprise</h2>
        <!-- Le formulaire envoie les données à ce même script PHP. -->
        <!-- L'ID du formulaire est 'loginForm' pour correspondre au JavaScript. -->
        <section class="form-section card">
        <form id="loginForm" action="" method="POST" class="stage-form"> 
            <div class="mb-4">
                        <input 
                        type = 'email'
                        id ='email_company'
                        name = 'email_company'
                        placeholder="Adresse mail"
                        class = 'form-input'
                        >
                    </div>
                    <div class="mb-6">
                        <input 
                        type = 'password'
                        id ='password_company'
                        name = 'password_company'
                        placeholder="Mot de passe"
                        class = 'form-input'
                        >
                    </div>
            <div class="form-actions">
                        <button type="submit" class="btn btn-purple">
                          Connexion
                        </button>
                        <p class="mt-6" >
                        <a href="#" class="login-mdp"> Mot de passe oublié ?  </a>
                        </p>
                        <p class="mt-4" >
                        Vous êtes une nouvelle entreprise sur la plateforme ? Inscrivez vous !
                        <a href="register_entreprises.php" class="signun-mdp"> Inscription </a>
                        </p>
            </div>
         
        </form>
    </section>
    </main>

    <!-- Structure de la modale pour afficher les messages de succès/erreur. -->
    <div id="loginModalOverlay" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalHeader" class="modal-header"></h3>
            <p id="modalMessage" class="modal-message"></p>
            <button id="modalCloseButton" class="modal-button">Fermer</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Récupération des éléments du DOM.
            // ATTENTION: L'ID du formulaire doit correspondre à celui dans le HTML (loginForm).
            const loginForm = document.getElementById('loginForm');
            const modalOverlay = document.getElementById('loginModalOverlay');
            const modalHeader = document.getElementById('modalHeader');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseButton = document.getElementById('modalCloseButton');

            let timeoutId; // Variable pour stocker l'ID du temporisateur de fermeture automatique.

            // Fonction pour afficher la modale avec un message.
            function showModal(header, message, type = 'info', redirectUrl = null) {
                modalHeader.textContent = header;
                modalMessage.innerHTML = message;
                
                // Réinitialiser les classes de couleur et définir la couleur en fonction du type de message.
                modalCloseButton.classList.remove('modal-button-error'); 
                if (type === 'success') {
                    modalCloseButton.style.backgroundColor = '#4CAF50'; // Vert pour le succès.
                    modalCloseButton.textContent = "Continuer"; // Texte du bouton pour succès.
                } else if (type === 'error') {
                    modalCloseButton.style.backgroundColor = '#f44336'; // Rouge pour l'erreur.
                    modalCloseButton.textContent = "Fermer"; // Texte du bouton pour erreur.
                } else {
                    modalCloseButton.style.backgroundColor = '#2196F3'; // Bleu par défaut (info).
                    modalCloseButton.textContent = "Fermer";
                }

                modalOverlay.classList.add('visible'); // Rendre la modale visible.

                // Effacer tout temporisateur existant pour éviter les conflits si une nouvelle modale apparaît.
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }

                // Définir un temporisateur pour masquer la modale et rediriger après 5 secondes.
                timeoutId = setTimeout(() => {
                    modalOverlay.classList.remove('visible');
                    // Rediriger uniquement en cas de succès après le timeout.
                    if (redirectUrl && type === 'success') {
                        window.location.href = redirectUrl;
                    }
                }, 5000); // 5000 millisecondes = 5 secondes.

                // Gérer le clic manuel sur le bouton de fermeture de la modale.
                modalCloseButton.onclick = () => {
                    clearTimeout(timeoutId); // Effacer le temporisateur automatique si l'utilisateur clique.
                    modalOverlay.classList.remove('visible');
                    // Rediriger également en cas de succès si l'utilisateur clique sur le bouton.
                    if (redirectUrl) { // Redirige quel que soit le type si un URL est fourni.
                        window.location.href = redirectUrl;
                    }
                };
            }

            // Gestionnaire de soumission du formulaire de connexion.
            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault(); // Empêche la soumission par défaut du formulaire (qui rechargerait la page).

                const formData = new FormData(loginForm); // Crée un objet FormData à partir du formulaire.
                
                try {
                    // Envoyer les données du formulaire au script PHP via Fetch API.
                    // loginForm.action sera vide ou '#' ici, ce qui signifie que la requête sera envoyée à l'URL actuelle.
                    const response = await fetch(loginForm.action, {
                        method: 'POST',
                        body: formData // Les données sont envoyées dans le corps de la requête.
                    });

                    const result = await response.json(); // Attendre la réponse JSON du script PHP.

                    if (result.success) {
                        // Si la connexion PHP indique un succès.
                        showModal("Connexion réussie", result.message, 'success', result.redirect_url);
                    } else {
                        // Si la connexion PHP indique un échec.
                        showModal("Échec de la connexion", result.message, 'error');
                    }
                } catch (error) {
                    // Gérer les erreurs réseau (ex: le serveur PHP n'est pas accessible).
                    console.error('Erreur de soumission du formulaire ou problème réseau:', error);
                    showModal('Erreur réseau', 'Une erreur réseau est survenue. Veuillez vérifier votre connexion ou réessayer plus tard.', 'error');
                }
            });

            // Gérer les messages de statut passés via l'URL (par exemple, après une inscription réussie ou un accès non autorisé).
            const urlParams = new URLSearchParams(window.location.search);
            const statusMessage = urlParams.get('status_message');
            const statusType = urlParams.get('status_type'); 

            if (statusMessage) {
                // Afficher la modale pour ces messages.
                showModal("Notification", statusMessage, statusType || 'info');
                // Nettoyer l'URL après l'affichage pour éviter que le message ne réapparaisse au rechargement de la page.
                history.replaceState(null, '', window.location.pathname);
            }
        });
    </script>
    
    <footer>
        <p>&copy; 2025 EPAC . Tous droits réservés.</p>
    </footer>
</body>
</html>