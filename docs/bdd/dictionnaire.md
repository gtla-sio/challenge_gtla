# Dictionnaire des Données - Challenge GTLA

Ce document recense l'intégralité des **15 tables** et des **78 colonnes** composant la base de données relationnelle `challenge_gtla`, issue de la rétro-conception du script SQL `bdd/challenge_gtla.sql`.

---

## Sommaire des tables

| # | Nom de la table | Catégorie | Nb colonnes | Rôle fonctionnel |
|---|---|---|:---:|---|
| 1 | `equipes` | Entité métier | 5 | Équipes d'étudiants GTLA participantes |
| 2 | `lycees` | Entité métier | 3 | Établissements scolaires participant au challenge |
| 3 | `ref_carrosseries` | Table de référence | 2 | Types de carrosseries des véhicules |
| 4 | `ref_departements` | Table de référence | 2 | Référentiel des départements français |
| 5 | `ref_energies` | Table de référence | 3 | Motorisations et coûts kilométriques associés |
| 6 | `ref_types_vehicule` | Table de référence | 2 | Catégories physiques de véhicules routiers |
| 7 | `utilisateurs` | Entité métier | 6 | Comptes enseignants et administrateurs de la plateforme |
| 8 | `zl_affretements` | Entité d'association | 8 | Demandes de sous-traitance d'ordres de transport entre équipes |
| 9 | `zl_config_parametres` | Table singleton | 13 | Paramètres globaux de calcul et de simulation du challenge |
| 10 | `zl_departements_limitrophes` | Table d'association | 2 | Voisinage géographique réflexif entre départements |
| 11 | `zl_feuilles_otea` | Entité métier | 4 | Feuilles hebdomadaires de route OTEA par véhicule et équipe |
| 12 | `zl_lignes_activite` | Entité métier | 13 | Événements chronologiques journaliers de planification |
| 13 | `zl_ordres_transport` | Entité métier | 15 | Ordres de transport (missions de fret confiées aux équipes) |
| 14 | `zl_types_supports` | Table de référence | 2 | Supports et emballages de manutention des marchandises |
| 15 | `zl_vehicules` | Entité métier | 5 | Flotte de véhicules routiers disponibles pour les équipes |

---

## Dictionnaire détaillé

