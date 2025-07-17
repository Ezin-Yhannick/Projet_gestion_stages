<?php
// Démarre la session PHP
session_start();

// Inclure le gestionnaire de session et le fichier de connexion à la base de données PDO.
require 'session_manager.php'; 
require 'db.php'; 

// Rediriger si l'utilisateur n'est pas connecté ou si ce n'est pas un étudiant
if (!is_logged_in() || get_user_role() !== 'student') {
    header("Location: login_etudiant.php?status_type=error&status_message=" . urlencode("Accès non autorisé. Veuillez vous connecter en tant qu'étudiant."));
    exit();
}

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = get_user_id();

// Initialiser les variables de profil
$student_data = null;
$message = '';
$message_type = '';

try {
    // Récupérer les informations complètes de l'étudiant depuis la base de données
    $stmt = $pdo->prepare("SELECT * FROM tb_etudiant WHERE id_etudiant = ?");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_data) {
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
    <title> Mon profil  </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="Header">
        <div class="contenu header">
            <h1 class="header-title">Espace Étudiant</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a>
                <a href="tableau_de_bord_etudiant.php" class="nav-link">Tableau de Bord</a>
                <a href="logout.php" class="nav-link">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <h2 class="page-title">Mon Profil</h2>

        <?php if ($message): ?>
            <div class="info-message <?php echo $message_type === 'error' ? 'text-red-600' : 'text-green-600'; ?> mb-4">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($student_data): ?>
            <section class="card profile-card">
                <h3 class="section-title">Informations Personnelles</h3>
                <p><strong>Nom d'utilisateur:</strong> <?php echo htmlspecialchars($student_data['nom_utilisateur']); ?></p>
                <p><strong>Nom:</strong> <?php echo htmlspecialchars($student_data['nom']); ?></p>
                <p><strong>Prénom:</strong> <?php echo htmlspecialchars($student_data['prenom']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student_data['email']); ?></p>
                <p><strong>Date de Naissance:</strong> <?php echo htmlspecialchars($student_data['date_naissance']); ?></p>
                <p><strong>Niveau d'études:</strong> <?php echo htmlspecialchars($student_data['niveau_etudes'] ?: 'Non spécifié'); ?></p>
                <p><strong>Spécialité:</strong> <?php echo htmlspecialchars($student_data['specialite'] ?: 'Non spécifiée'); ?></p>
                <p><strong>Date d'inscription:</strong> <?php echo htmlspecialchars((new DateTime($student_data['cree_le']))->format('d/m/Y H:i')); ?></p>
                
                <div class="mt-6">
                    <button class=" btn-primary">Modifier Profil</button>
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
