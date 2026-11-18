-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- HÃ´te : 127.0.0.1
-- GÃ©nÃ©rÃ© le : mar. 13 oct. 2026 Ã  09:05
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12
--
-- ---------------------------------------------------------------------
-- BASE DE DÃ‰MONSTRATION ANONYMISÃ‰E (version finale du schÃ©ma)
-- Comptes de dÃ©monstration (mots de passe connus, Ã€ CHANGER en production) :
--   Administration (/admin) : admin.demo / Admin-Demo-2026
--                             admin2.demo / Admin-Demo-2026
--                             prof.demo  / Prof-Demo-2026 (rÃ´le professeur)
--   Espace Ã©quipes (/)      : sio-transports / Equipe-Demo-2026
--                             pmf-transports / Equipe-Demo-2026
-- ---------------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de donnÃ©es : `challenge_gtla`
--

-- --------------------------------------------------------

--
-- Structure de la table `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL,
  `nom_equipe` varchar(50) NOT NULL,
  `login_equipe` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `id_lycee` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `equipes`
--


-- --------------------------------------------------------

--
-- Structure de la table `lycees`
--

CREATE TABLE `lycees` (
  `id_lycee` int(11) NOT NULL,
  `nom_lycee` varchar(100) NOT NULL,
  `ville` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `lycees`
--


-- --------------------------------------------------------

--
-- Structure de la table `ref_carrosseries`
--

CREATE TABLE `ref_carrosseries` (
  `id_carrosserie` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `ref_carrosseries`
--


-- --------------------------------------------------------

--
-- Structure de la table `ref_departements`
--

CREATE TABLE `ref_departements` (
  `code_dept` varchar(3) NOT NULL,
  `nom_dept` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `ref_departements`
--


-- --------------------------------------------------------

--
-- Structure de la table `ref_energies`
--

CREATE TABLE `ref_energies` (
  `id_energie` int(11) NOT NULL,
  `libelle` varchar(30) NOT NULL,
  `cout_km` decimal(6,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `ref_energies`
--


-- --------------------------------------------------------

--
-- Structure de la table `ref_types_vehicule`
--

CREATE TABLE `ref_types_vehicule` (
  `id_type` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `ref_types_vehicule`
--


-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `identifiant` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('professeur','administrateur') NOT NULL DEFAULT 'professeur',
  `id_lycee` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `utilisateurs`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_affretements`
--

