# Challenge GTLA

Application web qui aide les enseignants de BTS Transport et Logistique (GTLA) à piloter un challenge national entre équipes d'étudiants : ordres de transport, affrètement entre équipes, planning « zone longue » par véhicule et par jour, rentabilité et classement.

Projet réalisé par des étudiants de BTS SIO option SLAM, encadrés par leur enseignant.

## Sommaire

1. [Fonctionnement général](#1-fonctionnement-général)
2. [Installation en local](#2-installation-en-local)
3. [Règles métier](#3-règles-métier)
4. [Règles de développement](#4-règles-de-développement)
5. [Organisation des fichiers](#5-organisation-des-fichiers)
6. [Pistes d'évolution](#6-pistes-dévolution)

## 1. Fonctionnement général

L'application a deux espaces.

| Espace | Adresse | Utilisateurs | Rôle |
|---|---|---|---|
| Espace équipes | `/` | Équipes d'étudiants GTLA | Planifier les véhicules jour par jour, proposer ou accepter des affrètements, suivre la rentabilité |
| Administration | `/admin` | Enseignants (rôles `administrateur` et `professeur`) | Gérer les lycées, équipes, utilisateurs, véhicules, énergies, départements, paramètres, ordres de transport, et consulter le classement |

Le déroulé d'un challenge :

1. L'administration crée les équipes et leur attribue des **ordres de transport (OT)**, saisis à la main ou importés depuis un fichier Excel (modèle `import/modele_import_ot.xlsx`).
2. Chaque équipe décide, pour chacun de ses OT, de l'**exécuter** avec ses propres véhicules ou de le **sous-traiter** (affrètement) à une autre équipe.
3. Pour chaque véhicule et chaque jour du challenge, l'équipe saisit son **planning** : prise de service, chargement, conduite, déchargement, pause, fin de service.
4. L'application calcule les durées, les cumuls de conduite et de service, les s, les marges et le **classement**.

## 2. Installation en local

### Prérequis

- PHP 8.2 (extension `pdo_mysql`)
- MariaDB 10.4 (ou plus récent)
- Apache, ou le serveur intégré de PHP pour un test rapide

XAMPP 8.2 fournit ces trois composants. Une installation avec Docker est aussi possible.

### Créer la base

La base complète, avec des données de démonstration, est dans `bdd/challenge_gtla.sql`.

```bash
mysql -u root -p -e "CREATE DATABASE challenge_gtla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p challenge_gtla < bdd/challenge_gtla.sql
```

Avec phpMyAdmin : créer la base `challenge_gtla` (interclassement `utf8mb4_unicode_ci`), puis onglet **Importer** et choisir `bdd/challenge_gtla.sql`.

### Configurer la connexion

`db.php` lit les paramètres de connexion dans des variables d'environnement. Sans elles, il utilise les valeurs par défaut de XAMPP : aucune configuration n'est nécessaire pour une installation locale standard.

| Variable d'environnement | Valeur par défaut |
|---|---|
| `GTLA_DB_HOST` | `localhost` |
| `GTLA_DB_NAME` | `challenge_gtla` |
| `GTLA_DB_USER` | `root` |
| `GTLA_DB_PASS` | (vide) |

Ne jamais écrire un mot de passe réel dans `db.php` : le dépôt est public.

### Comptes de démonstration

Ces comptes n'existent que dans la base de démonstration. **Changer ces mots de passe avant toute mise en ligne.**

| Espace | Identifiant | Mot de passe | Rôle |
|---|---|---|---|
| Administration | `admin.demo` | `Admin-Demo-2026` | administrateur |
| Administration | `admin2.demo` | `Admin-Demo-2026` | administrateur |
| Administration | `prof.demo` | `Prof-Demo-2026` | professeur |
| Équipes | `sio-transports` | `Equipe-Demo-2026` | équipe « Sio Transports » |
| Équipes | `pmf-transports` | `Equipe-Demo-2026` | équipe « PMF Transports » |

Pour générer le hash d'un nouveau mot de passe :

```bash
php -r "echo password_hash('NouveauMotDePasse', PASSWORD_BCRYPT), PHP_EOL;"
```

## 3. Règles métier

Ces règles ont été validées par l'enseignant responsable du projet. **Ne pas les modifier sans son accord.**

### 3.1 Ordres de transport (OT)

- Un OT décrit un transport : expéditeur et destinataire (rue, code postal, ville), nombre et type de supports (palettes, rolls…), poids brut total, chiffre d'affaires (CA), équipe cliente.
- Le **département** d'une adresse est déduit des 2 premiers chiffres du code postal. Exception de la Corse : 200xx et 201xx → `2A` (Corse-du-Sud), 202xx et au-delà → `2B` (Haute-Corse). Un code postal qui n'a pas 5 chiffres, ou hors métropole, donne un département inconnu.
- Un OT est **traité** (livré) dès qu'il a au moins une ligne d'activité « Déchargement ».

### 3.2 Affrètement entre équipes

- L'équipe cliente d'un OT peut le proposer à une autre équipe, avec un prix d'affrètement supérieur à 0.
- L'équipe sollicitée **doit accepter** avant que l'OT apparaisse dans son planning. Elle peut aussi refuser : le refus est affiché explicitement à l'équipe qui a proposé.
- L'équipe qui a proposé peut **annuler** sa proposition tant qu'elle est en attente.
- Un OT n'a qu'un seul affrètement actif (en attente ou accepté) à la fois.
- On ne peut pas proposer un OT déjà exploité (des activités sont déjà saisies dessus), ni se proposer un OT à soi-même.
- Une fois l'affrètement accepté, seule l'équipe affrétée peut saisir des activités sur l'OT. L'équipe cliente garde le suivi financier : son CA moins le prix payé.

Statuts : `en_attente`, `acceptee`, `refusee`, `annulee`.

### 3.3 Véhicules et énergies

- Chaque véhicule a un type (ensemble articulé, porteur), une carrosserie et une **énergie obligatoire** : Diesel, B100 ou Électrique.
- Chaque énergie a un **coût au km**, réglable dans l'administration (page « Énergies »).
- Le coût de revient d'un OT = somme, sur ses lignes de conduite, des km parcourus × coût au km de l'énergie du véhicule utilisé. Ce coût n'est jamais saisi à la main.

### 3.4 Planning par véhicule et par jour

- Le planning se saisit par véhicule et par jour, dans la plage de dates du challenge (paramètres).
- Une journée commence obligatoirement par une **prise de service** (une seule par jour). Aucune activité ne peut être ajoutée après la **fin de service**.
- Durées : la conduite dure km ÷ vitesse moyenne ; chargement, déchargement et pause ont une durée fixe réglée dans les paramètres.
- **Fin de journée** : une activité qui dure (conduite, chargement, déchargement, pause) doit être terminée au plus tard à minuit (fin à 24:00 acceptée). Une saisie ou une modification qui ferait déborder une activité est refusée. Les suppressions ne sont pas contrôlées, pour pouvoir corriger une journée déjà en dépassement.

### 3.5  (calculé automatiquement)

- Repos du jour D = 24 h + heure de prise de service du jour D+1 − heure de fin du jour D.
- Il est ajouté automatiquement en dernière ligne du jour D. Il n'est **ni saisissable, ni modifiable, ni supprimable**, et il est recalculé à chaque écriture dans le planning.
- Minimum : paramètre `repos_journalier_min_h` (9 h par défaut). Une saisie qui ferait passer un repos sous le minimum est refusée, avec un message qui donne l'heure de prise de service minimale du lendemain.
- Pas de repos pour le dernier jour du challenge, ni si le lendemain n'a pas de prise de service. Le repos est calculé dès que le lendemain a une prise de service, même sans « Fin de service » le jour D.

### 3.6 Infractions et pénalités

- **Conduite et repos** : chaque ligne d'activité qui dépasse un des seuils suivants compte comme une infraction (une ligne peut en cumuler plusieurs) :
  - conduite continue > 4 h 30 (une pause remet ce compteur à zéro) ;
  - conduite > 10 h dans la journée ;
  - service > 12 h dans la journée.
- **Rechargement hors zone** : pour un même véhicule, si l'OT livré arrive dans le département X, l'OT suivant doit partir du département X ou d'un département limitrophe de X, sinon pénalité. Un département inconnu ne donne pas de pénalité. La table des départements limitrophes est administrable.
- Chaque infraction coûte un malus réglable dans les paramètres (50 points par défaut).

### 3.7 Rentabilité et classement

- Seuls les **OT livrés** comptent dans les marges.
- **Points d'une catégorie** (sur 1000) : interpolation linéaire du taux de marge entre les points d'ancrage suivants.

  | Taux de marge | 0 % | 5 % | 15 % | 25 % | 40 % et plus |
  |---|---|---|---|---|---|
  | Points | 0 | 250 | 500 | 750 | 1000 |

  Exemple : 10 % de marge donne 375 points. Une marge de 92 % donne 1000 points : c'est le plafond, pas un bug.
- **Affrètement** : taux = (CA client − prix payé) ÷ CA client, sur les OT que l'équipe a sous-traités et qui ont été livrés.
- **Exploitation** : taux = (CA ou prix reçu − coût de revient) ÷ CA ou prix reçu, sur les OT que l'équipe a livrés elle-même (les siens et ceux qu'elle exécute pour une autre équipe).
- **Score calculé** = max(0 ; 1/3 × points affrètement + 2/3 × points exploitation − infractions conduite/repos × malus − rechargements hors zone × malus).
- **Coefficient de traitement** = OT traités ÷ OT à traiter. Les OT à traiter sont les OT clients de l'équipe plus les OT qu'elle a acceptés en affrètement. Un OT sous-traité ne compte comme traité que si l'équipe affrétée l'a réellement livré. Une équipe sans OT à traiter a un coefficient de 1.
- **Score final** = score calculé × coefficient de traitement. Le plancher à 0 est appliqué avant le coefficient.

## 4. Règles de développement

- PHP sans framework, PDO, Bootstrap 5. MariaDB 10.4 : pas de contrainte `CHECK`, les règles sont contrôlées en PHP.
- Requêtes SQL **toujours préparées** ; `htmlspecialchars` sur tout affichage de donnée.
- Toute écriture : méthode POST + jeton CSRF (`verifierCsrfToken`) + contrôle de propriété de la ressource (`ligneAppartientEquipe`, `otAppartientEquipe`).
- Constantes et fonctions communes dans `functions.php`.
- Commentaires pédagogiques en français.
- `admin/includes/config-zl.php` utilise des fins de ligne CRLF : les conserver.
- Après chaque modification d'un fichier PHP : `php -l fichier.php`.
- Aucun identifiant ni mot de passe dans le dépôt.

## 5. Organisation des fichiers

```
challenge_gtla/
├── index.php            espace équipes (connexion, routage des pages)
├── actions.php          traitements de l'espace équipes (planning, affrètement)
├── functions.php        constantes métier, sécurité, calculs (rentabilité, classement)
├── db.php               connexion PDO
├── includes/            pages de l'espace équipes (planning, affrètement, rentabilité)
├── admin/               administration (index.php, actions.php, includes/)
├── import/              modèle et exemple de fichier Excel d'import des OT
└── bdd/challenge_gtla.sql   base complète avec données de démonstration
```

## 6. Pistes d'évolution

À définir avec l'enseignant avant de coder :

- pénalité de retard de livraison (nécessite une date de livraison prévue sur les OT) ;
- pénalité de kilomètres à vide (nécessite un moyen de déclarer les trajets à vide) ;
- horaire de clôture, classement quotidien intermédiaire, historique des pertes de points.