| Table | Colonne | Type | Obligatoire | Contrainte | Description |
|---|---|---|:---:|---|---|
| **equipes** | `id_equipe` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de l'équipe |
| **equipes** | `nom_equipe` | `varchar(50)` | Oui | Aucune | Nom commercial ou désignation de l'équipe étudiante |
| **equipes** | `login_equipe` | `varchar(50)` | Oui | **UNIQUE** (`uq_login_equipe`) | Identifiant unique de connexion à l'espace équipe |
| **equipes** | `mot_de_passe` | `varchar(255)` | Oui | Aucune | Empreinte sécurisée du mot de passe (hash bcrypt) |
| **equipes** | `id_lycee` | `int(11)` | Oui | **FK** `lycees(id_lycee)` | Identifiant du lycée auquel l'équipe est rattachée (ON UPDATE CASCADE) |
| **lycees** | `id_lycee` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de l'établissement scolaire |
| **lycees** | `nom_lycee` | `varchar(100)` | Oui | Aucune | Dénomination officielle du lycée |
| **lycees** | `ville` | `varchar(100)` | Non | Aucune | Ville d'implantation géographique du lycée |
| **ref_carrosseries** | `id_carrosserie` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique du type de carrosserie |
| **ref_carrosseries** | `libelle` | `varchar(50)` | Oui | Aucune | Désignation de la carrosserie (Fourgon, Tautliner, Frigorifique, Citerne, Plateau) |
| **ref_departements** | `code_dept` | `varchar(3)` | Oui | **PK** | Code officiel du département métropolitain (ex. : '01', '2A', '2B', '80') |
| **ref_departements** | `nom_dept` | `varchar(50)` | Oui | Aucune | Nom usuel complet du département français |
| **ref_energies** | `id_energie` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de la motorisation / énergie |
| **ref_energies** | `libelle` | `varchar(30)` | Oui | Aucune | Désignation de l'énergie (Diesel, B100, Électrique) |
| **ref_energies** | `cout_km` | `decimal(6,3)` | Oui | Aucune | Coût de revient kilométrique unitaire hors péage en euros (€/km) |
| **ref_types_vehicule** | `id_type` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de la catégorie de véhicule |
| **ref_types_vehicule** | `libelle` | `varchar(50)` | Oui | Aucune | Dénomination de la catégorie (Ensemble Articulé, Porteur) |
| **utilisateurs** | `id_utilisateur` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de l'utilisateur encadrant |
| **utilisateurs** | `identifiant` | `varchar(50)` | Oui | **UNIQUE** (`identifiant`) | Identifiant de connexion à l'espace administration |
| **utilisateurs** | `mot_de_passe` | `varchar(255)` | Oui | Aucune | Empreinte sécurisée du mot de passe (hash bcrypt) |
| **utilisateurs** | `role` | `enum('professeur','administrateur')` | Oui | DEFAULT `'professeur'` | Niveau d'habilitation et privilèges d'accès dans l'application |
| **utilisateurs** | `id_lycee` | `int(11)` | Non | **FK** `lycees(id_lycee)` | Lycée d'affectation pour les professeurs (NULL pour les administrateurs) (ON DELETE SET NULL, ON UPDATE CASCADE) |
| **utilisateurs** | `date_creation` | `timestamp` | Oui | DEFAULT `CURRENT_TIMESTAMP` | Horodatage de création du compte utilisateur |
| **zl_affretements** | `id_affretement` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de l'offre d'affrètement |
| **zl_affretements** | `id_ot` | `int(11)` | Oui | **FK** `zl_ordres_transport(id_ot)` | Ordre de transport faisant l'objet de la proposition de sous-traitance |
| **zl_affretements** | `id_equipe_affreteur` | `int(11)` | Oui | **FK** `equipes(id_equipe)` | Identifiant de l'équipe émettrice de l'offre (affréteur) |
| **zl_affretements** | `id_equipe_affrete` | `int(11)` | Oui | **FK** `equipes(id_equipe)` | Identifiant de l'équipe réceptrice sollicitée (affrété) |
| **zl_affretements** | `prix_affretement` | `decimal(10,2)` | Oui | Aucune | Rémunération convenue pour la sous-traitance en euros (€) |
| **zl_affretements** | `statut` | `enum('en_attente','acceptee','refusee','annulee')` | Oui | DEFAULT `'en_attente'` | État d'avancement de la transaction dans le cycle de vie de l'affrètement |
| **zl_affretements** | `date_declaration` | `datetime` | Oui | DEFAULT `CURRENT_TIMESTAMP` | Date et heure de transmission de la proposition |
| **zl_affretements** | `date_reponse` | `datetime` | Non | Aucune | Date et heure de la réponse (acceptation, refus ou annulation) |
| **zl_config_parametres** | `id_config` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Clé technique unique de la ligne de configuration (singleton) |
| **zl_config_parametres** | `vitesse_moyenne_kmh` | `int(11)` | Oui | DEFAULT `75` | Vitesse moyenne conventionnelle en km/h pour l'estimation du temps de trajet |
| **zl_config_parametres** | `temps_chargement_h` | `decimal(3,2)` | Oui | DEFAULT `1.00` | Temps forfaitaire immobilisé pour un chargement complet en heures |
| **zl_config_parametres** | `temps_dechargement_h` | `decimal(3,2)` | Oui | DEFAULT `1.00` | Temps forfaitaire immobilisé pour un déchargement complet en heures |
| **zl_config_parametres** | `temps_pause_h` | `decimal(3,2)` | Oui | DEFAULT `0.75` | Durée réglementaire d'une pause obligatoire en heures (0.75 = 45 min) |
| **zl_config_parametres** | `repos_journalier_min_h` | `decimal(4,2)` | Oui | DEFAULT `9.00` | Seuil minimum obligatoire de repos consécutif quotidien en heures |
| **zl_config_parametres** | `heure_prise_service_defaut` | `decimal(4,2)` | Oui | DEFAULT `5.00` | Horaire indicatif par défaut de prise de service matinale (5.00 = 05h00) |
| **zl_config_parametres** | `date_application` | `date` | Oui | Aucune | Date de mise en vigueur des présents paramètres |
| **zl_config_parametres** | `date_debut_challenge_zl` | `date` | Non | DEFAULT `curdate()` | Date officielle de démarrage de la session du challenge |
| **zl_config_parametres** | `date_fin_challenge_zl` | `date` | Non | Aucune | Date officielle de clôture de la session du challenge |
| **zl_config_parametres** | `penalite_jour_non_roule` | `decimal(10,2)` | Oui | DEFAULT `0.00` | Montant de la pénalité financière par véhicule inactif un jour ouvré |
| **zl_config_parametres** | `malus_infraction_conduite_repos` | `decimal(7,2)` | Oui | DEFAULT `50.00` | Pénalité en points appliquée par infraction aux temps de conduite et de repos |
| **zl_config_parametres** | `malus_rechargement_hors_zone` | `decimal(7,2)` | Oui | DEFAULT `50.00` | Pénalité en points appliquée par rechargement hors département limitrophe |
| **zl_departements_limitrophes** | `code_dept` | `varchar(3)` | Oui | **PK**, **FK** `ref_departements(code_dept)` | Code du département d'origine |
| **zl_departements_limitrophes** | `code_dept_limitrophe` | `varchar(3)` | Oui | **PK**, **FK** `ref_departements(code_dept)` | Code du département voisin limitrophe contigu |
| **zl_feuilles_otea** | `id_feuille` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de la feuille de route OTEA |
| **zl_feuilles_otea** | `id_equipe` | `int(11)` | Oui | **UNIQUE** (`uq_equipe_vehicule`), **FK** `equipes(id_equipe)` | Identifiant de l'équipe exploitant le véhicule (ON DELETE CASCADE, ON UPDATE CASCADE) |
| **zl_feuilles_otea** | `id_vehicule` | `int(11)` | Oui | **UNIQUE** (`uq_equipe_vehicule`), **FK** `zl_vehicules(id_vehicule)` | Identifiant du véhicule affecté (ON UPDATE CASCADE) |
| **zl_feuilles_otea** | `date_debut_semaine` | `date` | Oui | Aucune | Date du lundi débutant la semaine d'exploitation |
| **zl_lignes_activite** | `id_ligne` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique de la ligne d'événement du planning |
| **zl_lignes_activite** | `id_feuille` | `int(11)` | Oui | **FK** `zl_feuilles_otea(id_feuille)` | Identifiant de la feuille OTEA de rattachement (ON DELETE CASCADE, ON UPDATE CASCADE) |
| **zl_lignes_activite** | `id_ot` | `int(11)` | Oui | **FK** `zl_ordres_transport(id_ot)` | Identifiant de l'ordre de transport exécuté lors de l'activité (ON UPDATE CASCADE) |
| **zl_lignes_activite** | `ordre_chronologique` | `int(11)` | Oui | Aucune | Rang séquentiel de l'activité dans le déroulement chronologique du planning |
| **zl_lignes_activite** | `date_activite` | `date` | Oui | Aucune | Date calendaire d'exécution de l'activité |
| **zl_lignes_activite** | `horaire_debut` | `decimal(4,2)` | Oui | Aucune | Heure de début en notation décimale (ex. : 6.50 = 06:30) |
| **zl_lignes_activite** | `type_activite` | `enum(...)` | Oui | Aucune | Nature de l'opération : `'Prise de service'`, `'Conduite'`, `'Chargement'`, `'Déchargement'`, `'Pause'`, `''` (repos), `'Fin de service'` |
| **zl_lignes_activite** | `km_parcouru` | `int(11)` | Non | DEFAULT `0` | Distance kilométrique réalisée lors d'une activité de conduite |
| **zl_lignes_activite** | `duree_repos_h` | `decimal(4,2)` | Non | Aucune | Durée du repos journalier calculée automatiquement en heures |
| **zl_lignes_activite** | `est_debut_journee` | `tinyint(1)` | Non | DEFAULT `0` | Indicateur booléen (1 si Prise de service marquant l'ouverture du jour) |
| **zl_lignes_activite** | `conduite_continue_cumul` | `decimal(10,2)` | Non | DEFAULT `0.00` | Temps cumulé de conduite continue sans interruption de pause (en heures) |
| **zl_lignes_activite** | `total_conduite_cumul` | `decimal(10,2)` | Non | DEFAULT `0.00` | Temps cumulé journalier de conduite du conducteur (en heures) |
| **zl_lignes_activite** | `total_service_cumul` | `decimal(10,2)` | Non | DEFAULT `0.00` | Temps cumulé journalier d'amplitude de service (travail + conduite) (en heures) |
| **zl_ordres_transport** | `id_ot` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant interne unique de l'ordre de transport |
| **zl_ordres_transport** | `numero_ot` | `varchar(50)` | Oui | **UNIQUE** (`uq_numero_ot`) | Référence commerciale / numéro de dossier unique de l'OT |
| **zl_ordres_transport** | `expediteur` | `varchar(100)` | Oui | Aucune | Nom ou raison sociale de l'expéditeur (lieu de chargement) |
| **zl_ordres_transport** | `rue_adresse_expediteur` | `varchar(150)` | Non | Aucune | Adresse postale de l'expéditeur (numéro et voie) |
| **zl_ordres_transport** | `cp_adresse_expediteur` | `varchar(10)` | Non | Aucune | Code postal du lieu d'enlèvement (permet de déduire le département) |
| **zl_ordres_transport** | `ville_adresse_expediteur` | `varchar(100)` | Non | Aucune | Ville de l'expéditeur |
| **zl_ordres_transport** | `destinataire` | `varchar(100)` | Oui | Aucune | Nom ou raison sociale du destinataire final (lieu de livraison) |
| **zl_ordres_transport** | `rue_adresse_destinataire` | `varchar(150)` | Non | Aucune | Adresse postale du destinataire (numéro et voie) |
| **zl_ordres_transport** | `cp_adresse_destinataire` | `varchar(10)` | Non | Aucune | Code postal du lieu de livraison (permet de déduire le département) |
| **zl_ordres_transport** | `ville_adresse_destinataire` | `varchar(100)` | Non | Aucune | Ville du destinataire |
| **zl_ordres_transport** | `nb_supports` | `int(11)` | Oui | Aucune | Nombre total d'unités de chargement (palettes, rolls, etc.) |
| **zl_ordres_transport** | `id_type_support` | `int(11)` | Oui | **FK** `zl_types_supports(id_type_support)` | Identifiant du type de support de manutention (ON UPDATE CASCADE) |
| **zl_ordres_transport** | `pbt` | `decimal(10,2)` | Oui | Aucune | Poids Brut Total de la cargaison exprimé en tonnes |
| **zl_ordres_transport** | `ca` | `decimal(10,2)` | Oui | Aucune | Chiffre d'affaires facturé au donneur d'ordre en euros (€) |
| **zl_ordres_transport** | `id_equipe` | `int(11)` | Non | **FK** `equipes(id_equipe)` | Équipe cliente initiale détentrice de l'OT (ON DELETE SET NULL) |
| **zl_types_supports** | `id_type_support` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique du type d'emballage / conditionnement |
| **zl_types_supports** | `intitule` | `varchar(50)` | Oui | Aucune | Libellé du conditionnement (Palette EURO, Palette ISO, Big-bags, Caisses, Futs, Rolls) |
| **zl_vehicules** | `id_vehicule` | `int(11)` | Oui | **PK** (AUTO_INCREMENT) | Identifiant unique du véhicule de la flotte |
| **zl_vehicules** | `libelle` | `varchar(50)` | Oui | Aucune | Nom ou code d'identification interne du véhicule (ex. : VE1, VE2) |
| **zl_vehicules** | `id_type` | `int(11)` | Oui | **FK** `ref_types_vehicule(id_type)` | Identifiant de la catégorie de véhicule |
| **zl_vehicules** | `id_carrosserie` | `int(11)` | Oui | **FK** `ref_carrosseries(id_carrosserie)` | Identifiant du type de carrosserie équipant le véhicule |
| **zl_vehicules** | `id_energie` | `int(11)` | Oui | **FK** `ref_energies(id_energie)` | Identifiant de l'énergie alimentant le moteur du véhicule |
