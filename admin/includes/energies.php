<?php
// admin/includes/energies.php
//
// 🆕 Remplace l'ancien panneau "Coût au km par énergie" intégré à la page
// Véhicules : CRUD complet (créer / modifier / supprimer une énergie),
// sur le même modèle que les autres référentiels (zones.php, lycees.php).

$energies = $pdo->query("SELECT * FROM ref_energies ORDER BY libelle ASC")->fetchAll();

$edit_energie = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ref_energies WHERE id_energie = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_energie = $stmt->fetch();
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_energie ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-lightning-charge me-2"></i> Ajouter' ?> une Énergie
            </div>
            <div class="card-body">
                <form action="actions.php?action=save_energie" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_energie" value="<?= $edit_energie['id_energie'] ?? '' ?>">

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Libellé (ex: Diesel, B100, Électrique)</label>
                        <input type="text" name="libelle" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_energie['libelle'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Coût au km (€/km)</label>
                        <input type="number" step="0.001" min="0" name="cout_km" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_energie['cout_km'] ?? '') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-indigo btn-sm w-100 fw-bold">Enregistrer</button>
                    <?php if($edit_energie): ?><a href="?section=energies" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Énergies enregistrées</div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-sm small text-center">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Libellé</th><th>Coût au km</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($energies)): ?>
                            <tr><td colspan="4" class="text-muted py-3">Aucune énergie enregistrée.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($energies as $en): ?>
                        <tr>
                            <td><?= $en['id_energie'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($en['libelle']) ?></td>
                            <td><?= number_format($en['cout_km'], 3, ',', ' ') ?> €/km</td>
                            <td>
                                <a href="?section=energies&edit=<?= $en['id_energie'] ?>" class="text-primary me-2"><i class="bi bi-pencil-fill"></i></a>
                                <!-- 🔒 Suppression en POST + CSRF, bloquée si des véhicules utilisent cette énergie (voir admin/actions.php) -->
                                <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette énergie ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="delete_energie">
                                    <input type="hidden" name="id" value="<?= $en['id_energie'] ?>">
                                    <button type="submit" class="btn btn-link text-danger p-0 border-0 align-baseline"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">L'énergie est obligatoire sur chaque véhicule : supprimer une énergie encore utilisée par un véhicule est bloqué.</div>
        </div>
    </div>
</div>
