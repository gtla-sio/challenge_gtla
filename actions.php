<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
// 🔒 Les fonctions convertisseurTimeToDecimal() / recalculerFeuille() ne
// sont plus dupliquées : convertisseurTimeToDecimal() vient de
// functions.php. recalculerFeuille() reste ici car c'est vraiment une
// fonction propre au traitement des actions (pas partagée avec index.php).

// -----------------------------------------------------------------------
// 🔒 CORRECTION SÉCURITÉ n°4 : authentification obligatoire
// -----------------------------------------------------------------------
// AVANT : ce fichier lisait $_SESSION['team_id'] sans jamais vérifier
// qu'il existait réellement avant de l'utiliser. Un visiteur non connecté
// qui appelait directement actions.php pouvait provoquer des erreurs PHP
// (notice "undefined array key"), voire, selon la configuration du
// serveur, révéler des informations de débogage.
if (!isset($_SESSION['team_id'])) {
    http_response_code(403);
    die("Accès refusé : vous devez être connecté.");
}
$id_equipe = (int) $_SESSION['team_id'];

// -----------------------------------------------------------------------
// 🔒 CORRECTION SÉCURITÉ n°5 : vérification systématique du jeton CSRF
// -----------------------------------------------------------------------
// Toute requête qui MODIFIE des données (POST, mais aussi la suppression
// qui est repassée en POST ci-dessous, voir correction n°7) doit prouver
// qu'elle a été initiée depuis notre propre formulaire, via le jeton CSRF
// stocké en session. On centralise cette vérification tout en haut.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrfToken($_POST['csrf_token'] ?? null);
}

/**
 * Recalcule toute la feuille d'un véhicule : ordre des lignes, horaires,
 * cumuls de conduite/service, ET (🆕) les s.
 *
 * 🆕  AUTOMATIQUE : le repos n'est plus saisi par l'équipe.
 * Pour chaque jour D qui a une prise de service ET dont le lendemain
 * (D+1) a une prise de service :
 *     repos(D) = (24 h + heure de prise de service de D+1) − heure de fin de D
 * Une ligne "" est ajoutée en dernière ligne du jour D.
 * Aucun repos n'est créé : pour le dernier jour du challenge, ni si le
 * lendemain n'a pas de prise de service. Les repos sont supprimés puis
 * recréés à chaque recalcul : ils sont donc toujours à jour, quelle que
 * soit la modification (prise de service du lendemain, activité du jour...).
 *
 * Retourne la liste des repos calculés < minimum (paramètre
 * repos_journalier_min_h). Si $dates_controlees est fourni, seuls les repos
 * des jours listés sont retournés (voir executerAvecControleRepos()), pour
 * qu'une saisie ne soit pas bloquée par un défaut ancien sans rapport.
 *
 * 🆕 FIN DE JOURNÉE : les activités qui dépassent HEURE_FIN_JOURNEE (minuit)
 * sont aussi signalées, avec 'nature' => 'fin_journee' (les repos trop
 * courts ont 'nature' => 'repos'). Une écriture qui fait déborder une
 * activité après 23h59 est ainsi refusée comme un repos insuffisant.
 */
