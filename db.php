<?php
    $host = 'localhost';
    $dbname = 'gestion_stage'; // Nom de votre base de données : assurez-vous que c'est bien ce nom dans Laragon
    $user = 'root';
    $pass = ''; // Mot de passe vide par défaut pour Laragon

    try
    {
        // Renommé $mysqlClient en $pdo pour la cohérence avec les autres scripts
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",$user,$pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active le mode d'erreur pour les exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Récupère les résultats sous forme de tableau associatif
                PDO::ATTR_EMULATE_PREPARES => false // Désactive l'émulation des requêtes préparées pour une meilleure sécurité
            ]
        );
    }
    catch(Exception $e)
    {
        // En cas d'erreur de connexion, arrête le script et affiche le message d'erreur
        die('Erreur de connexion à la base de données: '.$e->getMessage());
    }   
?>
