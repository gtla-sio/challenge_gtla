<?php
// 1. Suivi des OT par équipe (à traiter, en cours, livrés)
$stmtEquipes = $pdo->query("
    SELECT
        e.id_equipe,
        e.nom_equipe,
        SUM(CASE WHEN ot.id_ot IS NOT NULL AND ot.nb_activites = 0 THEN 1 ELSE 0 END) AS a_traiter,
        SUM(CASE WHEN ot.id_ot IS NOT NULL AND ot.nb_activites > 0 AND ot.est_livre = 0 THEN 1 ELSE 0 END) AS en_cours,
        SUM(CASE WHEN ot.id_ot IS NOT NULL AND ot.est_livre = 1 THEN 1 ELSE 0 END) AS livres,
        COUNT(ot.id_ot) AS total_ot
    FROM equipes e
    LEFT JOIN (
        SELECT
            o.id_ot,
            o.id_equipe,
            COUNT(la.id_ligne) AS nb_activites,
            COALESCE(MAX(CASE WHEN la.type_activite = 'Déchargement' THEN 1 ELSE 0 END), 0) AS est_livre
        FROM zl_ordres_transport o
        LEFT JOIN zl_lignes_activite la ON la.id_ot = o.id_ot
        GROUP BY o.id_ot, o.id_equipe
    ) ot ON ot.id_equipe = e.id_equipe
    GROUP BY e.id_equipe, e.nom_equipe
    ORDER BY e.nom_equipe ASC
");
$statsEquipes = $stmtEquipes->fetchAll(PDO::FETCH_ASSOC);

// 2. Propositions d'affrètement en attente de réponse
$stmtAffretements = $pdo->query("
    SELECT COUNT(*)
    FROM zl_affretements
    WHERE statut = 'en_attente'
");
$nbAffretementsAttente = (int) $stmtAffretements->fetchColumn();

// 3. Activités saisies par jour du challenge
$stmtActivitesJour = $pdo->query("
    SELECT
        date_activite,
        COUNT(*) AS nb_activites
    FROM zl_lignes_activite
    GROUP BY date_activite
    ORDER BY date_activite ASC
");
$activitesParJour = $stmtActivitesJour->fetchAll(PDO::FETCH_ASSOC);

// Conversion date -> jour en français pour l'affichage
$joursFr = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche',
];
?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-secondary">Statut de Session</h5>
            <p class="mb-0">Connecté en tant que <strong><?= htmlspecialchars($_SESSION['admin_identifiant']) ?></strong></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-secondary">Affrètements en attente</h5>
            <p class="display-6 fw-bold text-warning mb-1"><?= $nbAffretementsAttente ?></p>
            <p class="text-muted small mb-0">Proposition(s) en attente de réponse des équipes</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Tableau de suivi des OT par équipe -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-secondary mb-3">Avancement des OT par équipe</h5>
            <?php if (empty($statsEquipes)): ?>
                <p class="text-muted mb-0">Aucune équipe enregistrée.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Équipe</th>
                                <th class="text-center">À traiter</th>
                                <th class="text-center">En cours</th>
                                <th class="text-center">Livrés</th>
                                <th class="text-center">Total OT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsEquipes as $eq): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($eq['nom_equipe']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= (int) $eq['a_traiter'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= (int) $eq['en_cours'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= (int) $eq['livres'] ?></span>
                                    </td>
                                    <td class="text-center fw-bold"><?= (int) $eq['total_ot'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activités saisies par jour du challenge -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-secondary mb-3">Activités par jour</h5>
            <?php if (empty($activitesParJour)): ?>
                <p class="text-muted mb-0">Aucune activité saisie pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jour</th>
                                <th class="text-end">Activités</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activitesParJour as $jour): ?>
                                <?php
                                    $ts = strtotime($jour['date_activite']);
                                    $nomJour = $joursFr[(int) date('N', $ts)] ?? '';
                                    $dateFormatee = date('d/m/Y', $ts);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($nomJour . ' (' . $dateFormatee . ')') ?></td>
                                    <td class="text-end fw-bold"><?= (int) $jour['nb_activites'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>