<?php
// Démarre la session PHP. Ceci est crucial pour pouvoir manipuler les variables de session.
// Si session_manager.php n'inclut pas déjà session_start(), il est important de l'avoir ici.
session_start();

// Inclure le gestionnaire de session pour utiliser la fonction logout().
// Assurez-vous que le chemin vers session_manager.php est correct.
require 'session_manager.php';

// Appelle la fonction de déconnexion définie dans session_manager.php.
// Cette fonction vide toutes les variables de session et détruit la session.
logout();

// Redirige l'utilisateur vers la page d'accueil ou la page de connexion après la déconnexion.
// Vous pouvez choisir l'une des options suivantes :
// Option 1 : Rediriger vers la page d'accueil générale (index.html)
header("Location: page_accueil.html");
exit(); // Très important : arrêter l'exécution du script après la redirection.

// Option 2 : Rediriger vers une page de connexion spécifique si vous voulez forcer une nouvelle connexion
// (Décommentez l'une et commentez l'autre selon votre besoin)
// header("Location: login_etudiant.php"); // Ou login_entreprise.php, login_enseignant.php
// exit();
?>
