-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 23 juil. 2025 à 09:23
-- Version du serveur : 5.7.33
-- Version de PHP : 7.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_stage`
--

-- --------------------------------------------------------

--
-- Structure de la table `conventions_stage`
--

CREATE TABLE `conventions_stage` (
  `id_convention` int(11) NOT NULL,
  `id_candidature` int(11) NOT NULL,
  `id_etudiant` int(11) NOT NULL,
  `id_stage` int(11) NOT NULL,
  `id_entreprise` int(11) NOT NULL,
  `date_generation` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut_convention` enum('en attente','générée','signée par étudiant','signée par entreprise','signée par enseignant','finalisée') DEFAULT 'générée',
  `chemin_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `conventions_stage`
--

INSERT INTO `conventions_stage` (`id_convention`, `id_candidature`, `id_etudiant`, `id_stage`, `id_entreprise`, `date_generation`, `statut_convention`, `chemin_pdf`) VALUES
(12, 42, 11, 11, 1004, '2025-07-20 10:50:27', 'générée', 'conventions/convention_42_20250720095027.pdf');

-- --------------------------------------------------------

--
-- Structure de la table `proposition_stage`
--

CREATE TABLE `proposition_stage` (
  `id_stage` int(11) NOT NULL,
  `id_entreprise` int(11) NOT NULL,
  `sujet` char(255) NOT NULL,
  `duree` int(11) NOT NULL,
  `date_de_debut` date DEFAULT NULL,
  `niveau_requis` varchar(255) NOT NULL,
  `lieu` char(255) DEFAULT NULL,
  `renumeration` double DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `statut` enum('active','en_cours','fermee') DEFAULT 'active',
  `proposee_le` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `proposition_stage`
--

INSERT INTO `proposition_stage` (`id_stage`, `id_entreprise`, `sujet`, `duree`, `date_de_debut`, `niveau_requis`, `lieu`, `renumeration`, `description`, `statut`, `proposee_le`) VALUES
(11, 1004, 'Telecom', 2, NULL, 'Licence', 'Calavi', 500, 'Juste une description\r\n', 'active', '2025-07-20 09:47:54');

-- --------------------------------------------------------

--
-- Structure de la table `tb_candidature`
--

CREATE TABLE `tb_candidature` (
  `id_candidature` int(11) NOT NULL,
  `id_etudiant` int(11) NOT NULL,
  `id_stage` int(11) NOT NULL,
  `date_candidature` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` varchar(255) DEFAULT 'en cours',
  `date_debut_stage` date DEFAULT NULL,
  `date_fin_stage` date DEFAULT NULL,
  `rapport_stage` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `lettre_motivation_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `tb_candidature`
--

INSERT INTO `tb_candidature` (`id_candidature`, `id_etudiant`, `id_stage`, `date_candidature`, `statut`, `date_debut_stage`, `date_fin_stage`, `rapport_stage`, `cv_path`, `lettre_motivation_path`) VALUES
(42, 11, 11, '2025-07-20 09:48:59', 'en cours', '2025-07-19', '2025-07-21', NULL, 'uploads/cv_11_11_1753004939.pdf', 'uploads/lettre_motivation_11_11_1753004939.pdf');

-- --------------------------------------------------------

--
-- Structure de la table `tb_entreprise`
--

CREATE TABLE `tb_entreprise` (
  `id_entreprise` int(11) NOT NULL,
  `nom_entreprise` char(255) NOT NULL,
  `IFU` int(11) NOT NULL,
  `ville` char(255) DEFAULT NULL,
  `email_entreprise` varchar(255) NOT NULL,
  `telephone` char(255) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `domaine_activite` char(255) DEFAULT NULL,
  `cree_le` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `jeton` varchar(255) DEFAULT NULL,
  `duree_jeton` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `tb_entreprise`
--

INSERT INTO `tb_entreprise` (`id_entreprise`, `nom_entreprise`, `IFU`, `ville`, `email_entreprise`, `telephone`, `mot_de_passe`, `domaine_activite`, `cree_le`, `jeton`, `duree_jeton`) VALUES
(1002, 'SATECHy', 124567897, NULL, 'saty@gmail.com', NULL, '$2y$10$AfYF7NzZ0XvUpiXLKKetLe9QsxeAEN7b63goB3L4BhdTYWWzzzbCa', NULL, '2025-06-26 08:52:35', NULL, NULL),
(1003, 'INFOTECH', 1111111, NULL, 'infotech@gmail.com', NULL, '$2y$10$K3WH7Rbelf3UJnm3F4Sade4QuJoEUgW3bX8l8bmCbiY6d.EVvraVG', NULL, '2025-06-26 22:48:46', NULL, NULL),
(1004, 'TATASARL', 222222222, NULL, 'tata@gmail.com', NULL, '$2y$10$nh/.AysY8qgaxLws6XtRruIqqz78Hwk5czVUHXVj9H5MuKBxvwD5S', NULL, '2025-06-27 00:17:22', NULL, NULL),
(1005, 'totosarl', 14521452, NULL, 'toto@yahoo.fr', NULL, '$2y$10$E.fdYm/wVpWEa7xJfgjmH.G6p7o9nXpPVMYq2DlGpvB9s3zZOiDUK', 'commerce', '2025-06-27 06:39:56', NULL, NULL),
(1006, 'MELYA', 22102005, NULL, 'melya@gmail.com', NULL, '$2y$10$TTiOaIRouTuNHig5wCPiBO7NsHKLEgCZKgHo3dhGFP/qEKgq3GmMi', 'Telecom', '2025-07-02 10:15:48', NULL, NULL),
(1009, 'MELYAN', 123456, NULL, 'melyan@gmail.com', NULL, '$2y$10$0ewh1O7t/atKurDm143BaO9MTyB29A//GsPZaS3tq8OOUUm3aMbTO', 'Telecom', '2025-07-14 14:27:02', NULL, NULL),
(1010, 'inessarl', 561586666, NULL, 'inessarl@gmail.com', NULL, '$2y$10$fDpZfwULX.0ohleRfqx.wuu5wXhNfXMaqAJcI.o3mOzeV46qY2a2i', 'marketing digital', '2025-07-17 07:59:09', NULL, NULL),
(1015, 'joseph', 321654987, NULL, 'joseph@gmail.com', NULL, '$2y$10$hcWPnZJRO4cApLaoraw1UOXPJa7GsstGxXi8f2n.9iTvUA8hZABsO', 'comme', '2025-07-17 08:08:48', NULL, NULL),
(1016, 'EPAC', 753951, NULL, 'epac@gmail.com', NULL, '$2y$10$o26QpS1mtHxDHD8Je7fUt.IdJQJtz3X93I2oEZEKFwn.K1KhlAaYm', 'Telecom', '2025-07-17 08:46:16', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `tb_etudiant`
--

CREATE TABLE `tb_etudiant` (
  `id_etudiant` int(11) NOT NULL,
  `nom` char(255) NOT NULL,
  `prenom` char(255) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `email` char(255) NOT NULL,
  `mot_de_passe` char(255) NOT NULL,
  `niveau_etudes` char(155) DEFAULT NULL,
  `specialite` char(255) DEFAULT NULL,
  `cree_le` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nom_utilisateur` varchar(255) NOT NULL,
  `jeton` varchar(255) DEFAULT NULL,
  `duree_jeton` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `tb_etudiant`
