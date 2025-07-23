Pack d'Installation de la Plateforme de Stages
1. Bienvenue et Prérequis
Bienvenue dans le guide d'installation de votre plateforme de gestion de stages !
Pour commencer, assurez-vous d'avoir les éléments suivants installés sur votre système :
•	Serveur Web : Apache (recommandé) ou Nginx. Utilisez Laragon, XAMPP, WAMP ou MAMP pour une installation facile.
•	PHP : Version 7.4 ou supérieure.
•	Base de Données : MySQL ou MariaDB.
•	Composer : Pour la gestion des dépendances PHP (Télécharger Composer).
•	Git : Si vous clonez le projet depuis un dépôt (Télécharger Git).
Si vous n'avez pas ces outils, veuillez les installer avant de continuer.
2. Téléchargement du Projet
Vous pouvez obtenir le code source de la plateforme de deux manières :
Option A : Télécharger l'archive du projet
Cliquez sur le bouton ci-dessous pour télécharger la dernière version du projet sous forme d'archive ZIP. Une fois téléchargé, décompressez-le dans le répertoire htdocs (XAMPP/WAMP) ou www (MAMP) de votre serveur web.
Prendre le fichier zip associé
Option B : Cloner depuis un dépôt Git
Si le projet est hébergé sur un dépôt Git (GitHub, GitLab, etc.), ouvrez votre terminal et naviguez vers le répertoire de votre serveur web (par exemple, C:\xampp\htdocs).
cd C:\xampp\htdocs # ou votre chemin
git clone <URL_DU_DEPOT_GIT> nom_de_votre_dossier_projet
cd nom_de_votre_dossier_projet
Remplacez <URL_DU_DEPOT_GIT> : https://github.com/Ezin-Yhannick/Projet_gestion_stages par l'URL réelle de votre dépôt et nom_de_votre_dossier_projet par le nom de votre choix.
3. Installation des Dépendances PHP
Si votre projet utilise Composer pour gérer les dépendances PHP (ce qui est très courant pour les frameworks modernes), vous devez les installer.
1.	Ouvrez votre terminal ou invite de commande.
2.	Naviguez jusqu'au répertoire racine de votre projet (là où se trouve le fichier composer.json).
3.	Exécutez la commande suivante :
composer install
Cette commande téléchargera et installera toutes les bibliothèques PHP nécessaires à votre plateforme.
4. Configuration de la Base de Données
La plateforme a besoin d'une base de données pour fonctionner. Suivez ces étapes :
1.	Assurez-vous que votre serveur MySQL (ou MariaDB) est démarré (via XAMPP/WAMP/MAMP).
2.	Ouvrez votre navigateur et accédez à phpMyAdmin.
3.	Dans phpMyAdmin, cliquez sur l'onglet "Bases de données" et créez une nouvelle base de données (par exemple, db_stages).
4.	Sélectionnez la base de données que vous venez de créer.
5.	Cliquez sur l'onglet "Importer", puis "Choisir un fichier" et sélectionnez le fichier .sql de votre projet (il se trouve souvent dans un dossier database ou sql). Cliquez sur "Exécuter".
Ce fichier .sql contient la structure de la base de données et les données initiales.
5. Accès à la Plateforme
Félicitations ! Votre plateforme est maintenant installée.
1.	Assurez-vous que votre serveur web (Apache/Nginx) et votre serveur de base de données (MySQL) sont bien démarrés.
2.	Ouvrez votre navigateur web.
3.	Accédez à l'URL de votre plateforme :
http://localhost/nom_de_votre_dossier_projet
Remplacez nom_de_votre_dossier_projet par le nom du dossier où vous avez placé les fichiers de la plateforme (par exemple, http://localhost/gestion-stages).
Votre plateforme de gestion de stages devrait maintenant être accessible !
Ensuite accéder à a seesion àpropos de la page d'acceuil pour voir comment utiliser le site 

