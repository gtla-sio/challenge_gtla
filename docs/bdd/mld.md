# Modèle Logique de Données (MLD) - Challenge GTLA

Ce document présente le **Modèle Logique de Données (schéma relationnel)** issu de la rétro-conception de la base de données `challenge_gtla`.  
Conformément aux conventions de modélisation Merise / relationnelle :
- Les **clés primaires** sont <u>soulignées</u>.
- Les **clés étrangères** sont suivies du symbole **#**.

---

## 1. Schéma Relationnel Textuel

### 1.1 Tables de Référence

- **REF_TYPES_VEHICULE** (<u>id_type</u>, libelle)
- **REF_CARROSSERIES** (<u>id_carrosserie</u>, libelle)
- **REF_ENERGIES** (<u>id_energie</u>, libelle, cout_km)
- **REF_DEPARTEMENTS** (<u>code_dept</u>, nom_dept)
- **ZL_TYPES_SUPPORTS** (<u>id_type_support</u>, intitule)

---

### 1.2 Entités Métier Principales

- **LYCEES** (<u>id_lycee</u>, nom_lycee, ville)

- **EQUIPES** (<u>id_equipe</u>, nom_equipe, login_equipe, mot_de_passe, id_lycee#)
  - Clé étrangère : `id_lycee#` fait référence à `LYCEES(id_lycee)`

- **UTILISATEURS** (<u>id_utilisateur</u>, identifiant, mot_de_passe, role, id_lycee#, date_creation)
  - Clé étrangère : `id_lycee#` fait référence à `LYCEES(id_lycee)` (optionnelle / NULLable)

- **ZL_VEHICULES** (<u>id_vehicule</u>, libelle, id_type#, id_carrosserie#, id_energie#)
  - Clé étrangère : `id_type#` fait référence à `REF_TYPES_VEHICULE(id_type)`
  - Clé étrangère : `id_carrosserie#` fait référence à `REF_CARROSSERIES(id_carrosserie)`
  - Clé étrangère : `id_energie#` fait référence à `REF_ENERGIES(id_energie)`

- **ZL_ORDRES_TRANSPORT** (<u>id_ot</u>, numero_ot, expediteur, rue_adresse_expediteur, cp_adresse_expediteur, ville_adresse_expediteur, destinataire, rue_adresse_destinataire, cp_adresse_destinataire, ville_adresse_destinataire, nb_supports, pbt, ca, id_type_support#, id_equipe#)
  - Clé étrangère : `id_type_support#` fait référence à `ZL_TYPES_SUPPORTS(id_type_support)`
  - Clé étrangère : `id_equipe#` fait référence à `EQUIPES(id_equipe)` (optionnelle / NULLable)

- **ZL_FEUILLES_OTEA** (<u>id_feuille</u>, id_equipe#, id_vehicule#, date_debut_semaine)
  - Clé étrangère : `id_equipe#` fait référence à `EQUIPES(id_equipe)`
  - Clé étrangère : `id_vehicule#` fait référence à `ZL_VEHICULES(id_vehicule)`
  - Contrainte d'unicité : `UNIQUE (id_equipe#, id_vehicule#)`

- **ZL_LIGNES_ACTIVITE** (<u>id_ligne</u>, id_feuille#, id_ot#, ordre_chronologique, date_activite, horaire_debut, type_activite, km_parcouru, duree_repos_h, est_debut_journee, conduite_continue_cumul, total_conduite_cumul, total_service_cumul)
  - Clé étrangère : `id_feuille#` fait référence à `ZL_FEUILLES_OTEA(id_feuille)`
  - Clé étrangère : `id_ot#` fait référence à `ZL_ORDRES_TRANSPORT(id_ot)`

---

### 1.3 Tables d'Association et Relations Spécifiques

- **ZL_AFFRETEMENTS** (<u>id_affretement</u>, id_ot#, id_equipe_affreteur#, id_equipe_affrete#, prix_affretement, statut, date_declaration, date_reponse)
  - Clé étrangère : `id_ot#` fait référence à `ZL_ORDRES_TRANSPORT(id_ot)`
  - Clé étrangère : `id_equipe_affreteur#` fait référence à `EQUIPES(id_equipe)` (équipe cédante)
  - Clé étrangère : `id_equipe_affrete#` fait référence à `EQUIPES(id_equipe)` (équipe cessionnaire)

- **ZL_DEPARTEMENTS_LIMITROPHES** (<u>code_dept#</u>, <u>code_dept_limitrophe#</u>)
  - Clé primaire composite : (<u>code_dept#</u>, <u>code_dept_limitrophe#</u>)
  - Clé étrangère : `code_dept#` fait référence à `REF_DEPARTEMENTS(code_dept)`
  - Clé étrangère : `code_dept_limitrophe#` fait référence à `REF_DEPARTEMENTS(code_dept)`

---

### 1.4 Table de Configuration (Singleton isolée)

- **ZL_CONFIG_PARAMETRES** (<u>id_config</u>, vitesse_moyenne_kmh, temps_chargement_h, temps_dechargement_h, temps_pause_h, repos_journalier_min_h, heure_prise_service_defaut, date_application, date_debut_challenge_zl, date_fin_challenge_zl, penalite_jour_non_roule, malus_infraction_conduite_repos, malus_rechargement_hors_zone)
  - Aucune clé étrangère (table autonome paramétrant l'application).

---

## 2. Dépendances Fonctionnelles et Contraintes d'Intégrité Référentielle

```
LYCEES (id_lycee)
   ▲          ▲
   │ (1,1)    │ (0,1)
   │          └────────────────────────── UTILISATEURS (id_lycee#)
   │
EQUIPES (id_equipe)
   ▲          ▲          ▲
   │ (0,1)    │ (1,1)    │ (1,1)
   │          │          ├─────────────── ZL_AFFRETEMENTS (id_equipe_affreteur#)
   │          │          └─────────────── ZL_AFFRETEMENTS (id_equipe_affrete#)
   │          │
   │          └────────────────────────── ZL_FEUILLES_OTEA (id_equipe#)
   │
ZL_ORDRES_TRANSPORT (id_equipe#)
   ▲          ▲
   │ (1,1)    │ (1,1)
   │          └────────────────────────── ZL_AFFRETEMENTS (id_ot#)
   │
   └───────────────────────────────────── ZL_LIGNES_ACTIVITE (id_ot#)
                                                 ▲
                                                 │ (1,1)
ZL_FEUILLES_OTEA (id_feuille) ───────────────────┘ (id_feuille#)
   ▲
   │ (1,1)
ZL_VEHICULES (id_vehicule)
   ▲             ▲             ▲
   │ (1,1)       │ (1,1)       │ (1,1)
   │             │             └───────── REF_ENERGIES (id_energie)
   │             │
   │             └─────────────────────── REF_CARROSSERIES (id_carrosserie)
   │
   └───────────────────────────────────── REF_TYPES_VEHICULE (id_type)

ZL_TYPES_SUPPORTS (id_type_support)
   ▲
   │ (1,1)
   └───────────────────────────────────── ZL_ORDRES_TRANSPORT (id_type_support#)

REF_DEPARTEMENTS (code_dept)
   ▲                        ▲
   │ (1,1)                  │ (1,1)
   └─ ZL_DEPT_LIMITROPHES ──┘
      (code_dept#, code_dept_limitrophe#)
```

---

## 3. Analyse Comparative : MLD Théorique vs Script Physique SQL

La confrontation systématique entre le MLD théorique généré par rétro-conception du MCD et le script SQL réel `bdd/challenge_gtla.sql` met en lumière les points de convergence et les spécificités de réalisation :

| Table / Relation | Règle de transformation Merise $\rightarrow$ Relationnel | Observation dans le script SQL | Concordance / Justification de l'écart |
|---|---|---|:---:|
| `equipes` | L'association `Rattacher_Equipe` (1,1 vers LYCEE) migre la clé primaire `id_lycee` dans `equipes`. | Colonne `id_lycee int(11) NOT NULL` avec contrainte FK `fk_equipes_lycees`. | **Conforme** |
| `utilisateurs` | L'association `Rattacher_Utilisateur` (0,1 vers LYCEE) migre la clé `id_lycee` en tant que colonne optionnelle. | Colonne `id_lycee int(11) DEFAULT NULL` avec contrainte FK `ON DELETE SET NULL`. | **Conforme** |
| `zl_vehicules` | Les trois associations fonctionnelles (1,1 vers TYPE, CARROSSERIE, ENERGIE) migrent leurs identifiants respectifs. | Colonnes `id_type`, `id_carrosserie`, `id_energie` NOT NULL avec 3 contraintes FK. | **Conforme** |
| `zl_departements_limitrophes` | L'association réflexive `Etre_Limitrophe` (0,n - 0,n) engendre une table d'association avec clé primaire composite. | Table physique avec PK (`code_dept`, `code_dept_limitrophe`) et 2 contraintes FK vers `ref_departements`. | **Conforme** |
| `zl_affretements` | Au niveau conceptuel, une proposition d'affrètement lie un OT et deux équipes distinctes (affréteur et affrété). L'entité dispose de son identifiant propre `id_affretement`. | Table avec clé primaire artificielle `id_affretement` et 3 clés étrangères : `id_ot`, `id_equipe_affreteur`, `id_equipe_affrete`. | **Conforme**. La clé primaire artificielle permet de conserver l'historique de plusieurs offres successives pour un même OT (ex. : refusée puis acceptée). |
| `zl_feuilles_otea` | L'association binaire entre EQUIPE (0,n) et VEHICULE (0,n) engendre la table `zl_feuilles_otea` avec `id_equipe` et `id_vehicule`. | Clé primaire artificielle `id_feuille` + index unique `uq_equipe_vehicule (id_equipe, id_vehicule)`. | **Écart physique / conceptuel expliqué** : La présence de `date_debut_semaine` montre que conceptuellement une feuille devrait être unique par (équipe, véhicule, semaine). La contrainte SQL `uq_equipe_vehicule` empêche d'avoir plusieurs semaines pour un même véhicule (anomalie relevée dans les issues BDD). |
| `zl_lignes_activite` | Dépendances (1,1) vers `FEUILLE_OTEA` et vers `ORDRE_TRANSPORT`. | Clés étrangères `id_feuille` et `id_ot` NOT NULL. | **Conforme**. *Remarque métier* : Même pour les pauses et prises de service, un `id_ot` est techniquement obligatoire au niveau de la clé étrangère SQL (géré applicativement en affectant le premier OT de la journée). |
| `zl_config_parametres` | Entité singleton isolée sans cardinalité. | Table à 1 ligne sans clé étrangère. | **Conforme** |
| `cp_adresse` vs `ref_departements` | Dans une modélisation théorique stricte, l'adresse pourrait référencer la table `ref_departements`. | Dans le schéma physique, les codes postaux sont des chaînes libres `varchar(10)` sans contrainte FK vers `ref_departements`. | **Choix d'implémentation** : La déduction du département s'opère en PHP à partir des deux premiers chiffres du CP (avec cas particulier de la Corse). |
