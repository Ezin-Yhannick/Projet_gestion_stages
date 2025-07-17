<?php
// Démarre la session PHP. DOIT être la toute première ligne avant tout HTML ou autre sortie.
session_start();

// Inclure le gestionnaire de session pour utiliser les fonctions is_logged_in, get_user_role, get_user_id.
require 'session_manager.php'; 
// Inclure le fichier de connexion à la base de données PDO.
require 'db.php'; 

// Rediriger si l'utilisateur n'est pas connecté ou si ce n'est pas une entreprise
if (!is_logged_in() || get_user_role() !== 'company') {
    // Rediriger vers la page de connexion entreprise avec un message d'erreur
    header("Location: login_entreprise.php?status_type=error&status_message=" . urlencode("Accès non autorisé. Veuillez vous connecter en tant qu'entreprise."));
    exit();
}

// Récupérer les informations de l'entreprise connectée depuis la session
$user_id = get_user_id(); // L'ID de l'entreprise est stocké dans 'user_id' dans la session
$user_email = $_SESSION['user_email'] ?? 'N/A';
$company_name = $_SESSION['username'] ?? 'N/A'; // 'username' contient le nom de l'entreprise pour ce rôle
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Entreprise</title>
    <!-- Lien vers le fichier CSS global -->
    <link rel="stylesheet" href="style.css">
    <style>
          /* Styles pour la modale de saisie des dates */
          .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto; /* Center vertically and horizontally */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more specific */
            max-width: 500px; /* Max width for larger screens */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }

        .modal-content h3 {
            margin-top: 0;
            color: #333;
        }

        .modal-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .modal-content input[type="date"] {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
        }

        .modal-content .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Styles pour les cartes d'offres */
        .proposal-card button, .application-item button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 15px;
            font-weight: 500;
        }

        .proposal-card button:hover:not(:disabled), .application-item button:hover:not(:disabled) {
            background-color: #0056b3;
        }

        .proposal-card button:disabled, .application-item button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Styles pour les messages d'information (utilisé maintenant dans la modale) */
        .info-message-content { /* Renommé pour être le contenu de la modale */
            background-color: #e0f7fa;
            border-left: 5px solid #00bcd4;
            padding: 15px 20px;
            margin-top: 20px;
            border-radius: 8px;
            color: #006064;
        }

        .info-message-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1em;
        }

        .info-message-content p {
            margin: 0;
        }

        .hidden {
            display: none !important;
        }

        .proposals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .card-title {
            color: #2c3e50;
            font-size: 1.5em;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .card-text {
            color: #555;
            margin-bottom: 8px;
        }

        .card-text span {
            font-weight: 600;
            color: #333;
        }

        .list-group {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        /* Styles pour les éléments de candidature */
        .list-group-item {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .list-group-item strong {
            color: #2c3e50;
        }

        .list-group-item p {
            margin: 0;
            color: #666;
        }

        .list-group-item .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }

        /* Styles pour les différents statuts de candidature */
        .status-en-attente { background-color: #ffeb3b; color: #795548; } /* Jaune */
        .status-acceptée { background-color: #8bc34a; color: #33691e; } /* Vert */
        .status-refusée { background-color: #f44336; color: white; } /* Rouge */
        .status-complétée { background-color: #9e9e9e; color: white; } /* Gris */
        .status-signée { background-color: #2196f3; color: white; } /* Bleu */

        .section-title {
            color: #2c3e50;
            font-size: 2em;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #007bff;
            border-radius: 5px;
        }

        .main-content {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #f8f9fa;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .Header {
            background-color: #007bff;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header .header-title {
            margin: 0;
            font-size: 2.2em;
        }

        .header-nav .nav-link {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-size: 1.1em;
            transition: color 0.3s ease;
        }

        .header-nav .nav-link:hover {
            color: #e0e0e0;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
            font-size: 0.9em;
        }

        .tab-menu {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .tab-button {
            background-color: transparent;
            border: none;
            padding: 10px 20px;
            font-size: 1.1em;
            cursor: pointer;
            color: #555;
            transition: color 0.3s ease, border-bottom 0.3s ease;
            position: relative;
        }

        .tab-button.active {
            color: #007bff;
            font-weight: bold;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px; /* Aligné avec la bordure du menu */
            width: 100%;
            height: 3px;
            background-color: #007bff;
            border-radius: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header .header-title {
                font-size: 1.8em;
            }
            .header-nav {
                flex-direction: column;
                gap: 10px;
                margin-top: 15px;
            }
            .header-nav .nav-link {
                margin-left: 0;
            }
            .proposals-grid {
                grid-template-columns: 1fr;
            }
            .tab-menu {
                flex-direction: column;
                gap: 10px;
            }
            .tab-button {
                width: 100%;
            }
        }

        /* Styles pour la modale de message */
        .modal-message {
            display: none; /* Caché par défaut */
            position: fixed; /* Reste en place même en scrollant */
            z-index: 1000; /* Au-dessus de tout le reste */
            left: 0;
            top: 0;
            width: 100%; /* Pleine largeur */
            height: 100%; /* Pleine hauteur */
            overflow: auto; /* Permet le défilement si le contenu est trop grand */
            background-color: rgba(0,0,0,0.6); /* Fond semi-transparent noir */
            display: flex; /* Utilisation de flexbox pour centrer */
            justify-content: center;
            align-items: center;
        }

        .modal-message-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px; /* Largeur maximale pour le contenu */
            position: relative;
            animation: fadeIn 0.3s ease-out;
            color: #333;
            text-align: center;
        }

        .modal-message-content .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-message-content .close-button:hover,
        .modal-message-content .close-button:focus {
            color: #333;
            text-decoration: none;
        }

        .modal-message-content .message-title {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .modal-message-content .message-text {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Couleurs spécifiques pour les types de messages dans la modale */
        .modal-message-content.info .message-title { color: #007bff; }
        .modal-message-content.success .message-title { color: #28a745; }
        .modal-message-content.error .message-title { color: #dc3545; }

        .modal-message-content.info { border-left-color: #007bff; }
        .modal-message-content.success { border-left-color: #28a745; }
        .modal-message-content.error { border-left-color: #dc3545; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Espace Entreprise</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="profil_entreprise.php" class="nav-link">Mon Entreprise</a>
                <a href="logout.php" class="nav-link">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <h2 class="page-title">Gestion des Stages</h2>

        <!-- Informations de l'entreprise connectée (pour le débogage/vérification) -->
        <section class="card mb-6">
            <h3 class="section-title">Bienvenue, <?php echo htmlspecialchars($company_name); ?>!</h3>
            <p><strong>Email de contact:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            <p><strong>Rôle:</strong> <?php echo htmlspecialchars(get_user_role()); ?></p>
            <p><strong>ID Entreprise:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        </section>

        <div class="tab-menu">
            <button id="proposalsTab" class="tab-button active">Mes Propositions de Stage</button>
            <button id="applicationsTab" class="tab-button">Candidatures Reçues</button> <!-- Nouveau tab -->
            <button id="activeInternshipsTab" class="tab-button">Stages Effectifs en Cours</button>
        </div>

        <section id="proposalsSection" class="content-section">
            <h3 class="section-title">Mes Propositions de Stage</h3>
            <div class="button-group">
                <a href="proposition_stage.php" class="btn btn-success">
                    + Nouvelle Proposition
                </a>
            </div>

            <div id="propositionsContainer" class="proposals-grid hidden">
                <!-- Les propositions seront chargées dynamiquement ici -->
            </div>
            <div id="noProposalsMessage" class="info-message">
                <p class="info-message-title">Chargement des propositions...</p>
                <p>Si aucune proposition n'apparaît, cliquez sur "+ Nouvelle Proposition" pour en créer une !</p>
            </div>
        </section>

        <section id="applicationsSection" class="content-section hidden">
            <h3 class="section-title">Candidatures Reçues</h3>
            <div id="applicationsContainer" class="card hidden">
                <ul class="list-group" id="applicationsList">
                    <!-- Les candidatures seront chargées dynamiquement ici -->
                </ul>
            </div>
            <div id="noApplicationsMessage" class="info-message mt-4">
                <p class="info-message-title">Chargement des candidatures...</p>
                <p>Si aucune candidature n'apparaît, il n'y en a pas de disponible pour le moment.</p>
            </div>
        </section>

        <section id="activeInternshipsSection" class="content-section hidden">
            <h3 class="section-title">Stages Effectifs en Cours</h3>
            <div id="activeInternshipsContainer" class="card hidden">
                <ul class="list-group">
                    <!-- Les stages effectifs seront chargés dynamiquement ici -->
                </ul>
            </div>
            <div id="noActiveInternshipsMessage" class="info-message mt-4">
                <p class="info-message-title">Aucun stage effectif n'est en cours pour le moment.</p>
                <p>Une fois qu'une proposition de stage est acceptée et qu'une convention est signée, le stage apparaîtra ici.</p>
            </div>
        </section>
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
            const proposalsTab = document.getElementById('proposalsTab');
            const applicationsTab = document.getElementById('applicationsTab');
            const activeInternshipsTab = document.getElementById('activeInternshipsTab');

            const proposalsSection = document.getElementById('proposalsSection');
            const applicationsSection = document.getElementById('applicationsSection');
            const activeInternshipsSection = document.getElementById('activeInternshipsSection');

            const propositionsContainer = document.getElementById('propositionsContainer');
            const noProposalsMessage = document.getElementById('noProposalsMessage');

            const applicationsContainer = document.getElementById('applicationsContainer');
            const applicationsList = document.getElementById('applicationsList');
            const noApplicationsMessage = document.getElementById('noApplicationsMessage');

            const activeInternshipsContainer = document.getElementById('activeInternshipsContainer');
            const noActiveInternshipsMessage = document.getElementById('noActiveInternshipsMessage');

            // Références aux éléments de la modale de message
            const messageModal = document.getElementById('messageModal');
            const messageModalContent = messageModal.querySelector('.modal-message-content'); 
            const messageModalTitle = document.getElementById('messageModalTitle');
            const messageModalText = document.getElementById('messageModalText');
            const messageModalCloseButton = messageModal.querySelector('.close-button');

            let timeoutId; // Variable pour stocker l'ID du temporisateur de fermeture automatique.

            // Fonction pour afficher les messages dans la modale
            function showMessageBox(message, type = 'info', redirectUrl = null) {
                console.log(`showMessageBox appelée avec: message="${message}", type="${type}", redirectUrl="${redirectUrl}"`);
                // Effacer tout temporisateur existant pour éviter les conflits
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    console.log('Timeout précédent effacé.');
                }

                // Réinitialiser les classes de type sur le contenu de la modale
                messageModalContent.classList.remove('info', 'success', 'error');
                
                // Ajouter la classe de type appropriée
                messageModalContent.classList.add(type);

                messageModalTitle.textContent = type.toUpperCase();
                messageModalText.innerHTML = message;
                messageModal.style.display = 'flex'; // Assurez-vous que c'est 'flex' pour être visible

                // Pour les messages non-erreur, définir un timeout pour la fermeture automatique
                if (type !== 'error') { 
                    timeoutId = setTimeout(() => {
                        console.log('Tentative de fermeture automatique de la modale après 5 secondes.');
                        messageModal.style.display = 'none';
                        if (redirectUrl) {
                            console.log(`Redirection vers: ${redirectUrl}`);
                            window.location.href = redirectUrl;
                        }
                    }, 5000); 
                } else {
                    console.log('Type de modale est "error", ne se fermera pas automatiquement.');
                }
            }

            // Écouteurs pour fermer la modale de message
            // Attaché une seule fois au chargement du DOM pour plus de robustesse
            messageModalCloseButton.addEventListener('click', () => {
                console.log('Bouton de fermeture de modale cliqué.');
                clearTimeout(timeoutId); // Effacer le temporisateur automatique si l'utilisateur clique.
                messageModal.style.display = 'none';
                console.log('Affichage de la modale défini sur "none" après le clic sur le bouton de fermeture.');
            });

            // Écouteur pour fermer la modale en cliquant en dehors de son contenu
            window.addEventListener('click', (event) => {
                if (event.target === messageModal) {
                    console.log('Clic en dehors de la modale détecté.');
                    clearTimeout(timeoutId);
                    messageModal.style.display = 'none';
                    console.log('Affichage de la modale défini sur "none" après le clic en dehors.');
                }
            });

            // --- IMPORTANT : ID DE L'ENTREPRISE ---
            // Récupérer l'ID de l'entreprise depuis le PHP (session)
            const currentCompanyId = "<?php echo htmlspecialchars($user_id); ?>";

            // Fonction pour charger et afficher les propositions de stage
            async function loadProposals(companyId) {
                noProposalsMessage.textContent = "Chargement des propositions...";
                noProposalsMessage.classList.remove('hidden');
                propositionsContainer.classList.add('hidden');
                console.log('loadProposals: Début du chargement des propositions pour companyId:', companyId);


                const apiUrl = `get_proposition.php?companyId=${companyId}`;

                try {
                    console.log('loadProposals: Requête Fetch envoyée à:', apiUrl);
                    const response = await fetch(apiUrl);
                    console.log('loadProposals: Réponse reçue.');
                    const contentType = response.headers.get("content-type");

                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        const result = await response.json();
                        console.log('loadProposals: Réponse JSON analysée:', result);

                        if (result.success) {
                            if (result.data.length > 0) {
                                propositionsContainer.innerHTML = ''; // Nettoyer le conteneur
                                result.data.forEach(proposal => {
                                    const card = createProposalCard(proposal);
                                    propositionsContainer.appendChild(card);
                                });
                                noProposalsMessage.classList.add('hidden');
                                propositionsContainer.classList.remove('hidden');
                                console.log('loadProposals: Propositions affichées avec succès.');
                            } else {
                                noProposalsMessage.textContent = "Aucune proposition de stage n'a été ajoutée pour le moment.";
                                noProposalsMessage.classList.remove('hidden');
                                propositionsContainer.classList.add('hidden');
                                showMessageBox('Aucune proposition de stage n\'a été ajoutée pour le moment.', 'info');
                                console.log('loadProposals: Aucune proposition trouvée.');
                            }
                        } else {
                            const msg = result.message && result.message.trim() !== '' 
                                ? result.message 
                                : 'Erreur lors de la récupération des propositions.';
                            console.error('loadProposals: Erreur de succès dans la réponse JSON:', msg);
                            throw new Error(msg);
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('loadProposals: Réponse non JSON de get_proposals.php:', errorText);
                        throw new Error('La réponse de get_proposals.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.');
                    }
                } catch (error) {
                    console.error("loadProposals: Erreur lors du chargement des propositions:", error);
                    noProposalsMessage.textContent = `Erreur lors du chargement: ${error.message}. Veuillez vérifier votre serveur PHP et les logs.`;
                    noProposalsMessage.classList.remove('hidden');
                    propositionsContainer.classList.add('hidden');
                    showMessageBox(`Erreur lors du chargement des propositions: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
            }

            // Fonction pour créer une carte de proposition de stage
            function createProposalCard(proposal) {
                const card = document.createElement('div');
                card.classList.add('proposal-card', 'card');
                card.dataset.id = proposal.id; // Stocker l'ID de la base de données

                // Formater la date si elle existe
                const createdAt = proposal.proposee_le ? new Date(proposal.proposee_le).toLocaleDateString('fr-FR') : 'N/A';

                card.innerHTML = `
                    <h4 class="card-title purple">${proposal.sujet}</h4>
                    <p class="card-text"><span class="font-semibold">Durée:</span> ${proposal.duree} mois</p>
                    <p class="card-text"><span class="font-semibold">Niveau:</span> ${proposal.niveau_requis || 'Non spécifié'}</p>
                    <p class="card-text"><span class="font-semibold">Lieu:</span> ${proposal.lieu || 'Non spécifié'}</p>
                    <p class="card-text"><span class="font-semibold">Rémunération:</span> ${proposal.renumeration || 'Non spécifiée'}</p>
                    <p class="card-text"><span class="font-semibold">Description:</span> ${proposal.description || 'Non disponible'}</p>
                    <p class="card-text"><span class="font-semibold">Créée le:</span> ${createdAt}</p>
                    <p class="card-text mb-4"><span class="font-semibold">Statut:</span> ${proposal.status || 'Actif'}</p>
                    <div class="card-actions">
                        <button class="btn btn-primary" data-proposal-id="${proposal.id}">
                            Modifier
                        </button>
                        <button class="btn btn-danger" data-proposal-id="${proposal.id}">
                            Supprimer
                        </button>
                        <button class="btn btn-info view-applications-btn" data-proposal-id="${proposal.id}">
                            Voir les Candidatures
                        </button>
                    </div>
                `;
                return card;
            }

            // NOUVEAU: Fonction pour charger et afficher les candidatures pour l'entreprise
            async function loadApplicationsForCompany(companyId) {
                applicationsList.innerHTML = ''; // Nettoyer les candidatures existantes
                noApplicationsMessage.textContent = "Chargement des candidatures...";
                noApplicationsMessage.classList.remove('hidden');
                applicationsContainer.classList.add('hidden');
                console.log('loadApplicationsForCompany: Début du chargement des candidatures pour companyId:', companyId);


                // Assurez-vous que le nom du fichier est correct et qu'il gère le paramètre company_id
                const apiUrl = `get_candidature_entreprise.php?company_id=${companyId}`; 

                try {
                    console.log('loadApplicationsForCompany: Requête Fetch envoyée à:', apiUrl);
                    const response = await fetch(apiUrl);
                    console.log('loadApplicationsForCompany: Réponse reçue.');
                    const contentType = response.headers.get("content-type");

                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        const result = await response.json();
                        console.log('loadApplicationsForCompany: Réponse JSON analysée:', result);

                        if (result.success && result.data.length > 0) {
                            applicationsList.innerHTML = ''; // Nettoyer le conteneur
                            result.data.forEach(application => {
                                const listItem = document.createElement('li');
                                listItem.classList.add('list-group-item', 'application-item'); // Ajout de 'application-item' pour les styles
                                let statusClass = '';
                                switch (application.application_status) { // Assurez-vous que le nom de la colonne de statut est correct
                                    case 'en attente': statusClass = 'status-en-attente'; break;
                                    case 'acceptée': statusClass = 'status-acceptée'; break;
                                    case 'refusée': statusClass = 'status-refusée'; break;
                                    case 'complétée': statusClass = 'status-complétée'; break;
                                    case 'signée': statusClass = 'status-signée'; break;
                                    default: statusClass = '';
                                }

                                listItem.innerHTML = `
                                    <p><strong>Stage:</strong> ${application.sujet}</p>
                                    <p><strong>Candidat:</strong> ${application.student_prenom} ${application.student_nom}</p>
                                    <p><strong>Email Candidat:</strong> ${application.student_email}</p>
                                    <p><strong>Date de candidature:</strong> ${new Date(application.applied_at).toLocaleDateString('fr-FR')}</p>
                                    <p><strong>Statut:</strong> <span class="status ${statusClass}">${application.application_status}</span></p>
                                    <!-- NOUVEAU: Bouton pour télécharger le CV et la LM en même temps (ZIP) -->
                            <a href="telecharger_dossier.php?id_candidature=${application.application_id}&type=cv" 
                               class="btn bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out" 
                               target="_blank" view>
                                Voir le CV du candidat
                            </a>
                            <a href="telecharger_dossier.php?id_candidature=${application.application_id}&type=lm" 
                               class="btn bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out" 
                               target="_blank" view>
                                Voir la lettre de motivation
                            </a>
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button class="btn bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out accept-application-btn" 
                                data-application-id="${application.application_id}" 
                                ${application.application_status === 'signée' ||application.application_status === 'acceptée' || application.application_status === 'refusée' ? 'disabled' : ''}>
                                Accepter
                            </button>
                            <button class="btn bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out reject-application-btn" 
                                data-application-id="${application.application_id}" 
                                ${application.application_status === 'signée' ||application.application_status === 'acceptée' || application.application_status === 'refusée' ? 'disabled' : ''}>
                                Refuser
                            </button>
                        </div>
                                `;
                                applicationsList.appendChild(listItem);
                            });
                            noApplicationsMessage.classList.add('hidden');
                            applicationsContainer.classList.remove('hidden');
                            console.log('loadApplicationsForCompany: Candidatures affichées avec succès.');
                        } else {
                            noApplicationsMessage.textContent = "Aucune candidature reçue pour le moment.";
                            noApplicationsMessage.classList.remove('hidden');
                            applicationsContainer.classList.add('hidden');
                            showMessageBox('Aucune candidature reçue pour le moment.', 'info');
                            console.log('loadApplicationsForCompany: Aucune candidature trouvée.');
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('loadApplicationsForCompany: Réponse non JSON de get_candidature_entreprise.php:', errorText);
                        throw new Error('La réponse de get_candidature_entreprise.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.');
                    }
                } catch (error) {
                    console.error("loadApplicationsForCompany: Erreur lors du chargement des candidatures:", error);
                    showMessageBox(`Erreur lors du chargement des candidatures: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
            }

            // NOUVEAU: Fonction pour traiter l'acceptation/le refus d'une candidature
            async function processApplicationStatus(applicationId, newStatus) {
                console.log(`processApplicationStatus: Tentative de mise à jour de la candidature ${applicationId} vers le statut ${newStatus}.`);
                try {
                    const response = await fetch('valider_candidature.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            application_id: applicationId,
                            new_status: newStatus,
                            company_id: currentCompanyId // Pour la vérification côté serveur
                        })
                    });
                    console.log('processApplicationStatus: Réponse reçue de update_application_status.php.');

                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        const result = await response.json();
                        console.log('processApplicationStatus: Résultat de la mise à jour:', result);

                        if (result.success) {
                            showMessageBox(result.message, 'success');
                            await loadApplicationsForCompany(currentCompanyId); // Recharger les candidatures
                        } else {
                            const msg = result.message && result.message.trim() !== '' 
                                ? result.message 
                                : `Échec de la mise à jour du statut de la candidature en ${newStatus}.`;
                            showMessageBox(`Erreur: ${msg}`, 'error');
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('processApplicationStatus: Réponse non JSON de update_application_status.php:', errorText);
                        showMessageBox('Erreur: La réponse de update_application_status.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.', 'error');
                    }
                } catch (error) {
                    console.error("processApplicationStatus: Erreur lors de la mise à jour du statut:", error);
                    showMessageBox(`Une erreur est survenue lors de la mise à jour du statut: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
            }

            // NOUVEAU: Écouteurs d'événements pour les boutons Accepter/Refuser
            applicationsContainer.addEventListener('click', async (event) => {
                const target = event.target;
                if (target.classList.contains('accept-application-btn')) {
                    const applicationId = target.dataset.applicationId;
                    console.log('Clic sur bouton "Accepter" pour application ID:', applicationId);
                    if (applicationId && !target.disabled) { // Vérifier si le bouton n'est pas désactivé
                        await processApplicationStatus(applicationId, 'acceptée'); // Utiliser 'acceptée' pour le statut
                    }
                } else if (target.classList.contains('reject-application-btn')) {
                    const applicationId = target.dataset.applicationId;
                    console.log('Clic sur bouton "Refuser" pour application ID:', applicationId);
                    if (applicationId && !target.disabled) { // Vérifier si le bouton n'est pas désactivé
                        await processApplicationStatus(applicationId, 'refusée'); // Utiliser 'refusée' pour le statut
                    }
                }
            });

            // NOUVEAU: Écouteur d'événement pour le bouton "Voir les Candidatures" sur les cartes de proposition
            propositionsContainer.addEventListener('click', async (event) => {
                const target = event.target;
                if (target.classList.contains('view-applications-btn')) {
                    console.log('Clic sur bouton "Voir les Candidatures".');
                    // Simule un clic sur l'onglet "Candidatures Reçues"
                    applicationsTab.click(); 
                }
            });

                // NOUVEAU: Fonction pour charger et afficher les stages effectifs en cours
                async function loadActiveInternships(companyId) {
                activeInternshipsContainer.innerHTML = ''; // Nettoyer les stages existants
                noActiveInternshipsMessage.textContent = "Chargement des stages effectifs...";
                noActiveInternshipsMessage.classList.remove('hidden');
                activeInternshipsContainer.classList.add('hidden');
                console.log('loadActiveInternships: Début du chargement des stages actifs pour companyId:', companyId);

                const apiUrl = `stage_en_cours.php?company_id=${companyId}`;

                try {
                    console.log('loadActiveInternships: Requête Fetch envoyée à:', apiUrl);
                    const response = await fetch(apiUrl);
                    console.log('loadActiveInternships: Réponse reçue.');
                    const contentType = response.headers.get("content-type");

                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        const result = await response.json();
                        console.log('loadActiveInternships: Réponse JSON analysée:', result);

                        if (result.success && result.internships.length > 0) {
                            result.internships.forEach(internship => {
                                const card = createActiveInternshipCard(internship);
                                activeInternshipsContainer.appendChild(card);
                            });
                            noActiveInternshipsMessage.classList.add('hidden');
                            activeInternshipsContainer.classList.remove('hidden');
                            console.log('loadActiveInternships: Stages actifs affichés avec succès.');
                        } else {
                            noActiveInternshipsMessage.textContent = "Aucun stage effectif n'est en cours pour le moment.";
                            noActiveInternshipsMessage.classList.remove('hidden');
                            activeInternshipsContainer.classList.add('hidden');
                            showMessageBox('Aucun stage effectif n\'est en cours pour le moment.', 'info');
                            console.log('loadActiveInternships: Aucun stage actif trouvé.');
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('loadActiveInternships: Réponse non JSON de get_active_internships.php:', errorText);
                        throw new Error('La réponse de get_active_internships.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.');
                    }
                } catch (error) {
                    console.error("loadActiveInternships: Erreur lors du chargement des stages actifs:", error);
                    showMessageBox(`Erreur lors du chargement des stages actifs: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
            }

            // NOUVEAU: Fonction pour créer une carte de stage actif
            function createActiveInternshipCard(internship) {
                const card = document.createElement('div');
                card.classList.add('active-internship-card', 'card');
                card.dataset.idCandidature = internship.id_candidature;

                // Formater les dates si elles existent
                const dateCandidature = internship.date_candidature ? new Date(internship.date_candidature).toLocaleDateString('fr-FR') : 'N/A';
                const dateDebut = internship.date_debut_stage ? internship.date_debut_stage : ''; // Garder le format YYYY-MM-DD pour l'input
                const dateFin = internship.date_fin_stage ? internship.date_fin_stage : ''; // Garder le format YYYY-MM-DD pour l'input

                let statusClass = '';
                switch (internship.statut) {
                    case 'acceptée': statusClass = 'status-acceptée'; break;
                    case 'signée': statusClass = 'status-signée'; break;
                    case 'en cours': statusClass = 'status-en-cours'; break;
                    default: statusClass = '';
                }

                card.innerHTML = `
                    <h4 class="card-title purple">${internship.titre_offre}</h4>
                    <p><strong>Étudiant:</strong> ${internship.prenom_etudiant} ${internship.nom_etudiant}</p>
                    <p><strong>Statut:</strong> <span class="status ${statusClass}">${internship.statut}</span></p>
                    <p><strong>Date de candidature:</strong> ${dateCandidature}</p>

                    <div class="form-group">
                        <label for="dateDebut_${internship.id_candidature}"><strong>Date de début:</strong></label>
                        <input type="date" id="dateDebut_${internship.id_candidature}" value="${dateDebut}" class="date-input" ${internship.statut === 'en cours' ? 'disabled' : ''}>
                    </div>
                    <div class="form-group">
                        <label for="dateFin_${internship.id_candidature}">Date de fin:</label>
                        <input type="date" id="dateFin_${internship.id_candidature}" value="${dateFin}" class="date-input" ${internship.statut === 'en cours' ? 'disabled' : ''}>
                    </div>

                    <div class="card-actions">
                        <button class="btn btn-primary save-dates-btn" data-id-candidature="${internship.id_candidature}" ${internship.statut === 'en cours' ? 'disabled' : ''}>
                            ${internship.statut === 'terminée' ? 'Dates définies' : 'Définir les dates'}
                        </button>
                        ${internship.rapport_stage ? `
                            <a href="${internship.chemin_rapport_stage}" target="_blank" class="btn btn-info download-report-btn">
                                Télécharger Rapport
                            </a>
                        ` : ''}
                    </div>
                `;
                return card;
            }

            // NOUVEAU: Écouteur d'événements pour les boutons "Définir les dates"
            activeInternshipsContainer.addEventListener('click', async (event) => {
                const target = event.target;
                if (target.classList.contains('save-dates-btn')) {
                    const idCandidature = target.dataset.idCandidature;
                    const dateDebutInput = document.getElementById(`dateDebut_${idCandidature}`);
                    const dateFinInput = document.getElementById(`dateFin_${idCandidature}`);

                    const dateDebut = dateDebutInput.value;
                    const dateFin = dateFinInput.value;

                    if (!dateDebut || !dateFin) {
                        showMessageBox('Veuillez saisir les dates de début et de fin.', 'error');
                        return;
                    }

                    if (new Date(dateFin) < new Date(dateDebut)) {
                        showMessageBox('La date de fin ne peut pas être antérieure à la date de début.', 'error');
                        return;
                    }

                    console.log(`Tentative de sauvegarde des dates pour candidature ID ${idCandidature}: Début ${dateDebut}, Fin ${dateFin}`);

                    try {
                        const response = await fetch('dates.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id_candidature: idCandidature,
                                date_debut_stage: dateDebut,
                                date_fin_stage: dateFin
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            showMessageBox(result.message, 'success');
                            // Recharger les stages actifs pour refléter le nouveau statut et les dates
                            await loadActiveInternships(currentCompanyId);
                            // Recharger aussi les candidatures au cas où le statut a changé
                            await loadApplicationsForCompany(currentCompanyId);
                        } else {
                            showMessageBox(`Erreur lors de la sauvegarde des dates: ${result.message || 'Erreur inconnue'}.`, 'error');
                        }
                    } catch (error) {
                        console.error("Erreur lors de l'envoi des dates:", error);
                        showMessageBox(`Une erreur est survenue lors de la sauvegarde des dates: ${error.message || 'Erreur inconnue'}.`, 'error');
                    }
                }
            });


            // --- Logique des onglets ---
            function showSection(sectionToShow, activeTab) { // Simplifié pour ne prendre que la section à montrer et l'onglet actif
                console.log('showSection: Changement de section vers:', sectionToShow.id);
                // Masquer toutes les sections
                proposalsSection.classList.add('hidden');
                applicationsSection.classList.add('hidden');
                activeInternshipsSection.classList.add('hidden');

                // Désactiver tous les onglets
                proposalsTab.classList.remove('active');
                applicationsTab.classList.remove('active');
                activeInternshipsTab.classList.remove('active');

                // Afficher la section et activer l'onglet correspondant
                sectionToShow.classList.remove('hidden');
                activeTab.classList.add('active');
            }

            proposalsTab.addEventListener('click', () => {
                showSection(proposalsSection, proposalsTab); // Appel simplifié
                loadProposals(currentCompanyId); // Charger les propositions quand l'onglet est activé
            });

            applicationsTab.addEventListener('click', () => { // Nouveau
                showSection(applicationsSection, applicationsTab); // Appel simplifié
                loadApplicationsForCompany(currentCompanyId); // Charger les candidatures quand l'onglet est activé
            });

            activeInternshipsTab.addEventListener('click', () => {
                showSection(activeInternshipsSection, activeInternshipsTab);
                loadActiveInternships(currentCompanyId); // Charger les stages effectifs quand l'onglet est activé
            });

            // Initialiser l'affichage au chargement de la page
            proposalsTab.click(); // Simule un clic sur l'onglet "Propositions" au chargement
        });
    </script>
</body>
</html>
