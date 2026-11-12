<?php
// Sécurité : Vérification de la session
if (!isset($_SESSION['team_id'])) { exit; }

$id_equipe = (int) $_SESSION['team_id'];

// 1. CONFIGURATIONS
$stmtConfig = $pdo->query('SELECT heure_prise_service_defaut, date_debut_challenge_zl, date_fin_challenge_zl, repos_journalier_min_h FROM zl_config_parametres WHERE id_config = 1 LIMIT 1');
$config = $stmtConfig->fetch();
$heure_fixe_bdd = $config ? floatval($config['heure_prise_service_defaut']) : 5.00;
$date_debut_semaine = $config ? $config['date_debut_challenge_zl'] : '2026-06-15';
$repos_min = $config ? floatval($config['repos_journalier_min_h']) : 9.0;

// 🆕 PLANNING PAR JOUR : liste des jours du challenge (date de début ->
// date de fin), pour la nouvelle liste déroulante de sélection de date.
// -----------------------------------------------------------------------
// Si la date de fin n'est pas renseignée par l'admin (champ optionnel
// dans Paramètres ZL), plage de 14 jours par défaut (voir getJoursChallenge()
// dans functions.php, partagée avec le calcul des s).
$noms_jours_fr = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$jours_challenge = getJoursChallenge($pdo);

/** Formate une date (Y-m-d) en "Lundi 15/06/2026" sans dépendre de la locale serveur. */
function formaterJourFr(string $date_iso, array $noms_jours): string {
    $ts = strtotime($date_iso);
    $nom = $noms_jours[((int) date('N', $ts)) - 1];
    return $nom . ' ' . date('d/m/Y', $ts);
}

// Jour sélectionné par l'équipe (doit faire partie de la plage du challenge)
$date_jour = isset($_GET['date_jour']) && in_array($_GET['date_jour'], $jours_challenge, true)
    ? $_GET['date_jour']
    : null;

// 🗑️ Zones retirées (voir migration_suppression_zones_dept_adresses.sql) :
// la liste des OT de l'équipe n'est plus conditionnée à une zone.

// 2. RÉCUPÉRATION VÉHICULES
// 🆕 Type, carrosserie, énergie et coût/km récupérés en une fois, pour
// affichage à droite du sélecteur une fois un véhicule choisi.
$id_vehicule = isset($_GET['id_vehicule']) ? intval($_GET['id_vehicule']) : null;
$stmtVehicules = $pdo->query(
    "SELECT v.id_vehicule, v.libelle, t.libelle AS type_vehicule, c.libelle AS type_carrosserie, re.libelle AS libelle_energie, re.cout_km
     FROM zl_vehicules v
     JOIN ref_types_vehicule t ON v.id_type = t.id_type
     JOIN ref_carrosseries c ON v.id_carrosserie = c.id_carrosserie
     JOIN ref_energies re ON v.id_energie = re.id_energie
     ORDER BY v.libelle"
);
$liste_vehicules = $stmtVehicules->fetchAll();

$vehicule_selectionne = null;
if ($id_vehicule) {
    foreach ($liste_vehicules as $v) {
        if ((int) $v['id_vehicule'] === $id_vehicule) { $vehicule_selectionne = $v; break; }
    }
}

// 3. On filtre les OT par équipe
// 🆕 ÉVOLUTION "AFFRÈTEMENT" : la liste doit désormais contenir
//    - les OT dont l'équipe est cliente ET qu'elle n'a PAS sous-traités
//      (pas d'affrètement accepté dessus) ;
//    - les OT que d'autres équipes lui ont sous-traités et qu'elle a
//      ACCEPTÉ d'exécuter.
// (même logique que otAppartientEquipe() dans functions.php, en version
// "liste" plutôt que "test d'un seul id_ot")
$stmtOts = $pdo->prepare(
    "SELECT id_ot, numero_ot, expediteur, destinataire FROM zl_ordres_transport ot
     WHERE ot.id_equipe = :id_equipe
       AND NOT EXISTS (
           SELECT 1 FROM zl_affretements a WHERE a.id_ot = ot.id_ot AND a.statut = 'acceptee'
       )
     UNION
     SELECT ot.id_ot, ot.numero_ot, ot.expediteur, ot.destinataire FROM zl_ordres_transport ot
     JOIN zl_affretements a ON a.id_ot = ot.id_ot
     WHERE a.id_equipe_affrete = :id_equipe_affrete AND a.statut = 'acceptee'
     ORDER BY numero_ot"
);
$stmtOts->execute([':id_equipe' => $id_equipe, ':id_equipe_affrete' => $id_equipe]);
$liste_ots = $stmtOts->fetchAll();

