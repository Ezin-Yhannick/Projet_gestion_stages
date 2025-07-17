<?php
// Démarre la session PHP. Ceci doit être la toute première chose sur la page avant tout HTML ou autre sortie.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si un utilisateur est connecté.
 * @return bool True si un utilisateur est connecté, false sinon.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Récupère le rôle de l'utilisateur connecté.
 * @return string|null Le rôle de l'utilisateur ('student', 'company', 'teacher') ou null si non connecté.
 */
function get_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Récupère l'ID de l'utilisateur connecté.
 * @return string|null L'ID de l'utilisateur ou null si non connecté.
 */
function get_user_id(): ?string {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Détruit la session de l'utilisateur (déconnexion).
 */
function logout(): void {
    $_SESSION = array(); // Efface toutes les variables de session
    session_destroy(); // Détruit la session
    // Optionnel: rediriger l'utilisateur vers la page d'accueil ou de connexion
    // header("Location: index.html");
    // exit();
}
?>
        