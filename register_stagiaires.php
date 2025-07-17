<?php
// Inclure le fichier de connexion à la base de données PDO.
require 'db.php'; 

// Initialiser le tableau pour la réponse JSON
$response_data = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Vérifier si la requête est de type POST (soumission du formulaire).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et filtrer les données du formulaire.
    $nom = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, 'userprename', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'usermail', FILTER_SANITIZE_EMAIL);
    $date_naissance = filter_input(INPUT_POST, 'datenaissance', FILTER_SANITIZE_STRING);
    $mot_de_passe = $_POST['password'] ?? ''; 
    $confirmed_mot_de_passe = $_POST['confirmed_password'] ?? '';

    // --- Validation des données (côté serveur) ---

    // Vérifier si les mots de passe correspondent.
    if ($mot_de_passe !== $confirmed_mot_de_passe) {
        $response_data['errors']['mdp'] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier si tous les champs obligatoires sont remplis.
    // Utilisation de trim pour s'assurer que les champs ne sont pas juste des espaces.
    if (empty(trim($nom)) || empty(trim($prenom)) || empty(trim($email)) || empty(trim($date_naissance)) || empty($mot_de_passe) || empty($confirmed_mot_de_passe)) {
        $response_data['errors']['all_not_null'] = "Tous les champs sont obligatoires.";
    }

    // Valider le format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response_data['errors']['email_format'] = "L'adresse e-mail n'est pas valide.";
    }

    // Si aucune erreur de validation initiale, procéder à l'insertion.
    if (empty($response_data['errors'])) {
        // Hachage du mot de passe (STANDARD et SÉCURISÉ).
        $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
      
        try {
            // --- VERIFICATION D'UNICITÉ DE L'EMAIL ---
            $stmt_check_email = $pdo->prepare("SELECT id_etudiant FROM tb_etudiant WHERE email = ?");
            $stmt_check_email->execute([$email]);
            $student_exists = $stmt_check_email->fetch();

            if ($student_exists) {
                // Si l'e-mail existe déjà, définir une erreur.
                $response_data['errors']['email_exists'] = "Cette adresse e-mail est déjà utilisée par un autre étudiant.";
            } else {
                // --- Génération et vérification de l'unicité du nom d'utilisateur ---
                // Nettoyage plus robuste des caractères spéciaux pour le nom d'utilisateur
                $base_generated_username = strtolower(substr($prenom, 0, 1) . preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '', $nom)));
                
                $generated_username = $base_generated_username;
                $counter = 1;
                
                while (true) {
                    $stmt_check_username = $pdo->prepare("SELECT id_etudiant FROM tb_etudiant WHERE nom_utilisateur = ?");
                    $stmt_check_username->execute([$generated_username]);
                    $username_exists = $stmt_check_username->fetch();

                    if (!$username_exists) {
                        break; // Nom d'utilisateur unique trouvé
                    } else {
                        $generated_username = $base_generated_username . $counter;
                        $counter++;
                    }
                }
                
                // --- INSERTION DU NOUVEL ÉTUDIANT ---
                $stmt_student = $pdo->prepare("
                    INSERT INTO tb_etudiant (nom, prenom, email, mot_de_passe, date_naissance, nom_utilisateur)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $success_insert = $stmt_student->execute([
                    $nom,
                    $prenom,
                    $email,
                    $mot_de_passe_hache,
                    $date_naissance,
                    $generated_username,
                ]);

                if ($success_insert) {
                    $response_data['success'] = true;
                    $response_data['message'] = "Inscription réussie ! Votre nom d'utilisateur est : " . htmlspecialchars($generated_username) . ". Vous pouvez maintenant vous connecter.";
                    // On ne redirige plus ici pour que le JS puisse afficher le message
                    // et éventuellement rediriger après un délai.
                } else {
                    $response_data['errors']['insert_failed'] = "Erreur lors de l'enregistrement de l'étudiant. Veuillez réessayer.";
                }
            }
      
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // SQLSTATE pour les violations de contrainte d'unicité.
                $response_data['errors']['db_error'] = "Erreur: Une contrainte d'unicité a été violée (ex: e-mail déjà enregistré).";
            } else {
                $response_data['errors']['db_error'] = "Erreur de base de données : " . $e->getMessage();
            }
            error_log("Erreur PDO lors de l'inscription étudiant: " . $e->getMessage()); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'inscription sur la plateforme de gestion des stages</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* Styles pour les messages d'alerte */
        .message-container {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .message-container.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-container.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="Header header-blue">
        <div class="contenu header-content">
            <h1 class="header-title">Plateforme de Gestion des Stages</h1>
            <nav class="header-nav">
                <a href="page_accueil.html" class="nav-link">Accueil</a> 
            </nav>
        </div>
    </header>

    <main class="contenu main-content">
        <h2 class="login-title">Inscription Étudiant</h2>
        <section class="form-section card">
            <form action="" method="POST" class="stage-form">
                <div class="mb-4">
                    <label for="username" class="form-label">Nom :</label>
                    <input 
                        type='text'
                        id='username'
                        name="username"
                        placeholder="Bossou"
                        class='form-input'
                        required
                        value="<?php echo htmlspecialchars($nom ?? ''); ?>"
                    >
                </div>
                <div class="mb-4">
                    <label for="userprename" class="form-label">Prénom :</label>
                    <input 
                        type='text'
                        id='userprename'
                        name="userprename"
                        placeholder="Jacques"
                        class='form-input'
                        required
                        value="<?php echo htmlspecialchars($prenom ?? ''); ?>"
                    >
                </div>
                <div class="mb-4">
                    <label for="datenaissance" class="form-label">Date de naissance :</label>
                    <input 
                        type='date'
                        id='datenaissance'
                        name="datenaissance"
                        class='form-input'
                        required
                        value="<?php echo htmlspecialchars($date_naissance ?? ''); ?>"
                    >
                </div>
                <div class="mb-4">
                    <label for="usermail" class="form-label">Adresse mail :</label>
                    <input 
                        type='email' 
                        id='usermail'
                        name="usermail"
                        placeholder="exemple@domaine.com"
                        class='form-input'
                        required
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                    >
                </div>

                <div class="mb-6">
                    <label for="password" class="form-label">Mot de passe :</label>
                    <input 
                        type='password'
                        id='password' 
                        name="password"
                        placeholder="Mot de passe"
                        class='form-input'
                        required
                    >
                </div>
                <div class="mb-6">
                    <label for="confirmed_password" class="form-label">Confirmez votre mot de passe :</label>
                    <input 
                        type='password'
                        id='confirmed_password' 
                        name="confirmed_password"
                        placeholder="Confirmez le mot de passe"
                        class='form-input'
                        required
                    >
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-purple">
                        S'inscrire
                    </button>
                    <a href="page_accueil.html" class="link-text purple"> 
                        Annuler
                    </a>
                </div>
                <div id="status_message" class="message-container mt-4 text-center"></div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 EPAC. Tous droits réservés.</p>
    </footer>

    <script>
        // Récupérer l'élément HTML pour afficher les messages.
        let messageDiv = document.getElementById("status_message");

        // Convertir les données de réponse PHP en objet JavaScript.
        const responseData = <?php echo json_encode($response_data); ?>;

        // Afficher les messages d'erreur ou de succès.
        if (Object.keys(responseData.errors).length > 0) { 
            let errorMessage = "";
            for (let key in responseData.errors) {
                errorMessage += responseData.errors[key] + "<br>"; 
            }
            messageDiv.innerHTML = errorMessage;
            messageDiv.classList.add('error');
            messageDiv.classList.remove('success');
        } else if (responseData.success) { 
            messageDiv.textContent = responseData.message;
            messageDiv.classList.add('success');
            messageDiv.classList.remove('error');

            // Optionnel : rediriger après quelques secondes en cas de succès.
            // Si tu veux rediriger immédiatement, déplace le header("Location") dans le PHP.
            setTimeout(() => {
                window.location.href = 'login_etudiant.php'; 
            }, 3000); // Redirige après 3 secondes
        }
        
        // Optionnel : masquer le message d'erreur après quelques secondes (pas le succès s'il y a redirection).
        if (!responseData.success && messageDiv.textContent !== "") {
            setTimeout(() => {
                messageDiv.textContent = "";
                messageDiv.classList.remove('error'); // Retire la classe d'erreur
            }, 5000); // Le message disparaîtra après 5 secondes.
        }
    </script>
</body>
</html>