// 4. AFFICHAGE DU SÉLECTEUR (TOUJOURS VISIBLE)
?>
<div class="mb-4">
    <h2 class="fw-bold text-dark">Planning ZL : <?= htmlspecialchars($_SESSION['team_nom']) ?></h2>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label class="fw-bold">Sélectionner un véhicule :</label>
                <select name="id_vehicule" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Choisir un véhicule --</option>
                    <?php foreach ($liste_vehicules as $v): ?>
                        <option value="<?= $v['id_vehicule'] ?>" <?= ($id_vehicule == $v['id_vehicule']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['libelle']) ?> — <?= htmlspecialchars($v['type_vehicule']) ?> / <?= htmlspecialchars($v['type_carrosserie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- 🆕 Détails du véhicule choisi, affichés à droite du sélecteur -->
            <?php if ($vehicule_selectionne): ?>
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-secondary bg-opacity-75 fs-6 fw-normal py-2 px-3">
                        <i class="bi bi-truck me-1"></i> <?= htmlspecialchars($vehicule_selectionne['type_vehicule']) ?>
                    </span>
                    <span class="badge bg-secondary bg-opacity-50 fs-6 fw-normal py-2 px-3">
                        <i class="bi bi-box-seam me-1"></i> <?= htmlspecialchars($vehicule_selectionne['type_carrosserie']) ?>
                    </span>
                    <span class="badge bg-info bg-opacity-25 text-dark border fs-6 fw-normal py-2 px-3">
                        <i class="bi bi-fuel-pump me-1"></i>
                        <?= htmlspecialchars($vehicule_selectionne['libelle_energie']) ?>
                    </span>
                    <span class="badge bg-warning bg-opacity-25 text-dark border fs-6 fw-normal py-2 px-3">
                        <i class="bi bi-cash-coin me-1"></i> <?= number_format($vehicule_selectionne['cout_km'], 3, ',', ' ') ?> €/km
                    </span>
                </div>
            </div>

            <!-- 🆕 PLANNING PAR JOUR : une fois le véhicule choisi, l'équipe doit
                 choisir le jour qu'elle veut saisir. La saisie est volontairement
                 limitée à UN SEUL jour à la fois (pas de saisie de plusieurs
                 jours d'affilée sur le même écran). -->
            <div class="col-md-4 mt-3">
                <label class="fw-bold">Date du planning à remplir :</label>
                <select name="date_jour" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Choisir une date --</option>
                    <?php foreach ($jours_challenge as $jour): ?>
                        <option value="<?= $jour ?>" <?= ($date_jour === $jour) ? 'selected' : '' ?>>
                            <?= formaterJourFr($jour, $noms_jours_fr) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$config || empty($config['date_fin_challenge_zl'])): ?>
                    <div class="form-text text-warning"><i class="bi bi-exclamation-triangle"></i> Date de fin du challenge non définie par l'administrateur — plage de 14 jours affichée par défaut.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php
// 5. LOGIQUE FEUILLE ET RÉCUPÉRATION DONNÉES (CUMULS DIRECTS EN BDD)
$id_feuille = null;
$lignes = [];
$titre_tableau_de_bord = "Tableau de bord (Données Relatées Zone Longue)";
// 🆕 Par défaut (avant saisie du jour), l'horaire proposé reste celui de
// la prise de service par défaut — plus de notion de "dernière date" du
// challenge entier, puisqu'on ne travaille plus que sur UN jour à la fois.
$derniere_date = $date_jour;
$prochain_horaire = $heure_fixe_bdd;

// 🆕 Il faut désormais un véhicule ET un jour choisis pour afficher le
// planning. Tant que l'un des deux manque, on arrête l'affichage ici
// (après le sélecteur), comme avant pour le seul véhicule.
if (!$id_vehicule) {
    echo '<div class="alert alert-warning">Veuillez sélectionner un véhicule pour commencer la saisie.</div>';
    return;
}
if (!$date_jour) {
    echo '<div class="alert alert-warning">Veuillez choisir une date dans la liste ci-dessus pour commencer la saisie.</div>';
    return;
}

$stmtV = $pdo->prepare("SELECT libelle FROM zl_vehicules WHERE id_vehicule = ?");
$stmtV->execute([$id_vehicule]);
if ($v = $stmtV->fetch()) {
    $titre_tableau_de_bord = "Tableau de bord " . htmlspecialchars($v['libelle']) . " — " . formaterJourFr($date_jour, $noms_jours_fr);
}

$stmtF = $pdo->prepare('SELECT id_feuille FROM zl_feuilles_otea WHERE id_equipe = ? AND id_vehicule = ? AND date_debut_semaine = ?');
$stmtF->execute([$id_equipe, $id_vehicule, $date_debut_semaine]);
if ($f = $stmtF->fetch()) {
    $id_feuille = $f['id_feuille'];

    // 🆕 "AND l.date_activite = ?" : on ne récupère plus que les lignes
    // du JOUR choisi, pas toute la feuille du véhicule (qui couvre
    // l'ensemble du challenge). C'est ce qui donne "un planning par
    // jour" à l'écran, même si en base la feuille reste unique par
    // véhicule (le recalcul des cumuls, lui, continue de se faire sur
    // l'ensemble de la feuille — voir recalculerFeuille() dans actions.php
    // — pour que les cumuls jour après jour restent corrects).
    $stmtL = $pdo->prepare('
        SELECT
            l.*,
            ot.numero_ot,
            CASE
                WHEN l.type_activite = "Conduite" THEN ROUND(l.km_parcouru / p.vitesse_moyenne_kmh, 2)
                WHEN l.type_activite = "Chargement" THEN p.temps_chargement_h
                WHEN l.type_activite = "Déchargement" THEN p.temps_dechargement_h
                WHEN l.type_activite = "Pause" THEN p.temps_pause_h
                WHEN l.type_activite = "" THEN COALESCE(l.duree_repos_h, 0.00)
                ELSE 0.00
            END AS duree_calculee
        FROM zl_lignes_activite l
        JOIN zl_ordres_transport ot ON l.id_ot = ot.id_ot
        JOIN zl_config_parametres p ON p.id_config = 1
        WHERE l.id_feuille = ? AND l.date_activite = ?
        ORDER BY l.ordre_chronologique ASC
    ');
    $stmtL->execute([$id_feuille, $date_jour]);
    $lignes = $stmtL->fetchAll();
}

// Calcul des données pour le formulaire (horaire proposé pour la
// prochaine ligne DE CE JOUR)
if (!empty($lignes)) {
    // 🆕 Le  est calculé automatiquement et toujours en
    // dernière ligne du jour : l'horaire suivant se base sur la dernière
    // ligne qui n'est PAS un repos.
    $last = null;
    foreach ($lignes as $candidate) {
        if ($candidate['type_activite'] !== ACTIVITE_REPOS_JOURNALIER) $last = $candidate;
    }
    if ($last) {
        $derniere_date = $last['date_activite'];
        $prochain_horaire = $last['horaire_debut'] + $last['duree_calculee'];
    }
}

// Gestion édition
// 🔒 CORRECTION SÉCURITÉ n°10 : contrôle de propriété à l'affichage aussi
// -----------------------------------------------------------------------
// Avant, n'importe quel id_ligne passé dans l'URL "?action=modifier&id_ligne=X"
// était chargé sans vérifier qu'il appartenait à l'équipe connectée : une
// équipe pouvait ainsi voir apparaître, pré-rempli dans SON formulaire,
// le contenu d'une ligne appartenant à une AUTRE équipe (fuite de
// données). On réutilise ici le même helper que dans actions.php.
$ligne_a_modifier = null;
if (isset($_GET['action']) && $_GET['action'] === 'modifier' && isset($_GET['id_ligne'])) {
    $id_ligne_demandee = intval($_GET['id_ligne']);
    if (ligneAppartientEquipe($pdo, $id_ligne_demandee, $id_equipe)) {
        $stmtMod = $pdo->prepare('SELECT * FROM zl_lignes_activite WHERE id_ligne = ?');
        $stmtMod->execute([$id_ligne_demandee]);
        $ligne_a_modifier = $stmtMod->fetch();

        // 🆕 PLANNING PAR JOUR : le champ date n'est plus éditable dans le
        // formulaire (il est désormais verrouillé sur $date_jour). Si la
        // ligne demandée appartient à un AUTRE jour que celui actuellement
        // sélectionné, on refuse le mode édition plutôt que de risquer
        // d'écraser silencieusement sa date au moment de l'enregistrement.
        if ($ligne_a_modifier && $ligne_a_modifier['date_activite'] !== $date_jour) {
            $ligne_a_modifier = null;
        }
        // 🆕 Le  (calculé automatiquement) ne se modifie pas.
        if ($ligne_a_modifier && $ligne_a_modifier['type_activite'] === ACTIVITE_REPOS_JOURNALIER) {
            $ligne_a_modifier = null;
        }
    }
    // Si la ligne n'appartient pas à l'équipe : $ligne_a_modifier reste
    // `null`, le formulaire s'affiche simplement en mode "ajout".
}

// 🔒 Jeton CSRF à injecter dans tous les formulaires de cette page
// (généré dans index.php, on le récupère simplement ici)
$csrf_token = $_SESSION['csrf_token'] ?? '';
?>

<div class="row g-4">
    <div class="col-md-4">
        <?php
        // 🆕 Note : à ce stade, $id_vehicule ET $date_jour sont forcément
        // définis — sinon la page se serait arrêtée plus haut (voir les
        // deux `return;` juste après la récupération des véhicules). Plus
        // besoin du "sinon, sélectionnez un véhicule" qui existait ici.
        ?>
            <div class="card shadow-sm border-0">
                <div class="card-header <?= $ligne_a_modifier ? 'bg-warning text-dark' : 'bg-secondary text-white' ?> fw-bold">
                    <?= $ligne_a_modifier ? '<i class="bi bi-pencil-square me-1"></i> Modifier l\'événement' : '<i class="bi bi-plus-circle me-1"></i> Ajouter un événement' ?>
                </div>
                <div class="card-body">
                    <?php if(!empty($form_error)): ?>
                        <div class="alert alert-danger small py-2 fw-bold"><i class="bi bi-exclamation-octagon-fill me-1"></i> <?= htmlspecialchars($form_error) ?></div>
                    <?php endif; ?>

                    <form action="actions.php" method="POST">
                        <!-- 🔒 Jeton CSRF obligatoire pour toute écriture -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <?php if ($ligne_a_modifier): ?>
                            <input type="hidden" name="id_ligne" value="<?= $ligne_a_modifier['id_ligne'] ?>">
                        <?php endif; ?>

                        <input type="hidden" name="id_vehicule" value="<?= htmlspecialchars($id_vehicule) ?>">
                        <input type="hidden" name="date_debut_semaine" value="<?= htmlspecialchars($date_debut_semaine) ?>">

                        <!-- 🆕 PLANNING PAR JOUR : la date n'est plus un champ libre — elle
                             est verrouillée sur le jour choisi dans le sélecteur ci-dessus,
                             pour garantir que toute la saisie de cet écran reste sur UN SEUL
                             jour. Pour saisir un autre jour, il faut changer la sélection en
                             haut de page. -->
                        <input type="hidden" name="date_activite" value="<?= htmlspecialchars($date_jour) ?>">
                        <input type="hidden" name="date_jour" value="<?= htmlspecialchars($date_jour) ?>">
                        <div class="mb-2">
                            <label class="small text-muted fw-bold">Date d'activité</label>
                            <input type="text" class="form-control bg-light" value="<?= formaterJourFr($date_jour, $noms_jours_fr) ?>" disabled>
                        </div>

                        <div class="mb-2">
                            <label class="small text-primary fw-bold"><i class="bi bi-scooter mr-1"></i> Ordre de Transport (OT) affecté</label>
                            <select name="id_ot" class="form-select border-primary" required>
                                <option value="" disabled <?= !$ligne_a_modifier ? 'selected' : '' ?>>-- Sélectionner l'OT concerné --</option>
                                <?php foreach ($liste_ots as $ot): ?>
                                    <?php $selected_ot = ($ligne_a_modifier && $ligne_a_modifier['id_ot'] == $ot['id_ot']) ? 'selected' : ''; ?>
                                    <option value="<?= $ot['id_ot'] ?>" <?= $selected_ot ?>>
                                        OT n°<?= htmlspecialchars($ot['numero_ot']) ?> (<?= htmlspecialchars($ot['expediteur']) ?> -> <?= htmlspecialchars($ot['destinataire']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold">Type d'activité</label>
                            <select name="type_activite" id="type_activite" class="form-select" onchange="toggleFormFields()" required>
                                <?php
                                // 🔒 Utilise désormais la constante partagée TYPES_ACTIVITE_VALIDES
                                // (functions.php) plutôt qu'un tableau recopié localement, pour
                                // rester garanti cohérent avec la validation faite côté serveur
                                // dans actions.php.
                                $selected_type = $ligne_a_modifier ? $ligne_a_modifier['type_activite'] : ACTIVITE_PRISE_DE_SERVICE;
                                foreach (TYPES_ACTIVITE_VALIDES as $t) {
                                    // 🆕 Le  n'est plus saisi : calculé automatiquement
                                    if ($t === ACTIVITE_REPOS_JOURNALIER) continue;
                                    $sel = ($t === $selected_type) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($t) . '" ' . $sel . '>' . htmlspecialchars($t) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-2" id="group_horaire" style="display: <?= $selected_type === ACTIVITE_PRISE_DE_SERVICE ? 'block' : 'none' ?>;">
                            <label class="small text-success fw-bold">Horaire Début (Heures:Minutes)</label>
                            <input type="time" name="horaire_debut" id="horaire_debut" value="<?= $ligne_a_modifier ? convertisseurDecimalToTime($ligne_a_modifier['horaire_debut']) : convertisseurDecimalToTime($prochain_horaire) ?>" class="form-control" required>
                        </div>

                        <div class="mb-3" id="group_km" style="display: <?= $selected_type === ACTIVITE_CONDUITE ? 'block' : 'none' ?>;">
                            <label class="small text-muted fw-bold text-primary">Kilomètres parcourus</label>
                            <input type="number" name="km_parcouru" class="form-control" value="<?= $ligne_a_modifier ? $ligne_a_modifier['km_parcouru'] : 0 ?>" min="1" required>
                        </div>

                        <?php if ($ligne_a_modifier): ?>
                            <button type="submit" name="modifier_activite" class="btn btn-warning w-100 text-dark fw-bold mb-2">Enregistrer les modifications</button>
                            <a href="index.php?id_vehicule=<?= $id_vehicule ?>&date_jour=<?= urlencode($date_jour) ?>" class="btn btn-light border w-100 btn-sm text-secondary">Annuler la modification</a>
                        <?php else: ?>
                            <button type="submit" name="ajouter_activite" class="btn btn-success w-100 fw-bold">Enregistrer la ligne</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
    </div>


    <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold"><?= $titre_tableau_de_bord ?></div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0 text-center table-sm small w-100">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>N°</th>
                                <th>Date</th>
                                <th>Début</th>
                                <th>N° OT</th>
                                <th class="text-start ps-3">Activité</th>
                                <th>Km</th>
                                <th>Durée</th>
                                <th>Total Km</th>
                                <th>Conduite<br>continue</th>
                                <th>Total<br>Conduite</th>
                                <th>Total<br>Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if(empty($lignes))
                        { ?>
                            <tr><td colspan="12" class="text-center text-muted py-4">Aucun fait enregistré pour cette date. Démarrez la saisie à gauche.</td></tr>
                        <?php
                        }
                        else
                        {
                            $cumul_km_temporaire = 0;
                            $date_precedente_km = null;

                            foreach($lignes as $l)
                            {
                                if ($date_precedente_km !== null && $l['date_activite'] !== $date_precedente_km) {
                                    $cumul_km_temporaire = 0;
                                }
                                $date_precedente_km = $l['date_activite'];

                                $cumul_km_temporaire += $l['km_parcouru'];
                                $l['total_km_cumul'] = $cumul_km_temporaire;
                            ?>
                                <tr>
                                    <td><?= $l['ordre_chronologique'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['date_activite'])) ?></td>
                                    <td class="fw-bold text-dark"><?= formaterHeureDecimales($l['horaire_debut']) ?></td>
                                    <td><span class="badge bg-secondary">OT-<?= htmlspecialchars($l['numero_ot']) ?></span></td>
                                    <td class="text-start ps-3"><?= htmlspecialchars($l['type_activite']) ?></td>
                                    <td><?= $l['km_parcouru'] > 0 ? $l['km_parcouru'] : '-' ?></td>
                                    <td<?= ($l['type_activite'] === ACTIVITE_REPOS_JOURNALIER && $l['duree_calculee'] < $repos_min) ? ' class="text-danger fw-bold" title="Inférieur au repos minimum"' : '' ?>><?= formaterHeureDecimales($l['duree_calculee']) ?></td>
                                    <td><?= number_format($l['total_km_cumul'], 1, ',', '') ?> km</td>
                                    <td>
                                        <?= $l['conduite_continue_cumul'] > 0 ? formaterHeureDecimales($l['conduite_continue_cumul']) : '-' ?>
                                        <?php if($l['conduite_continue_cumul'] > 4.5 && $l['type_activite'] === 'Conduite'): ?>
                                            <br><span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle-fill"></i> > 4h30 !</span>
                                        <?php endif; ?>
                                    </td>
                                        <td>
                                        <?= formaterHeureDecimales($l['total_conduite_cumul']) ?>
                                        <?php if($l['total_conduite_cumul'] > 10 ): ?>
                                            <br><span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle-fill"></i> > 10h00 !</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= formaterHeureDecimales($l['total_service_cumul']) ?>
                                        <?php if($l['total_service_cumul'] > 12 ): ?>
                                            <br><span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle-fill"></i> > 12h00 !</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($l['type_activite'] === ACTIVITE_REPOS_JOURNALIER): ?>
                                            <!-- 🆕  calculé automatiquement : non modifiable, non supprimable -->
                                            <span class="badge bg-secondary" title="Calculé automatiquement à partir de la prise de service du lendemain"><i class="bi bi-lock-fill"></i> Auto</span>
                                        <?php else: ?>
                                        <div class="btn-group btn-group-sm">
                                                <a href="index.php?action=modifier&id_ligne=<?= $l['id_ligne'] ?>&id_vehicule=<?= htmlspecialchars($id_vehicule) ?>&date_jour=<?= urlencode($date_jour) ?>" class="btn btn-outline-warning"><i class="bi bi-pencil-square text-dark"></i></a>

                                                <!--
                                                    🔒 CORRECTION : suppression passée d'un lien GET à un
                                                    mini-formulaire POST protégé par jeton CSRF (voir
                                                    actions.php). Un lien <a href="...action=supprimer...">
                                                    est une requête GET : le navigateur (ou une page piégée
                                                    sur un autre site) peut la déclencher sans action
                                                    volontaire de l'utilisateur. Un <form method="POST"> avec
                                                    jeton CSRF ne peut pas être déclenché depuis un site tiers.
                                                -->
                                                <form action="actions.php" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Confirmer la suppression ?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="id_ligne" value="<?= $l['id_ligne'] ?>">
                                                    <input type="hidden" name="id_vehicule" value="<?= htmlspecialchars($id_vehicule) ?>">
                                                    <input type="hidden" name="date_jour" value="<?= htmlspecialchars($date_jour) ?>">
                                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php
                            }
                            unset($l);
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>

<script>
    const estEnModeEdition = <?= $ligne_a_modifier ? 'true' : 'false' ?>;
    const heureFixeBDD = <?= json_encode($heure_fixe_bdd) ?>;
    const prochainHoraireCalcule = <?= json_encode($prochain_horaire) ?>;


    function formatDecimalToTimeString(decimal) {
        let heures = Math.floor(decimal) % 24;
        let minutes = Math.round((decimal - Math.floor(decimal)) * 60);
        if (minutes === 60) { heures = (heures + 1) % 24; minutes = 0; }
        return String(heures).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
    }

    function toggleFormFields() {
        const type = document.getElementById('type_activite').value;
        const groupHoraire = document.getElementById('group_horaire');
        const champHoraire = document.getElementById('horaire_debut');
        const groupKm = document.getElementById('group_km');
        const inputKm = document.querySelector('input[name="km_parcouru"]');

        if (type === 'Prise de service') {
            groupHoraire.style.display = 'block';
            if (!estEnModeEdition && champHoraire.value === "") {
                champHoraire.value = formatDecimalToTimeString(heureFixeBDD);
            }
        } else {
            groupHoraire.style.display = 'none';
            champHoraire.value = formatDecimalToTimeString(prochainHoraireCalcule);
        }

        if (groupKm && inputKm) {
            groupKm.style.display = (type === 'Conduite') ? 'block' : 'none';
            inputKm.disabled = (type !== 'Conduite');
        }

    }

    document.addEventListener("DOMContentLoaded", function() {
        const selectType = document.getElementById('type_activite');
        if (selectType) selectType.addEventListener('change', toggleFormFields);
        toggleFormFields();
    });
</script>
