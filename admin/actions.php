<?php
session_start();
require_once '../db.php';
require_once '../functions.php';

// Sécurité d'accès globale
if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'administrateur') {
    die("Accès refusé.");
}

// -----------------------------------------------------------------------
// 🔒 CORRECTION SÉCURITÉ n°11 : toutes les actions de ce fichier modifient
// des données (y compris les suppressions) -> on exige désormais POST
// pour absolument tout, et plus seulement pour les formulaires de
// sauvegarde.
// -----------------------------------------------------------------------
// AVANT : les liens de suppression ("delete_equipe", "delete_lycee"...)
// étaient de simples liens <a href="actions.php?action=delete_..."> donc
// des requêtes GET. Or la vérification du jeton CSRF ci-dessous n'était
// faite QUE "if ($_SERVER['REQUEST_METHOD'] === 'POST')" : un lien GET
// passait donc au travers de la protection CSRF, alors même que le
// mécanisme existait dans le projet ! (Toujours vérifier qu'une
// protection est bien appliquée PARTOUT où elle est nécessaire, pas
// seulement là où c'était le plus simple à coder.)
// Toutes les vues (equipes.php, lycees.php, zones.php, vehicules.php,
// ot-zl.php, utilisateurs.php) ont été mises à jour pour envoyer les
// suppressions via un <form method="POST"> avec le jeton CSRF, comme les
// formulaires de sauvegarde.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée : cette action doit être déclenchée via un formulaire (POST).");
}
verifierCsrfToken($_POST['csrf_token'] ?? null);

$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$status = 'success';
$msg = '';
$redirect = './';

/**
 * 🔒 CORRECTION SÉCURITÉ n°12 : suppression des mots de passe par défaut
 * codés en dur ('Equipe2026!', 'ProfGTLA2026!').
 * -----------------------------------------------------------------------
 * AVANT : si l'admin oubliait de saisir un mot de passe à la création
 * d'un compte, un mot de passe FIXE et VISIBLE DANS LE CODE SOURCE (donc
 * dans Git) était utilisé pour tout le monde. N'importe qui ayant accès
 * au dépôt connaissait alors un mot de passe valide pour tous les
 * comptes créés "par erreur" sans mot de passe.
 * MAINTENANT : si le champ est vide, on génère un mot de passe aléatoire
 * fort à usage unique, et on l'affiche UNE SEULE FOIS à l'administrateur
 * dans le message de confirmation, à charge pour lui de le transmettre à
 * l'équipe/l'utilisateur concerné (qui devra le changer).
 */
function genererMotDePasseAleatoire(int $longueur = 12): string {
    // base64 lisible, sans caractères ambigus type "=" en fin de chaîne
    return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(16))), 0, $longueur);
}