--

INSERT INTO `tb_etudiant` (`id_etudiant`, `nom`, `prenom`, `date_naissance`, `email`, `mot_de_passe`, `niveau_etudes`, `specialite`, `cree_le`, `nom_utilisateur`, `jeton`, `duree_jeton`) VALUES
(3, 'EZIN', 'Mélina', '2005-01-01', 'homshop74@gmail.com', '$2y$10$xMmuE4ZgmyoYlMDMd1/spup7rM8Z/suUcbN/5xADoOJOSy2x7MlEa', NULL, NULL, '2025-06-25 23:20:38', 'mezin', NULL, NULL),
(5, 'MEDAGBE', 'Francois', '2007-05-14', 'franc@gmail.com', '$2y$10$WRUV7n.kEddfFoqYtV.OH.pGItrufMWdSBVEUtWuAh.11Ou3xwdYG', NULL, NULL, '2025-06-25 23:47:04', 'fmedagbe', '68f7c0c3187674cffdb58b1b89230fd04d2d3fbbb15f63db16a72780d654bba2', '2025-07-10 09:29:15'),
(6, 'BOKO', 'Daniel', '2025-06-26', 'daniel@gmail.com', '$2y$10$nrA8wqftjfQgATo1shPNAuop.HvKX9yufx9eFGl/lXlZA5XTwfuhu', NULL, NULL, '2025-06-26 08:08:37', 'dboko', NULL, NULL),
(8, 'AGBOMAKOU', 'Dayan', '2008-05-14', 'dayan@gmail.com', '$2y$10$GAofxkxUjh8JC8oQTYChhO0ms6CHwG.l9MEpgPPZrn3AKESRg6wKG', NULL, NULL, '2025-06-27 08:59:45', 'dagbomakou', 'af5df4abf39e681e5604a52af7089ca7b12c96e27ee40ed58fb8f880efed6469', '2025-07-09 13:21:15'),
(9, 'BOSSOU', 'Jean', '2006-02-01', 'bossou@gmail.com', '$2y$10$lIhklgNUJdqgrNtOFQNlLO4TOPLokXkQ7I09pWzU0Af8hNgvJf90m', NULL, NULL, '2025-07-10 08:23:21', 'jbossou', NULL, NULL),
(10, 'TONY', 'Stark', '2005-01-01', 'stark@gmail.com', '$2y$10$AWvnDW5H0F616KuWqhXE0O7aRb/WBGxeXNo3OqpT.tlpnt6ArBL/m', NULL, NULL, '2025-07-11 05:33:12', 'stony', NULL, NULL),
(11, 'EZIN', 'Hilaris', '2001-02-18', 'hilas@gmail.com', '$2y$10$qSQLGGGss5/O1qD3IJEz0uTnC0zaQSUSy7ZMkgyJ1xWsGCireN86y', NULL, NULL, '2025-07-11 05:47:51', 'hezin', NULL, NULL),
(12, 'VIHO', 'Diane', '1982-04-16', 'diane@gmail.com', '$2y$10$qqWYv9ltzY7.N9XMNg/Bw.XlTBhczGegnsJa2pXBeY95WXg/Axi9S', NULL, NULL, '2025-07-17 08:13:16', 'dviho', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `conventions_stage`
--
ALTER TABLE `conventions_stage`
  ADD PRIMARY KEY (`id_convention`),
  ADD UNIQUE KEY `id_candidature` (`id_candidature`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_stage` (`id_stage`),
  ADD KEY `id_entreprise` (`id_entreprise`);

--
-- Index pour la table `proposition_stage`
--
ALTER TABLE `proposition_stage`
  ADD PRIMARY KEY (`id_stage`),
  ADD KEY `proposeindex` (`id_entreprise`);

--
-- Index pour la table `tb_candidature`
--
ALTER TABLE `tb_candidature`
  ADD PRIMARY KEY (`id_candidature`),
  ADD UNIQUE KEY `id_etudiant` (`id_etudiant`,`id_stage`),
  ADD KEY `idx_student_id` (`id_etudiant`),
  ADD KEY `idx_internship_id` (`id_stage`),
  ADD KEY `idx_status` (`statut`);

--
-- Index pour la table `tb_entreprise`
--
ALTER TABLE `tb_entreprise`
  ADD PRIMARY KEY (`id_entreprise`),
  ADD UNIQUE KEY `IFU` (`IFU`),
  ADD UNIQUE KEY `email_entreprise` (`email_entreprise`),
  ADD UNIQUE KEY `mot_de_passe` (`mot_de_passe`),
  ADD UNIQUE KEY `telephone` (`telephone`),
  ADD UNIQUE KEY `jeton` (`jeton`);

--
-- Index pour la table `tb_etudiant`
--
ALTER TABLE `tb_etudiant`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `mot_de_passe` (`mot_de_passe`),
  ADD UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`),
  ADD UNIQUE KEY `jeton` (`jeton`),
  ADD KEY `etudiant_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `conventions_stage`
--
ALTER TABLE `conventions_stage`
  MODIFY `id_convention` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `proposition_stage`
--
ALTER TABLE `proposition_stage`
  MODIFY `id_stage` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `tb_candidature`
--
ALTER TABLE `tb_candidature`
  MODIFY `id_candidature` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `tb_entreprise`
--
ALTER TABLE `tb_entreprise`
  MODIFY `id_entreprise` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1017;

--
-- AUTO_INCREMENT pour la table `tb_etudiant`
--
ALTER TABLE `tb_etudiant`
  MODIFY `id_etudiant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `conventions_stage`
--
ALTER TABLE `conventions_stage`
  ADD CONSTRAINT `conventions_stage_ibfk_1` FOREIGN KEY (`id_candidature`) REFERENCES `tb_candidature` (`id_candidature`) ON DELETE CASCADE,
  ADD CONSTRAINT `conventions_stage_ibfk_2` FOREIGN KEY (`id_etudiant`) REFERENCES `tb_etudiant` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `conventions_stage_ibfk_3` FOREIGN KEY (`id_stage`) REFERENCES `proposition_stage` (`id_stage`) ON DELETE CASCADE,
  ADD CONSTRAINT `conventions_stage_ibfk_4` FOREIGN KEY (`id_entreprise`) REFERENCES `tb_entreprise` (`id_entreprise`) ON DELETE CASCADE;

--
-- Contraintes pour la table `proposition_stage`
--
ALTER TABLE `proposition_stage`
  ADD CONSTRAINT `proposition_stage_ibfk_1` FOREIGN KEY (`id_entreprise`) REFERENCES `tb_entreprise` (`id_entreprise`);

--
-- Contraintes pour la table `tb_candidature`
--
ALTER TABLE `tb_candidature`
  ADD CONSTRAINT `tb_candidature_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `tb_etudiant` (`id_etudiant`),
  ADD CONSTRAINT `tb_candidature_ibfk_2` FOREIGN KEY (`id_stage`) REFERENCES `proposition_stage` (`id_stage`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
