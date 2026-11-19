<?php
// includes/affretement.php
//
// 🆕 Page dédiée au WORKFLOW d'affrètement (séparée de la rentabilité,
// qui a sa propre page — includes/rentabilite.php). Ici : qui sous-traite
// quoi à qui, et où en sont les décisions. Les calculs de marge/coût/taux
// sont sur la page Rentabilité, pour ne pas mélanger "statut d'une
// décision" et "combien ça rapporte".
if (!isset($_SESSION['team_id'])) { exit; }

$id_equipe = (int) $_SESSION['team_id'];
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Liste des autres équipes, pour le formulaire "Affréter à..."
$stmtAutresEquipes = $pdo->prepare('SELECT id_equipe, nom_equipe FROM equipes WHERE id_equipe != ? ORDER BY nom_equipe');
$stmtAutresEquipes->execute([$id_equipe]);
$autres_equipes = $stmtAutresEquipes->fetchAll();

// -----------------------------------------------------------------------
// 1. MES OT CLIENTS (ceux dont je suis l'équipe d'origine)
// -----------------------------------------------------------------------
$stmtMesOts = $pdo->prepare(
    'SELECT id_ot, numero_ot, expediteur, destinataire, ca
     FROM zl_ordres_transport
     WHERE id_equipe = ?
     ORDER BY numero_ot'
);
$stmtMesOts->execute([$id_equipe]);
$mes_ots = $stmtMesOts->fetchAll();

foreach ($mes_ots as &$ot) {
    $ot['affretement'] = affretementActifPourOt($pdo, $ot['id_ot']);

    $stmtLignes = $pdo->prepare('SELECT 1 FROM zl_lignes_activite WHERE id_ot = ? LIMIT 1');
    $stmtLignes->execute([$ot['id_ot']]);
    $ot['deja_exploite'] = (bool) $stmtLignes->fetchColumn();

    // Dernier refus éventuel (pour affichage explicite), même logique que
    // précédemment : affretementActifPourOt() ignore les refus/annulations.
    $ot['refus'] = null;
    if (!$ot['affretement']) {
        $stmtDernier = $pdo->prepare(
            'SELECT a.statut, e.nom_equipe AS nom_affrete
             FROM zl_affretements a
             JOIN equipes e ON a.id_equipe_affrete = e.id_equipe
             WHERE a.id_ot = ?
             ORDER BY a.id_affretement DESC LIMIT 1'
        );
        $stmtDernier->execute([$ot['id_ot']]);
        $dernier = $stmtDernier->fetch();
        if ($dernier && $dernier['statut'] === 'refusee') {
            $ot['refus'] = $dernier;
        }
    }
}
unset($ot);

// -----------------------------------------------------------------------
// 2. PROPOSITIONS D'AFFRÈTEMENT REÇUES (en attente de ma réponse)
// -----------------------------------------------------------------------
$stmtRecues = $pdo->prepare(
    "SELECT a.id_affretement, a.prix_affretement, a.date_declaration,
            ot.numero_ot, ot.expediteur, ot.destinataire,
            e.nom_equipe AS nom_equipe_affreteur
     FROM zl_affretements a
     JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
     JOIN equipes e ON a.id_equipe_affreteur = e.id_equipe
     WHERE a.id_equipe_affrete = ? AND a.statut = 'en_attente'
     ORDER BY a.date_declaration ASC"
);
$stmtRecues->execute([$id_equipe]);
$propositions_recues = $stmtRecues->fetchAll();

// -----------------------------------------------------------------------
// 3. OT QUE J'EXÉCUTE POUR UNE AUTRE ÉQUIPE (affrètements acceptés)
// -----------------------------------------------------------------------
$stmtExecutesPourAutrui = $pdo->prepare(
    "SELECT a.prix_affretement, ot.numero_ot, ot.expediteur, ot.destinataire,
            e.nom_equipe AS nom_equipe_affreteur
     FROM zl_affretements a
     JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
     JOIN equipes e ON a.id_equipe_affreteur = e.id_equipe
     WHERE a.id_equipe_affrete = ? AND a.statut = 'acceptee'
     ORDER BY ot.numero_ot"
);
$stmtExecutesPourAutrui->execute([$id_equipe]);
$executes_pour_autrui = $stmtExecutesPourAutrui->fetchAll();
?>
<h2 class="fw-bold text-dark mb-4">Affrètement</h2>

<?php if (empty($mes_ots) && empty($propositions_recues) && empty($executes_pour_autrui)): ?>
    <div class="alert alert-info">Aucun ordre de transport ne vous est encore affecté.</div>
<?php endif; ?>

