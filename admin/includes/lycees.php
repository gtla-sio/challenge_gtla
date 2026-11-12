<?php
$lycees = $pdo->query("SELECT * FROM lycees ORDER BY nom_lycee ASC")->fetchAll();
$edit_lycee = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM lycees WHERE id_lycee = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_lycee = $stmt->fetch();
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
             <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_lycee ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-building me-2"></i> Ajouter' ?> un Lycée
            </div>
            <div class="card-body">
                <form action="actions.php?action=save_lycee" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_lycee" value="<?= $edit_lycee['id_lycee'] ?? '' ?>">
                    <div class="mb-2"><label class="small fw-bold text-muted">Nom du Lycée</label><input type="text" name="nom_lycee" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_lycee['nom_lycee'] ?? '') ?>" required></div>
                    <div class="mb-3"><label class="small fw-bold text-muted">Ville</label><input type="text" name="ville" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_lycee['ville'] ?? '') ?>" required></div>
                    <button type="submit" class="btn btn-indigo btn-sm w-100 fw-bold">Enregistrer</button>
                    <?php if($edit_lycee): ?><a href="?section=lycees" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Liste des Établissements</div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-sm small text-center">
                    <thead class="table-light"><tr><th>ID</th><th>Nom du Lycée</th><th>Ville</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($lycees as $l): ?>
                        <tr>
                            <td><?= $l['id_lycee'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($l['nom_lycee']) ?></td>
                            <td><?= htmlspecialchars($l['ville']) ?></td>
                            <td>
                                <a href="?section=lycees&edit=<?= $l['id_lycee'] ?>" class="text-primary me-2"><i class="bi bi-pencil-fill"></i></a>
                                <!-- 🔒 CORRECTION : suppression en POST + CSRF (voir admin/actions.php) -->
                                <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce lycée ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="delete_lycee">
                                    <input type="hidden" name="id" value="<?= $l['id_lycee'] ?>">
                                    <button type="submit" class="btn btn-link text-danger p-0 border-0 align-baseline"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
