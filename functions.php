<?php
/**
 * functions.php
 * ---------------------------------------------------------------------
 * Fichier ajouté lors de la correction du projet.
 *
 * POURQUOI CE FICHIER ?
 * Dans la version initiale, les fonctions de conversion d'heures étaient
 * copiées-collées à l'identique dans index.php ET dans actions.php.
 * -> Règle de base : une fonction ne doit exister qu'à UN SEUL endroit.
 *    Si un jour on corrige un bug dedans, on ne doit avoir qu'un seul
 *    fichier à modifier, sinon on risque d'oublier une des deux copies
 *    et d'avoir un bug "qui revient" alors qu'on pensait l'avoir corrigé.
 *
 * On y regroupe aussi :
 *  - les constantes métier (types d'activité), pour éviter les chaînes
 *    de caractères "magiques" éparpillées dans le code (une faute de
 *    frappe dans 'Conduite' écrit 'Condiute' ne provoque AUCUNE erreur
 *    PHP, mais casse silencieusement tous les calculs de temps de
 *    conduite) ;
 *  - les fonctions de sécurité (CSRF, vérification de propriété d'une
 *    ressource) réutilisées par index.php, actions.php et les vues.
 * ---------------------------------------------------------------------
 */

// -----------------------------------------------------------------------
// 1. CONSTANTES MÉTIER
// -----------------------------------------------------------------------
// On centralise ici les libellés des types d'activité. Si on doit un jour
// renommer '' en 'Repos quotidien', on ne modifie
// qu'ici : toutes les comparaisons du code utilisent la constante, pas
// une chaîne recopiée à la main.
const ACTIVITE_PRISE_DE_SERVICE = 'Prise de service';
const ACTIVITE_CONDUITE         = 'Conduite';
const ACTIVITE_CHARGEMENT       = 'Chargement';
const ACTIVITE_DECHARGEMENT     = 'Déchargement';
const ACTIVITE_PAUSE            = 'Pause';
const ACTIVITE_REPOS_JOURNALIER = '';
const ACTIVITE_FIN_DE_SERVICE   = 'Fin de service';

const TYPES_ACTIVITE_VALIDES = [
    ACTIVITE_PRISE_DE_SERVICE,
    ACTIVITE_CONDUITE,
    ACTIVITE_CHARGEMENT,
    ACTIVITE_DECHARGEMENT,
    ACTIVITE_PAUSE,
    ACTIVITE_REPOS_JOURNALIER,
    ACTIVITE_FIN_DE_SERVICE,
];

// 🆕 Fin de la journée de planning (en heures décimales) : une activité
// qui dure (conduite, chargement, déchargement, pause) doit être terminée
// au plus tard à minuit, c'est-à-dire ne pas déborder après 23h59.
const HEURE_FIN_JOURNEE = 24.0;