function recalculerFeuille($pdo, $id_feuille, ?array $dates_controlees = null): array {
    $violations = [];

    // 0. Les s sont toujours recalculés : on supprime les
    //    existants, ils sont réinsérés à l'étape 5.
    $pdo->prepare("DELETE FROM zl_lignes_activite WHERE id_feuille = ? AND type_activite = ?")
        ->execute([$id_feuille, ACTIVITE_REPOS_JOURNALIER]);

    // 1. RÉINDEXATION ROBUSTE
    // 🆕 Tri par DATE puis par ordre de saisie dans la journée. Avant, le
    // tri se faisait sur l'ordre de saisie seul : avec la saisie jour par
    // jour, compléter le jour 1 après avoir commencé le jour 2 mélangeait
    // les jours et faussait les cumuls et les horaires.
    $stmt_get = $pdo->prepare("SELECT id_ligne FROM zl_lignes_activite WHERE id_feuille = ? ORDER BY date_activite ASC, ordre_chronologique ASC, id_ligne ASC");
    $stmt_get->execute([$id_feuille]);
    $lignes = $stmt_get->fetchAll();

    $i = 1;
    foreach ($lignes as $ligne) {
        $pdo->prepare("UPDATE zl_lignes_activite SET ordre_chronologique = ? WHERE id_ligne = ?")
            ->execute([$i++, $ligne['id_ligne']]);
    }

    // 2. RÉCUPÉRATION DONNÉES ET CONFIG
    $stmtActivites = $pdo->prepare("SELECT * FROM zl_lignes_activite WHERE id_feuille = ? ORDER BY ordre_chronologique ASC");
    $stmtActivites->execute([$id_feuille]);
    $activites = $stmtActivites->fetchAll();

    $stmtConf = $pdo->query("SELECT heure_prise_service_defaut, temps_chargement_h, temps_dechargement_h, temps_pause_h, vitesse_moyenne_kmh, repos_journalier_min_h FROM zl_config_parametres WHERE id_config = 1");
    $config = $stmtConf->fetch(PDO::FETCH_ASSOC);
    if (!$config) return [];

    // 3. INITIALISATION DES CUMULS
    $horaire_courant = floatval($config['heure_prise_service_defaut']);
    $conduite_continue_cumul = 0.0;
    $total_conduite_cumul = 0.0;
    $total_service_cumul = 0.0;
    $vitesse = floatval($config['vitesse_moyenne_kmh']);

    // Variable pour mémoriser la date de la ligne précédente et détecter le changement de journée
    $date_precedente = null;

    // 🆕 Pour le repos : heure de prise de service de chaque jour, et état
    // de fin de chaque jour (heure de fin, OT de la dernière ligne, cumuls)
    $prises_de_service = [];
    $fins_de_jour = [];

    // 4. BOUCLE DE CALCUL ET MISE À JOUR
    foreach ($activites as $l) {
        $type = $l['type_activite'];
        $date_actuelle = $l['date_activite'];

        if ($date_precedente !== null && $date_actuelle !== $date_precedente) {
            $total_conduite_cumul = 0.0;
            $total_service_cumul = 0.0;
            $conduite_continue_cumul = 0.0;
        }
        $date_precedente = $date_actuelle;

        // A. Gestion de l'horaire de début
        if ($type === ACTIVITE_PRISE_DE_SERVICE) {
            $horaire_courant = floatval($l['horaire_debut']);
            $conduite_continue_cumul = 0.0;
            $total_conduite_cumul = 0.0;
            $total_service_cumul = 0.0;
            $prises_de_service[$date_actuelle] = $horaire_courant;
        }

        // B. Calcul de la durée de l'activité actuelle
        $duree = 0.0;
        if ($type === ACTIVITE_CONDUITE) {
            $duree = ($vitesse > 0) ? round(floatval($l['km_parcouru']) / $vitesse, 2) : 0;
        } else {
            $key_conf = 'temps_' . strtolower(str_replace(['é', 'è', 'ê'], 'e', $type)) . '_h';
            $duree = floatval($config[$key_conf] ?? 0);
        }

        // C. Gestion des règles de cumul
        if ($type === ACTIVITE_CONDUITE) {
            $conduite_continue_cumul += $duree;
            $total_conduite_cumul += $duree;
            $total_service_cumul += $duree;
        } elseif ($type === ACTIVITE_PAUSE) {
            $conduite_continue_cumul = 0.0;
            $total_service_cumul += $duree;
        } else {
            if ($type !== ACTIVITE_PRISE_DE_SERVICE && $type !== ACTIVITE_FIN_DE_SERVICE) {
                $total_service_cumul += $duree;
            }
        }

        // D. Mise à jour de la base
        $sql = "UPDATE zl_lignes_activite SET
                horaire_debut = ?,
                conduite_continue_cumul = ?,
                total_conduite_cumul = ?,
                total_service_cumul = ?
                WHERE id_ligne = ?";

        $pdo->prepare($sql)->execute([
            $horaire_courant,
            $conduite_continue_cumul,
            $total_conduite_cumul,
            $total_service_cumul,
            $l['id_ligne']
        ]);

        // 🆕 D bis. Une activité qui dure (conduite, chargement, déchargement,
        // pause) ne doit pas se terminer après minuit. Le 
        // n'est pas concerné : il est recréé à l'étape 5 et chevauche
        // forcément la nuit. La tolérance de 0,005 h absorbe les arrondis
        // à 2 décimales des durées.
        $fin_activite = $horaire_courant + $duree;
        if ($duree > 0 && $type !== ACTIVITE_REPOS_JOURNALIER && $fin_activite > HEURE_FIN_JOURNEE + 0.005
            && ($dates_controlees === null || in_array($date_actuelle, $dates_controlees, true))) {
            $violations[] = [
                'nature' => 'fin_journee',
                'date'   => $date_actuelle,
                'type'   => $type,
                'debut'  => $horaire_courant,
                'fin'    => $fin_activite,
            ];
        }

        // E. Incrémentation pour la ligne suivante
        $horaire_courant += $duree;

        // 🆕 État de fin de journée (écrasé à chaque ligne : la dernière du jour l'emporte)
        $fins_de_jour[$date_actuelle] = [
            'fin'            => $horaire_courant,
            'id_ot'          => $l['id_ot'],
            'total_conduite' => $total_conduite_cumul,
            'total_service'  => $total_service_cumul,
        ];
    }

    // 5. 🆕 S AUTOMATIQUES
    $repos_min = floatval($config['repos_journalier_min_h'] ?? 9);
    $jours_challenge = getJoursChallenge($pdo);
    $dernier_jour = end($jours_challenge);

    $stmtRepos = $pdo->prepare(
        'INSERT INTO zl_lignes_activite
            (id_feuille, id_ot, ordre_chronologique, date_activite, horaire_debut, type_activite, km_parcouru, duree_repos_h, est_debut_journee,
             conduite_continue_cumul, total_conduite_cumul, total_service_cumul)
         VALUES (?, ?, 0, ?, ?, ?, 0, ?, 0, 0, ?, ?)'
    );

    foreach ($fins_de_jour as $jour => $fj) {
        if ($jour >= $dernier_jour) continue;                     // dernier jour : pas de lendemain à renseigner
        if (!isset($prises_de_service[$jour])) continue;          // jour sans prise de service : horaires non fiables
        $lendemain = date('Y-m-d', strtotime($jour . ' +1 day'));
        if (!isset($prises_de_service[$lendemain])) continue;     // pas de prise de service le lendemain : pas de repos

        $repos = round(24 + $prises_de_service[$lendemain] - $fj['fin'], 2);

        if ($repos < $repos_min && ($dates_controlees === null || in_array($jour, $dates_controlees, true))) {
            $violations[] = [
                'nature'        => 'repos',
                'date_repos'    => $jour,
                'lendemain'     => $lendemain,
                'fin'           => $fj['fin'],
                'prise_service' => $prises_de_service[$lendemain],
                'repos'         => $repos,
                'repos_min'     => $repos_min,
            ];
        }

        // La ligne reprend l'OT de la dernière ligne du jour (pas de faux
        // changement d'OT) et les cumuls du jour, comme les anciens repos saisis.
        $stmtRepos->execute([
            $id_feuille, $fj['id_ot'], $jour, $fj['fin'], ACTIVITE_REPOS_JOURNALIER,
            max(0.0, $repos), $fj['total_conduite'], $fj['total_service']
        ]);
    }

    // 6. RÉINDEXATION FINALE : le repos est toujours la dernière ligne de son jour
    $stmt_get = $pdo->prepare("SELECT id_ligne FROM zl_lignes_activite WHERE id_feuille = ?
        ORDER BY date_activite ASC, (type_activite = '" . ACTIVITE_REPOS_JOURNALIER . "') ASC, ordre_chronologique ASC, id_ligne ASC");
    $stmt_get->execute([$id_feuille]);
    $i = 1;
    foreach ($stmt_get->fetchAll() as $ligne) {
        $pdo->prepare("UPDATE zl_lignes_activite SET ordre_chronologique = ? WHERE id_ligne = ?")
            ->execute([$i++, $ligne['id_ligne']]);
    }

    return $violations;
}

/**
 * 🆕 Exécute une écriture (ajout / modification / suppression d'une ligne)
 * puis recalcule la feuille, le tout dans une transaction : si un repos
 * journalier calculé passe sous le minimum, l'écriture est ANNULÉE et un
 * message explicite est retourné (null si tout est bon).
 *
 * Seuls les repos du jour de la ligne ($date) et du jour précédent sont
 * contrôlés : ce sont les seuls que cette écriture peut modifier.
 *
 * 🆕 Si $controler_fin_journee est vrai, l'écriture est aussi refusée quand
 * une activité du jour $date déborde après minuit (voir recalculerFeuille()).
 * On le désactive pour une suppression : retirer une ligne ne peut
 * qu'avancer les horaires suivants, et il faut pouvoir corriger une journée
 * déjà en dépassement (données saisies avant ce contrôle).
 */
function executerAvecControleRepos(PDO $pdo, int $id_feuille, string $date, callable $ecriture, bool $controler_fin_journee = true): ?string {
    $dates_controlees = [$date, date('Y-m-d', strtotime($date . ' -1 day'))];

    $pdo->beginTransaction();
    try {
        $ecriture();
        $violations = recalculerFeuille($pdo, $id_feuille, $dates_controlees);

        // Le dépassement de fin de journée est signalé en premier : c'est la
        // cause directe de la saisie refusée.
        foreach ($violations as $v) {
            if ($controler_fin_journee && $v['nature'] === 'fin_journee' && $v['date'] === $date) {
                $pdo->rollBack();
                return messageDepassementJournee($v);
            }
        }
        foreach ($violations as $v) {
            if ($v['nature'] === 'repos') {
                $pdo->rollBack();
                return messageViolationRepos($v);
            }
        }
        $pdo->commit();
        return null;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// Récupération configuration
$stmtConfig = $pdo->query('SELECT heure_prise_service_defaut FROM zl_config_parametres WHERE id_config = 1');
$config = $stmtConfig->fetch();
$heure_fixe_bdd = $config ? floatval($config['heure_prise_service_defaut']) : 5.00;

$date_debut = $_POST['date_debut_semaine'] ?? '';
$id_vehicule = $_POST['id_vehicule'] ?? null;

// NOTE : $id_feuille est ici toujours dérivé d'une recherche filtrée par
// $id_equipe (voir la requête juste en dessous). C'est ce qui garantit
// que la feuille appartient forcément à l'équipe connectée dès sa
// création — cette partie du code était déjà correcte.
if ($id_vehicule && !empty($date_debut)) {
    $stmt = $pdo->prepare("SELECT id_feuille FROM zl_feuilles_otea
                           WHERE id_equipe = ? AND id_vehicule = ? AND date_debut_semaine = ?");
    $stmt->execute([$id_equipe, $id_vehicule, $date_debut]);
    $feuille = $stmt->fetch();

    if ($feuille) {
        $id_feuille = $feuille['id_feuille'];
    } else {
        try {
            $stmtCreate = $pdo->prepare("INSERT INTO zl_feuilles_otea (id_equipe, id_vehicule, date_debut_semaine) VALUES (?, ?, ?)");
            $stmtCreate->execute([$id_equipe, $id_vehicule, $date_debut]);
            $id_feuille = $pdo->lastInsertId();
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("SELECT id_feuille FROM zl_feuilles_otea WHERE id_equipe = ? AND id_vehicule = ? AND date_debut_semaine = ?");
            $stmt->execute([$id_equipe, $id_vehicule, $date_debut]);
            $id_feuille = $stmt->fetchColumn();
        }
    }
}

// -----------------------------------------------------------------------
// --- SUPPRESSION ---
// 🔒 CORRECTION SÉCURITÉ n°6 et n°7 (les deux essentielles de la review)
// -----------------------------------------------------------------------
// n°6 (IDOR) : on vérifie désormais, via ligneAppartientEquipe(), que la
//     ligne demandée appartient bien à l'équipe connectée AVANT de faire
//     quoi que ce soit. Sans ce garde-fou, changer juste l'id_ligne dans
//     l'URL permettait de supprimer la ligne d'une AUTRE équipe.
// n°7 (CSRF + méthode HTTP) : la suppression est passée d'un lien <a>
//     (méthode GET) à un petit formulaire POST protégé par jeton CSRF.
//     Règle à retenir : une requête GET ne doit JAMAIS avoir d'effet de
//     bord (elle doit pouvoir être répétée, mise en cache, préchargée par
//     le navigateur, sans danger). Toute action qui modifie la base doit
//     être un POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id_ligne = intval($_POST['id_ligne'] ?? 0);
    $id_vehicule_redirection = intval($_POST['id_vehicule'] ?? 0);
    // 🆕 PLANNING PAR JOUR : on renvoie vers le même jour après suppression,
    // sinon l'équipe retomberait sur l'écran "choisissez une date".
    $date_jour_redirection = '&date_jour=' . urlencode($_POST['date_jour'] ?? '');

    $id_feuille_recupere = ligneAppartientEquipe($pdo, $id_ligne, $id_equipe);

    if (!$id_feuille_recupere) {
        // La ligne n'existe pas OU n'appartient pas à l'équipe connectée :
        // on refuse, sans donner plus de détail (pour ne pas confirmer à
        // un attaquant qu'un id_ligne particulier existe chez une autre
        // équipe).
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Action refusée : cette ligne n'existe pas ou ne vous appartient pas."));
        exit;
    }

    // 🆕 Le  est calculé automatiquement : il ne se supprime pas.
    $stmtLigne = $pdo->prepare('SELECT type_activite, date_activite FROM zl_lignes_activite WHERE id_ligne = ?');
    $stmtLigne->execute([$id_ligne]);
    $ligne_a_supprimer = $stmtLigne->fetch();

    if ($ligne_a_supprimer['type_activite'] === ACTIVITE_REPOS_JOURNALIER) {
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Le  est calculé automatiquement : il ne peut pas être supprimé."));
        exit;
    }

    $erreur_repos = executerAvecControleRepos($pdo, (int) $id_feuille_recupere, $ligne_a_supprimer['date_activite'], function () use ($pdo, $id_ligne) {
        $pdo->prepare('DELETE FROM zl_lignes_activite WHERE id_ligne = ?')->execute([$id_ligne]);
    }, false);

    if ($erreur_repos) {
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Suppression impossible. " . $erreur_repos));
        exit;
    }

    header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Evènement supprimé."));
    exit;
}

// -----------------------------------------------------------------------
// 🆕 AFFRÈTEMENT : déclaration (par l'équipe cliente / affréteur)
// -----------------------------------------------------------------------
if (isset($_POST['declarer_affretement'])) {
    $id_ot = intval($_POST['id_ot'] ?? 0);
    $id_equipe_cible = intval($_POST['id_equipe_cible'] ?? 0);
    $prix = round(floatval(str_replace(',', '.', $_POST['prix_affretement'] ?? 0)), 2);

    // Vérifications métier, dans l'ordre où un problème doit être signalé
    // à l'utilisateur :
    $stmtOt = $pdo->prepare('SELECT id_equipe FROM zl_ordres_transport WHERE id_ot = ?');
    $stmtOt->execute([$id_ot]);
    $ot = $stmtOt->fetch();

    if (!$ot || (int)$ot['id_equipe'] !== $id_equipe) {
        // 🔒 IDOR : seule l'équipe cliente d'origine de l'OT peut décider
        // de le sous-traiter — pas n'importe quelle équipe qui devine un id_ot.
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Cet OT ne vous appartient pas."));
        exit;
    }

    if ($id_equipe_cible === $id_equipe) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Vous ne pouvez pas vous affréter un OT à vous-même."));
        exit;
    }

    $stmtCibleExiste = $pdo->prepare('SELECT 1 FROM equipes WHERE id_equipe = ?');
    $stmtCibleExiste->execute([$id_equipe_cible]);
    if (!$stmtCibleExiste->fetchColumn()) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Équipe cible invalide."));
        exit;
    }

    if ($prix <= 0) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Le prix d'affrètement doit être supérieur à 0."));
        exit;
    }

    // On refuse une nouvelle déclaration si un affrètement est déjà actif
    // (en attente ou déjà accepté) sur cet OT.
    if (affretementActifPourOt($pdo, $id_ot)) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Un affrètement est déjà en cours ou accepté sur cet OT."));
        exit;
    }

    // On refuse aussi si l'équipe a déjà commencé à exploiter cet OT
    // elle-même (des lignes d'activité existent déjà dessus) : il faut
    // choisir CAS 1 (exploitation) OU CAS 2 (affrètement), pas les deux.
    $stmtDejaExploite = $pdo->prepare('SELECT 1 FROM zl_lignes_activite WHERE id_ot = ? LIMIT 1');
    $stmtDejaExploite->execute([$id_ot]);
    if ($stmtDejaExploite->fetchColumn()) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Impossible : cet OT a déjà des activités saisies en exploitation propre."));
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO zl_affretements (id_ot, id_equipe_affreteur, id_equipe_affrete, prix_affretement, statut)
         VALUES (?, ?, ?, ?, "en_attente")'
    );
    $stmt->execute([$id_ot, $id_equipe, $id_equipe_cible, $prix]);

    header("Location: index.php?section=affretement&status=success&msg=" . urlencode("Proposition d'affrètement envoyée."));
    exit;
}