CREATE TABLE `zl_affretements` (
  `id_affretement` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `id_equipe_affreteur` int(11) NOT NULL,
  `id_equipe_affrete` int(11) NOT NULL,
  `prix_affretement` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
  `date_declaration` datetime NOT NULL DEFAULT current_timestamp(),
  `date_reponse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_affretements`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_config_parametres`
--

CREATE TABLE `zl_config_parametres` (
  `id_config` int(11) NOT NULL,
  `vitesse_moyenne_kmh` int(11) NOT NULL DEFAULT 75,
  `temps_chargement_h` decimal(3,2) NOT NULL DEFAULT 1.00,
  `temps_dechargement_h` decimal(3,2) NOT NULL DEFAULT 1.00,
  `temps_pause_h` decimal(3,2) NOT NULL DEFAULT 0.75,
  `repos_journalier_min_h` decimal(4,2) NOT NULL DEFAULT 9.00,
  `heure_prise_service_defaut` decimal(4,2) NOT NULL DEFAULT 5.00,
  `date_application` date NOT NULL,
  `date_debut_challenge_zl` date DEFAULT curdate(),
  `date_fin_challenge_zl` date DEFAULT NULL,
  `penalite_jour_non_roule` decimal(10,2) NOT NULL DEFAULT 0.00,
  `malus_infraction_conduite_repos` decimal(7,2) NOT NULL DEFAULT 50.00,
  `malus_rechargement_hors_zone` decimal(7,2) NOT NULL DEFAULT 50.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_config_parametres`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_departements_limitrophes`
--

CREATE TABLE `zl_departements_limitrophes` (
  `code_dept` varchar(3) NOT NULL,
  `code_dept_limitrophe` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_departements_limitrophes`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_feuilles_otea`
--

CREATE TABLE `zl_feuilles_otea` (
  `id_feuille` int(11) NOT NULL,
  `id_equipe` int(11) NOT NULL,
  `id_vehicule` int(11) NOT NULL,
  `date_debut_semaine` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_feuilles_otea`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_lignes_activite`
--

CREATE TABLE `zl_lignes_activite` (
  `id_ligne` int(11) NOT NULL,
  `id_feuille` int(11) NOT NULL,
  `id_ot` int(11) NOT NULL,
  `ordre_chronologique` int(11) NOT NULL,
  `date_activite` date NOT NULL,
  `horaire_debut` decimal(4,2) NOT NULL,
  `type_activite` enum('Prise de service','Conduite','Chargement','DÃ©chargement','Pause','','Fin de service') NOT NULL,
  `km_parcouru` int(11) DEFAULT 0,
  `duree_repos_h` decimal(4,2) DEFAULT NULL,
  `est_debut_journee` tinyint(1) DEFAULT 0,
  `conduite_continue_cumul` decimal(10,2) DEFAULT 0.00,
  `total_conduite_cumul` decimal(10,2) DEFAULT 0.00,
  `total_service_cumul` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_lignes_activite`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_ordres_transport`
--

CREATE TABLE `zl_ordres_transport` (
  `id_ot` int(11) NOT NULL,
  `numero_ot` varchar(50) NOT NULL,
  `expediteur` varchar(100) NOT NULL,
  `rue_adresse_expediteur` varchar(150) DEFAULT NULL,
  `cp_adresse_expediteur` varchar(10) DEFAULT NULL,
  `ville_adresse_expediteur` varchar(100) DEFAULT NULL,
  `destinataire` varchar(100) NOT NULL,
  `rue_adresse_destinataire` varchar(150) DEFAULT NULL,
  `cp_adresse_destinataire` varchar(10) DEFAULT NULL,
  `ville_adresse_destinataire` varchar(100) DEFAULT NULL,
  `nb_supports` int(11) NOT NULL,
  `id_type_support` int(11) NOT NULL,
  `pbt` decimal(10,2) NOT NULL,
  `ca` decimal(10,2) NOT NULL,
  `id_equipe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_ordres_transport`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_types_supports`
--

CREATE TABLE `zl_types_supports` (
  `id_type_support` int(11) NOT NULL,
  `intitule` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_types_supports`
--


-- --------------------------------------------------------

--
-- Structure de la table `zl_vehicules`
--

CREATE TABLE `zl_vehicules` (
  `id_vehicule` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `id_type` int(11) NOT NULL,
  `id_carrosserie` int(11) NOT NULL,
  `id_energie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `zl_vehicules`
--


--
-- Index pour les tables dÃ©chargÃ©es
--

--
-- Index pour la table `equipes`
--
ALTER TABLE `equipes`
  ADD PRIMARY KEY (`id_equipe`),
  ADD UNIQUE KEY `uq_login_equipe` (`login_equipe`),
  ADD KEY `fk_equipes_lycees` (`id_lycee`);

--
-- Index pour la table `lycees`
--
ALTER TABLE `lycees`
  ADD PRIMARY KEY (`id_lycee`);

--
-- Index pour la table `ref_carrosseries`
--
ALTER TABLE `ref_carrosseries`
  ADD PRIMARY KEY (`id_carrosserie`);

--
-- Index pour la table `ref_departements`
--
ALTER TABLE `ref_departements`
  ADD PRIMARY KEY (`code_dept`);

--
-- Index pour la table `ref_energies`
--
ALTER TABLE `ref_energies`
  ADD PRIMARY KEY (`id_energie`);

--
-- Index pour la table `ref_types_vehicule`
--
ALTER TABLE `ref_types_vehicule`
  ADD PRIMARY KEY (`id_type`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `identifiant` (`identifiant`),
  ADD KEY `fk_utilisateur_lycees` (`id_lycee`);

--
-- Index pour la table `zl_affretements`
--
ALTER TABLE `zl_affretements`
  ADD PRIMARY KEY (`id_affretement`),
  ADD KEY `idx_affr_ot` (`id_ot`),
  ADD KEY `idx_affr_affreteur` (`id_equipe_affreteur`),
  ADD KEY `idx_affr_affrete` (`id_equipe_affrete`);

--
-- Index pour la table `zl_config_parametres`
--
ALTER TABLE `zl_config_parametres`
  ADD PRIMARY KEY (`id_config`);

--
-- Index pour la table `zl_departements_limitrophes`
--
ALTER TABLE `zl_departements_limitrophes`
  ADD PRIMARY KEY (`code_dept`,`code_dept_limitrophe`),
  ADD KEY `fk_limit_voisin` (`code_dept_limitrophe`);

--
-- Index pour la table `zl_feuilles_otea`
--
ALTER TABLE `zl_feuilles_otea`
  ADD PRIMARY KEY (`id_feuille`),
  ADD UNIQUE KEY `uq_equipe_vehicule` (`id_equipe`,`id_vehicule`),
  ADD KEY `fk_feuille_vehicule` (`id_vehicule`);

--
-- Index pour la table `zl_lignes_activite`
--
ALTER TABLE `zl_lignes_activite`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `fk_zl_lignes_feuilles` (`id_feuille`),
  ADD KEY `fk_zl_lignes_ot` (`id_ot`);

--
-- Index pour la table `zl_ordres_transport`
--
ALTER TABLE `zl_ordres_transport`
  ADD PRIMARY KEY (`id_ot`),
  ADD UNIQUE KEY `uq_numero_ot` (`numero_ot`),
  ADD KEY `fk_ot_type_palette` (`id_type_support`),
  ADD KEY `fk_ot_equipe` (`id_equipe`);

--
-- Index pour la table `zl_types_supports`
--
ALTER TABLE `zl_types_supports`
  ADD PRIMARY KEY (`id_type_support`);

--
-- Index pour la table `zl_vehicules`
--
ALTER TABLE `zl_vehicules`
  ADD PRIMARY KEY (`id_vehicule`),
  ADD KEY `fk_vehicule_type` (`id_type`),
  ADD KEY `fk_vehicule_carrosserie` (`id_carrosserie`),
  ADD KEY `fk_vehicule_energie` (`id_energie`);

--
-- AUTO_INCREMENT pour les tables dÃ©chargÃ©es
--

--
-- AUTO_INCREMENT pour la table `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `lycees`
--
ALTER TABLE `lycees`
  MODIFY `id_lycee` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `ref_carrosseries`
--
ALTER TABLE `ref_carrosseries`
  MODIFY `id_carrosserie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `ref_energies`
--
ALTER TABLE `ref_energies`
  MODIFY `id_energie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `ref_types_vehicule`
--
ALTER TABLE `ref_types_vehicule`
  MODIFY `id_type` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `zl_affretements`
--
ALTER TABLE `zl_affretements`
  MODIFY `id_affretement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `zl_config_parametres`
--
ALTER TABLE `zl_config_parametres`
  MODIFY `id_config` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `zl_feuilles_otea`
--
ALTER TABLE `zl_feuilles_otea`
  MODIFY `id_feuille` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `zl_lignes_activite`
--
ALTER TABLE `zl_lignes_activite`
  MODIFY `id_ligne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `zl_ordres_transport`
--
ALTER TABLE `zl_ordres_transport`
  MODIFY `id_ot` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=508;

--
-- AUTO_INCREMENT pour la table `zl_types_supports`
--
ALTER TABLE `zl_types_supports`
  MODIFY `id_type_support` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `zl_vehicules`
--
ALTER TABLE `zl_vehicules`
  MODIFY `id_vehicule` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Contraintes pour les tables dÃ©chargÃ©es
--

--
-- Contraintes pour la table `equipes`
--
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_equipes_lycees` FOREIGN KEY (`id_lycee`) REFERENCES `lycees` (`id_lycee`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateur_lycees` FOREIGN KEY (`id_lycee`) REFERENCES `lycees` (`id_lycee`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `zl_affretements`
--
ALTER TABLE `zl_affretements`
  ADD CONSTRAINT `fk_affr_affrete` FOREIGN KEY (`id_equipe_affrete`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_affr_affreteur` FOREIGN KEY (`id_equipe_affreteur`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_affr_ot` FOREIGN KEY (`id_ot`) REFERENCES `zl_ordres_transport` (`id_ot`);

--
-- Contraintes pour la table `zl_departements_limitrophes`
--
ALTER TABLE `zl_departements_limitrophes`
  ADD CONSTRAINT `fk_limit_dept` FOREIGN KEY (`code_dept`) REFERENCES `ref_departements` (`code_dept`),
  ADD CONSTRAINT `fk_limit_voisin` FOREIGN KEY (`code_dept_limitrophe`) REFERENCES `ref_departements` (`code_dept`);

--
-- Contraintes pour la table `zl_feuilles_otea`
--
ALTER TABLE `zl_feuilles_otea`
  ADD CONSTRAINT `fk_feuille_vehicule` FOREIGN KEY (`id_vehicule`) REFERENCES `zl_vehicules` (`id_vehicule`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zl_feuilles_equipes` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `zl_lignes_activite`
--
ALTER TABLE `zl_lignes_activite`
  ADD CONSTRAINT `fk_zl_lignes_feuilles` FOREIGN KEY (`id_feuille`) REFERENCES `zl_feuilles_otea` (`id_feuille`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zl_lignes_ot` FOREIGN KEY (`id_ot`) REFERENCES `zl_ordres_transport` (`id_ot`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `zl_ordres_transport`
--
ALTER TABLE `zl_ordres_transport`
  ADD CONSTRAINT `fk_ot_equipe` FOREIGN KEY (`id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ot_type_support` FOREIGN KEY (`id_type_support`) REFERENCES `zl_types_supports` (`id_type_support`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `zl_vehicules`
--
ALTER TABLE `zl_vehicules`
  ADD CONSTRAINT `fk_vehicule_carrosserie` FOREIGN KEY (`id_carrosserie`) REFERENCES `ref_carrosseries` (`id_carrosserie`),
  ADD CONSTRAINT `fk_vehicule_energie` FOREIGN KEY (`id_energie`) REFERENCES `ref_energies` (`id_energie`),
  ADD CONSTRAINT `fk_vehicule_type` FOREIGN KEY (`id_type`) REFERENCES `ref_types_vehicule` (`id_type`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

