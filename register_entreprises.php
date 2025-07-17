<?php
// Inclure la connexion à la base de données.
require 'db.php';

// Variables pour les messages de succès ou d'erreur
$message = '';
$message_type = ''; // 'success' ou 'error'

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $nom = trim($_POST['username'] ?? '');
    $ifu = trim($_POST['company-ifu'] ?? '');
    $email = filter_input(INPUT_POST, 'usermail', FILTER_SANITIZE_EMAIL);
    $mot_de_passe = $_POST['password'] ?? '';
    $confirmer_mot_de_passe = $_POST['confirm_password'] ?? ''; // Nouveau champ pour la confirmation

    // --- Validation côté serveur ---
    if (empty($nom) || empty($ifu) || empty($email) || empty($mot_de_passe) || empty($confirmer_mot_de_passe)) {
        $message = "Veuillez remplir tous les champs.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format d'adresse email invalide.";
        $message_type = 'error';
    } elseif ($mot_de_passe !== $confirmer_mot_de_passe) {
        $message = "Les mots de passe ne correspondent pas.";
        $message_type = 'error';
    } else {
        // Hacher le mot de passe
        $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $activite = trim($_POST['domaine_activite'] ?? '');

        try {
            // --- Vérifier si l'entreprise existe déjà (par email ou IFU) ---
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tb_entreprise WHERE email_entreprise = ? OR IFU = ?");
            $stmt_check->execute([$email, $ifu]);
            $exists = $stmt_check->fetchColumn();

            if ($exists > 0) {
                $message = "Une entreprise avec cet email ou cet IFU existe déjà.";
                $message_type = 'error';
            } else {
                // --- Insérer la nouvelle entreprise ---
                // Correction de la ligne 13 et des arguments : un seul mot de passe haché est utilisé.
                $statement = $pdo->prepare("INSERT INTO tb_entreprise(nom_entreprise, IFU, domaine_activite, email_entreprise, mot_de_passe) VALUES(?, ?, ?, ?, ?)");
                $success = $statement->execute([$nom, $ifu, $activite, $email, $mot_de_passe_hache]);

                if ($success) {
                    // Redirection en cas de succès, après avoir défini un message flash
                    $_SESSION['message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    $_SESSION['message_type'] = 'success';
                    header("Location: login_entreprise.php"); // Ou une page de connexion
                    exit();
                } else {
                    $message = "Erreur lors de l'inscription. Veuillez réessayer.";
                    $message_type = 'error';
                    // Log the PDO error if needed for debugging
                    // error_log("PDO Error: " . print_r($statement->errorInfo(), true));
                }
            }
        } catch (PDOException $e) {
            $message = "Une erreur de base de données est survenue : " . $e->getMessage();
            $message_type = 'error';
            // Log l'erreur pour le débogage
            error_log("Erreur PDO lors de l'inscription: " . $e->getMessage());
        }
    }
}

// Récupérer les messages flash après redirection (si tu utilises les sessions pour cela)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success'; // Par défaut succès si non défini
    unset($_SESSION['message']); // Supprimer le message après l'avoir affiché
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'inscription sur la plateforme des gestion des stages</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles pour les messages d'alerte */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Plateforme de Gestion des Stages</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
            </nav>
        </div>
    </header>

    <main class="contenu login">
        <h2 class="login-title">Inscription entreprise</h2>
        <section class="form-section card">
            <?php if (!empty($message)): // Afficher le message si non vide ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="#" method="POST" class="stage-form">
                <div class="mb-4">
                    <label for="username" class="form-label">Nom de votre entreprise :</label>
                    <input
                        type = 'text'
                        id ='username'
                        name = 'username'
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="mb-4">
                    <label for="domaine_activite" class="form-label">Précisez le domaine d'activité :</label>
                    <input
                        type = 'text'
                        id ='domaine_activite'
                        name = 'domaine_activite'
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="mb-4">
                    <label for="company-ifu" class="form-label">IFU :</label>
                    <input
                        type = 'text'
                        id ='company-ifu'
                        name = 'company-ifu'
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="mb-4">
                    <label for="usermail" class="form-label">Votre mail :</label>
                    <input
                        type = 'email'
                        id ='usermail'
                        name = 'usermail'
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="mb-6">
                    <label for="password" class="form-label">Mot de passe :</label>
                    <input
                        type = 'password'
                        id ='password'
                        name = 'password'
                        placeholder="Mot de passe"
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="form-label">Confirmez votre mot de passe :</label>
                    <input
                        type = 'password'
                        id ='confirm_password'
                        name = 'confirm_password' placeholder="Confirmer le mot de passe"
                        class = 'form-input'
                        required
                    >
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-purple">
                        S'inscrire
                    </button>
                    <a href="page_accueil.html" class="link-text purple"> Annuler
                    </a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 EPAC. Tous droits réservés.</p>
    </footer>
</body>
</html>