// -----------------------------------------------------------------------
// 🆕 AFFRÈTEMENT : annulation par le déclarant (l'affréteur), tant que
// l'autre équipe n'a pas encore répondu
// -----------------------------------------------------------------------
if (isset($_POST['annuler_affretement'])) {
    $id_affretement = intval($_POST['id_affretement'] ?? 0);

    // 🔒 IDOR : seule l'équipe qui a déclaré cet affrètement peut
    // l'annuler, et uniquement tant qu'il est encore "en_attente" (pas
    // déjà accepté ou refusé par l'autre équipe — une réponse déjà
    // donnée ne se "désannule" pas).
    $stmt = $pdo->prepare(
        "SELECT id_affretement FROM zl_affretements
         WHERE id_affretement = ? AND id_equipe_affreteur = ? AND statut = 'en_attente'"
    );
    $stmt->execute([$id_affretement, $id_equipe]);

    if (!$stmt->fetchColumn()) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Cette proposition n'existe pas, ne vous appartient pas, ou a déjà reçu une réponse."));
        exit;
    }

    $stmtMaj = $pdo->prepare("UPDATE zl_affretements SET statut = 'annulee', date_reponse = NOW() WHERE id_affretement = ?");
    $stmtMaj->execute([$id_affretement]);

    header("Location: index.php?section=affretement&status=success&msg=" . urlencode("Proposition d'affrètement annulée."));
    exit;
}

