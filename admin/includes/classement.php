<?php
// admin/includes/classement.php
//
// Classement pondéré (1/3 affrètement + 2/3 exploitation), pénalités, puis
// 🆕 coefficient de traitement (OT traités ÷ OT à traiter) appliqué au
// score calculé. Le détail du calcul est dans functions.php,
// calculerClassementEquipe(). Voir CLASSEMENT_README.md.

$equipes = $pdo->query('SELECT id_equipe, nom_equipe FROM equipes ORDER BY nom_equipe')->fetchAll();

$classement = [];
foreach ($equipes as $e) {
    $resultat = calculerClassementEquipe($pdo, (int) $e['id_equipe']);
    $resultat['nom_equipe'] = $e['nom_equipe'];
    $classement[] = $resultat;
}

// Tri par score final décroissant (meilleure équipe en premier)
usort($classement, fn($a, $b) => $b['note'] <=> $a['note']);
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-admin-header text-white fw-bold"><i class="bi bi-trophy me-2"></i>Classement Général Pondéré — Challenge ZL</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center small">
            <thead class="table-light">
                <tr>
                    <th>Rang</th>
                    <th>Équipe</th>
                    <th>Affrètement<br><span class="fw-normal text-muted">(1/3)</span></th>
                    <th>Exploitation<br><span class="fw-normal text-muted">(2/3)</span></th>
                    <th>Infractions<br>conduite/repos</th>
                    <th>Rechargements<br>hors zone</th>
                    <th>Pénalités</th>
                    <th>Score<br>calculé</th>
                    <th>OT traités</th>
                    <th>Coefficient</th>
                    <th>Score final<br>/1000</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classement as $index => $row): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= $index + 1 ?></span></td>
                    <td class="fw-bold text-start"><?= htmlspecialchars($row['nom_equipe']) ?></td>
                    <td>
                        <?= $row['affretement']['points'] ?> pts
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= $row['affretement']['taux'] !== null ? $row['affretement']['taux'] . '%' : '—' ?>
                            (<?= $row['affretement']['nb_ot'] ?> OT)
                        </div>
                    </td>
                    <td>
                        <?= $row['exploitation']['points'] ?> pts
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= $row['exploitation']['taux'] !== null ? $row['exploitation']['taux'] . '%' : '—' ?>
                            (<?= $row['exploitation']['nb_ot'] ?> OT)
                        </div>
                    </td>
                    <td class="<?= $row['nb_infractions'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $row['nb_infractions'] ?></td>
                    <td class="<?= $row['nb_rechargements_hors_zone'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $row['nb_rechargements_hors_zone'] ?></td>
                    <td class="text-danger">− <?= number_format($row['total_penalites'], 2, ',', ' ') ?></td>
                    <td><?= number_format($row['score_calcule'], 2, ',', ' ') ?></td>
                    <td class="<?= $row['nb_ot_traites'] < $row['nb_ot_a_traiter'] ? 'text-danger fw-bold' : '' ?>"><?= $row['nb_ot_traites'] ?> / <?= $row['nb_ot_a_traiter'] ?></td>
                    <td class="<?= $row['coefficient'] < 1 ? 'text-danger fw-bold' : '' ?>">× <?= number_format($row['coefficient'], 2, ',', ' ') ?></td>
                    <td class="fw-bold fs-6"><?= number_format($row['note'], 2, ',', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">
        Score final = (1/3 affrètement + 2/3 exploitation − pénalités, minimum 0) × coefficient.
        Coefficient = OT traités ÷ OT à traiter (OT clients de l'équipe + OT acceptés en affrètement). Un OT est traité quand il a été livré (une ligne « Déchargement » existe sur l'OT) ; seuls les OT livrés comptent dans les marges.
        Barème taux de marge → points (interpolation continue, sur 1000) : 0% → 0&nbsp;pt · 5% → 250&nbsp;pts · 15% → 500&nbsp;pts · 25% → 750&nbsp;pts · 40% et au-delà → 1000&nbsp;pts. Négatif ou aucune activité → 0&nbsp;pt.
        Malus réglables dans Paramètres ZL.
    </div>
</div>