// -----------------------------------------------------------------------
// 2. FONCTIONS UTILITAIRES DE CONVERSION (DÉCIMAL <=> HEURES:MINUTES)
//    (inchangées sur le fond, juste déplacées ici pour ne plus être
//    dupliquées)
// -----------------------------------------------------------------------
function formaterHeureDecimales($decimal) {
    $heures = floor($decimal);
    $minutes = round(($decimal - $heures) * 60);
    if ($minutes == 60) {
        $heures++;
        $minutes = 0;
    }
    return $heures . 'h' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

function convertisseurTimeToDecimal($timeStr) {
    if (empty($timeStr)) return 0.00;
    $parts = explode(':', $timeStr);
    $heures = intval($parts[0]);
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
    return round($heures + ($minutes / 60), 2);
}

function convertisseurDecimalToTime($decimal) {
    $heures = floor($decimal);
    $minutes = round(($decimal - $heures) * 60);
    if ($minutes == 60) {
        $heures++;
        $minutes = 0;
    }
    return str_pad($heures, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

// -----------------------------------------------------------------------
// 3. PROTECTION CSRF (Cross-Site Request Forgery)
// -----------------------------------------------------------------------
// RAPPEL DU PROBLÈME (voir la code review) : l'espace admin générait déjà
// un jeton CSRF, mais PAS l'espace équipe. Résultat : un attaquant pouvait
// piéger une équipe connectée pour lui faire, à son insu, supprimer une
// ligne d'activité (via une simple balise <img> pointant vers l'URL de
// suppression). On applique donc EXACTEMENT le même mécanisme des deux
// côtés, via ces deux fonctions communes.

/**
 * Génère (si besoin) et retourne le jeton CSRF de la session en cours.
 * À appeler une fois en haut de chaque page qui affiche un formulaire.
 */
function genererCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le jeton CSRF envoyé par un formulaire (POST) ou un lien
 * contenant explicitement le jeton. Stoppe l'exécution si le jeton est
 * absent ou invalide.
 *
 * hash_equals() est utilisé (plutôt que ===) pour comparer les jetons de
 * façon "à temps constant" et éviter les attaques par mesure de timing.
 */
function verifierCsrfToken(?string $jetonRecu): void {
    if (!$jetonRecu || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $jetonRecu)) {
        http_response_code(403);
        die("Erreur de sécurité : jeton CSRF invalide ou manquant. Merci de recharger la page et de réessayer.");
    }
}

// -----------------------------------------------------------------------
// 4. CONTRÔLE D'ACCÈS (IDOR - Insecure Direct Object Reference)
// -----------------------------------------------------------------------
// RAPPEL DU PROBLÈME : avant correction, n'importe quelle équipe connectée
// pouvait modifier/supprimer la ligne d'activité d'une AUTRE équipe en
// changeant simplement l'id_ligne dans l'URL ou le formulaire, car aucune
// requête ne vérifiait le propriétaire réel de la ressource.
//
// RÈGLE À RETENIR : "être connecté" (authentification) ne veut pas dire
// "avoir le droit d'agir sur CETTE ressource précise" (autorisation).
// Il faut vérifier les deux, systématiquement, côté serveur.

/**
 * Vérifie qu'une ligne d'activité (id_ligne) appartient bien, via sa
 * feuille de route, à l'équipe donnée (id_equipe).
 * Retourne l'id_feuille si c'est le cas, sinon null.
 */
function ligneAppartientEquipe(PDO $pdo, int $id_ligne, int $id_equipe): ?int {
    $stmt = $pdo->prepare(
        'SELECT l.id_feuille
         FROM zl_lignes_activite l
         JOIN zl_feuilles_otea f ON l.id_feuille = f.id_feuille
         WHERE l.id_ligne = ? AND f.id_equipe = ?'
    );
    $stmt->execute([$id_ligne, $id_equipe]);
    $id_feuille = $stmt->fetchColumn();
    return $id_feuille !== false ? (int)$id_feuille : null;
}

/**
 * Vérifie qu'un ordre de transport (id_ot) peut être exécuté (saisie
 * d'activité sur véhicule) par l'équipe donnée.
 *
 * 🆕 ÉVOLUTION "AFFRÈTEMENT" : ce n'est plus un simple test
 * "id_equipe = ?". Un OT peut être exécuté par une équipe dans deux cas :
 *   1. C'est l'équipe cliente d'origine (zl_ordres_transport.id_equipe)
 *      ET elle n'a pas sous-traité cet OT à une autre équipe (pas
 *      d'affrètement ACCEPTÉ dessus) ;
 *   2. Une autre équipe le lui a sous-traité et elle a ACCEPTÉ cet
 *      affrètement (voir zl_affretements).
 * Dès qu'un affrètement est accepté, l'équipe cliente d'origine perd la
 * main sur l'exécution (elle ne peut plus saisir d'activité dessus),
 * mais garde le suivi financier (son CA, moins le prix payé à
 * l'affrété — voir rentabilite.php).
 */
function otAppartientEquipe(PDO $pdo, int $id_ot, int $id_equipe): bool {
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM zl_ordres_transport ot
         WHERE ot.id_ot = ?
           AND (
                (ot.id_equipe = ? AND NOT EXISTS (
                    SELECT 1 FROM zl_affretements a
                    WHERE a.id_ot = ot.id_ot AND a.statut = 'acceptee'
                ))
                OR EXISTS (
                    SELECT 1 FROM zl_affretements a
                    WHERE a.id_ot = ot.id_ot AND a.id_equipe_affrete = ? AND a.statut = 'acceptee'
                )
           )"
    );
    $stmt->execute([$id_ot, $id_equipe, $id_equipe]);
    return (bool) $stmt->fetchColumn();
}

// -----------------------------------------------------------------------
// 5. AFFRÈTEMENT ET RENTABILITÉ
// -----------------------------------------------------------------------
// Fonctions communes utilisées par actions.php (déclarer/répondre à un
// affrètement) et includes/rentabilite.php (afficher les calculs).

/**
 * Total des kilomètres réellement parcourus (lignes de type "Conduite")
 * pour un OT donné, tous véhicules confondus (un seul véhicule à la fois
 * traite un OT en pratique, mais on ne fait pas cette hypothèse ici).
 */
function kmParcourusPourOt(PDO $pdo, int $id_ot): float {
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(km_parcouru), 0) FROM zl_lignes_activite WHERE id_ot = ? AND type_activite = ?'
    );
    $stmt->execute([$id_ot, ACTIVITE_CONDUITE]);
    return (float) $stmt->fetchColumn();
}