// -----------------------------------------------------------------------
// 🆕 AFFRÈTEMENT : réponse (par l'équipe sollicitée / affrétée)
// -----------------------------------------------------------------------
if (isset($_POST['repondre_affretement'])) {
    $id_affretement = intval($_POST['id_affretement'] ?? 0);
    $reponse = $_POST['reponse'] ?? '';

    if (!in_array($reponse, ['acceptee', 'refusee'], true)) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Réponse invalide."));
        exit;
    }

    // 🔒 IDOR : seule l'équipe VISÉE par la proposition peut y répondre,
    // et uniquement si elle est encore "en_attente" (pas déjà tranchée).
    $stmt = $pdo->prepare(
        "SELECT id_affretement FROM zl_affretements
         WHERE id_affretement = ? AND id_equipe_affrete = ? AND statut = 'en_attente'"
    );
    $stmt->execute([$id_affretement, $id_equipe]);

    if (!$stmt->fetchColumn()) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Cette proposition n'existe pas, ne vous concerne pas, ou a déjà reçu une réponse."));
        exit;
    }

    // 🔒 "AND statut = 'en_attente'" dans l'UPDATE : si l'affréteur vient
    // d'annuler sa proposition entre la vérification ci-dessus et cette
    // écriture, on ne doit surtout pas écraser le statut 'annulee'.
    // rowCount() nous dit si la mise à jour a réellement eu lieu.
    $stmtMaj = $pdo->prepare(
        "UPDATE zl_affretements SET statut = ?, date_reponse = NOW()
         WHERE id_affretement = ? AND statut = 'en_attente'"
    );
    $stmtMaj->execute([$reponse, $id_affretement]);

    if ($stmtMaj->rowCount() === 0) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Cette proposition vient d'être annulée par l'équipe demandeuse."));
        exit;
    }

    $msg = ($reponse === 'acceptee')
        ? "Affrètement accepté : l'OT apparaît désormais dans votre planning véhicules."
        : "Affrètement refusé.";
    header("Location: index.php?section=affretement&status=success&msg=" . urlencode($msg));
    exit;
}

