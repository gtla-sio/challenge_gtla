<?php
$vehicules = $pdo->query("
    SELECT v.*, t.libelle as type_vehicule, c.libelle as type_carrosserie, re.libelle as libelle_energie, re.cout_km
    FROM zl_vehicules v
    JOIN ref_types_vehicule t ON v.id_type = t.id_type
    JOIN ref_carrosseries c ON v.id_carrosserie = c.id_carrosserie
    LEFT JOIN ref_energies re ON v.id_energie = re.id_energie
    ORDER BY v.libelle ASC
")->fetchAll();

$types = $pdo->query("SELECT * FROM ref_types_vehicule")->fetchAll();
$carrosseries = $pdo->query("SELECT * FROM ref_carrosseries")->fetchAll();
$energies = $pdo->query("SELECT * FROM ref_energies ORDER BY libelle")->fetchAll();

$edit_v = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM zl_vehicules WHERE id_vehicule = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_v = $stmt->fetch();
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_v ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-truck me-2 me-2"></i> Ajouter' ?> un Véhicule
            </div>
            <div class="card-body">
                <?php if (empty($energies)): ?>
                    <div class="alert alert-warning small">Aucune énergie n'existe encore. <a href="?section=energies">Créez-en une d'abord</a>.</div>
                <?php endif; ?>
                <form action="actions.php?action=save_vehicule" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_vehicule" value="<?= $edit_v['id_vehicule'] ?? '' ?>">

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Libellé (ex: EA1)</label>
                        <input type="text" name="libelle" class="form-control" value="<?= htmlspecialchars($edit_v['libelle'] ?? '') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Type</label>
                        <select name="id_type" class="form-select" required>
                            <?php foreach($types as $t): ?>
                                <option value="<?= $t['id_type'] ?>" <?= ($edit_v['id_type'] ?? 0) == $t['id_type'] ? 'selected' : '' ?>><?= htmlspecialchars($t['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Carrosserie</label>
                        <select name="id_carrosserie" class="form-select" required>
                            <?php foreach($carrosseries as $c): ?>
                                <option value="<?= $c['id_carrosserie'] ?>" <?= ($edit_v['id_carrosserie'] ?? 0) == $c['id_carrosserie'] ? 'selected' : '' ?>><?= htmlspecialchars($c['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 🆕 Énergie obligatoire — détermine le coût au km du véhicule -->
                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Énergie <span class="text-danger">*</span></label>
                        <select name="id_energie" class="form-select" required>
                            <option value="" disabled <?= empty($edit_v['id_energie']) ? 'selected' : '' ?>>-- Choisir --</option>
                            <?php foreach($energies as $en): ?>
                                <option value="<?= $en['id_energie'] ?>" <?= ($edit_v['id_energie'] ?? 0) == $en['id_energie'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($en['libelle']) ?> (<?= number_format($en['cout_km'], 3, ',', ' ') ?> €/km)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-indigo w-100 fw-bold" <?= empty($energies) ? 'disabled' : '' ?>>Enregistrer</button>
                    <?php if($edit_v): ?><a href="?section=vehicules" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <table class="table table-striped align-middle">
            <thead class="table-light">
                <tr><th>Libellé</th><th>Type</th><th>Carrosserie</th><th>Énergie</th><th>Coût/km</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($vehicules as $v): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($v['libelle']) ?></td>
                    <td><?= htmlspecialchars($v['type_vehicule']) ?></td>
                    <td><?= htmlspecialchars($v['type_carrosserie']) ?></td>
                    <td>
                        <?php if ($v['libelle_energie']): ?>
                            <span class="badge bg-info bg-opacity-25 text-dark border"><?= htmlspecialchars($v['libelle_energie']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger bg-opacity-25 text-danger border">Non renseignée</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $v['cout_km'] !== null ? number_format($v['cout_km'], 3, ',', ' ') . ' €/km' : '—' ?></td>
                    <td>
                        <a href="?section=vehicules&edit=<?= $v['id_vehicule'] ?>" class="text-primary"><i class="bi bi-pencil-fill"></i></a>
                        <!-- 🔒 CORRECTION : suppression en POST + CSRF (voir admin/actions.php) -->
                        <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="action" value="delete_vehicule">
                            <input type="hidden" name="id" value="<?= $v['id_vehicule'] ?>">
                            <button type="submit" class="btn btn-link text-danger ms-2 p-0 border-0 align-baseline"><i class="bi bi-trash-fill"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