/**
 * 🆕 Coût de revient réel d'un OT : pour chaque ligne de type "Conduite"
 * de cet OT, on applique le coût au km de l'ÉNERGIE du véhicule utilisé
 * sur cette ligne (via sa feuille de route), réglable dans Admin >
 * Énergies. L'énergie étant obligatoire sur chaque véhicule, il n'y a
 * plus de coût par défaut de repli.
 */
function coutRevientPourOt(PDO $pdo, int $id_ot): float {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(l.km_parcouru * re.cout_km), 0)
         FROM zl_lignes_activite l
         JOIN zl_feuilles_otea f ON l.id_feuille = f.id_feuille
         JOIN zl_vehicules v ON f.id_vehicule = v.id_vehicule
         JOIN ref_energies re ON v.id_energie = re.id_energie
         WHERE l.id_ot = ? AND l.type_activite = ?"
    );
    $stmt->execute([$id_ot, ACTIVITE_CONDUITE]);
    return (float) $stmt->fetchColumn();
}

// -----------------------------------------------------------------------
// 6. CLASSEMENT PONDÉRÉ (1/3 affrètement + 2/3 exploitation) ET PÉNALITÉS
// -----------------------------------------------------------------------
// Barème choisi : chaque catégorie (affrètement / exploitation) est notée
// sur POINTS_MAX, indépendamment des autres équipes — chacune sait ce
// qu'elle doit viser, sans dépendre du résultat des autres.
//
// 🆕 Conversion CONTINUE (interpolation linéaire) plutôt que par paliers
// plats : entre deux seuils d'ancrage, chaque point de taux gagné compte
// proportionnellement, au lieu de ne récompenser que le franchissement
// d'un seuil. Ça évite l'effet "falaise" (une équipe à 14,9% et une à
// 15,1% de taux ne doivent pas se retrouver 250 points d'écart pour
// 0,2 point de taux de différence).
const POINTS_MAX = 1000;

// Points d'ancrage [taux_%, points], triés par taux croissant. En dehors
// des bornes : 0 point en dessous du premier seuil (0%), POINTS_MAX
// (plafond) au-delà du dernier (40%).
const BAREME_TAUX_MARGE = [
    [0,  0],
    [5,  250],
    [15, 500],
    [25, 750],
    [40, 1000],
];

/**
 * Convertit un taux de marge (%) en points, par interpolation linéaire
 * entre les points d'ancrage de BAREME_TAUX_MARGE.
 */
