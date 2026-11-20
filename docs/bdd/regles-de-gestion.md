# Règles de Gestion - Challenge GTLA

Ce document répertorie l'ensemble des règles de gestion (RG) régissant le système d'information du **Challenge GTLA**.  
Elles sont établies à partir de l'analyse du script SQL (`bdd/challenge_gtla.sql`), du code métier PHP (`functions.php`, `actions.php`) et de la spécification fonctionnelle du `README.md`.

Chaque règle justifie une ou plusieurs cardinalités du Modèle Conceptuel de Données (MCD), les types d'associations, les contraintes d'intégrité référentielle (PK, FK, UNIQUE, NOT NULL) ainsi que les contrôles applicatifs.

---

## Sommaire
- [1. Structure organisationnelle (Lycées, Équipes, Utilisateurs)](#1-structure-organisationnelle)
- [2. Flotte et Matériel (Véhicules, Carrosseries, Énergies, Supports)](#2-flotte-et-matériel)
- [3. Géographie et Territoire (Départements et Voisinage)](#3-géographie-et-territoire)
- [4. Ordres de Transport (OT)](#4-ordres-de-transport-ot)
- [5. Affrètement entre Équipes](#5-affrètement-entre-équipes)
- [6. Planification et Feuilles OTEA](#6-planification-et-feuilles-otea)
- [7. Déroulement de la Journée et Réglementation](#7-déroulement-de-la-journée-et-réglementation)
- [8. Infractions, Pénalités et Malus](#8-infractions-pénalités-et-malus)
- [9. Rentabilité, Performance et Classement](#9-rentabilité-performance-et-classement)
- [10. Configuration Globale du Challenge](#10-configuration-globale-du-challenge)

---

## 1. Structure organisationnelle

### RG01 - Rattachement d'une équipe à un lycée
- **Énoncé** : Une équipe d'étudiants appartient obligatoirement à un et un seul lycée. Un lycée peut inscrire zéro, une ou plusieurs équipes au challenge.
- **Impact MCD / MLD** : Association `Rattacher_Equipe` entre `LYCEE` (0,n) et `EQUIPE` (1,1).  
  Clé étrangère `equipes.id_lycee` NOT NULL référençant `lycees(id_lycee)`.

### RG02 - Compte et authentification d'équipe
- **Énoncé** : Chaque équipe dispose d'un identifiant de connexion (`login_equipe`) unique au sein de la plateforme et d'un mot de passe chiffré (hash bcrypt). Deux équipes ne peuvent pas avoir le même login.
- **Impact MCD / MLD** : Contrainte d'unicité `UNIQUE (login_equipe)` sur la table `equipes`.

### RG03 - Rôles et rattachement des utilisateurs encadrants
- **Énoncé** : Les utilisateurs de l'espace administration ont soit le rôle `administrateur`, soit le rôle `professeur`.  
  Un professeur est rattaché à son lycée d'exercice (1,1 au niveau métier, 0,1 au niveau relationnel). Un administrateur dispose d'une vue transversale et n'est rattaché à aucun lycée particulier. Un lycée peut être rattaché à zéro, un ou plusieurs utilisateurs.
- **Impact MCD / MLD** : Association `Rattacher_Utilisateur` entre `LYCEE` (0,n) et `UTILISATEUR` (0,1).  
  Clé étrangère `utilisateurs.id_lycee` NULLable référençant `lycees(id_lycee)` avec `ON DELETE SET NULL`.

### RG04 - Unicité de l'identifiant utilisateur
- **Énoncé** : Chaque utilisateur dispose d'un identifiant de connexion unique.
- **Impact MCD / MLD** : Contrainte d'unicité `UNIQUE (identifiant)` sur la table `utilisateurs`.

---

## 2. Flotte et Matériel

### RG05 - Typologie d'un véhicule
- **Énoncé** : Tout véhicule de la flotte appartient obligatoirement à un et un seul type de véhicule (ex. : *Ensemble Articulé*, *Porteur*). Un type de véhicule regroupe zéro, un ou plusieurs véhicules.
- **Impact MCD / MLD** : Association `Typer_Vehicule` entre `REF_TYPES_VEHICULE` (0,n) et `ZL_VEHICULES` (1,1).  
  Clé étrangère `zl_vehicules.id_type` NOT NULL référençant `ref_types_vehicule(id_type)`.

### RG06 - Carrosserie d'un véhicule
- **Énoncé** : Tout véhicule dispose obligatoirement d'une et une seule carrosserie (ex. : *Fourgon*, *Tautliner*, *Frigorifique*, *Citerne*, *Plateau*). Une carrosserie peut équiper zéro, un ou plusieurs véhicules.
- **Impact MCD / MLD** : Association `Carrosser_Vehicule` entre `REF_CARROSSERIES` (0,n) et `ZL_VEHICULES` (1,1).  
  Clé étrangère `zl_vehicules.id_carrosserie` NOT NULL référençant `ref_carrosseries(id_carrosserie)`.

### RG07 - Motorisation et énergie d'un véhicule
- **Énoncé** : Tout véhicule fonctionne avec une et une seule énergie obligatoire (ex. : *Diesel*, *B100*, *Électrique*). Une énergie peut alimenter zéro, un ou plusieurs véhicules.
- **Impact MCD / MLD** : Association `Motoriser_Vehicule` entre `REF_ENERGIES` (0,n) et `ZL_VEHICULES` (1,1).  
  Clé étrangère `zl_vehicules.id_energie` NOT NULL référençant `ref_energies(id_energie)`.

### RG08 - Coût au kilomètre de l'énergie
- **Énoncé** : Chaque énergie possède un coût au kilomètre administrable (ex. : Diesel = 1.000 €/km, B100 = 1.150 €/km, Électrique = 0.800 €/km). Ce coût sert au calcul automatique du coût de revient des trajets effectués par les véhicules utilisant cette énergie.
- **Impact MCD / MLD** : Attribut `cout_km` dans l'entité `REF_ENERGIES` (`DECIMAL(6,3)`).

### RG09 - Conditionnement et type de support
- **Énoncé** : Un ordre de transport mentionne obligatoirement un et un seul type de support de manutention (ex. : *Palette EURO*, *Palette ISO*, *Big-bags*, *Caisses*, *Futs*, *Rolls*). Un type de support peut être utilisé par zéro, un ou plusieurs ordres de transport.
- **Impact MCD / MLD** : Association `Conditionner_OT` entre `ZL_TYPES_SUPPORTS` (0,n) et `ZL_ORDRES_TRANSPORT` (1,1).  
  Clé étrangère `zl_ordres_transport.id_type_support` NOT NULL référençant `zl_types_supports(id_type_support)`.

---

## 3. Géographie et Territoire

### RG10 - Référentiel des départements
- **Énoncé** : La France métropolitaine est découpée en départements identifiés par leur code officiel (`code_dept` sur 2 à 3 caractères, ex. : '01', '2A', '2B', '95'). Le code est la clé primaire naturelle.
- **Impact MCD / MLD** : Entité `REF_DEPARTEMENTS` ayant pour clé primaire `code_dept` (`VARCHAR(3)`).

### RG11 - Contiguïté et départements limitrophes (Association réflexive)
- **Énoncé** : Un département peut avoir zéro, un ou plusieurs départements limitrophes (voisins directs). La relation de contiguïté est réflexive (un département est lié à d'autres départements du même référentiel).
- **Impact MCD / MLD** : Association réflexive `Etre_Limitrophe` sur l'entité `REF_DEPARTEMENTS` avec les cardinalités (0,n) et (0,n).  
  Table de liaison physique `zl_departements_limitrophes` ayant pour clé primaire composite (`code_dept`, `code_dept_limitrophe`), les deux colonnes étant des clés étrangères vers `ref_departements(code_dept)`.

### RG12 - Déduction du département à partir du code postal
- **Énoncé** : Le département d'une adresse est déterminé par les deux premiers chiffres de son code postal. Cas particulier de la Corse : les codes postaux 200xx et 201xx correspondent à la Corse-du-Sud (`2A`), 202xx et supérieurs à la Haute-Corse (`2B`). Tout code postal non conforme ou hors métropole donne un département indéterminé.
- **Impact Applicatif** : Règle métier implémentée en PHP (`determinerDepartement()`). Pas de contrainte FK directe entre l'adresse texte et la table des départements.

---

## 4. Ordres de Transport (OT)

### RG13 - Identification et caractéristiques d'un OT
- **Énoncé** : Chaque ordre de transport possède un numéro unique (`numero_ot`), des adresses complètes pour l'expéditeur et le destinataire, un nombre de supports, un poids brut total (`pbt` en tonnes) et un chiffre d'affaires prévisionnel (`ca` en euros).
- **Impact MCD / MLD** : Attributs de `ZL_ORDRES_TRANSPORT` avec contrainte `UNIQUE (numero_ot)`.

### RG14 - Attribution initiale d'un OT à une équipe
- **Énoncé** : Lors de la constitution du challenge, chaque OT est attribué à une équipe cliente (ou reste temporairement non affecté lors de la préparation). Une équipe peut se voir attribuer zéro, un ou plusieurs OT.
- **Impact MCD / MLD** : Association `Attribuer_OT` entre `EQUIPE` (0,n) et `ZL_ORDRES_TRANSPORT` (0,1).  
  Clé étrangère `zl_ordres_transport.id_equipe` NULLable avec `ON DELETE SET NULL`.

### RG15 - Critère de complétion d'un OT (Statut « Traité »)
- **Énoncé** : Un OT est considéré comme **traité** (livré) dès lors qu'il fait l'objet d'au moins une ligne d'activité de type « Déchargement » dans un planning validé.
- **Impact Applicatif** : Fonction SQL / PHP comptant les lignes d'activité de type `'Déchargement'` associées à l'OT.

---

## 5. Affrètement entre Équipes

### RG16 - Proposition d'affrètement
- **Énoncé** : L'équipe détentrice (cliente) d'un OT peut décider de le sous-traiter (l'affréter) à une autre équipe concurrente. La proposition exige la désignation explicite de l'équipe affrétée et un prix d'affrètement strictement supérieur à 0 €. On ne peut pas proposer un OT à sa propre équipe.
- **Impact MCD / MLD** : Entité `ZL_AFFRETEMENTS` reliant l'OT à deux équipes distinctes via deux associations :
  - `Affreter_Proposer` (rôle affréteur) : `EQUIPE` (0,n) vers `ZL_AFFRETEMENTS` (1,1) via `id_equipe_affreteur`.
  - `Affreter_Recevoir` (rôle affrété) : `EQUIPE` (0,n) vers `ZL_AFFRETEMENTS` (1,1) via `id_equipe_affrete`.
  - `Affreter_OT` : `ZL_ORDRES_TRANSPORT` (0,n) vers `ZL_AFFRETEMENTS` (1,1) via `id_ot`.

### RG17 - Cycle de vie et statuts de l'affrètement
- **Énoncé** : Un affrètement évolue selon les statuts successifs : `en_attente`, `acceptee`, `refusee`, `annulee`.  
  - Dès création, l'affrètement est `en_attente`.  
  - L'équipe sollicitée peut accepter (`acceptee`) ou refuser (`refusee`) l'offre.  
  - L'équipe émettrice peut annuler (`annulee`) son offre tant qu'elle est en attente.
- **Impact MCD / MLD** : Colonne `zl_affretements.statut` de type `ENUM('en_attente','acceptee','refusee','annulee')` avec valeur par défaut `'en_attente'`.

### RG18 - Unicité de l'affrètement actif par OT
- **Énoncé** : Un ordre de transport ne peut faire l'objet que d'un seul affrètement actif (statut `en_attente` ou `acceptee`) à un instant donné. Un OT ne peut pas être proposé à l'affrètement s'il est déjà en cours d'exécution dans le planning de l'équipe d'origine.
- **Impact Applicatif** : Vérification dans `actions.php` avant insertion dans `zl_affretements`.

### RG19 - Droits d'exploitation post-affrètement
- **Énoncé** : Dès qu'un affrètement est accepté, seule l'équipe affrétée est autorisée à planifier et exécuter les activités de transport sur cet OT. L'équipe affréteuse conserve le suivi financier (marge = CA initial − prix d'affrètement payé).
- **Impact Applicatif** : Contrôle d'accès lors de la sélection des OT disponibles dans le planning.

---

## 6. Planification et Feuilles OTEA

### RG20 - Feuille de route hebdomadaire (OTEA)
- **Énoncé** : La planification des véhicules s'effectue au moyen de feuilles hebdomadaires OTEA. Une feuille OTEA est ouverte pour une équipe, pour un véhicule précis et pour une date de début de semaine donnée.
- **Impact MCD / MLD** : Entité `ZL_FEUILLES_OTEA` reliée par :
  - `Affecter_Equipe` : `EQUIPE` (0,n) vers `ZL_FEUILLES_OTEA` (1,1) via `id_equipe` (`ON DELETE CASCADE`).
  - `Affecter_Vehicule` : `ZL_VEHICULES` (0,n) vers `ZL_FEUILLES_OTEA` (1,1) via `id_vehicule`.

### RG21 - Unicité d'affectation véhicule-équipe
- **Énoncé** : Dans le schéma physique de la base, une contrainte d'unicité impose qu'un véhicule ne peut être associé qu'une seule fois à une équipe donnée (`uq_equipe_vehicule`).
- **Impact MCD / MLD** : Contrainte physique `UNIQUE (id_equipe, id_vehicule)` sur `zl_feuilles_otea`.

### RG22 - Composition d'une feuille en lignes d'activité
- **Énoncé** : Une feuille OTEA est constituée d'une suite ordonnée de lignes d'activité décrivant le travail du véhicule jour par jour. Une ligne d'activité appartient obligatoirement à une feuille OTEA et se rapporte obligatoirement à un OT.
- **Impact MCD / MLD** : Entité `ZL_LIGNES_ACTIVITE` reliée par :
  - `Contenir_Activite` : `ZL_FEUILLES_OTEA` (0,n) vers `ZL_LIGNES_ACTIVITE` (1,1) via `id_feuille` (`ON DELETE CASCADE`).
  - `Concerner_OT` : `ZL_ORDRES_TRANSPORT` (0,n) vers `ZL_LIGNES_ACTIVITE` (1,1) via `id_ot`.

---

## 7. Déroulement de la Journée et Réglementation

### RG23 - Typologie des activités
- **Énoncé** : Les activités réalisables au cours d'une journée sont strictement définies :
  - `Prise de service` : début de la journée de travail.
  - `Chargement` : mise à bord des marchandises.
  - `Conduite` : acheminement routier avec kilométrage associé.
  - `Déchargement` : livraison chez le destinataire.
  - `Pause` : interruption de conduite / travail.
  - `Fin de service` : clôture de la journée de travail.
  - `Repos journalier` : plage de repos obligatoire entre deux journées de service (enregistrée avec un libellé technique vide `''` dans l'énumération SQL).
- **Impact MCD / MLD** : Colonne `zl_lignes_activite.type_activite` de type `ENUM`.

### RG24 - Structure obligatoire d'une journée
- **Énoncé** : Chaque journée de travail commence obligatoirement par une unique « Prise de service » (`est_debut_journee = 1`). Aucune activité ne peut être insérée chronologiquement après la « Fin de service ».
- **Impact Applicatif** : Contrôle d'intégrité temporelle lors de l'enregistrement dans `actions.php`.

### RG25 - Calcul des durées d'activité
- **Énoncé** :
  - La durée d'une activité de **Conduite** est calculée automatiquement : `km_parcouru ÷ vitesse_moyenne_kmh` (ex. : 75 km/h par défaut).
  - Le **Chargement**, le **Déchargement** et la **Pause** ont une durée forfaitaire fixe définie dans la configuration (par défaut : 1h00, 1h00 et 45 min).
  - La **Prise de service** et la **Fin de service** ont une durée instantanée (0h).
- **Impact Applicatif** : Calcul des cumuls et horaires de fin d'activité dans le moteur PHP.

### RG26 - Butoir de fin de journée
- **Énoncé** : Toute activité dotée d'une durée doit impérativement se terminer au plus tard à minuit (24:00) le jour même. Tout enregistrement ou modification provoquant un dépassement horaire au-delà de 24:00 est rejeté.
- **Impact Applicatif** : Contrôle de saisie dans le formulaire de planification.

### RG27 - Calcul automatique et inviolabilité du repos journalier
- **Énoncé** : Le repos journalier séparant le jour J du jour J+1 est calculé automatiquement :
  $$\text{Repos}(J) = 24\text{ h} + \text{Heure de prise de service}(J+1) - \text{Heure de fin de service}(J)$$
  Cette ligne est générée automatiquement en dernière position du jour J dès que la prise de service du jour J+1 est saisie. Elle n'est ni saisissable, ni modifiable, ni supprimable par l'utilisateur.
- **Impact Applicatif / BDD** : Ligne injectée avec `duree_repos_h` renseigné et recalculée à chaque mise à jour du planning.

### RG28 - Seuil minimal de repos journalier
- **Énoncé** : La durée de repos journalier doit atteindre au minimum le seuil réglementaire paramétré (`repos_journalier_min_h`, 9h00 par défaut). Si la prise de service du lendemain réduit le repos sous ce seuil, la saisie est bloquée en indiquant l'heure minimale de reprise autorisée.
- **Impact Applicatif** : Validation de conformité réglementaire avant enregistrement du jour J+1.

---

## 8. Infractions, Pénalités et Malus

### RG29 - Détection des infractions de conduite et de repos
- **Énoncé** : Chaque ligne d'activité est analysée au regard des cumuls temporels de sécurité :
  1. **Conduite continue** : le temps de conduite ininterrompue ne doit pas dépasser 4h30 (une pause de 45 min remet ce compteur à zéro).
  2. **Conduite journalière** : le cumul de conduite sur une journée ne doit pas dépasser 10h00.
  3. **Amplitude / Service journalier** : le temps total de service (travail + conduite) ne doit pas dépasser 12h00 dans la journée.
  Chaque dépassement constaté constitue une infraction distincte (une ligne pouvant cumuler plusieurs infractions).
- **Impact MCD / BDD** : Colonnes de cumul dans `zl_lignes_activite` (`conduite_continue_cumul`, `total_conduite_cumul`, `total_service_cumul`).

### RG30 - Règle de rechargement en zone (Départements limitrophes)
- **Énoncé** : Pour un véhicule donné, après avoir livré un OT dans un département d'arrivée $D_{\text{livraison}}$, l'OT suivant pris en charge par ce même véhicule doit impérativement avoir son expéditeur situé :
  - soit dans le même département ($D_{\text{depart}} = D_{\text{livraison}}$) ;
  - soit dans un département limitrophe déclaré dans la table `zl_departements_limitrophes`.  
  Dans le cas contraire, un « rechargement hors zone » est constaté.
- **Impact MCD / Applicatif** : Jointure de vérification avec la table `zl_departements_limitrophes`.

### RG31 - Application des malus
- **Énoncé** : Chaque infraction de conduite/repos et chaque rechargement hors zone entraîne un malus forfaitaire en points (par défaut 50 points par infraction, paramétrable dans `zl_config_parametres`).
- **Impact Applicatif** : Déduction des points lors du calcul du score global de l'équipe.

---

## 9. Rentabilité, Performance et Classement

### RG32 - Éligibilité financière des OT
- **Énoncé** : Seuls les ordres de transport entièrement **traités** (livrés avec ligne de déchargement) sont pris en compte dans le calcul du chiffre d'affaires, des coûts et des marges.
- **Impact Applicatif** : Filtre strict sur le statut de livraison dans les requêtes de rentabilité.

### RG33 - Taux de marge d'affrètement
- **Énoncé** : Pour une équipe affréteuse, le taux de marge sur les OT sous-traités livrés est calculé selon :
  $$\text{Taux Marge Affrètement} = \frac{\sum (\text{CA client} - \text{Prix payé})}{\sum \text{CA client}}$$
- **Impact Applicatif** : Calcul consolidé dans `functions.php`.

### RG34 - Taux de marge d'exploitation
- **Énoncé** : Pour les OT livrés par les véhicules propres de l'équipe (OT propres + OT acceptés en affrètement), le taux de marge est calculé selon :
  $$\text{Taux Marge Exploitation} = \frac{\sum (\text{Revenu} - \text{Coût de revient km})}{\sum \text{Revenu}}$$
  Le coût de revient est la somme des kilomètres parcourus multipliés par le coût/km de l'énergie du véhicule utilisé.
- **Impact Applicatif** : Calcul basé sur les lignes de conduite et le référentiel des énergies.

### RG35 - Barème de conversion en points
- **Énoncé** : Les taux de marge sont convertis en points (sur 1000) par interpolation linéaire entre paliers :
  - 0 % de marge $\rightarrow$ 0 point
  - 5 % de marge $\rightarrow$ 250 points
  - 15 % de marge $\rightarrow$ 500 points
  - 25 % de marge $\rightarrow$ 750 points
  - 40 % et plus $\rightarrow$ 1000 points (plafond)
- **Impact Applicatif** : Fonction d'interpolation `calculerPointsMarge()`.

### RG36 - Score global et Coefficient de traitement
- **Énoncé** :
  1. $\text{Score calculé} = \max\left(0\,;\,\frac{1}{3} \times \text{Points Affrètement} + \frac{2}{3} \times \text{Points Exploitation} - \sum \text{Malus}\right)$
  2. $\text{Coefficient de traitement} = \frac{\text{OT traités}}{\text{OT à traiter}}$ (OT à traiter = OT clients + OT affrétés acceptés).
  3. $\text{Score final} = \text{Score calculé} \times \text{Coefficient de traitement}$.
- **Impact Applicatif** : Algorithme de classement général de la compétition.

---

## 10. Configuration Globale du Challenge

### RG37 - Paramétrage unique du challenge (Table singleton)
- **Énoncé** : Les paramètres régissant le calcul du challenge (vitesse moyenne, durées forfaitaires, seuil de repos, dates du challenge, pénalités et malus) sont stockés dans une unique ligne de configuration (`id_config = 1`). Cette entité ne possède aucun lien relationnel direct avec les autres tables de la base.
- **Impact MCD / MLD** : Entité isolée `ZL_CONFIG_PARAMETRES` sans clé étrangère, administrable uniquement par les enseignants.
