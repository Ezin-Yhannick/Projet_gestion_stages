<?php
// Démarre la session PHP
session_start();

// Inclure le gestionnaire de session et le fichier de connexion à la base de données PDO.
require 'session_manager.php'; 
require 'db.php'; 

// Rediriger si l'utilisateur n'est pas connecté ou si ce n'est pas un étudiant
if (!is_logged_in() || get_user_role() !== 'company') {
    header("Location: login_entreprise.php?status_type=error&status_message=" . urlencode("Accès non autorisé. Veuillez vous connecter en tant qu'entreprise."));
    exit();
}

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = get_user_id();

// Initialiser les variables de profil
$entreprise_data = null;
$message = '';
$message_type = '';

try {
    // Récupérer les informations complètes de l'étudiant depuis la base de données
    $stmt = $pdo->prepare("SELECT * FROM tb_entreprise WHERE id_entreprise = ?");
    $stmt->execute([$user_id]);
    $entreprise_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entreprise_data) {
        $message = "Impossible de récupérer les informations de votre profil.";
        $message_type = "error";
    }

} catch (PDOException $e) {
    $message = "Erreur de base de données lors de la récupération du profil: " . $e->getMessage();
    $message_type = "error";
    error_log("Erreur PDO etudiant_profile: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Mon Entreprise  </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Espace Entreprise</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="tableau_de_bord_entreprise.php" class="nav-link">Tableau de bord </a>
                <a href="logout.php" class="nav-link">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <h2 class="page-title">Mon Entreprise</h2>

        <?php if ($message): ?>
            <div class="info-message <?php echo $message_type === 'error' ? 'text-red-600' : 'text-green-600'; ?> mb-4">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($entreprise_data): ?>
            <section class="card profile-card">
                <h3 class="section-title">Informations Professionnelles</h3>
                <p><strong>Nom de l'Entreprise :</strong> <?php echo htmlspecialchars($entreprise_data['nom_entreprise']); ?></p>
                <p><strong>IFU:</strong> <?php echo htmlspecialchars($entreprise_data['IFU']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($entreprise_data['email_entreprise']); ?></p>
                <p><strong>Ville de résidence de l'entreprise:</strong> <?php echo htmlspecialchars($entreprise_data['ville']); ?></p>
                <p><strong>Domaine d'activité:</strong> <?php echo htmlspecialchars($entreprise_data['domaine_activite'] ?: 'Non spécifié'); ?></p>
                <p><strong>Date d'inscription:</strong> <?php echo htmlspecialchars((new DateTime($entreprise_data['cree_le']))->format('d/m/Y H:i')); ?></p>
                
                <div class="mt-6">
                    <button >Modifier Profil</button>
                    <!-- Ajoutez d'autres actions ici si nécessaire -->
                </div>
            </section>
        <?php else: ?>
            <p class="info-message">Aucune information de profil trouvée.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Université [Nom de l'université]. Tous droits réservés.</p>
    </footer>
</body>
</html>