function tauxVersPoints(?float $taux): int {
    if ($taux === null || $taux <= 0) return 0; // pas d'activité, ou marge négative -> 0 pt

    $paliers = BAREME_TAUX_MARGE;
    $dernier = end($paliers);
    if ($taux >= $dernier[0]) return $dernier[1]; // plafond

    for ($i = 0; $i < count($paliers) - 1; $i++) {
        [$seuil_bas, $pts_bas] = $paliers[$i];
        [$seuil_haut, $pts_haut] = $paliers[$i + 1];
        if ($taux >= $seuil_bas && $taux <= $seuil_haut) {
            $ratio = ($taux - $seuil_bas) / ($seuil_haut - $seuil_bas);
            return (int) round($pts_bas + $ratio * ($pts_haut - $pts_bas));
        }
    }
    return 0; // ne devrait jamais être atteint (couvert par les cas ci-dessus)
}

/**
 * 🆕 Prédicat SQL "l'OT est livré" : au moins une ligne d'activité
 * "Déchargement" existe sur cet OT. C'est la définition d'un OT TRAITÉ,
 * utilisée à la fois par le coefficient du classement et par les scores
 * d'affrètement / d'exploitation (seuls les OT livrés comptent dans les
 * marges).
 * Après acceptation d'un affrètement, seule l'équipe affrétée peut saisir
 * des lignes sur l'OT : un Déchargement existant signifie donc "livré
 * par elle" (voir otAppartientEquipe()).
 */
function sqlOtLivre(string $alias_ot): string {
    return "EXISTS (SELECT 1 FROM zl_lignes_activite ld
                    WHERE ld.id_ot = $alias_ot.id_ot AND ld.type_activite = '" . ACTIVITE_DECHARGEMENT . "')";
}

/**
 * Score "Affrètement" d'une équipe : performance en tant qu'AFFRÉTEUR
 * (OT qu'elle a sous-traités et dont la sous-traitance a été acceptée).
 * Taux = marge globale (CA client − prix payé) / CA client global.
 */
function calculerScoreAffretement(PDO $pdo, int $id_equipe): array {
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(ot.ca), 0) AS total_ca, COALESCE(SUM(ot.ca - a.prix_affretement), 0) AS total_marge, COUNT(*) AS nb_ot
         FROM zl_affretements a
         JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
         WHERE a.id_equipe_affreteur = ? AND a.statut = 'acceptee'
           AND " . sqlOtLivre('ot')
    );
    $stmt->execute([$id_equipe]);
    $r = $stmt->fetch();

    $taux = ($r['nb_ot'] > 0 && $r['total_ca'] > 0) ? round(($r['total_marge'] / $r['total_ca']) * 100, 2) : null;

    return [
        'nb_ot'  => (int) $r['nb_ot'],
        'ca'     => (float) $r['total_ca'],
        'marge'  => (float) $r['total_marge'],
        'taux'   => $taux,
        'points' => tauxVersPoints($taux),
    ];
}

/**
 * Score "Exploitation" d'une équipe : performance en tant qu'EXÉCUTANT
 * (tous les OT qu'elle traite avec ses propres véhicules, que ce soit
 * ses propres OT clients non sous-traités, OU des OT qu'elle exécute
 * pour le compte d'une autre équipe après avoir accepté un affrètement).
 * Taux = marge globale / CA (ou prix reçu) global.
 */
