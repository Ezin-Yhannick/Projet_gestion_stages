<?php
// Démarre la session PHP. DOIT être la toute première ligne avant tout HTML ou autre sortie.
session_start();

// Inclure le gestionnaire de session pour utiliser les fonctions is_logged_in, get_user_role, get_user_id.
require 'session_manager.php'; 
// Inclure le fichier de connexion à la base de données PDO.
require 'db.php'; 

// Rediriger si l'utilisateur n'est pas connecté ou si ce n'est pas un étudiant
if (!is_logged_in() || get_user_role() !== 'student') {
    // Rediriger vers la page de connexion étudiant avec un message d'erreur
    header("Location: login_etudiant.php?status_type=error&status_message=" . urlencode("Accès non autorisé. Veuillez vous connecter en tant qu'étudiant."));
    exit();
}

// Récupérer les informations de l'utilisateur connecté depuis la session
$user_id = get_user_id();
$user_email = $_SESSION['user_email'] ?? 'N/A';
$username = $_SESSION['username'] ?? 'N/A'; // Nom d'utilisateur (peut être le nom ou un nom généré)
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant</title>
    <!-- Lien vers le fichier CSS global -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles pour les cartes d'offres */
        .internship-offer-card button, .application-item button {
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

        .internship-offer-card button:hover:not(:disabled), .application-item button:hover:not(:disabled) {
            background-color: #0056b3;
        }

        .internship-offer-card button:disabled, .application-item button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Nouveau style pour le bouton "Déjà Postulé" */
        .internship-offer-card .applied-button {
            background-color: #28a745; /* Vert pour "Déjà Postulé" */
            color: white;
        }

        .internship-offer-card .applied-button:hover {
            background-color: #218838;
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

        .offers-grid {
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
        .status-pending { background-color: #ffeb3b; color: #795548; } /* Jaune */
        .status-accepted { background-color: #8bc34a; color: #33691e; } /* Vert */
        .status-rejected { background-color: #f44336; color: white; } /* Rouge */
        .status-completed { background-color: #9e9e9e; color: white; } /* Gris */
        .status-signed { background-color: #2196f3; color: white; } /* Bleu */
        /* Nouveau statut pour l'étudiant */
        .status-refusée-par-étudiant { background-color: #ff5722; color: white; } /* Orange foncé */


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

        .filter-section {
            margin-bottom: 40px;
            text-align: center;
        }

        .filter-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }

        .filter-label {
            font-size: 1.1em;
            color: #333;
            font-weight: 500;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1em;
            cursor: pointer;
            background-color: white;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
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
            .offers-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Modale générique (pour messages et candidature) */
    .modal {
        display: none; /* Caché par défaut */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6); /* Fond semi-transparent */
        justify-content: center; /* Centrer horizontalement */
        align-items: center; /* Centrer verticalement */
        animation: fadeIn 0.3s ease-out;
    }
    .modal-content {
        background-color: #fefefe;
        padding: 2.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 8px 16px rgba(0,0,0,0.25);
        width: 90%;
        max-width: 550px;
        position: relative;
        animation: slideIn 0.3s ease-out;
    }

        /* Styles pour la modale de message (maintenue) */
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
        
        /* Styles du formulaire de candidature dans la modale */
    .apply-modal-form .file-input-group {
        margin-bottom: 1.5rem;
    }
    .apply-modal-form label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #2d3748;
    }
    .apply-modal-form input[type="file"] {
        display: block;
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e0;
        border-radius: 0.5rem;
        background-color: #f7fafc;
        font-size: 1rem;
        color: #2d3748;
        cursor: pointer;
    }
    .apply-modal-form input[type="file"]::file-selector-button {
        background-color: #4299e1;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        margin-right: 1rem;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
    }
    .apply-modal-form input[type="file"]::file-selector-button:hover {
        background-color: #3182ce;
    }
    .apply-modal-form .help-text {
        font-size: 0.85rem;
        color: #718096;
        margin-top: 0.5rem;
    }
    .apply-modal-form .btn-submit {
        background-color: #4299e1;
        color: #ffffff;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
        width: 100%;
        border: none;
        margin-top: 1rem;
    }
    .apply-modal-form .btn-submit:hover:not(:disabled) {
        background-color: #3182ce;
        transform: translateY(-1px);
    }
    .apply-modal-form .btn-submit:disabled {
        background-color: #a0aec0;
        cursor: not-allowed;
        opacity: 0.7;
        transform: none;
    }
        /* Styles pour le bouton "Postuler" dans la carte d'offre (qui ouvre la modale) */
        .internship-offer-card .apply-button {
            width: 100%;
            background-color: #2563eb; /* blue-600 */
            color: white;
            font-weight: 600; /* font-semibold */
            padding: 0.5rem 1rem;
            border-radius: 0.375rem; /* rounded-md */
            transition: all 0.3s ease-in-out;
            transform: scale(1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-md */
        }
        .internship-offer-card .apply-button:hover {
            background-color: #1d4ed8; /* blue-700 */
            transform: scale(1.02); /* hover:scale-105 */
        }
        .internship-offer-card .apply-button:disabled,
        .internship-offer-card .applied-button {
            background-color: #6b7280; /* gray-500 */
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .info-message.applied-message {
            background-color: #e0f2fe; /* light blue */
            color: #0c4a6e; /* dark blue text */
            padding: 0.75rem;
            border-radius: 0.375rem;
            text-align: center;
            margin-top: 1rem;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    </style>
</head>
<body> 
<header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Espace Étudiant</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="profil_etudiant.php" class="nav-link">Mon Profil</a>
                <a href="logout.php" class="nav-link">Déconnexion</a>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <h2 class="page-title">Mes Stages et Candidatures</h2>

        <!-- Informations de l'étudiant connecté (pour le débogage/vérification) -->
        <section class="card mb-6">
            <h3 class="section-title">Bienvenue, <?php echo htmlspecialchars($username); ?>!</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            <p><strong>Rôle:</strong> <?php echo htmlspecialchars(get_user_role()); ?></p>
            <p><strong>ID Étudiant:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        </section>

        <!-- Section de filtre par domaine -->
        <section class="card filter-section">
            <h3 class="section-title">Filtrer les Offres par Domaine</h3>
            <div class="filter-controls">
                <label for="domainFilter" class="filter-label">Sélectionnez un domaine :</label>
                <select id="domainFilter" class="filter-select">
                    <option value="all">Tous les domaines</option>
                    <option value="informatique">Développement</option>
                    <option value="data">Data Science & IA</option>
                    <option value="marketing">Marketing & Communication</option>
                    <option value="finance">Comptabilité</option>
                    <option value="design">Design UX/UI</option>
                    <option value="gestion">Gestion de Projet</option>
                    <!-- Ajoutez d'autres domaines ici si nécessaire -->
                </select>
            </div>
        </section>

        <!-- Section des Offres de Stage Disponibles -->
        <section class="offers-section">
            <h3 class="section-title">Offres de Stage Disponibles</h3>
            <!-- Le conteneur des offres sera rempli par JS -->
            <div id="internshipOffersContainer" class="offers-grid hidden">
                <!-- Les offres seront chargées dynamiquement ici -->
            </div>
            <!-- Message "Aucune offre" est visible par défaut -->
            <div id="noOffersMessage" class="info-message">
                <p class="info-message-title">Chargement des offres...</p>
                <p>Si aucune offre n'apparaît, il n'y en a pas de disponible pour le moment.</p>
            </div>
        </section>

        <section class="applications-section">
            <h3 class="section-title">Mes Candidatures</h3>
            <div id="applicationsContainer" class="card">
                <!-- Message "Aucune candidature" est visible par défaut -->
                <div id="noApplicationsMessage" class="info-message">
                    <p class="info-message-title">Aucune candidature n'a été soumise pour le moment.</p>
                    <p>Explorez les offres de stage disponibles et postulez !</p>
                </div>
                <!-- La liste des candidatures est cachée par défaut -->
                <ul id="applicationsList" class="list-group hidden">
                    <!-- Les candidatures seront chargées dynamiquement ici -->
                </ul>
            </div>
        </section>

        <section class="current-internship-section">
            <h3 class="section-title">Mon Stage Actuel</h3>
            <div id="currentInternshipContainer" class="card">
                <!-- Message "Pas de stage actuel" est visible par défaut -->
                <div id="noCurrentInternshipMessage" class="info-message">
                    <p class="info-message-title">Vous n'avez pas de stage effectif en cours pour l'année universitaire actuelle.</p>
                    <p class="small-text">Une fois qu'une convention de stage est signée, les détails apparaîtront ici.</p>
                </div>
                <!-- Les détails du stage actuel sont cachés par défaut -->
                <div id="actualInternshipDetails" class="hidden">
                    <!-- Les détails du stage actuel seront chargés dynamiquement ici si implémentés -->
                </div>
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
    <div id="rapportDepotSection" class="hidden">
    <h2 class="text-xl font-semibold mb-4">Déposer mon rapport de stage</h2>
    <form id="rapportUploadForm" enctype="multipart/form-data">
        <input type="hidden" id="rapportApplicationId" name="application_id" value="[ID_DE_LA_CANDIDATURE_DU_STAGE_ACTUEL]">
        <div class="mb-4">
            <label for="rapportFile" class="block text-gray-700 text-sm font-bold mb-2">Sélectionner le fichier du rapport (PDF, DOCX, max 10 Mo) :</label>
            <input type="file" id="rapportFile" name="rapport_stage" accept=".pdf,.doc,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            Déposer le rapport
        </button>
        <p id="uploadMessage" class="mt-2 text-sm"></p>
    </form>
</div>

    <!-- NOUVELLE MODALE : Formulaire de Candidature -->
    <div id="applyModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeApplyModal">&times;</span>
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Postuler à l'Offre de Stage</h3>
            <p class="text-center text-gray-600 mb-4">Veuillez joindre votre CV et votre lettre de motivation.</p>

            <form id="applyFormModal" class="apply-modal-form" action="postuler_stage.php" method="POST" enctype="multipart/form-data">
                <!-- Ces champs seront remplis par JavaScript lors de l'ouverture de la modale -->
                <input type="hidden" name="id_offre" id="modalInternshipId">
                <input type="hidden" name="id_etudiant" value="<?php echo $user_id; ?>">

                <div class="file-input-group">
                    <label for="cvFile">Votre CV (PDF, DOCX)</label>
                    <input type="file" id="cvFile" name="cv" accept=".pdf,.doc,.docx" required>
                    <p class="help-text">Taille maximale : 5 Mo. Formats acceptés : PDF, DOC, DOCX.</p>
                </div>

                <div class="file-input-group">
                    <label for="lettreMotivationFile">Votre Lettre de Motivation (PDF, DOCX)</label>
                    <input type="file" id="lettreMotivationFile" name="lettre_motivation" accept=".pdf,.doc,.docx" required>
                    <p class="help-text">Taille maximale : 5 Mo. Formats acceptés : PDF, DOC, DOCX.</p>
                </div>

                <button type="submit" class="btn-submit" id="submitApplicationBtn">Postuler</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Université [Nom de l'université]. Tous droits réservés.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const domainFilter = document.getElementById('domainFilter');
            const internshipOffersContainer = document.getElementById('internshipOffersContainer');
            const noOffersMessage = document.getElementById('noOffersMessage');
            
            const applicationsContainer = document.getElementById('applicationsContainer');
            const noApplicationsMessage = document.getElementById('noApplicationsMessage');
            const applicationsList = document.getElementById('applicationsList'); 

            const currentInternshipContainer = document.getElementById('currentInternshipContainer');
            const noCurrentInternshipMessage = document.getElementById('noCurrentInternshipMessage');
            const actualInternshipDetails = document.getElementById('actualInternshipDetails');

            // Références aux éléments de la modale de message
            const messageModal = document.getElementById('messageModal');
            const messageModalContent = messageModal.querySelector('.modal-message-content'); 
            const messageModalTitle = document.getElementById('messageModalTitle');
            const messageModalText = document.getElementById('messageModalText');
            const messageModalCloseButton = messageModal.querySelector('.close-button');

            // NOUVEAU : Références aux éléments de la modale de candidature
            const applyModal = document.getElementById('applyModal');
            const closeApplyModalBtn = document.getElementById('closeApplyModal');
            const applyFormModal = document.getElementById('applyFormModal');
            const modalInternshipIdInput = document.getElementById('modalInternshipId');
            const submitApplicationBtn = document.getElementById('submitApplicationBtn');

            let appliedInternshipIds = new Set(); 
            let currentActiveInternship = null; // Variable pour stocker le stage actuel signé
            let timeoutId; // Variable pour stocker l'ID du temporisateur de fermeture automatique.

            // showMessageBox utilise maintenant la modale
            function showMessageBox(message, type = 'info') {
                console.log(`showMessageBox appelée avec: message="${message}", type="${type}"`);
                // Effacer tout temporisateur existant pour éviter les conflits
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }

                // Réinitialiser les classes de type sur le contenu de la modale
                messageModalContent.classList.remove('info', 'success', 'error');
                
                // Ajouter la classe de type appropriée
                messageModalContent.classList.add(type);

                messageModalTitle.textContent = type.toUpperCase();
                messageModalText.textContent = message;
                messageModal.style.display = 'flex'; // Afficher la modale

                // Fermer automatiquement après 5 secondes pour les messages non-erreurs
                if (type !== 'error') { 
                    timeoutId = setTimeout(() => {
                        console.log('Tentative de fermeture automatique de la modale après 5 secondes.');
                        messageModal.style.display = 'none';
                    }, 5000); 
                } else {
                    console.log('Type de modale est "error", ne se fermera pas automatiquement.');
                }
            }

            // Écouteurs pour fermer la modale de message
            messageModalCloseButton.addEventListener('click', () => {
                console.log('Bouton de fermeture de modale cliqué.');
                clearTimeout(timeoutId); // Effacer le temporisateur automatique si l'utilisateur clique.
                messageModal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === messageModal) {
                    console.log('Clic en dehors de la modale détecté.');
                    clearTimeout(timeoutId);
                    messageModal.style.display = 'none';
                }
            });

            // NOUVEAU : Fonctions pour ouvrir et fermer la modale de candidature
            function openApplyModal(internshipId) {
                modalInternshipIdInput.value = internshipId; // Définit l'ID de l'offre dans le champ caché
                applyFormModal.reset(); // Réinitialise le formulaire
                submitApplicationBtn.disabled = false; // Réactive le bouton
                submitApplicationBtn.textContent = 'Postuler'; // Réinitialise le texte du bouton
                applyModal.style.display = 'flex'; // Affiche la modale
            }

            function closeApplyModal() {
                applyModal.style.display = 'none'; // Cache la modale
            }

            // NOUVEAU : Écouteurs pour la modale de candidature
            closeApplyModalBtn.addEventListener('click', closeApplyModal);
            applyModal.addEventListener('click', (event) => {
                if (event.target === applyModal) {
                    closeApplyModal();
                }
            });

            async function loadApplications() {
                console.log('loadApplications: Début du chargement des candidatures.');
                applicationsList.innerHTML = ''; 
                noApplicationsMessage.classList.remove('hidden');
                applicationsList.classList.add('hidden');
                appliedInternshipIds.clear(); 
                currentActiveInternship = null; // Réinitialiser le stage actuel
                console.log('loadApplications: appliedInternshipIds cleared. Current size:', appliedInternshipIds.size);

                try {
                    const response = await fetch(`get_candidature.php?student_id=<?php echo $user_id; ?>`);
                    console.log('loadApplications: Réponse Fetch reçue.');
                    const contentType = response.headers.get("content-type");
                    console.log('loadApplications: Content-Type de la réponse:', contentType);

                    if (contentType && contentType.includes("application/json")) {
                        const result = await response.json();
                        console.log('loadApplications: Réponse JSON analysée:', result);

                        if (result.success && result.data.length > 0) {
                            result.data.forEach(application => {
                                console.log('loadApplications: Ajout de l\'ID de candidature:', application.internship_id);
                                appliedInternshipIds.add(application.internship_id); 
                                const listItem = document.createElement('li');
                                listItem.classList.add('list-group-item');
                                let statusClass = '';
                                switch (application.status) {
                                    case 'pending': statusClass = 'status-pending'; break;
                                    case 'accepted': statusClass = 'status-accepted'; break;
                                    case 'rejected': statusClass = 'status-rejected'; break;
                                    case 'completed': statusClass = 'status-completed'; break;
                                    case 'signed': statusClass = 'status-signed'; break;
                                    case 'refusée par étudiant': statusClass = 'status-refusée-par-étudiant'; break; // Nouveau statut
                                    default: statusClass = '';
                                }

                                listItem.innerHTML = `
                                    <p><strong>Sujet:</strong> ${application.sujet}</p>
                                    <p><strong>Entreprise:</strong> ${application.nomentreprise || 'N/A'}</p>
                                    <p><strong>Date de candidature:</strong> ${new Date(application.date_candidature).toLocaleDateString('fr-FR')}</p>
                                    <p><strong>Statut:</strong> <span class="status ${statusClass}">${application.statut}</span></p>
                                `;

                                // Ajouter les boutons "Accepter" / "Refuser" si le statut est 'accepted'
                                if (application.statut === 'acceptée') {
                                    const actionsDiv = document.createElement('div');
                                    actionsDiv.classList.add('card-actions');
                                    actionsDiv.innerHTML = `
                                        <button class="btn btn-success student-accept-btn" data-application-id="${application.id_candidature}">Accepter l'offre</button>
                                        <button class="btn btn-danger student-reject-btn" data-application-id="${application.id_candidature}">Refuser l'offre</button>
                                    `;
                                    listItem.appendChild(actionsDiv);
                                } else if (application.statut === 'en cours' && application.convention_pdf_path) {
                                    // Si la convention est signée et le chemin PDF est disponible
                                    const conventionLinkDiv = document.createElement('div');
                                    conventionLinkDiv.classList.add('card-actions');
                                    conventionLinkDiv.innerHTML = `
                                        <a href="${application.convention_pdf_path}" target="_blank" class="btn btn-primary">Voir la Convention PDF</a>
                                    `;
                                    listItem.appendChild(conventionLinkDiv);
                                    currentActiveInternship = application; // Définir le stage actuel
                                }

                                applicationsList.appendChild(listItem);
                            });
                            noApplicationsMessage.classList.add('hidden');
                            applicationsList.classList.remove('hidden');
                            console.log('loadApplications: Candidatures affichées avec succès. appliedInternshipIds size:', appliedInternshipIds.size);
                        } else {
                            const msg = result.message && result.message.trim() !== '' 
                                ? result.message 
                                : 'Aucune candidature trouvée pour le moment.';
                            showMessageBox(msg, result.success ? 'info' : 'error'); 
                            noApplicationsMessage.classList.remove('hidden');
                            applicationsList.classList.add('hidden');
                            console.log('loadApplications: Aucune candidature trouvée ou succès faux. Result:', result);
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('loadApplications: Réponse non JSON de get_applications.php:', errorText);
                        showMessageBox('Erreur: La réponse des candidatures n\'est pas au format JSON valide. Vérifiez les erreurs PHP.', 'error');
                    }
                } catch (error) {
                    console.error("Erreur lors du chargement des candidatures:", error);
                    showMessageBox(`Erreur lors du chargement des candidatures: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
                displayCurrentInternship(); // Appeler cette fonction après le chargement des candidatures
            }

            // Nouvelle fonction pour afficher le stage actuel
            function displayCurrentInternship() {
    // Référence à la section qui contient le formulaire de dépôt (initialement cachée)
    const rapportDepotSection = document.getElementById('rapportDepotSection');
    // Référence à la section qui contient les infos sur l'attestation (à créer si ce n'est pas déjà fait)
    //const attestationSection = document.getElementById('attestationSection'); 

    if (currentActiveInternship) {
        noCurrentInternshipMessage.classList.add('hidden');
        actualInternshipDetails.classList.remove('hidden');
        
        // Formater les dates pour l'affichage
        const dateDebut = currentActiveInternship.date_debut_stage ? new Date(currentActiveInternship.date_debut_stage).toLocaleDateString('fr-FR') : 'Non définie';
        const dateFin = currentActiveInternship.date_fin_stage ? new Date(currentActiveInternship.date_fin_stage).toLocaleDateString('fr-FR') : 'Non définie';

        // Déterminer la classe de statut du rapport pour le style CSS
        let rapportStatusClass = '';
        switch (currentActiveInternship.rapport_statut) {
            case 'non soumis': rapportStatusClass = 'status-en-attente'; break; // À adapter à vos classes CSS pour les statuts
            case 'soumis': rapportStatusClass = 'status-en-attente-validation'; break; // Nouvelle classe si besoin
            case 'validé': rapportStatusClass = 'status-acceptée'; break;
            case 'refusé': rapportStatusClass = 'status-refusée'; break;
            default: rapportStatusClass = '';
        }

        actualInternshipDetails.innerHTML = `
            <h4 class="card-title">${currentActiveInternship.sujet}</h4>
            <p class="card-text"><span class="font-semibold">Entreprise:</span> ${currentActiveInternship.nomentreprise || 'N/A'}</p>
            <p class="card-text"><span class="font-semibold">Durée:</span> ${currentActiveInternship.duree} mois</p>
            <p class="card-text"><span class="font-semibold">Niveau:</span> ${currentActiveInternship.niveau_requis || 'Non spécifié'}</p>
            <p class="card-text"><span class="font-semibold">Lieu:</span> ${currentActiveInternship.lieu || 'Non spécifié'}</p>
            <p class="card-text mb-4"><span class="font-semibold">Rémunération:</span> ${currentActiveInternship.renumeration || 'Non spécifiée'}</p>
            <p class="card-text"><span class="font-semibold">Description:</span> ${currentActiveInternship.description || 'Non disponible'}</p>
            <p class="card-text"><span class="font-semibold">Statut de la convention:</span> <span class="status status-signed">${currentActiveInternship.convention_status || 'Signée'}</span></p>
            <a href="${currentActiveInternship.convention_pdf_path}" target="_blank" class="btn btn-primary" style="margin-top: 15px;">Voir la Convention PDF</a>

            <hr class="my-4"> <h5 class="font-semibold text-lg purple">Dates Effectives du Stage</h5>
            <p class="card-text"><span class="font-semibold">Début:</span> ${dateDebut}</p>
            <p class="card-text"><span class="font-semibold">Fin:</span> ${dateFin}</p>

            <h5 class="font-semibold text-lg purple mt-4">Rapport de Stage</h5>
            <p class="card-text"><strong>Statut du rapport:</strong> <span class="status ${rapportStatusClass}">${currentActiveInternship.rapport_statut || 'Non soumis'}</span></p>
            ${currentActiveInternship.rapport_url ? `
                <a href="${currentActiveInternship.rapport_url}" target="_blank" class="btn btn-secondary mt-2">Voir mon rapport de stage</a>
            ` : ''}
            
            <h5 class="font-semibold text-lg purple mt-4">Attestation de Fin de Stage</h5>
            ${currentActiveInternship.attestation_url ? `
                <p class="card-text">Votre attestation est disponible.</p>
                <a href="${currentActiveInternship.attestation_url}" target="_blank" class="btn btn-info mt-2">Télécharger mon attestation</a>
            ` : `
                <p class="card-text">Attestation non disponible. Elle le sera après validation de votre rapport par l'entreprise.</p>
            `}
        `;

        // Gérer la visibilité de la section de dépôt du rapport
        const today = new Date();
        const internshipEndDate = currentActiveInternship.date_fin_stage ? new Date(currentActiveInternship.date_fin_stage) : null;

        if (rapportDepotSection) { // S'assurer que la section existe dans le HTML
            if (internshipEndDate && today > internshipEndDate && 
                (currentActiveInternship.rapport_statut === 'non soumis' || currentActiveInternship.rapport_statut === 'refusé')) {
                rapportDepotSection.classList.remove('hidden'); // Afficher le formulaire de dépôt
                // Mettre à jour l'ID de la candidature dans le formulaire
                const rapportApplicationIdInput = document.getElementById('rapportApplicationId');
                if (rapportApplicationIdInput) {
                    rapportApplicationIdInput.value = currentActiveInternship.id_candidature;
                }
            } else {
                rapportDepotSection.classList.add('hidden'); // Cacher le formulaire de dépôt
            }
        }

        // Gérer la visibilité de la section de l'attestation si elle est séparée ou a besoin d'une logique complexe
        // Pour l'instant, c'est intégré dans actualInternshipDetails.innerHTML
        // Si vous avez une 'attestationSection' séparée, vous pourriez faire:
        /*
        if (attestationSection) {
            if (currentActiveInternship.attestation_url) {
                attestationSection.classList.remove('hidden');
                // Et remplir attestationSection.innerHTML ici
            } else {
                attestationSection.classList.add('hidden');
            }
        }
        */

    } else {
        noCurrentInternshipMessage.classList.remove('hidden');
        actualInternshipDetails.classList.add('hidden');
        actualInternshipDetails.innerHTML = ''; // Nettoyer les détails précédents
        
        // Cacher également le formulaire de dépôt si aucun stage actif
        if (rapportDepotSection) {
            rapportDepotSection.classList.add('hidden');
        }
        // Et l'attestationSection si elle est gérée séparément
        // if (attestationSection) { attestationSection.classList.add('hidden'); }
    }
}


            function createOfferCard(offer) {
                console.log('createOfferCard: Début de création de carte pour l\'offre:', offer);
                console.log('createOfferCard: Valeur de offer.id:', offer.id);
                console.log('createOfferCard: Type de offer.id:', typeof offer.id);

                const card = document.createElement('div');
                card.classList.add('internship-offer-card', 'card');
                card.dataset.id = offer.id_stage; 
                card.dataset.domain = offer.domaine || "general"; 

                const createdAt = offer.proposee_le ? new Date(offer.proposee_le).toLocaleDateString('fr-FR') : 'N/A';
                
                const hasApplied = appliedInternshipIds.has(offer.id_stage);
                console.log(`createOfferCard: Vérification appliedInternshipIds.has(${offer.id_stage}): ${hasApplied}`);

                const buttonText = hasApplied ? 'Déjà Postulé' : 'Postuler'; 
                const buttonClass = hasApplied ? 'applied-button' : ''; 
                const buttonDisabled = hasApplied ? 'disabled' : '';

                card.innerHTML = `
                    <h4 class="card-title">${offer.sujet}</h4>
                    <p class="card-text"><span class="font-semibold">Nom de l'Entreprise:</span> ${offer.nomentreprise || 'Inconnue'}</p>
                    <p class="card-text"><span class="font-semibold">Durée:</span> ${offer.duree} mois</p>
                    <p class="card-text"><span class="font-semibold">Niveau:</span> ${offer.niveau_requis || 'Non spécifié'}</p>
                    <p class="card-text"><span class="font-semibold">Lieu:</span> ${offer.lieu || 'Non spécifié'}</p>
                    <p class="card-text mb-4"><span class="font-semibold">Rémunération:</span> ${offer.renumeration || 'Non spécifiée'}</p>
                    <p class="card-text"><span class="font-semibold">Description:</span> ${offer.description || 'Non disponible'}</p>
                    <p class="card-text"><span class="font-semibold">Publiée le:</span> ${createdAt}</p>
                    <button class="apply-button ${buttonClass}" data-internship-id="${offer.id_stage}" ${buttonDisabled}>
                        ${buttonText}
                    </button>
                `;
                return card;
            }

            async function loadInternshipOffers() {
                noOffersMessage.textContent = "Chargement des offres...";
                noOffersMessage.classList.remove('hidden');
                internshipOffersContainer.classList.add('hidden');

                const apiUrl = `get_proposition.php`; 

                try {
                    const response = await fetch(apiUrl);
                    console.log('loadInternshipOffers: Réponse Fetch reçue.');
                    const contentType = response.headers.get("content-type");
                    console.log('loadInternshipOffers: Content-Type de la réponse:', contentType);

                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        const result = await response.json();
                        console.log('loadInternshipOffers: Données des offres reçues:', result.data);

                        if (result.success) {
                            if (result.data.length > 0) {
                                internshipOffersContainer.innerHTML = ''; 
                                result.data.forEach(offer => {
                                    console.log('loadInternshipOffers: Traitement de l\'offre ID:', offer.id, 'Sujet:', offer.sujet); 
                                    const card = createOfferCard(offer); 
                                    internshipOffersContainer.appendChild(card);
                                });
                                noOffersMessage.classList.add('hidden');
                                internshipOffersContainer.classList.remove('hidden');
                                console.log('loadInternshipOffers: Offres affichées avec succès.');
                            } else {
                                noOffersMessage.textContent = "Aucune offre de stage disponible pour le moment.";
                                noOffersMessage.classList.remove('hidden');
                                internshipOffersContainer.classList.add('hidden');
                                showMessageBox('Aucune offre de stage disponible pour le moment.', 'info');
                                console.log('loadInternshipOffers: Aucune offre trouvée.');
                            }
                        } else {
                            const errorText = await response.text();
                            console.error('Réponse non JSON de get_proposals.php:', errorText);
                            showMessageBox('Erreur: La réponse de get_proposals.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.', 'error');
                            noOffersMessage.textContent = `Erreur lors du chargement: ${errorText}. Veuillez vérifier votre serveur PHP.`; 
                            noOffersMessage.classList.remove('hidden');
                            internshipOffersContainer.classList.add('hidden');
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('Réponse non JSON de get_proposals.php:', errorText);
                        showMessageBox('Erreur: La réponse de get_proposals.php n\'est pas au format JSON valide. Vérifiez les erreurs PHP.', 'error');
                        noOffersMessage.textContent = `Erreur lors du chargement: ${errorText}. Veuillez vérifier votre serveur PHP.`; 
                        noOffersMessage.classList.remove('hidden');
                        internshipOffersContainer.classList.add('hidden');
                    }
                } catch (error) {
                    console.error("loadInternshipOffers: Erreur lors du chargement des offres:", error);
                    showMessageBox(`Erreur lors du chargement des offres: ${error.message || 'Erreur inconnue'}. Veuillez vérifier votre serveur PHP.`, 'error');
                    noOffersMessage.textContent = `Erreur lors du chargement: ${error.message || 'Erreur inconnue'}. Veuillez vérifier votre serveur PHP.`; 
                    noOffersMessage.classList.remove('hidden');
                    internshipOffersContainer.classList.add('hidden');
                }
            }

            
            
            // NOUVELLE FONCTION: Gérer la réponse de l'étudiant (accepter/refuser)
            async function handleStudentResponse(applicationId, responseType) {
                console.log(`handleStudentResponse: Tentative de réponse pour la candidature ${applicationId} avec le type: ${responseType}`);
                try {
                    const response = await fetch('reponse_offre.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            application_id: applicationId,
                            response_type: responseType,
                            student_id: '<?php echo $user_id; ?>' // Envoyer l'ID de l'étudiant pour vérification côté serveur
                        })
                    });
                    console.log('handleStudentResponse: Réponse Fetch reçue.');

                    const contentType = response.headers.get("content-type");
                    console.log('handleStudentResponse: Content-Type de la réponse:', contentType);

                    if (contentType && contentType.includes("application/json")) {
                        const result = await response.json();
                        console.log('handleStudentResponse: Réponse JSON analysée:', result);

                        if (result.success) {
                            showMessageBox(result.message, 'success');
                            await loadApplications(); // Recharger les candidatures pour mettre à jour l'affichage
                        } else {
                            const msg = result.message && result.message.trim() !== '' 
                                ? result.message 
                                : `Échec de l'enregistrement de votre réponse (${responseType}).`;
                            showMessageBox(msg, 'error');
                        }
                    } else {
                        const errorText = await response.text();
                        console.error('handleStudentResponse: Réponse non JSON de student_respond_to_offer.php:', errorText);
                        showMessageBox('Erreur: La réponse du serveur n\'est pas au format JSON valide. Vérifiez les logs PHP.', 'error');
                    }
                } catch (error) {
                    console.error("handleStudentResponse: Erreur lors de l'envoi de la réponse:", error);
                    showMessageBox(`Une erreur est survenue lors de l'enregistrement de votre réponse: ${error.message || 'Erreur inconnue'}.`, 'error');
                }
            }
            
            // LIGNES CLÉS : Gérer la soumission du formulaire de candidature
            applyFormModal.addEventListener('submit', async (event) => {
                event.preventDefault(); // <--- 1. Empêche la soumission par défaut du formulaire

                submitApplicationBtn.disabled = true;
                submitApplicationBtn.textContent = 'Envoi en cours...';

                const formData = new FormData(applyFormModal); // <--- 2. Crée un objet FormData à partir du formulaire

                try {
                    const response = await fetch('postuler_stage.php', { // <--- 3. Effectue la requête POST asynchrone
                        method: 'POST',
                        body: formData // <--- 4. Attache les données du formulaire (y compris les fichiers)
                    });

                    const result = await response.json(); // <--- 5. Parse la réponse JSON du serveur

                    if (response.ok && result.success) {
                        showMessageBox(result.message, 'success');
                        closeApplyModal(); // Fermer la modale
                        await loadApplications(); // Recharger les candidatures pour mettre à jour l'affichage
                        await loadInternshipOffers(); // Recharger les offres pour mettre à jour les boutons (déjà postulé)
                    } else {
                        throw new Error(result.message || 'Erreur lors de la soumission de la candidature.');
                    }
                } catch (error) {
                    console.error('Erreur lors de la soumission de la candidature:', error);
                    showMessageBox(error.message, 'error');
                } finally {
                    submitApplicationBtn.disabled = false;
                    submitApplicationBtn.textContent = 'Postuler';
                }
            });


             // MODIFICATION ICI : Écouteur d'événements pour les boutons "Postuler" (pour ouvrir la modale)
            internshipOffersContainer.addEventListener('click', async (event) => {
                const targetButton = event.target.closest('.apply-button');
                if (targetButton && !targetButton.disabled) {
                    const internshipId = targetButton.dataset.internshipId;
                    console.log('Bouton "Postuler" cliqué. internshipId du bouton:', internshipId);
                    if (internshipId) {
                        openApplyModal(internshipId); // Ouvre la modale
                    } else {
                        console.error('Erreur: L\'ID du stage est manquant sur le bouton cliqué.');
                        showMessageBox('Erreur interne: Impossible de récupérer l\'ID de l\'offre.', 'error');
                    }
                }
            });

           

            // NOUVEAU: Écouteur d'événements pour les boutons "Accepter" et "Refuser" des candidatures
            applicationsList.addEventListener('click', async (event) => {
                const target = event.target;
                if (target.classList.contains('student-accept-btn')) {
                    const applicationId = target.dataset.applicationId;
                    if (applicationId) {
                        await handleStudentResponse(applicationId, 'accept');
                    }
                } else if (target.classList.contains('student-reject-btn')) {
                    const applicationId = target.dataset.applicationId;
                    if (applicationId) {
                        await handleStudentResponse(applicationId, 'refuse');
                    }
                }
            });


            domainFilter.addEventListener('change', (event) => {
                const selectedDomain = event.target.value;
                const allOfferCards = internshipOffersContainer.querySelectorAll('.internship-offer-card');

                let offersFound = false;
                allOfferCards.forEach(card => {
                    const offerDomain = card.dataset.domain;
                    if (selectedDomain === 'all' || offerDomain === selectedDomain) {
                        card.style.display = 'block'; 
                        offersFound = true;
                    } else {
                        card.style.display = 'none'; 
                    }
                });

                if (!offersFound && selectedDomain !== 'all') {
                    noOffersMessage.textContent = `Aucune offre trouvée pour le domaine "${domainFilter.options[domainFilter.selectedIndex].text}".`;
                    noOffersMessage.classList.remove('hidden');
                    internshipOffersContainer.classList.add('hidden'); 
                } else if (offersFound && selectedDomain !== 'all') {
                    noOffersMessage.classList.add('hidden');
                    internshipOffersContainer.classList.remove('hidden');
                } else if (selectedDomain === 'all' && allOfferCards.length === 0) {
                    noOffersMessage.textContent = "Aucune offre de stage disponible pour le moment.";
                    noOffersMessage.classList.remove('hidden');
                    internshipOffersContainer.classList.add('hidden');
                } else {
                    noOffersMessage.classList.add('hidden');
                    internshipOffersContainer.classList.remove('hidden'); 
                }
            });

            // Ordre d'initialisation corrigé
            await loadApplications(); 
            await loadInternshipOffers(); 
            
            // La logique de displayCurrentInternship est maintenant appelée à la fin de loadApplications
            // donc ces lignes ne sont plus nécessaires ici.
            // const hasCurrentInternship = false; 
            // if (hasCurrentInternship) {
            //     noCurrentInternshipMessage.classList.add('hidden');
            //     actualInternshipDetails.classList.remove('hidden');
            // } else {
            //     noCurrentInternshipMessage.classList.remove('hidden');
            //     actualInternshipDetails.classList.add('hidden');
            // }
        });
    </script>
</body>
</html>