// -----------------------------------------------------------------------
// 🆕 AFFRÈTEMENT : annulation (par l'équipe qui a fait la proposition)
// -----------------------------------------------------------------------
// Seule l'affréteuse (celle qui a déclaré la proposition) peut l'annuler,
// et uniquement tant qu'elle est "en_attente". Une fois acceptée ou
// refusée, la décision est prise : on ne revient pas dessus.
if (isset($_POST['annuler_affretement'])) {
    $id_affretement = intval($_POST['id_affretement'] ?? 0);

    // 🔒 IDOR : la condition id_equipe_affreteur = équipe connectée garantit
    // qu'on n'annule pas la proposition d'une autre équipe. Le statut est
    // re-testé dans l'UPDATE lui-même (et pas seulement dans un SELECT
    // préalable) pour éviter qu'une acceptation simultanée soit écrasée.
    $stmt = $pdo->prepare(
        "UPDATE zl_affretements SET statut = 'annulee', date_reponse = NOW()
         WHERE id_affretement = ? AND id_equipe_affreteur = ? AND statut = 'en_attente'"
    );
    $stmt->execute([$id_affretement, $id_equipe]);

    if ($stmt->rowCount() === 0) {
        header("Location: index.php?section=affretement&status=danger&msg=" . urlencode("Annulation impossible : la proposition n'existe pas, ne vous appartient pas, ou a déjà reçu une réponse."));
        exit;
    }

    header("Location: index.php?section=affretement&status=success&msg=" . urlencode("Proposition d'affrètement annulée."));
    exit;
}