function calculerScoreExploitation(PDO $pdo, int $id_equipe): array {
    // (a) Ses propres OT clients, exploités en propre (pas sous-traités)
    $stmt = $pdo->prepare(
        "SELECT ot.id_ot, ot.ca
         FROM zl_ordres_transport ot
         WHERE ot.id_equipe = ?
           AND NOT EXISTS (SELECT 1 FROM zl_affretements a WHERE a.id_ot = ot.id_ot AND a.statut = 'acceptee')
           AND " . sqlOtLivre('ot')
    );
    $stmt->execute([$id_equipe]);
    $ots_propres = $stmt->fetchAll();

    // (b) Les OT affrétés qu'elle exécute pour une autre équipe
    $stmt = $pdo->prepare(
        "SELECT ot.id_ot, a.prix_affretement AS ca
         FROM zl_affretements a
         JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
         WHERE a.id_equipe_affrete = ? AND a.statut = 'acceptee'
           AND " . sqlOtLivre('ot')
    );
    $stmt->execute([$id_equipe]);
    $ots_affretes = $stmt->fetchAll();

    $total_ca = 0.0;
    $total_marge = 0.0;
    $nb_ot = 0;

    foreach (array_merge($ots_propres, $ots_affretes) as $ot) {
        // 🆕 Coût de revient réel (dépend du véhicule/énergie utilisé),
        // au lieu d'un coût au km unique pour tout le monde.
        $cout = coutRevientPourOt($pdo, (int) $ot['id_ot']);
        $total_ca += (float) $ot['ca'];
        $total_marge += (float) $ot['ca'] - $cout;
        $nb_ot++;
    }

    $taux = ($nb_ot > 0 && $total_ca > 0) ? round(($total_marge / $total_ca) * 100, 2) : null;

    return [
        'nb_ot'  => $nb_ot,
        'ca'     => $total_ca,
        'marge'  => round($total_marge, 2),
        'taux'   => $taux,
        'points' => tauxVersPoints($taux),
    ];
}

/**
 * 🆕 Coefficient de traitement d'une équipe = OT traités ÷ OT à traiter.
 *
 * OT à traiter = ses OT clients (attribués par l'admin, qu'elle les
 * exécute elle-même ou les sous-traite) + les OT qu'elle a accepté
 * d'exécuter pour une autre équipe (affrètement accepté).
 * OT traité    = OT livré (voir sqlOtLivre()). Pour un OT sous-traité,
 * c'est donc l'équipe affrétée qui doit l'avoir réellement livré : un
 * affrètement accepté mais jamais exécuté ne compte comme traité pour
 * personne.
 * Une équipe sans aucun OT à traiter a un coefficient de 1.
 */
function calculerCoefficientTraitement(PDO $pdo, int $id_equipe): array {
    $livre = sqlOtLivre('ot');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS nb_a_traiter, COALESCE(SUM(t.livre), 0) AS nb_traites
         FROM (
             SELECT ot.id_ot, $livre AS livre
             FROM zl_ordres_transport ot
             WHERE ot.id_equipe = ?
             UNION ALL
             SELECT ot.id_ot, $livre AS livre
             FROM zl_affretements a
             JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
             WHERE a.id_equipe_affrete = ? AND a.statut = 'acceptee'
         ) t"
    );
    $stmt->execute([$id_equipe, $id_equipe]);
    $r = $stmt->fetch();

    $nb_a_traiter = (int) $r['nb_a_traiter'];
    $nb_traites = (int) $r['nb_traites'];

    return [
        'nb_a_traiter' => $nb_a_traiter,
        'nb_traites'   => $nb_traites,
        'coefficient'  => $nb_a_traiter > 0 ? $nb_traites / $nb_a_traiter : 1.0,
    ];
}

/**
 * Infractions conduite/repos : chaque ligne d'activité EXÉCUTÉE par
 * l'équipe (feuilles où elle est l'équipe exploitante, quelle que soit
 * l'origine de l'OT) qui dépasse un des 3 seuils réglementaires compte
 * comme une infraction distincte (ce sont 3 règles différentes, une
 * ligne peut donc cumuler plusieurs infractions).
 */