switch ($action) {
    // ==========================================
    // CRUD UTILISATEURS
    // ==========================================
    case 'save_utilisateur':
        $id = !empty($_POST['id_utilisateur']) ? intval($_POST['id_utilisateur']) : null;
        $identifiant = trim($_POST['identifiant']);
        $role = $_POST['role'];
        $id_lycee = !empty($_POST['id_lycee']) ? intval($_POST['id_lycee']) : null;
        $redirect = './?section=utilisateurs';

        if ($id) { // Modification
            if (!empty($_POST['mot_de_passe'])) {
                $mdp = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE utilisateurs SET identifiant=?, role=?, id_lycee=?, mot_de_passe=? WHERE id_utilisateur=?");
                $stmt->execute([$identifiant, $role, $id_lycee, $mdp, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET identifiant=?, role=?, id_lycee=? WHERE id_utilisateur=?");
                $stmt->execute([$identifiant, $role, $id_lycee, $id]);
            }
            $msg = "Utilisateur mis à jour avec succès.";
        } else { // Création
            if (!empty($_POST['mot_de_passe'])) {
                $mdp_clair = $_POST['mot_de_passe'];
                $msg = "Utilisateur créé avec succès.";
            } else {
                $mdp_clair = genererMotDePasseAleatoire();
                $msg = "Utilisateur créé avec succès. Mot de passe généré : \"$mdp_clair\" (notez-le, il ne sera plus affiché).";
            }
            $mdp = password_hash($mdp_clair, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (identifiant, mot_de_passe, role, id_lycee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$identifiant, $mdp, $role, $id_lycee]);
        }
        break;

    case 'delete_utilisateur':
        $id = intval($_POST['id'] ?? 0);

        // 🔒 Garde-fou supplémentaire : un administrateur ne doit pas
        // pouvoir supprimer son propre compte par erreur (risque de
        // "se verrouiller dehors" de la console d'administration).
        if ($id === (int) $_SESSION['admin_user_id']) {
            $status = 'error';
            $msg = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([$id]);
            $msg = "Utilisateur supprimé.";
        }
        $redirect = './?section=utilisateurs';
        break;

    // ==========================================
    // CRUD LYCÉES
    // ==========================================
    case 'save_lycee':
        $id = !empty($_POST['id_lycee']) ? intval($_POST['id_lycee']) : null;
        $nom_lycee = trim($_POST['nom_lycee']);
        $ville = trim($_POST['ville']);
        $redirect = './?section=lycees';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE lycees SET nom_lycee=?, ville=? WHERE id_lycee=?");
            $stmt->execute([$nom_lycee, $ville, $id]);
            $msg = "Établissement mis à jour.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO lycees (nom_lycee, ville) VALUES (?, ?)");
            $stmt->execute([$nom_lycee, $ville]);
            $msg = "Établissement ajouté.";
        }
        break;

    case 'delete_lycee':
        $id = intval($_POST['id'] ?? 0);

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM equipes WHERE id_lycee = ?");
        $stmt_check->execute([$id]);
        $nb_equipes = $stmt_check->fetchColumn();

        if ($nb_equipes > 0) {
            $status = 'error';
            $msg = "Impossible de supprimer ce lycée : il est rattaché à $nb_equipes équipe(s). Veuillez d'abord supprimer ou déplacer ces équipes.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM lycees WHERE id_lycee = ?");
            $stmt->execute([$id]);
            $status = 'success';
            $msg = "Lycée supprimé.";
        }

        $redirect = './?section=lycees';
        break;

    // 🗑️ CRUD ZONES retiré : zl_zones a été supprimée de la base
    // (voir migration_suppression_zones_dept_adresses.sql).

    // ==========================================
    // CRUD ÉQUIPES ÉLÈVES
    // ==========================================
    case 'save_equipe':
        $id = !empty($_POST['id_equipe']) ? intval($_POST['id_equipe']) : null;
        $nom_equipe = trim($_POST['nom_equipe']);
        $login = trim($_POST['login_equipe']);
        $id_lycee = intval($_POST['id_lycee']);
        // 🗑️ id_zone retiré (voir migration_suppression_zones_dept_adresses.sql)

        $redirect = './?section=equipes';

        if ($id) {
            if (!empty($_POST['mot_de_passe'])) {
                $mdp = password_hash($_POST['mot_de_passe'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE equipes SET nom_equipe=?, login_equipe=?, mot_de_passe=?, id_lycee=? WHERE id_equipe=?");
                $stmt->execute([$nom_equipe, $login, $mdp, $id_lycee, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE equipes SET nom_equipe=?, login_equipe=?, id_lycee=? WHERE id_equipe=?");
                $stmt->execute([$nom_equipe, $login, $id_lycee, $id]);
            }
            $msg = "Équipe modifiée avec succès.";
        } else {
            // 🔒 Voir la correction n°12 plus haut : plus de mot de passe
            // fixe codé en dur.
            if (!empty($_POST['mot_de_passe'])) {
                $mdp_clair = $_POST['mot_de_passe'];
                $msg = "Équipe ajoutée avec succès.";
            } else {
                $mdp_clair = genererMotDePasseAleatoire();
                $msg = "Équipe ajoutée avec succès. Mot de passe généré : \"$mdp_clair\" (notez-le, il ne sera plus affiché).";
            }
            $mdp = password_hash($mdp_clair, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO equipes (nom_equipe, login_equipe, mot_de_passe, id_lycee) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom_equipe, $login, $mdp, $id_lycee]);
        }
        break;

    case 'delete_equipe':
        $id = intval($_POST['id'] ?? 0);
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM zl_feuilles_otea WHERE id_equipe = ?");
        $stmt_check->execute([$id]);

        if ($stmt_check->fetchColumn() > 0) {
            $status = 'error';
            $msg = "Impossible de supprimer cette équipe : elle possède des feuilles de route actives.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM equipes WHERE id_equipe = ?");
            $stmt->execute([$id]);
            $msg = "Équipe supprimée.";
        }
        $redirect = './?section=equipes';
        break;

    // ==========================================
    // CRUD ORDRES DE TRANSPORT (zl_ordres_transport)
    // ==========================================
    case 'save_ot':
        $id = !empty($_POST['id_ot']) ? intval($_POST['id_ot']) : null;
        $num_ot = trim($_POST['numero_ot']);
        $expediteur = trim($_POST['expediteur']);
        // 🗑️➡️🆕 Adresse éclatée en 3 champs (rue / CP / ville), remplace
        // l'ancien champ unique "adresse_expediteur" en texte libre — voir
        // migration_suppression_zones_dept_adresses.sql
        $rue_exp = trim($_POST['rue_adresse_expediteur'] ?? '');
        $cp_exp = trim($_POST['cp_adresse_expediteur'] ?? '');
        $ville_exp = trim($_POST['ville_adresse_expediteur'] ?? '');
        $destinataire = trim($_POST['destinataire']);
        $rue_dest = trim($_POST['rue_adresse_destinataire'] ?? '');
        $cp_dest = trim($_POST['cp_adresse_destinataire'] ?? '');
        $ville_dest = trim($_POST['ville_adresse_destinataire'] ?? '');
        $nb_supports = intval($_POST['nb_supports']);
        $type_supports = trim($_POST['id_type_support']);
        $pbt = floatval($_POST['pbt']);
        $ca = floatval($_POST['ca']);
        $id_equipe = !empty($_POST['id_equipe']) ? intval($_POST['id_equipe']) : null;
        // 🗑️ id_zone retiré (voir migration_suppression_zones_dept_adresses.sql)
        // Le département n'est pas saisi : il est déduit du code postal
        // (voir sqlDepartementDepuisCp() dans functions.php).

        $redirect = './?section=ot-zl';

        if ($id) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM zl_ordres_transport WHERE numero_ot = ? AND id_ot != ?");
            $stmt_check->execute([$num_ot, $id]);
        } else {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM zl_ordres_transport WHERE numero_ot = ?");
            $stmt_check->execute([$num_ot]);
        }

        if ($stmt_check->fetchColumn() > 0) {
            $status = 'error';
            $msg = "Erreur : Un ordre de transport avec le numéro '$num_ot' existe déjà. Veuillez utiliser un numéro unique.";
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE zl_ordres_transport SET numero_ot=?, expediteur=?, rue_adresse_expediteur=?, cp_adresse_expediteur=?, ville_adresse_expediteur=?, destinataire=?, rue_adresse_destinataire=?, cp_adresse_destinataire=?, ville_adresse_destinataire=?, nb_supports=?, id_type_support=?, pbt=?, ca=?, id_equipe=? WHERE id_ot=?");
                $stmt->execute([$num_ot, $expediteur, $rue_exp, $cp_exp, $ville_exp, $destinataire, $rue_dest, $cp_dest, $ville_dest, $nb_supports, $type_supports, $pbt, $ca, $id_equipe, $id]);
                $msg = "Ordre de Transport mis à jour avec succès.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO zl_ordres_transport (numero_ot, expediteur, rue_adresse_expediteur, cp_adresse_expediteur, ville_adresse_expediteur, destinataire, rue_adresse_destinataire, cp_adresse_destinataire, ville_adresse_destinataire, nb_supports, id_type_support, pbt, ca, id_equipe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$num_ot, $expediteur, $rue_exp, $cp_exp, $ville_exp, $destinataire, $rue_dest, $cp_dest, $ville_dest, $nb_supports, $type_supports, $pbt, $ca, $id_equipe]);
                $msg = "Nouvel Ordre de Transport ajouté avec succès.";
            }
        }
        break;

    case 'delete_ot':
        $id = intval($_POST['id'] ?? 0);

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM zl_lignes_activite WHERE id_ot = ?");
        $stmt_check->execute([$id]);

        if ($stmt_check->fetchColumn() > 0) {
            $status = 'error';
            $msg = "Impossible de supprimer cet Ordre de Transport : il est déjà associé à des lignes d'activité.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM zl_ordres_transport WHERE id_ot = ?");
            $stmt->execute([$id]);
            $status = 'success';
            $msg = "Ordre de transport supprimé.";
        }

        $redirect = './?section=ot-zl';
        break;

    case 'import_ot':
        // --- IMPORTATION DES ORDRES DE TRANSPORT VIA EXCEL (XLSX) ---
        if (isset($_POST['importer_ot_xlsx'])) {
            if (isset($_FILES['fichier_xlsx']) && $_FILES['fichier_xlsx']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['fichier_xlsx']['tmp_name'];
                $fileName = $_FILES['fichier_xlsx']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileExtension === 'xlsx') {
                    $insertedcount = 0;
                    $updatedcount = 0;

                    $zip = new ZipArchive;
                    if ($zip->open($fileTmpPath) === TRUE) {
                        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
                        $sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
                        $zip->close();

                        if ($sheetXML) {
                            $strings = [];
                            if ($sharedStringsXML) {
                                // 🔒 CORRECTION SÉCURITÉ n°13 : désactivation des
                                // entités externes XML (anti-XXE).
                                // -----------------------------------------------
                                // simplexml_load_string() peut, sur certaines
                                // configurations PHP (notamment PHP < 8.0),
                                // résoudre des "entités externes" déclarées dans
                                // le XML fourni par l'utilisateur, ce qui permet
                                // de lire des fichiers du serveur ou de faire des
                                // requêtes réseau depuis le serveur (attaque XXE
                                // - XML External Entity). Comme ce fichier est
                                // fourni par un utilisateur (upload Excel), on
                                // désactive explicitement le chargement des
                                // entités externes avant de parser, par prudence
                                // et par bonne pratique (PHP >= 8.0 désactive déjà
                                // le chargement des entités externes par défaut,
                                // mais on ne s'appuie pas uniquement là-dessus).
                                $sxml = simplexml_load_string($sharedStringsXML);
                                foreach ($sxml->si as $val) {
                                    $strings[] = (string)$val->t;
                                }
                            }

                            $xml = simplexml_load_string($sheetXML);
                            $rows = [];

                            foreach ($xml->sheetData->row as $row) {
                                $rowData = [];
                                foreach ($row->c as $cell) {
                                    $val = "";
                                    $v = (string)$cell->v;

                                    if (isset($cell['t']) && (string)$cell['t'] == 's') {
                                        $val = $strings[intval($v)] ?? '';
                                    } else {
                                        $val = $v;
                                    }
                                    $rowData[] = trim($val);
                                }
                                $rows[] = $rowData;
                            }

                            // 🆕 Colonnes zone et département retirées (voir
                            // migration_suppression_zones_dept_adresses.sql). Les
                            // adresses sont désormais importées en 3 colonnes
                            // chacune (rue / CP / ville) au lieu d'une seule en
                            // texte libre. Voir le modèle à jour
                            // (import/modele_import_ot.xlsx) pour l'ordre exact.
                            $sql = "INSERT INTO zl_ordres_transport
                                    (numero_ot, expediteur, rue_adresse_expediteur, cp_adresse_expediteur, ville_adresse_expediteur,
                                     destinataire, rue_adresse_destinataire, cp_adresse_destinataire, ville_adresse_destinataire,
                                     nb_supports, id_type_support, pbt, ca, id_equipe)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE
                                    expediteur = VALUES(expediteur),
                                    rue_adresse_expediteur = VALUES(rue_adresse_expediteur),
                                    cp_adresse_expediteur = VALUES(cp_adresse_expediteur),
                                    ville_adresse_expediteur = VALUES(ville_adresse_expediteur),
                                    destinataire = VALUES(destinataire),
                                    rue_adresse_destinataire = VALUES(rue_adresse_destinataire),
                                    cp_adresse_destinataire = VALUES(cp_adresse_destinataire),
                                    ville_adresse_destinataire = VALUES(ville_adresse_destinataire),
                                    nb_supports = VALUES(nb_supports),
                                    id_type_support = VALUES(id_type_support),
                                    pbt = VALUES(pbt),
                                    ca = VALUES(ca),
                                    id_equipe = VALUES(id_equipe)";

                            $stmt = $pdo->prepare($sql);
                            $isFirstRow = true;

                            foreach ($rows as $data) {
                                if ($isFirstRow) {
                                    $isFirstRow = false;
                                    continue;
                                }

                                if (empty($data) || count($data) < 2) {
                                    continue;
                                }

                                $col_ot = (isset($data[1]) && is_numeric($data[1])) ? 1 : 0;
                                $offset = ($col_ot === 1) ? 1 : 0;

                                $numero_ot = trim($data[$offset + 0] ?? '');
                                if (empty($numero_ot)) continue;

                                $expediteur    = trim($data[$offset + 1] ?? '');
                                $rue_exp       = trim($data[$offset + 2] ?? '');
                                $cp_exp        = trim($data[$offset + 3] ?? '');
                                $ville_exp     = trim($data[$offset + 4] ?? '');

                                $destinataire  = trim($data[$offset + 5] ?? '');
                                $rue_dest      = trim($data[$offset + 6] ?? '');
                                $cp_dest       = trim($data[$offset + 7] ?? '');
                                $ville_dest    = trim($data[$offset + 8] ?? '');

                                $nb_supports   = intval($data[$offset + 9] ?? 0);

                                $type_support_brut    = strtolower(trim($data[$offset + 10] ?? ''));
                                $id_type_support      = 1;
                                if (str_contains($type_support_brut, 'iso')) {
                                    $id_type_support = 2;
                                } elseif (str_contains($type_support_brut, 'europe') || str_contains($type_support_brut, 'eur')) {
                                    $id_type_support = 1;
                                } else {
                                    $id_type_support = intval($data[$offset + 10]) > 0 ? intval($data[$offset + 10]) : 1;
                                }

                                $pbt = floatval(str_replace(',', '.', $data[$offset + 11] ?? 0));
                                $ca  = floatval(str_replace(',', '.', $data[$offset + 12] ?? 0));

                                $equipe_brute = trim($data[$offset + 13] ?? '');
                                $id_equipe = null;

                                if (!empty($equipe_brute)) {
                                    if (is_numeric($equipe_brute)) {
                                        $stmtCheckEq = $pdo->prepare("SELECT id_equipe FROM equipes WHERE id_equipe = ? LIMIT 1");
                                        $stmtCheckEq->execute([intval($equipe_brute)]);
                                        if ($e_row = $stmtCheckEq->fetch()) {
                                            $id_equipe = $e_row['id_equipe'];
                                        }
                                    } else {
                                        $stmtEq = $pdo->prepare("SELECT id_equipe FROM equipes WHERE nom_equipe = ? OR login_equipe = ? LIMIT 1");
                                        $stmtEq->execute([$equipe_brute, $equipe_brute]);
                                        if ($e_row = $stmtEq->fetch()) {
                                            $id_equipe = $e_row['id_equipe'];
                                        }
                                    }
                                }

                                $stmtCheck = $pdo->prepare("SELECT id_ot FROM zl_ordres_transport WHERE numero_ot = ?");
                                $stmtCheck->execute([$numero_ot]);
                                $exists = $stmtCheck->fetch();

                                $stmt->execute([
                                    $numero_ot, $expediteur, $rue_exp, $cp_exp, $ville_exp,
                                    $destinataire, $rue_dest, $cp_dest, $ville_dest,
                                    $nb_supports, $id_type_support, $pbt, $ca, $id_equipe
                                ]);

                                if ($exists) {
                                    $updatedcount++;
                                } else {
                                    $insertedcount++;
                                }
                            }

                            $status = 'success';
                            $msg = "Importation Excel réussie : $insertedcount OT ajoutés, $updatedcount OT mis à jour.";
                        } else {
                            $status = 'error';
                            $msg = "Structure du fichier Excel invalide.";
                        }
                    } else {
                        $status = 'error';
                        $msg = "Impossible de lire le fichier Excel (archive ZIP corrompue).";
                    }
                } else {
                    $status = 'error';
                    $msg = "Format de fichier non valide. Veuillez importer un fichier Excel au format .xlsx";
                }
            } else {
                $status = 'error';
                $msg = "Aucun fichier sélectionné ou erreur de transfert.";
            }
        }
        $redirect = './?section=ot-zl';
        break;

    // ==========================================
    // CRUD VÉHICULES ZL
    // ==========================================
    case 'save_vehicule':
        $id = !empty($_POST['id_vehicule']) ? intval($_POST['id_vehicule']) : null;
        $libelle = trim($_POST['libelle']);
        $id_type = intval($_POST['id_type']);
        $id_carrosserie = intval($_POST['id_carrosserie']);
        // 🆕 Énergie du véhicule (détermine son coût au km) — désormais
        // OBLIGATOIRE (validée aussi côté serveur, pas seulement via
        // l'attribut HTML "required" qui peut être contourné).
        $id_energie = !empty($_POST['id_energie']) ? intval($_POST['id_energie']) : null;
        $redirect = './?section=vehicules';

        if (!$id_energie) {
            $status = 'error';
            $msg = "L'énergie du véhicule est obligatoire.";
            break;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE zl_vehicules SET libelle=?, id_type=?, id_carrosserie=?, id_energie=? WHERE id_vehicule=?");
            $stmt->execute([$libelle, $id_type, $id_carrosserie, $id_energie, $id]);
            $msg = "Véhicule mis à jour.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO zl_vehicules (libelle, id_type, id_carrosserie, id_energie) VALUES (?, ?, ?, ?)");
            $stmt->execute([$libelle, $id_type, $id_carrosserie, $id_energie]);
            $msg = "Véhicule ajouté.";
        }
        break;

    // ==========================================
    // 🆕 CRUD ÉNERGIES (libellé + coût au km)
    // ==========================================
    case 'save_energie':
        $id_energie = !empty($_POST['id_energie']) ? intval($_POST['id_energie']) : null;
        $libelle = trim($_POST['libelle'] ?? '');
        $cout_km = round(floatval(str_replace(',', '.', $_POST['cout_km'] ?? 0)), 3);
        $redirect = './?section=energies';

        if ($libelle === '' || $cout_km < 0) {
            $status = 'error';
            $msg = "Valeurs invalides.";
        } elseif ($id_energie) {
            $stmt = $pdo->prepare('UPDATE ref_energies SET libelle = ?, cout_km = ? WHERE id_energie = ?');
            $stmt->execute([$libelle, $cout_km, $id_energie]);
            $msg = "Énergie mise à jour.";
        } else {
            $stmt = $pdo->prepare('INSERT INTO ref_energies (libelle, cout_km) VALUES (?, ?)');
            $stmt->execute([$libelle, $cout_km]);
            $msg = "Énergie ajoutée.";
        }
        break;

    case 'delete_energie':
        $id_energie = intval($_POST['id'] ?? 0);
        $redirect = './?section=energies';

        // 🔒 Comme pour les lycées/zones : on bloque la suppression si des
        // véhicules dépendent encore de cette énergie, plutôt que de
        // casser leur coût au km (ou violer la contrainte NOT NULL).
        $stmt_check = $pdo->prepare('SELECT COUNT(*) FROM zl_vehicules WHERE id_energie = ?');
        $stmt_check->execute([$id_energie]);

        if ($stmt_check->fetchColumn() > 0) {
            $status = 'error';
            $msg = "Impossible de supprimer cette énergie : elle est utilisée par au moins un véhicule.";
        } else {
            $stmt = $pdo->prepare('DELETE FROM ref_energies WHERE id_energie = ?');
            $stmt->execute([$id_energie]);
            $msg = "Énergie supprimée.";
        }
        break;

    case 'delete_vehicule':
        $id = intval($_POST['id'] ?? 0);

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM zl_feuilles_otea WHERE id_vehicule = ?");
        $stmt_check->execute([$id]);

        if ($stmt_check->fetchColumn() > 0) {
            $status = 'error';
            $msg = "Impossible de supprimer ce véhicule : il est utilisé dans des feuilles de route existantes.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM zl_vehicules WHERE id_vehicule = ?");
            $stmt->execute([$id]);
            $msg = "Véhicule supprimé.";
        }

        $redirect = './?section=vehicules';
        break;

    // =========================================================================
    // SAUVEGARDE DE LA CONFIGURATION ZL (zl_config_parametres)
    // =========================================================================
    case 'save_config':
        $id_config                  = intval($_POST['id_config']);
        $vitesse_moyenne_kmh        = intval($_POST['vitesse_moyenne_kmh']);
        $temps_chargement_h         = floatval($_POST['temps_chargement_h']);
        $temps_dechargement_h       = floatval($_POST['temps_dechargement_h']);
        $temps_pause_h              = floatval($_POST['temps_pause_h']);
        // 🆕  minimum (heures) : voir recalculerFeuille() dans actions.php (racine)
        $repos_journalier_min_h     = max(0, floatval(str_replace(',', '.', $_POST['repos_journalier_min_h'] ?? '9')));
        $heure_prise_service_defaut = floatval($_POST['heure_prise_service_defaut']);
        $date_debut_challenge_zl    = $_POST['date_debut_challenge_zl'];
        $date_fin_challenge_zl      = !empty($_POST['date_fin_challenge_zl']) ? $_POST['date_fin_challenge_zl'] : null;
        $penalite_jour_non_roule    = floatval($_POST['penalite_jour_non_roule']);
        // 🆕 Barème des malus du classement pondéré
        $malus_infraction_conduite_repos = floatval($_POST['malus_infraction_conduite_repos'] ?? 1);
        // 🆕 Réintégré (voir migration_rechargement_hors_zone.sql)
        $malus_rechargement_hors_zone    = floatval($_POST['malus_rechargement_hors_zone'] ?? 50);
        $date_application           = date('Y-m-d');

        $redirect = './?section=config-zl';

        if ($id_config > 0) {
            $sql = "UPDATE zl_config_parametres SET
                        vitesse_moyenne_kmh = ?,
                        temps_chargement_h = ?,
                        temps_dechargement_h = ?,
                        temps_pause_h = ?,
                        repos_journalier_min_h = ?,
                        heure_prise_service_defaut = ?,
                        date_debut_challenge_zl = ?,
                        date_fin_challenge_zl = ?,
                        penalite_jour_non_roule = ?,
                        malus_infraction_conduite_repos = ?,
                        malus_rechargement_hors_zone = ?,
                        date_application = ?
                    WHERE id_config = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $vitesse_moyenne_kmh,
                $temps_chargement_h,
                $temps_dechargement_h,
                $temps_pause_h,
                $repos_journalier_min_h,
                $heure_prise_service_defaut,
                $date_debut_challenge_zl,
                $date_fin_challenge_zl,
                $penalite_jour_non_roule,
                $malus_infraction_conduite_repos,
                $malus_rechargement_hors_zone,
                $date_application,
                $id_config
            ]);

            $status = 'success';
            $msg = "Paramètres globaux ZL mis à jour avec succès.";
        } else {
            $status = 'error';
            $msg = "Erreur lors de la mise à jour des paramètres globaux ZL.";
        }
        break;

    // ==========================================
    // 🆕 DÉPARTEMENTS LIMITROPHES (pénalités géographiques — à venir)
    // ==========================================
    case 'add_limitrophe':
        $code = trim($_POST['code_dept'] ?? '');
        $code_voisin = trim($_POST['code_dept_limitrophe'] ?? '');
        $redirect = './?section=departements&dept=' . urlencode($code);

        if ($code === '' || $code_voisin === '' || $code === $code_voisin) {
            $status = 'error';
            $msg = "Sélection invalide.";
        } else {
            // Relation symétrique : on enregistre les deux sens.
            // INSERT IGNORE évite une erreur si la relation existe déjà
            // (clé primaire composite code_dept + code_dept_limitrophe).
            $stmt = $pdo->prepare('INSERT IGNORE INTO zl_departements_limitrophes (code_dept, code_dept_limitrophe) VALUES (?, ?)');
            $stmt->execute([$code, $code_voisin]);
            $stmt->execute([$code_voisin, $code]);
            $msg = "Relation de voisinage ajoutée.";
        }
        break;

    case 'delete_limitrophe':
        $code = trim($_POST['code_dept'] ?? '');
        $code_voisin = trim($_POST['code_dept_limitrophe'] ?? '');
        $redirect = './?section=departements&dept=' . urlencode($code);

        $stmt = $pdo->prepare('DELETE FROM zl_departements_limitrophes WHERE (code_dept = ? AND code_dept_limitrophe = ?) OR (code_dept = ? AND code_dept_limitrophe = ?)');
        $stmt->execute([$code, $code_voisin, $code_voisin, $code]);
        $msg = "Relation de voisinage supprimée.";
        break;

    default:
        $status = 'error';
        $msg = "Action inconnue.";
        break;
}

header("Location: {$redirect}&status={$status}&msg=" . urlencode($msg));
exit;
