-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 13 oct. 2026 à 09:05
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12
--
-- ---------------------------------------------------------------------
-- BASE DE DÉMONSTRATION ANONYMISÉE (version finale du schéma)
-- Comptes de démonstration (mots de passe connus, À CHANGER en production) :
--   Administration (/admin) : admin.demo / Admin-Demo-2026
--                             admin2.demo / Admin-Demo-2026
--                             prof.demo  / Prof-Demo-2026 (rôle professeur)
--   Espace équipes (/)      : sio-transports / Equipe-Demo-2026
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
-- Base de données : `challenge_gtla`
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
-- Déchargement des données de la table `equipes`
--

INSERT INTO `equipes` (`id_equipe`, `nom_equipe`, `login_equipe`, `mot_de_passe`, `id_lycee`) VALUES
(1, 'Sio Transports', 'sio-transports', '$2y$10$.YU.ecKtnMZy2YsQnhyUdufuRGbOMDup3YQmMgFgf9ya0z8S8UbOe', 1),
(2, 'PMF Transports', 'pmf-transports', '$2y$10$.YU.ecKtnMZy2YsQnhyUdufuRGbOMDup3YQmMgFgf9ya0z8S8UbOe', 2);

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
-- Déchargement des données de la table `lycees`
--

INSERT INTO `lycees` (`id_lycee`, `nom_lycee`, `ville`) VALUES
(1, 'Lycée Edouard Gand', 'Amiens'),
(2, 'Lycée Pierre Mendès-France', 'Péronne');

-- --------------------------------------------------------

--
-- Structure de la table `ref_carrosseries`
--