function compterInfractionsConduiteRepos(PDO $pdo, int $id_equipe): int {
    $stmt = $pdo->prepare(
        "SELECT
            SUM(l.conduite_continue_cumul > 4.5) +
            SUM(l.total_conduite_cumul > 10) +
            SUM(l.total_service_cumul > 12) AS nb
         FROM zl_lignes_activite l
         JOIN zl_feuilles_otea f ON l.id_feuille = f.id_feuille
         WHERE f.id_equipe = ?"
    );
    $stmt->execute([$id_equipe]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

/**
 * 🆕 Expression SQL qui DÉDUIT le département d'un code postal (colonne
 * passée en paramètre, ex. 'ot.cp_adresse_expediteur'), au lieu de
 * stocker le département dans une colonne à part (redondant avec le CP).
 *
 * Règle : 2 premiers chiffres du code postal, avec l'exception de la
 * Corse (codes postaux 20xxx, pas de département "20" depuis 1976) :
 * 200xx-201xx -> 2A (Corse-du-Sud), 202xx et plus -> 2B (Haute-Corse).
 * Résultat NULL si le code postal n'est pas composé de 5 chiffres.
 * Les codes outre-mer (97x) et Monaco (98) donnent un code absent de
 * ref_departements : ils sont ignorés par la jointure qui suit (donc
 * jamais pénalisés).
 */
function sqlDepartementDepuisCp(string $colonne_cp): string {
    $cp = "REPLACE($colonne_cp, ' ', '')";
    return "(CASE WHEN $cp REGEXP '^[0-9]{5}\$'
                   THEN (CASE WHEN LEFT($cp, 2) = '20'
                              THEN IF(CAST(LEFT($cp, 3) AS UNSIGNED) <= 201, '2A', '2B')
                              ELSE LEFT($cp, 2) END)
              END)";
}

/**
 * 🆕 Rechargements hors zone : à chaque changement d'OT au sein d'une
 * même feuille de route (donc sur le MÊME véhicule), on compare :
 *   - le département DESTINATAIRE de l'OT qui vient d'être quitté,
 *   - au département EXPÉDITEUR du nouvel OT.
 * Les deux départements sont déduits des codes postaux (voir
 * sqlDepartementDepuisCp()). S'ils ne sont ni identiques ni limitrophes
 * (zl_departements_limitrophes), c'est une infraction. Si l'un des deux
 * est inconnu (code postal absent/invalide, hors métropole), on ne
 * pénalise pas : donnée manquante, pas une infraction avérée.
 *
 * Exemple : OT précédent livré dans le 13, nouvel OT chargé dans un
 * département qui n'est ni le 13 ni limitrophe du 13 (04, 30, 83, 84)
 * -> infraction.
 *
 * Utilise LAG() (fenêtrage SQL, MariaDB >= 10.2) pour comparer chaque
 * ligne à la précédente de la même feuille sans boucle PHP.
 */
function compterRechargementsHorsZone(PDO $pdo, int $id_equipe): int {
    $dept_exp  = sqlDepartementDepuisCp('ot.cp_adresse_expediteur');
    $dept_dest = sqlDepartementDepuisCp('ot.cp_adresse_destinataire');

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM (
            SELECT
                l.id_ot,
                d_exp.code_dept AS dept_exp_courant,
                LAG(l.id_ot) OVER (PARTITION BY l.id_feuille ORDER BY l.ordre_chronologique) AS prev_id_ot,
                LAG(d_dest.code_dept) OVER (PARTITION BY l.id_feuille ORDER BY l.ordre_chronologique) AS prev_dept_dest
            FROM zl_lignes_activite l
            JOIN zl_feuilles_otea f ON l.id_feuille = f.id_feuille
            JOIN zl_ordres_transport ot ON l.id_ot = ot.id_ot
            LEFT JOIN ref_departements d_exp  ON d_exp.code_dept  = $dept_exp
            LEFT JOIN ref_departements d_dest ON d_dest.code_dept = $dept_dest
            WHERE f.id_equipe = ?
         ) t
         WHERE t.id_ot <> t.prev_id_ot
           AND t.dept_exp_courant IS NOT NULL AND t.prev_dept_dest IS NOT NULL
           AND t.dept_exp_courant <> t.prev_dept_dest
           AND NOT EXISTS (
               SELECT 1 FROM zl_departements_limitrophes dl
               WHERE dl.code_dept = t.prev_dept_dest AND dl.code_dept_limitrophe = t.dept_exp_courant
           )"
    );
    $stmt->execute([$id_equipe]);
    return (int) $stmt->fetchColumn();
}