// --- AJOUT / MODIF ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ot = intval($_POST['id_ot'] ?? 0);
    $date_act = $_POST['date_activite'] ?? '';
    $type_act = $_POST['type_activite'] ?? '';

    // 🆕 PLANNING PAR JOUR : conservé pour renvoyer l'équipe sur le même
    // jour après l'action (succès ou erreur), plutôt que de la faire
    // retomber sur l'écran "choisissez une date".
    $date_jour_redirection = '&date_jour=' . urlencode($_POST['date_jour'] ?? $date_act);

    // 🔒 CORRECTION SÉCURITÉ n°8 : validation du type d'activité
    // On refuse toute valeur qui ne fait pas partie de la liste connue
    // (TYPES_ACTIVITE_VALIDES, définie dans functions.php), plutôt que de
    // faire confiance à ce que le formulaire HTML "devrait" envoyer. Un
    // attaquant peut très bien poster n'importe quelle chaîne.
    if (!in_array($type_act, TYPES_ACTIVITE_VALIDES, true)) {
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=error&msg=" . urlencode("Type d'activité invalide."));
        exit;
    }

    // 🆕 Le  n'est plus saisi : il est calculé automatiquement
    // à partir de la prise de service du lendemain (voir recalculerFeuille()).
    if ($type_act === ACTIVITE_REPOS_JOURNALIER) {
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Le  est calculé automatiquement : il ne peut pas être saisi."));
        exit;
    }

    $km = ($type_act === ACTIVITE_CONDUITE) ? intval($_POST['km_parcouru'] ?? 0) : 0;
    $horaire = convertisseurTimeToDecimal($_POST['horaire_debut'] ?? '');

    $duree_repos = null; // colonne réservée aux repos calculés automatiquement

    if (isset($_POST['ajouter_activite'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_act)) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=error&msg=" . urlencode("Date d'activité invalide."));
            exit;
        }

        $stmtVerif = $pdo->prepare("SELECT id_feuille FROM zl_feuilles_otea WHERE id_feuille = ? AND id_vehicule = ? AND id_equipe = ? LIMIT 1");
        $stmtVerif->execute([$id_feuille ?? 0, $id_vehicule, $id_equipe]);
        $feuille_valide = $stmtVerif->fetch();

        if (!$feuille_valide) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=error&msg=" . urlencode("Erreur : La feuille spécifiée est invalide pour ce véhicule."));
            exit;
        }

        // 🔒 CORRECTION SÉCURITÉ n°9 : l'OT déclaré doit appartenir à
        // l'équipe connectée (même logique IDOR que pour les lignes).
        if (!otAppartientEquipe($pdo, $id_ot, $id_equipe)) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Cet ordre de transport ne vous est pas affecté."));
            exit;
        }

        if ($type_act === ACTIVITE_PRISE_DE_SERVICE) {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM zl_lignes_activite WHERE id_feuille = ? AND date_activite = ? AND type_activite = ?');
            $stmtCheck->execute([$id_feuille, $date_act, ACTIVITE_PRISE_DE_SERVICE]);

            if ($stmtCheck->fetchColumn() > 0) {
                header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Impossible : une prise de service existe déjà pour la date du " . date("d/m/Y", strtotime($date_act)) . "."));
                exit;
            }
        }

        if ($type_act !== ACTIVITE_PRISE_DE_SERVICE) {
            $stmtVerifPrise = $pdo->prepare('SELECT COUNT(*) FROM zl_lignes_activite WHERE id_feuille = :id_feuille AND date_activite = :date_act AND type_activite = :type');
            $stmtVerifPrise->execute([':id_feuille' => $id_feuille, ':date_act' => $date_act, ':type' => ACTIVITE_PRISE_DE_SERVICE]);

            if ($stmtVerifPrise->fetchColumn() == 0) {
                header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Impossible d'ajouter cette activité : vous devez d'abord enregistrer une 'Prise de service' pour le " . date("d/m/Y", strtotime($date_act)) . "."));
                exit;
            }
        }

        $stmtCheckFin = $pdo->prepare('SELECT COUNT(*) FROM zl_lignes_activite WHERE id_feuille = :id_feuille AND date_activite = :date_act AND type_activite = :type');
        $stmtCheckFin->execute([':id_feuille' => $id_feuille, ':date_act' => $date_act, ':type' => ACTIVITE_FIN_DE_SERVICE]);

        if ($stmtCheckFin->fetchColumn() > 0) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Impossible d'ajouter une activité : la journée est terminée."));
            exit;
        }

        $stmtOrdre = $pdo->prepare('SELECT IFNULL(MAX(ordre_chronologique), 0) + 1 FROM zl_lignes_activite WHERE id_feuille = ?');
        $stmtOrdre->execute([$id_feuille]);
        $ordre = $stmtOrdre->fetchColumn();

        // 🆕 Écriture + recalcul dans une transaction : refusée (et annulée)
        // si elle ferait passer un  sous le minimum.
        $erreur_repos = executerAvecControleRepos($pdo, (int) $id_feuille, $date_act, function () use ($pdo, $id_feuille, $id_ot, $ordre, $date_act, $horaire, $type_act, $km, $duree_repos) {
            $stmt = $pdo->prepare('INSERT INTO zl_lignes_activite
                (id_feuille, id_ot, ordre_chronologique, date_activite, horaire_debut, type_activite, km_parcouru, duree_repos_h, est_debut_journee,
                conduite_continue_cumul, total_conduite_cumul, total_service_cumul)
                VALUES (?,?,?,?,?,?,?,?,?, 0, 0, 0)');

            $stmt->execute([$id_feuille, $id_ot, $ordre, $date_act, $horaire, $type_act, $km, $duree_repos, ($type_act === ACTIVITE_PRISE_DE_SERVICE ? 1 : 0)]);
        });

        if ($erreur_repos) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode($erreur_repos));
            exit;
        }

        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=success&msg=" . urlencode("Nouvel évènement enregistré avec succès"));
        exit;
    }
    elseif (isset($_POST['modifier_activite'])) {
        $id_ligne = intval($_POST['id_ligne'] ?? 0);

        // 🔒 CORRECTION SÉCURITÉ n°6 bis : même contrôle de propriété que
        // pour la suppression, appliqué ici à la modification.
        $id_feuille_verifiee = ligneAppartientEquipe($pdo, $id_ligne, $id_equipe);
        if (!$id_feuille_verifiee) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Action refusée : cette ligne n'existe pas ou ne vous appartient pas."));
            exit;
        }

        if (!otAppartientEquipe($pdo, $id_ot, $id_equipe)) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Cet ordre de transport ne vous est pas affecté."));
            exit;
        }

        // 🆕 Ligne d'origine : le  (calculé) ne se modifie pas, et
        // la date d'une ligne ne change jamais (le formulaire la verrouille) :
        // on conserve celle de la base plutôt que celle envoyée par le formulaire.
        $stmtOrig = $pdo->prepare('SELECT type_activite, date_activite FROM zl_lignes_activite WHERE id_ligne = ?');
        $stmtOrig->execute([$id_ligne]);
        $ligne_origine = $stmtOrig->fetch();

        if ($ligne_origine['type_activite'] === ACTIVITE_REPOS_JOURNALIER) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule ?? 0) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Le  est calculé automatiquement : il ne peut pas être modifié."));
            exit;
        }
        $date_act = $ligne_origine['date_activite'];

        $erreur_repos = executerAvecControleRepos($pdo, (int) $id_feuille_verifiee, $date_act, function () use ($pdo, $id_ot, $date_act, $horaire, $type_act, $km, $duree_repos, $id_ligne) {
            $stmt = $pdo->prepare('UPDATE zl_lignes_activite SET id_ot=?, date_activite=?, horaire_debut=?, type_activite=?, km_parcouru=?, duree_repos_h=? WHERE id_ligne=?');
            $stmt->execute([$id_ot, $date_act, $horaire, $type_act, $km, $duree_repos, $id_ligne]);
        });

        $id_vehicule_redirection = isset($_POST['id_vehicule']) ? intval($_POST['id_vehicule']) : 0;
        if ($erreur_repos) {
            header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=danger&msg=" . urlencode("Modification impossible. " . $erreur_repos));
            exit;
        }
        header("Location: index.php?id_vehicule=" . urlencode($id_vehicule_redirection) . $date_jour_redirection . "&status=success&msg=" . urlencode("Modification enregistrée avec succès"));
        exit;
    }
}