<?php if (!empty($propositions_recues)): ?>
<div class="card shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning fw-bold"><i class="bi bi-envelope-paper me-2"></i>Propositions d'affrètement reçues</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle text-center mb-0">
            <thead class="table-light">
                <tr><th>OT</th><th>Trajet</th><th>Équipe demandeuse</th><th>Prix proposé</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($propositions_recues as $p): ?>
                <tr>
                    <td class="fw-bold">OT-<?= htmlspecialchars($p['numero_ot']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($p['expediteur']) ?> → <?= htmlspecialchars($p['destinataire']) ?></td>
                    <td><?= htmlspecialchars($p['nom_equipe_affreteur']) ?></td>
                    <td class="fw-bold"><?= number_format($p['prix_affretement'], 2, ',', ' ') ?> €</td>
                    <td>
                        <form action="actions.php" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id_affretement" value="<?= $p['id_affretement'] ?>">
                            <input type="hidden" name="reponse" value="acceptee">
                            <button type="submit" name="repondre_affretement" class="btn btn-success btn-sm">Accepter</button>
                        </form>
                        <form action="actions.php" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id_affretement" value="<?= $p['id_affretement'] ?>">
                            <input type="hidden" name="reponse" value="refusee">
                            <button type="submit" name="repondre_affretement" class="btn btn-outline-danger btn-sm" onclick="return confirm('Refuser cette proposition ?');">Refuser</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-clipboard-data me-2"></i>Mes OT clients</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle text-center mb-0">
            <thead class="table-light">
                <tr><th>OT</th><th>Trajet</th><th>CA</th><th>Statut</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($mes_ots)): ?>
                    <tr><td colspan="5" class="text-muted py-3">Aucun OT client pour le moment.</td></tr>
                <?php endif; ?>
                <?php foreach ($mes_ots as $ot): ?>
                <tr>
                    <td class="fw-bold">OT-<?= htmlspecialchars($ot['numero_ot']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($ot['expediteur']) ?> → <?= htmlspecialchars($ot['destinataire']) ?></td>
                    <td><?= number_format($ot['ca'], 2, ',', ' ') ?> €</td>
                    <td>
                        <?php if ($ot['affretement'] && $ot['affretement']['statut'] === 'acceptee'): ?>
                            <span class="badge bg-primary">Affrété à <?= htmlspecialchars($ot['affretement']['nom_affrete']) ?></span>
                        <?php elseif ($ot['affretement'] && $ot['affretement']['statut'] === 'en_attente'): ?>
                            <span class="badge bg-warning text-dark">En attente de <?= htmlspecialchars($ot['affretement']['nom_affrete']) ?></span>
                        <?php elseif ($ot['deja_exploite']): ?>
                            <span class="badge bg-success">Exploité en propre</span>
                        <?php elseif ($ot['refus']): ?>
                            <span class="badge bg-danger">Refusé par <?= htmlspecialchars($ot['refus']['nom_affrete']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Non traité</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$ot['affretement'] && !$ot['deja_exploite']): ?>
                            <?php if ($ot['refus']): ?>
                                <div class="text-danger small mb-1">Refusé par <?= htmlspecialchars($ot['refus']['nom_affrete']) ?> — retentez avec une autre équipe</div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAffreter<?= $ot['id_ot'] ?>">
                                <?= $ot['refus'] ? 'Re-proposer' : 'Affréter' ?>
                            </button>

                            <div class="modal fade" id="modalAffreter<?= $ot['id_ot'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="actions.php" method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Affréter OT-<?= htmlspecialchars($ot['numero_ot']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="hidden" name="id_ot" value="<?= $ot['id_ot'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Sous-traiter à l'équipe :</label>
                                                    <select name="id_equipe_cible" class="form-select" required>
                                                        <option value="">-- Choisir --</option>
                                                        <?php foreach ($autres_equipes as $ae): ?>
                                                            <option value="<?= $ae['id_equipe'] ?>"><?= htmlspecialchars($ae['nom_equipe']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Prix négocié (€)</label>
                                                    <input type="number" step="0.01" min="0.01" name="prix_affretement" class="form-control" required>
                                                    <div class="form-text">Le CA client (<?= number_format($ot['ca'], 2, ',', ' ') ?> €) reste le vôtre ; ce prix est ce que vous payez à l'équipe qui exécute. Simulez d'abord sur la page "Rentabilité" si besoin.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="declarer_affretement" class="btn btn-primary">Envoyer la proposition</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($ot['affretement'] && $ot['affretement']['statut'] === 'en_attente'): ?>
                            <form action="actions.php" method="POST" class="d-inline"
                                  onsubmit="return confirm('Annuler cette proposition d\'affrètement ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="id_affretement" value="<?= $ot['affretement']['id_affretement'] ?>">
                                <button type="submit" name="annuler_affretement" class="btn btn-sm btn-outline-danger">Annuler</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($executes_pour_autrui)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-truck me-2"></i>OT exécutés pour le compte d'une autre équipe</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle text-center mb-0">
            <thead class="table-light">
                <tr><th>OT</th><th>Trajet</th><th>Équipe cliente</th><th>Prix reçu</th></tr>
            </thead>
            <tbody>
                <?php foreach ($executes_pour_autrui as $exec): ?>
                <tr>
                    <td class="fw-bold">OT-<?= htmlspecialchars($exec['numero_ot']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($exec['expediteur']) ?> → <?= htmlspecialchars($exec['destinataire']) ?></td>
                    <td><?= htmlspecialchars($exec['nom_equipe_affreteur']) ?></td>
                    <td class="fw-bold"><?= number_format($exec['prix_affretement'], 2, ',', ' ') ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">Le détail de la rentabilité (km, coût, marge) est sur la page "Rentabilité".</div>
</div>
<?php endif; ?>