/**
 * Agrège tout ce qui précède en un score final pour une équipe, sur
 * POINTS_MAX (1000) points.
 *
 *   Score calculé = max(0, 1/3 × points_affrètement + 2/3 × points_exploitation
 *                          − pénalités conduite/repos − pénalités rechargement)
 *   Score final   = Score calculé × coefficient de traitement
 *
 * 🆕 Le plancher à 0 est appliqué AVANT le coefficient : sinon un score
 * négatif multiplié par un coefficient < 1 deviendrait moins négatif, et
 * le coefficient atténuerait les pénalités au lieu de les aggraver.
 * (Remplace l'ancien malus fixe par OT non traité.)
 */
function calculerClassementEquipe(PDO $pdo, int $id_equipe): array {
    $stmtMalus = $pdo->query(
        'SELECT malus_infraction_conduite_repos, malus_rechargement_hors_zone
         FROM zl_config_parametres WHERE id_config = 1'
    );
    $malus = $stmtMalus->fetch();

    $affretement = calculerScoreAffretement($pdo, $id_equipe);
    $exploitation = calculerScoreExploitation($pdo, $id_equipe);
    $traitement = calculerCoefficientTraitement($pdo, $id_equipe);

    $nb_infractions = compterInfractionsConduiteRepos($pdo, $id_equipe);
    $nb_rechargements_hors_zone = compterRechargementsHorsZone($pdo, $id_equipe);

    $total_penalites =
        ($nb_infractions * (float) $malus['malus_infraction_conduite_repos']) +
        ($nb_rechargements_hors_zone * (float) $malus['malus_rechargement_hors_zone']);

    $score_brut = (1 / 3 * $affretement['points']) + (2 / 3 * $exploitation['points']) - $total_penalites;
    $score_calcule = max(0, $score_brut);
    $note = $score_calcule * $traitement['coefficient'];

    return [
        'affretement'  => $affretement,
        'exploitation' => $exploitation,
        'nb_ot_a_traiter'            => $traitement['nb_a_traiter'],
        'nb_ot_traites'              => $traitement['nb_traites'],
        'coefficient'                => round($traitement['coefficient'], 4),
        'nb_infractions'             => $nb_infractions,
        'nb_rechargements_hors_zone' => $nb_rechargements_hors_zone,
        'total_penalites'            => round($total_penalites, 2),
        'score_calcule'              => round($score_calcule, 2),
        'note'                       => round($note, 2),
    ];
}

/**
 * Renvoie l'affrètement "actif" (en_attente ou acceptée) pour un OT,
 * ou null s'il n'y en a pas / plus (refusé). Un OT ne peut avoir qu'un
 * affrètement actif à la fois.
 */