CREATE TABLE `ref_carrosseries` (
  `id_carrosserie` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ref_carrosseries`
--

INSERT INTO `ref_carrosseries` (`id_carrosserie`, `libelle`) VALUES
(1, 'Fourgon'),
(2, 'Tautliner'),
(3, 'Frigorifique'),
(4, 'Citerne'),
(5, 'Plateau');

-- --------------------------------------------------------

--
-- Structure de la table `ref_departements`
--

CREATE TABLE `ref_departements` (
  `code_dept` varchar(3) NOT NULL,
  `nom_dept` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ref_departements`
--

INSERT INTO `ref_departements` (`code_dept`, `nom_dept`) VALUES
('01', 'Ain'),
('02', 'Aisne'),
('03', 'Allier'),
('04', 'Alpes-de-Haute-Provence'),
('05', 'Hautes-Alpes'),
('06', 'Alpes-Maritimes'),
('07', 'Ardèche'),
('08', 'Ardennes'),
('09', 'Ariège'),
('10', 'Aube'),
('11', 'Aude'),
('12', 'Aveyron'),
('13', 'Bouches-du-Rhône'),
('14', 'Calvados'),
('15', 'Cantal'),
('16', 'Charente'),
('17', 'Charente-Maritime'),
('18', 'Cher'),
('19', 'Corrèze'),
('21', 'Côte-d\'Or'),
('22', 'Côtes-d\'Armor'),
('23', 'Creuse'),
('24', 'Dordogne'),
('25', 'Doubs'),
('26', 'Drôme'),
('27', 'Eure'),
('28', 'Eure-et-Loir'),
('29', 'Finistère'),
('2A', 'Corse-du-Sud'),
('2B', 'Haute-Corse'),
('30', 'Gard'),
('31', 'Haute-Garonne'),
('32', 'Gers'),
('33', 'Gironde'),
('34', 'Hérault'),
('35', 'Ille-et-Vilaine'),
('36', 'Indre'),
('37', 'Indre-et-Loire'),
('38', 'Isère'),
('39', 'Jura'),
('40', 'Landes'),
('41', 'Loir-et-Cher'),
('42', 'Loire'),
('43', 'Haute-Loire'),
('44', 'Loire-Atlantique'),
('45', 'Loiret'),
('46', 'Lot'),
('47', 'Lot-et-Garonne'),
('48', 'Lozère'),
('49', 'Maine-et-Loire'),
('50', 'Manche'),
('51', 'Marne'),
('52', 'Haute-Marne'),
('53', 'Mayenne'),
('54', 'Meurthe-et-Moselle'),
('55', 'Meuse'),
('56', 'Morbihan'),
('57', 'Moselle'),
('58', 'Nièvre'),
('59', 'Nord'),
('60', 'Oise'),
('61', 'Orne'),
('62', 'Pas-de-Calais'),
('63', 'Puy-de-Dôme'),
('64', 'Pyrénées-Atlantiques'),
('65', 'Hautes-Pyrénées'),
('66', 'Pyrénées-Orientales'),
('67', 'Bas-Rhin'),
('68', 'Haut-Rhin'),
('69', 'Rhône'),
('70', 'Haute-Saône'),
('71', 'Saône-et-Loire'),
('72', 'Sarthe'),
('73', 'Savoie'),
('74', 'Haute-Savoie'),
('75', 'Paris'),
('76', 'Seine-Maritime'),
('77', 'Seine-et-Marne'),
('78', 'Yvelines'),
('79', 'Deux-Sèvres'),
('80', 'Somme'),
('81', 'Tarn'),
('82', 'Tarn-et-Garonne'),
('83', 'Var'),
('84', 'Vaucluse'),
('85', 'Vendée'),
('86', 'Vienne'),
('87', 'Haute-Vienne'),
('88', 'Vosges'),
('89', 'Yonne'),
('90', 'Territoire de Belfort'),
('91', 'Essonne'),
('92', 'Hauts-de-Seine'),
('93', 'Seine-Saint-Denis'),
('94', 'Val-de-Marne'),
('95', 'Val-d\'Oise');

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
-- Déchargement des données de la table `ref_energies`
--

INSERT INTO `ref_energies` (`id_energie`, `libelle`, `cout_km`) VALUES
(1, 'Diesel', 1.000),
(2, 'B100', 1.150),
(3, 'Électrique', 0.800);

-- --------------------------------------------------------

--
-- Structure de la table `ref_types_vehicule`
--

CREATE TABLE `ref_types_vehicule` (
  `id_type` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ref_types_vehicule`
--

INSERT INTO `ref_types_vehicule` (`id_type`, `libelle`) VALUES
(1, 'Ensemble Articulé'),
(2, 'Porteur');

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
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `identifiant`, `mot_de_passe`, `role`, `id_lycee`, `date_creation`) VALUES
(1, 'admin.demo', '$2y$10$Spwi1J5FogD5bx80tP8q1uxzxs.owk5ahDqPWy.xIHvwZV3dFpnUO', 'administrateur', NULL, '2026-06-18 12:07:09'),
(2, 'prof.demo', '$2y$10$QRIx4i7GbPBT/tITmhc/yedIJANdWQsch/sbz6zAIYeLgOgrmjIH6', 'professeur', 2, '2026-06-18 12:07:09'),
(3, 'admin2.demo', '$2y$10$Spwi1J5FogD5bx80tP8q1uxzxs.owk5ahDqPWy.xIHvwZV3dFpnUO', 'administrateur', NULL, '2026-06-21 07:14:22');

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
-- Déchargement des données de la table `zl_affretements`
--

INSERT INTO `zl_affretements` (`id_affretement`, `id_ot`, `id_equipe_affreteur`, `id_equipe_affrete`, `prix_affretement`, `statut`, `date_declaration`, `date_reponse`) VALUES
(1, 106, 1, 2, 1200.00, 'refusee', '2026-09-27 16:04:17', '2026-09-27 16:06:20'),
(2, 106, 1, 2, 1200.00, 'acceptee', '2026-09-27 16:06:55', '2026-09-27 16:07:14'),
(3, 119, 1, 2, 400.00, 'annulee', '2026-09-28 08:03:24', '2026-09-28 08:04:10'),
(4, 119, 1, 2, 300.00, 'refusee', '2026-09-28 08:12:27', '2026-09-28 08:12:47'),
(5, 119, 1, 2, 350.00, 'acceptee', '2026-09-28 08:13:27', '2026-09-28 08:14:11');

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
-- Déchargement des données de la table `zl_config_parametres`
--

INSERT INTO `zl_config_parametres` (`id_config`, `vitesse_moyenne_kmh`, `temps_chargement_h`, `temps_dechargement_h`, `temps_pause_h`, `repos_journalier_min_h`, `heure_prise_service_defaut`, `date_application`, `date_debut_challenge_zl`, `date_fin_challenge_zl`, `penalite_jour_non_roule`, `malus_infraction_conduite_repos`, `malus_rechargement_hors_zone`) VALUES
(1, 75, 1.00, 1.00, 0.75, 9.00, 5.50, '2026-09-15', '2026-06-15', '2026-06-19', 500.00, 50.00, 50.00);

-- --------------------------------------------------------

--
-- Structure de la table `zl_departements_limitrophes`
--

CREATE TABLE `zl_departements_limitrophes` (
  `code_dept` varchar(3) NOT NULL,
  `code_dept_limitrophe` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `zl_departements_limitrophes`
--

INSERT INTO `zl_departements_limitrophes` (`code_dept`, `code_dept_limitrophe`) VALUES
('01', '38'),
('01', '39'),
('01', '69'),
('01', '71'),
('01', '73'),
('01', '74'),
('02', '08'),
('02', '51'),
('02', '59'),
('02', '60'),
('02', '77'),
('02', '80'),
('03', '18'),
('03', '23'),
('03', '42'),
('03', '58'),
('03', '63'),
('03', '71'),
('04', '05'),
('04', '06'),
('04', '13'),
('04', '26'),
('04', '83'),
('04', '84'),
('05', '04'),
('05', '26'),
('05', '38'),
('05', '73'),
('06', '04'),
('06', '83'),
('07', '26'),
('07', '30'),
('07', '38'),
('07', '42'),
('07', '43'),
('07', '48'),
('08', '02'),
('08', '51'),
('08', '55'),
('09', '11'),
('09', '31'),
('09', '66'),
('10', '21'),
('10', '51'),
('10', '52'),
('10', '77'),
('10', '89'),
('11', '09'),
('11', '31'),
('11', '34'),
('11', '66'),
('11', '81'),
('12', '15'),
('12', '30'),
('12', '34'),
('12', '46'),
('12', '48'),
('12', '81'),
('12', '82'),
('13', '04'),
('13', '30'),
('13', '83'),
('13', '84'),
('14', '27'),
('14', '50'),
('14', '61'),
('15', '12'),
('15', '19'),
('15', '43'),
('15', '46'),
('15', '48'),
('15', '63'),
('16', '17'),
('16', '24'),
('16', '33'),
('16', '79'),
('16', '86'),
('16', '87'),
('17', '16'),
('17', '33'),
('17', '79'),
('17', '85'),
('18', '03'),
('18', '23'),
('18', '36'),
('18', '41'),
('18', '45'),
('18', '58'),
('19', '15'),
('19', '23'),
('19', '24'),
('19', '46'),
('19', '63'),
('19', '87'),
('21', '10'),
('21', '39'),
('21', '52'),
('21', '58'),
('21', '70'),
('21', '71'),
('21', '89'),
('22', '29'),
('22', '35'),
('22', '56'),
('23', '03'),
('23', '18'),
('23', '19'),
('23', '36'),
('23', '63'),
('23', '87'),
('24', '16'),
('24', '19'),
('24', '33'),
('24', '46'),
('24', '47'),
('24', '87'),
('25', '39'),
('25', '70'),
('25', '90'),
('26', '04'),
('26', '05'),
('26', '07'),
('26', '38'),
('26', '84'),
('27', '14'),
('27', '28'),
('27', '60'),
('27', '61'),
('27', '76'),
('27', '78'),
('27', '95'),
('28', '27'),
('28', '41'),
('28', '45'),
('28', '61'),
('28', '72'),
('28', '78'),
('28', '91'),
('29', '22'),
('29', '56'),
('2A', '2B'),
('2B', '2A'),
('30', '07'),
('30', '12'),
('30', '13'),
('30', '34'),
('30', '48'),
('30', '84'),
('31', '09'),
('31', '11'),
('31', '32'),
('31', '65'),
('31', '81'),
('31', '82'),
('32', '31'),
('32', '40'),
('32', '47'),
('32', '64'),
('32', '65'),
('32', '82'),
('33', '16'),
('33', '17'),
('33', '24'),
('33', '40'),
('33', '47'),
('34', '11'),
('34', '12'),
('34', '30'),
('34', '81'),
('35', '22'),
('35', '44'),
('35', '50'),
('35', '53'),
('35', '56'),
('36', '18'),
('36', '23'),
('36', '37'),
('36', '41'),
('36', '86'),
('36', '87'),
('37', '36'),
('37', '41'),
('37', '49'),
('37', '72'),
('37', '86'),
('38', '01'),
('38', '05'),
('38', '07'),
('38', '26'),
('38', '42'),
('38', '69'),
('38', '73'),
('39', '01'),
('39', '21'),
('39', '25'),
('39', '70'),
('39', '71'),
('40', '32'),
('40', '33'),
('40', '47'),
('40', '64'),
('41', '18'),
('41', '28'),
('41', '36'),
('41', '37'),
('41', '45'),
('41', '72'),
('42', '03'),
('42', '07'),
('42', '38'),
('42', '43'),
('42', '63'),
('42', '69'),
('42', '71'),
('43', '07'),
('43', '15'),
('43', '42'),
('43', '48'),
('43', '63'),
('44', '35'),
('44', '49'),
('44', '56'),
('44', '85'),
('45', '18'),
('45', '28'),
('45', '41'),
('45', '58'),
('45', '77'),
('45', '89'),
('45', '91'),
('46', '12'),
('46', '15'),
('46', '19'),
('46', '24'),
('46', '47'),
('46', '82'),
('47', '24'),
('47', '32'),
('47', '33'),
('47', '40'),
('47', '46'),
('47', '82'),
('48', '07'),
('48', '12'),
('48', '15'),
('48', '30'),
('48', '43'),
('49', '37'),
('49', '44'),
('49', '53'),
('49', '72'),
('49', '79'),
('49', '85'),
('50', '14'),
('50', '35'),
('50', '53'),
('50', '61'),
('51', '02'),
('51', '08'),
('51', '10'),
('51', '52'),
('51', '55'),
('51', '77'),
('52', '10'),
('52', '21'),
('52', '51'),
('52', '55'),
('52', '70'),
('52', '88'),
('53', '35'),
('53', '49'),
('53', '50'),
('53', '61'),
('53', '72'),
('54', '55'),
('54', '57'),
('54', '88'),
('55', '08'),
('55', '51'),
('55', '52'),
('55', '54'),
('55', '88'),
('56', '22'),
('56', '29'),
('56', '35'),
('56', '44'),
('57', '54'),
('57', '67'),
('57', '88'),
('58', '03'),
('58', '18'),
('58', '21'),
('58', '45'),
('58', '71'),
('58', '89'),
('59', '02'),
('59', '62'),
('60', '02'),
('60', '27'),
('60', '76'),
('60', '77'),
('60', '80'),
('60', '95'),
('61', '14'),
('61', '27'),
('61', '28'),
('61', '50'),
('61', '53'),
('61', '72'),
('62', '59'),
('62', '80'),
('63', '03'),
('63', '15'),
('63', '19'),
('63', '23'),
('63', '42'),
('63', '43'),
('64', '32'),
('64', '40'),
('64', '65'),
('65', '31'),
('65', '32'),
('65', '64'),
('66', '09'),
('66', '11'),
('67', '57'),
('67', '68'),
('67', '88'),
('68', '67'),
('68', '70'),
('68', '88'),
('68', '90'),
('69', '01'),
('69', '38'),
('69', '42'),
('69', '71'),
('70', '21'),
('70', '25'),
('70', '39'),
('70', '52'),
('70', '68'),
('70', '88'),
('70', '90'),
('71', '01'),
('71', '03'),
('71', '21'),
('71', '39'),
('71', '42'),
('71', '58'),
('71', '69'),
('72', '28'),
('72', '37'),
('72', '41'),
('72', '49'),
('72', '53'),
('72', '61'),
('73', '01'),
('73', '05'),
('73', '38'),
('73', '74'),
('74', '01'),
('74', '73'),
('75', '92'),
('75', '93'),
('75', '94'),
('76', '27'),
('76', '60'),
('76', '80'),
('77', '02'),
('77', '10'),
('77', '45'),
('77', '51'),
('77', '60'),
('77', '89'),
('77', '91'),
('77', '94'),
('78', '27'),
('78', '28'),
('78', '91'),
('78', '92'),
('78', '95'),
('79', '16'),
('79', '17'),
('79', '49'),
('79', '85'),
('79', '86'),
('80', '02'),
('80', '60'),
('80', '62'),
('80', '76'),
('81', '11'),
('81', '12'),
('81', '31'),
('81', '34'),
('81', '82'),
('82', '12'),
('82', '31'),
('82', '32'),
('82', '46'),
('82', '47'),
('82', '81'),
('83', '04'),
('83', '06'),
('83', '13'),
('84', '04'),
('84', '13'),
('84', '26'),
('84', '30'),
('85', '17'),
('85', '44'),
('85', '49'),
('85', '79'),
('86', '16'),
('86', '36'),
('86', '37'),
('86', '79'),
('86', '87'),
('87', '16'),
('87', '19'),
('87', '23'),
('87', '24'),
('87', '36'),
('87', '86'),
('88', '52'),
('88', '54'),
('88', '55'),
('88', '57'),
('88', '67'),
('88', '68'),
('88', '70'),
('89', '10'),
('89', '21'),
('89', '45'),
('89', '58'),
('89', '77'),
('90', '25'),
('90', '68'),
('90', '70'),
('91', '28'),
('91', '45'),
('91', '77'),
('91', '78'),
('91', '94'),
('92', '75'),
('92', '78'),
('92', '93'),
('92', '94'),
('92', '95'),
('93', '75'),
('93', '92'),
('93', '94'),
('93', '95'),
('94', '75'),
('94', '77'),
('94', '91'),
('94', '92'),
('94', '93'),
('95', '27'),
('95', '60'),
('95', '78'),
('95', '92'),
('95', '93');

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
-- Déchargement des données de la table `zl_feuilles_otea`
--

INSERT INTO `zl_feuilles_otea` (`id_feuille`, `id_equipe`, `id_vehicule`, `date_debut_semaine`) VALUES
(10, 1, 2, '2026-06-15');

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
  `type_activite` enum('Prise de service','Conduite','Chargement','Déchargement','Pause','','Fin de service') NOT NULL,
  `km_parcouru` int(11) DEFAULT 0,
  `duree_repos_h` decimal(4,2) DEFAULT NULL,
  `est_debut_journee` tinyint(1) DEFAULT 0,
  `conduite_continue_cumul` decimal(10,2) DEFAULT 0.00,
  `total_conduite_cumul` decimal(10,2) DEFAULT 0.00,
  `total_service_cumul` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `zl_lignes_activite`
--

INSERT INTO `zl_lignes_activite` (`id_ligne`, `id_feuille`, `id_ot`, `ordre_chronologique`, `date_activite`, `horaire_debut`, `type_activite`, `km_parcouru`, `duree_repos_h`, `est_debut_journee`, `conduite_continue_cumul`, `total_conduite_cumul`, `total_service_cumul`) VALUES
(22, 10, 113, 1, '2026-06-15', 5.50, 'Prise de service', 0, NULL, 1, 0.00, 0.00, 0.00),
(23, 10, 113, 2, '2026-06-15', 5.50, 'Chargement', 0, NULL, 0, 0.00, 0.00, 1.00),
(24, 10, 113, 3, '2026-06-15', 6.50, 'Conduite', 120, NULL, 0, 1.60, 1.60, 2.60),
(25, 10, 113, 4, '2026-06-15', 8.10, 'Déchargement', 0, NULL, 0, 1.60, 1.60, 3.60),
(26, 10, 113, 5, '2026-06-15', 9.10, 'Fin de service', 0, NULL, 0, 1.60, 1.60, 3.60);

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
-- Déchargement des données de la table `zl_ordres_transport`
--

INSERT INTO `zl_ordres_transport` (`id_ot`, `numero_ot`, `expediteur`, `rue_adresse_expediteur`, `cp_adresse_expediteur`, `ville_adresse_expediteur`, `destinataire`, `rue_adresse_destinataire`, `cp_adresse_destinataire`, `ville_adresse_destinataire`, `nb_supports`, `id_type_support`, `pbt`, `ca`, `id_equipe`) VALUES
(106, '555888', 'GEODIS  Distribution & Express', '2 Rue Santos Dumont', '44860', 'Saint-Aignan-Grandlieu', 'King Jouet', '3 Bd des Alpes', '38240', 'Meylan', 22, 2, 13.00, 1329.42, 1),
(107, '443140', 'L.T.S - Levasseur Transport Service', 'ZA NORD - N° 1013 Rte de Paris,', '16560', 'Tourriers', 'E.Leclerc NICE SAINT ROCH', '170-174 Rte de Turin', '06000', 'Nice', 31, 1, 14.00, 1298.76, 1),
(108, '289513', 'Transports JH MESGUEN (Agence de Perpignan)', '191 Av. de Berlin', '66000', 'Perpignan', 'Grand Frais Fegersheim', 'Zone d\'activité Ouest', '67640', 'Fegersheim', 29, 1, 21.00, 1207.50, 1),
(109, '368095', 'Safran Ceramics', 'Rue de Touban', '33185', 'Le Haillan', 'Carrefour Wasquehal', 'Av. du Grand Cottignies', '59290', 'Wasquehal', 29, 1, 23.00, 1101.73, 1),
(110, '757773', 'Panzani Usine La Montre', '136 Rte de la Valentine CS 40283', '13396', 'Marseille Cedex 11', 'E.Leclerc NICE SAINT ROCH', '170-174 Rte de Turin', '06000', 'Nice', 31, 1, 18.00, 1012.35, 1),
(111, '974442', 'STEF Transport et Logistique LESQUIN', '17 Rue des Seringats', '59273', 'Fretin', 'Kiabi', 'Centre Commercial 16 Rue Jean Vilar,', ' 71210', 'Torcy', 25, 2, 26.00, 998.40, 1),
(112, '546374', 'DB Schenker - Agence de Mulhouse', '3 Rue Jean Monnet', '68390', 'Sausheim', 'E.Leclerc NICE SAINT ROCH', '170-174 Rte de Turin', '06000', 'Nice', 25, 2, 18.00, 971.55, 1),
(113, '198700', 'Panzani Nanterre', '29-31 Rue Lavoisier', '92000', 'Nanterre', 'FNAC La Roche-sur-Yon', 'Rte de Nantes', '85000', 'La Roche-sur-Yon', 33, 1, 19.00, 802.65, 1),
(114, '945070', 'Soc Transports Rhône Méditerranée (STRM)', '34 Imp. du Mas Saint-pierre', '34000', 'Montpellier', 'Intermarché EXPRESS Orléans', '8 Pl. du Martroi', '45000', 'Orléans', 24, 2, 23.00, 777.09, 1),
(115, '274632', 'Safran Ceramics', 'Rue de Touban', '33185', 'Le Haillan', 'Auchan Hypermarché Cherbourg', 'Cc Cotentin Rn 13,', '50470', 'Cherbourg-en-Cotentin', 24, 2, 21.00, 753.52, 1),
(116, '261567', 'GPC Logistic et Overseas', '28 Rue du Moutot', '10150', 'Lavau', 'Carrefour Tours - Saint Pierre Des Corps', 'Cc Atlantes Zac Rue de Rochepinard, ', '37700', 'Saint-Pierre-des-Corps', 26, 2, 28.00, 620.92, 1),
(117, '941491', 'STG Saint-Brieuc', 'ZAC des Hautières', '22440', 'Trémuson', 'Scapnor', 'Zone Industrielle Chem du Bac des Aubins,', '95820', 'Bruyères-sur-Oise', 23, 2, 26.00, 599.48, 1),
(118, '940791', 'Groupe BLONDEL - Régis Martelet', '22 Rue de la Brot', '21000', 'Dijon', 'Socara', '19 Rue des Garines', '38070', 'Saint-Quentin-Fallavier', 25, 2, 13.00, 536.77, 1),
(119, '108742', 'Cantal Frêt', 'Rue Félix Daguerre', '15000', 'Aurillac', 'MONOPRIX', '2 Av. Pierre Semard', '26000', 'Valence', 33, 1, 18.00, 430.32, 1),
(120, '303184', 'Le Roy Logistique Lyon', '15 Av. des Pays-Bas', '69330', 'Meyzieu', 'Lidl', 'SAINT FLOUR FR 6054 Zi Montplain,', '15100', 'Andelat', 23, 2, 25.00, 406.83, 2),
(121, '503433', 'Panzani Nanterre', '29-31 Rue Lavoisier', '92000', 'Nanterre', 'Carrefour Wasquehal', 'Av. du Grand Cottignies', '59290', 'Wasquehal', 29, 1, 19.00, 319.72, 2),
(122, '605339', 'DB SCHENKER', 'Rue Henri Germaine Desjardin', '80260', 'Amiens', 'ELECTRO DEPOT LILLE - CAPINGHEM', 'Rue de la Zamin', '59160', 'Capinghem', 33, 1, 26.00, 140.34, 2),
(123, '436091', 'CEVA Logistics', 'Rue Marc Lefrancq', '59125', 'Trith-Saint-Léger', 'Decathlon Perpignan', '170 Av. André Tourne', '66100', 'Perpignan', 23, 2, 28.00, 1968.89, 2),
(124, '185206', 'Xpo logistics', 'Rue de l\'Europe Lomme', '59160', 'Lille', 'SCALANDES 2', 'Rue Condorcet', '40000', 'Mont-de-Marsan', 30, 1, 20.00, 1770.81, 2),
(125, '362316', 'DB SCHENKER', 'Rue Henri Germaine Desjardin', '80260', 'Amiens', 'SCALANDES 2', 'Rue Condorcet', '40000', 'Mont-de-Marsan', 31, 1, 28.00, 1584.48, 2),
(126, '696167', 'Olano Boulogne-sur-Mer', '11 Rue Alexandre Adam', '62200', 'Boulogne-sur-Mer', 'Socara', '19 Rue des Garines', '38070', 'Saint-Quentin-Fallavier', 32, 1, 14.00, 1417.83, 2),
(127, '475305', 'STG Saint-Brieuc', 'ZAC des Hautières', '22440', 'Trémuson', 'Pimkie - Rodez Centre Ville', '13 Rue Neuve', '12000', 'Rodez', 28, 1, 12.00, 1391.05, 2),
(128, '832363', 'DB SCHENKER', 'Rue Henri Germaine Desjardin', '80260', 'Amiens', 'MONOPRIX', '2 Av. Pierre Semard', '26000', 'Valence', 24, 2, 17.00, 1372.91, 2),
(129, '790126', 'Transports Bergès', 'ZA pinet 4 Imp. Castelviel, ', '31180', 'Rouffiac-Tolosan', 'ALDI Outreau', 'Rue Jules Massenet', '62230', 'Outreau', 24, 2, 18.00, 1251.25, 2),
(130, '192716', 'GEODIS  Contract Logistics', '396 Rue de Bruxelles', '62138', 'Douvrin', 'FNAC La Roche-sur-Yon', 'Rte de Nantes', '85000', 'La Roche-sur-Yon', 32, 1, 23.00, 1146.74, 2),
(131, '141506', 'GEODIS  Distribution & Express', '36 Bd du Val de Vesle', '51100', 'Reims', 'Castorama', 'ZAC de Belle Aire Rue Le Verrier,', '17440', 'Aytré', 30, 1, 20.00, 1135.42, 2),
(132, '810687', 'Soc Transports Rhône Méditerranée (STRM)', '34 Imp. du Mas Saint-pierre', '34000', 'Montpellier', 'Grand Frais Fegersheim', 'Zone d\'activité Ouest', '67640', 'Fegersheim', 32, 1, 19.00, 1007.75, 2),
(133, '799979', 'DSV Road', '94 Rue de Berzin', '59273', 'Fretin', 'Intermarché SUPER Rixheim', 'Route de l\'ile Napoléon 65 Rue de l\'Île Napoléon,', '68170', 'Rixheim', 25, 2, 27.00, 947.58, 2),
(134, '773469', 'Transports Goëvia (Gonzalez Logistique Services)', 'Chem. du Pot au Pin', '33610', 'Cestas', 'Lidl', '16 Rue de Malherbe', '76100', 'Rouen', 26, 2, 16.00, 872.96, 2),
(135, '322460', 'Transports Jacques Martin', '1225 Rue Louis Saillant', '26800', 'Portes-lès-Valence', 'Leroy Merlin Chasseneuil-du-Poitou - Poitiers', 'LEROY MERLIN 2 allée du Haut Poitou, RN 10,', '86360', 'Chasseneuil-du-Poitou', 25, 2, 16.00, 655.92, 2),
(136, '866523', 'Aldi Bois-Grenier(entrepot', 'Rue Ambroise Paré', '59280', 'Bois-Grenier', 'Brico Dépôt D\'Etain', '28 rue Des Potiers,', '57070', 'Metz', 33, 1, 26.00, 648.27, 2),
(137, '321073', 'Panzani Nanterre', '29-31 Rue Lavoisier', '92000', 'Nanterre', 'Intermarché EXPRESS Dijon', '78 BIS Av. du Drapeau', '21000', 'Dijon', 25, 2, 26.00, 571.78, 2),
(138, '913654', 'Soc Transports Rhône Méditerranée (STRM)', '34 Imp. du Mas Saint-pierre', '34000', 'Montpellier', 'Super U', 'La Riviere, Parc Commercial Du Moulin, Rue Alphonse Daudet,', '19360', 'Malemort', 26, 2, 26.00, 364.36, 2),
(139, '328309', 'ADL (Auvergne Distribution Logistique)', 'Rue André Citroën', '63118', 'Cébazat', 'Super U', 'La Riviere, Parc Commercial Du Moulin, Rue Alphonse Daudet,', '19360', 'Malemort', 33, 1, 12.00, 263.93, 2),
(140, '397793', 'DACHSER', '3 Rue de Phébus', '24660', 'Sanilhac', 'Castorama', 'ZAC de Belle Aire, Rue Le Verrier,', '17440', 'Aytré', 31, 1, 16.00, 204.53, 2);

-- --------------------------------------------------------

--
-- Structure de la table `zl_types_supports`
--

CREATE TABLE `zl_types_supports` (
  `id_type_support` int(11) NOT NULL,
  `intitule` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `zl_types_supports`
--

INSERT INTO `zl_types_supports` (`id_type_support`, `intitule`) VALUES
(1, 'Palette EURO'),
(2, 'Palette ISO'),
(3, 'Big-bags'),
(4, 'Caisses'),
(5, 'Futs'),
(6, 'Rolls');

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
-- Déchargement des données de la table `zl_vehicules`
--

INSERT INTO `zl_vehicules` (`id_vehicule`, `libelle`, `id_type`, `id_carrosserie`, `id_energie`) VALUES
(2, 'VE1', 1, 2, 1),
(3, 'VE2', 1, 2, 1),
(4, 'VE3', 2, 1, 3),
(5, 'VE4', 2, 1, 3),
(6, 'VE5', 1, 3, 2),
(7, 'VE6', 1, 4, 2),
(8, 'VE7', 2, 5, 1),
(11, 'VE8', 2, 5, 2);

--
-- Index pour les tables déchargées
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
-- AUTO_INCREMENT pour les tables déchargées
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
-- Contraintes pour les tables déchargées
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