function affretementActifPourOt(PDO $pdo, int $id_ot): ?array {
    $stmt = $pdo->prepare(
        "SELECT a.*, e1.nom_equipe AS nom_affreteur, e2.nom_equipe AS nom_affrete
         FROM zl_affretements a
         JOIN equipes e1 ON a.id_equipe_affreteur = e1.id_equipe
         JOIN equipes e2 ON a.id_equipe_affrete = e2.id_equipe
         WHERE a.id_ot = ? AND a.statut IN ('en_attente', 'acceptee')
         ORDER BY a.id_affretement DESC LIMIT 1"
    );
    $stmt->execute([$id_ot]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Renvoie le DERNIER affrètement connu pour un OT, quel que soit son
 * statut (y compris refusée / annulée), ou null s'il n'y en a jamais eu.
 * Sert uniquement à l'affichage (ex: "Refusé par l'équipe X", "Annulé"),
 * jamais à décider si une nouvelle déclaration est autorisée : c'est le
 * rôle de affretementActifPourOt() ci-dessus.
 */
function dernierAffretementPourOt(PDO $pdo, int $id_ot): ?array {
    $stmt = $pdo->prepare(
        "SELECT a.*, e1.nom_equipe AS nom_affreteur, e2.nom_equipe AS nom_affrete
         FROM zl_affretements a
         JOIN equipes e1 ON a.id_equipe_affreteur = e1.id_equipe
         JOIN equipes e2 ON a.id_equipe_affrete = e2.id_equipe
         WHERE a.id_ot = ?
         ORDER BY a.id_affretement DESC LIMIT 1"
    );
    $stmt->execute([$id_ot]);
    $res = $stmt->fetch();
    return $res ?: null;
}

// -----------------------------------------------------------------------
// 7.  AUTOMATIQUE ET JOURS DU CHALLENGE
// -----------------------------------------------------------------------

/**
 * 🆕 Liste des jours du challenge (Y-m-d), de la date de début à la date
 * de fin lues dans zl_config_parametres. Si la date de fin n'est pas
 * renseignée (champ optionnel), plage de repli de 14 jours. Garde-fou :
 * 120 jours maximum. Utilisée par la liste déroulante du planning ET par
 * le calcul des s (pas de repos pour le dernier jour).
 */
function getJoursChallenge(PDO $pdo): array {
    $config = $pdo->query('SELECT date_debut_challenge_zl, date_fin_challenge_zl FROM zl_config_parametres WHERE id_config = 1 LIMIT 1')->fetch();
    $debut = $config ? $config['date_debut_challenge_zl'] : '2026-06-15';
    $fin = $config && !empty($config['date_fin_challenge_zl'])
        ? $config['date_fin_challenge_zl']
        : date('Y-m-d', strtotime($debut . ' +13 days'));

    $jours = [];
    try {
        $curseur = new DateTime($debut);
        $limite = new DateTime($fin);
        while ($curseur <= $limite && count($jours) < 120) {
            $jours[] = $curseur->format('Y-m-d');
            $curseur->modify('+1 day');
        }
    } catch (Exception $e) {
        $jours = [];
    }
    return $jours ?: [$debut];
}

/**
 * 🆕 Message d'erreur pour un  calculé inférieur au
 * minimum. $v vient de recalculerFeuille() (actions.php) :
 * date_repos (jour D), lendemain, fin (heure de fin du jour D, en heures
 * décimales, peut dépasser 24), prise_service (heure de prise de service
 * du lendemain), repos (durée calculée), repos_min.
 */
function messageViolationRepos(array $v): string {
    $fmt_jour = fn(string $d) => date('d/m/Y', strtotime($d));
    $fmt_heure = fn(float $h) => convertisseurDecimalToTime(fmod($h, 24)) . ($h >= 24 ? ' (jour suivant)' : '');

    $ps_min = $v['fin'] + $v['repos_min'] - 24; // heure de prise de service minimale le lendemain

    $msg = " insuffisant entre le " . $fmt_jour($v['date_repos']) . " (fin de journée à " . $fmt_heure($v['fin'])
         . ") et le " . $fmt_jour($v['lendemain']) . " (prise de service à " . $fmt_heure($v['prise_service'])
         . ") : " . formaterHeureDecimales(max(0, $v['repos'])) . " au lieu de " . formaterHeureDecimales($v['repos_min']) . " minimum. ";

    if ($ps_min <= 24) {
        $msg .= "La prise de service du " . $fmt_jour($v['lendemain']) . " doit être au plus tôt à " . convertisseurDecimalToTime(max(0, $ps_min))
              . " (ou terminez plus tôt le " . $fmt_jour($v['date_repos']) . ").";
    } else {
        $msg .= "La journée du " . $fmt_jour($v['date_repos']) . " se termine trop tard pour respecter ce repos : réduisez-la.";
    }
    return $msg;
}

/**
 * 🆕 Message d'erreur pour une activité qui déborde après la fin de la
 * journée (HEURE_FIN_JOURNEE). $v vient de recalculerFeuille() (actions.php) :
 * date, type (type d'activité), debut et fin (heures décimales, fin > 24).
 */
function messageDepassementJournee(array $v): string {
    return "Dépassement de la fin de journée : l'activité « " . $v['type'] . " » du " . date('d/m/Y', strtotime($v['date']))
         . " commencerait à " . convertisseurDecimalToTime($v['debut'])
         . " et se terminerait à " . convertisseurDecimalToTime(fmod($v['fin'], 24)) . " le lendemain. "
         . "Une activité doit être terminée avant la fin de la journée (23h59).";
}
