<?php
// 🗑️ Zones retirées (voir migration_suppression_zones_dept_adresses.sql)
$supports = $pdo->query("SELECT * FROM zl_types_supports ORDER BY intitule ASC")->fetchAll();
$equipes = $pdo->query("SELECT id_equipe, nom_equipe FROM equipes ORDER BY nom_equipe ASC")->fetchAll();

$limit = 10;
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $limit;

$total_ots = $pdo->query("SELECT COUNT(*) FROM zl_ordres_transport")->fetchColumn();
$total_pages = ceil($total_ots / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Départements déduits des codes postaux (même règle que pour la pénalité
// "rechargement hors zone" — voir sqlDepartementDepuisCp() dans functions.php)
$dept_exp_sql  = sqlDepartementDepuisCp('o.cp_adresse_expediteur');
$dept_dest_sql = sqlDepartementDepuisCp('o.cp_adresse_destinataire');

$stmt = $pdo->prepare("
    SELECT o.*, e.nom_equipe, t.intitule,
           $dept_exp_sql AS dept_expediteur, $dept_dest_sql AS dept_destinataire
    FROM zl_ordres_transport o
    LEFT JOIN zl_types_supports t ON o.id_type_support = t.id_type_support
    LEFT JOIN equipes e ON o.id_equipe = e.id_equipe
    ORDER BY o.id_ot ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ots = $stmt->fetchAll();

$edit_ot = null;
if (isset($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM zl_ordres_transport WHERE id_ot = ?");
    $stmtEdit->execute([intval($_GET['edit'])]);
    $edit_ot = $stmtEdit->fetch();
}
?>

<div class="row g-4">
    <div class="col-md-3">
       <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-admin-header text-white fw-bold">
                <i class="bi bi-file-earmark-excel me-2"></i> Importer des OT (Excel)
            </div>
            <div class="card-body p-2">
                <!-- Modèle Excel (en-têtes uniquement) pour faciliter l'import -->
                <a href="../import/modele_import_ot.xlsx" download class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i> Télécharger le modèle (.xlsx)
                </a>
                <form action="actions.php?action=import_ot" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="row g-1">
                        <div class="col-12 mb-2">
                            <input class="form-control form-control-sm" type="file" id="fichier_xlsx" name="fichier_xlsx" accept=".xlsx" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="importer_ot_xlsx" class="btn btn-sky btn-sm w-100 fw-bold">
                                <i class="bi bi-upload me-1"></i> Importer / Mettre à jour (Excel)
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_ot ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-scooter me-2"></i> Ajouter' ?> un OT
            </div>
            <div class="card-body p-2">
                <form action="actions.php?action=save_ot" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_ot" value="<?= $edit_ot['id_ot'] ?? '' ?>">
                    <div class="row g-1">
                        <div class="col-6 mb-1">
                            <label class="small fw-bold text-muted">N° OT</label>
                            <input type="text" name="numero_ot" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['numero_ot'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-1"><label class="small fw-bold text-muted">Expéditeur</label><input type="text" name="expediteur" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['expediteur'] ?? '') ?>" required></div>

                    <!-- 🆕 Adresse expéditeur éclatée en 3 champs (rue / CP / ville) -->
                    <div class="mb-1"><label class="small fw-bold text-muted">Rue (expéditeur)</label><input type="text" name="rue_adresse_expediteur" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['rue_adresse_expediteur'] ?? '') ?>"></div>
                    <div class="row g-1 mb-1">
                        <div class="col-5">
                            <label class="small fw-bold text-muted">CP (expéditeur)</label>
                            <input type="text" name="cp_adresse_expediteur" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['cp_adresse_expediteur'] ?? '') ?>">
                        </div>
                        <div class="col-7">
                            <label class="small fw-bold text-muted">Ville (expéditeur)</label>
                            <input type="text" name="ville_adresse_expediteur" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['ville_adresse_expediteur'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-1 mt-2"><label class="small fw-bold text-muted">Destinataire</label><input type="text" name="destinataire" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['destinataire'] ?? '') ?>" required></div>

                    <!-- 🆕 Adresse destinataire éclatée en 3 champs (rue / CP / ville) -->
                    <div class="mb-1"><label class="small fw-bold text-muted">Rue (destinataire)</label><input type="text" name="rue_adresse_destinataire" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['rue_adresse_destinataire'] ?? '') ?>"></div>
                    <div class="row g-1 mb-1">
                        <div class="col-5">
                            <label class="small fw-bold text-muted">CP (destinataire)</label>
                            <input type="text" name="cp_adresse_destinataire" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['cp_adresse_destinataire'] ?? '') ?>">
                        </div>
                        <div class="col-7">
                            <label class="small fw-bold text-muted">Ville (destinataire)</label>
                            <input type="text" name="ville_adresse_destinataire" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['ville_adresse_destinataire'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-1 mt-2">
                        <div class="col-5 mb-1">
                            <label class="small fw-bold text-muted">Nb Supp.</label>
                            <input type="number" name="nb_supports" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['nb_supports'] ?? '') ?>" required>
                        </div>
                        <div class="col-7 mb-1">
                            <label class="small fw-bold text-muted">Format Support</label>
                            <select name="id_type_support" class="form-select form-select-sm" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach($supports as $s): ?>
                                    <option value="<?= $s['id_type_support'] ?>" <?= isset($edit_ot) && $edit_ot['id_type_support'] == $s['id_type_support'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['intitule']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-1 mb-3">
                        <div class="col-6"><label class="small fw-bold text-muted">PBT (t)</label><input type="number" step="0.01" name="pbt" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['pbt'] ?? '') ?>" required></div>
                        <div class="col-6"><label class="small fw-bold text-muted">CA (€)</label><input type="number" step="0.01" name="ca" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_ot['ca'] ?? '') ?>" required></div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary">Équipe assignée</label>
                        <select name="id_equipe" class="form-select" required>
                            <option value="">-- Choisir une équipe --</option>
                            <?php foreach ($equipes as $e): ?>
                                <option value="<?= $e['id_equipe'] ?>" <?= (isset($edit_ot) && $edit_ot['id_equipe'] == $e['id_equipe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nom_equipe']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-indigo btn-sm w-100 fw-bold">Enregistrer l'OT</button>
                    <?php if($edit_ot): ?><a href="?section=ot-zl" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>Plan de Transport Actif ZL</span>
                <span class="badge bg-secondary">Total : <?= $total_ots ?> OT</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-sm text-center table-bordered" style="font-size: 0.82rem;">
                    <thead class="table-warning border-dark text-dark fw-bold">
                        <tr>
                            <th>N° OT</th>
                            <th style="width: 26%;">Expéditeur / Adresse</th>
                            <th style="width: 26%;">Destinataire / Adresse</th>
                            <th>Nb Supports</th>
                            <th>Type</th>
                            <th>PBT</th>
                            <!-- nowrap : le montant (jusqu'à 9 999,99 €) ne doit jamais passer à la ligne -->
                            <th class="text-nowrap">CA (€)</th>
                            <th class="text-nowrap">Équipe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ots)): ?>
                            <tr>
                                <td colspan="9" class="text-muted py-3">Aucun ordre de transport enregistré.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($ots as $o): ?>
                            <?php
                                // Recomposition d'affichage "rue / CP ville (dept)" à partir des champs
                                $adresse_exp_affichee = trim(($o['cp_adresse_expediteur'] ?? '') . ' ' . ($o['ville_adresse_expediteur'] ?? ''));
                                $adresse_dest_affichee = trim(($o['cp_adresse_destinataire'] ?? '') . ' ' . ($o['ville_adresse_destinataire'] ?? ''));
                            ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($o['numero_ot']) ?></td>
                                <td class="text-start">
                                    <strong><?= htmlspecialchars($o['expediteur']) ?></strong><br>
                                    <span class="text-muted small">
                                        <?= htmlspecialchars($o['rue_adresse_expediteur'] ?? '') ?>
                                        <?php if ($adresse_exp_affichee !== ''): ?><br><?= htmlspecialchars($adresse_exp_affichee) ?><?php endif; ?>
                                        <?php if (!empty($o['dept_expediteur'])): ?> <span class="badge bg-light text-dark border">Dpt <?= htmlspecialchars($o['dept_expediteur']) ?></span><?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-start">
                                    <strong><?= htmlspecialchars($o['destinataire']) ?></strong><br>
                                    <span class="text-muted small">
                                        <?= htmlspecialchars($o['rue_adresse_destinataire'] ?? '') ?>
                                        <?php if ($adresse_dest_affichee !== ''): ?><br><?= htmlspecialchars($adresse_dest_affichee) ?><?php endif; ?>
                                        <?php if (!empty($o['dept_destinataire'])): ?> <span class="badge bg-light text-dark border">Dpt <?= htmlspecialchars($o['dept_destinataire']) ?></span><?php endif; ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($o['nb_supports']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($o['intitule'] ?? 'Non spécifié') ?></span></td>
                                <td><?= htmlspecialchars($o['pbt']) ?></td>
                                <td class="table-light fw-bold text-end text-nowrap"><?= number_format($o['ca'], 2, ',', ' ') ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($o['nom_equipe'] ?? '-') ?></td>
                                <td>
                                    <a href="?section=ot-zl&p=<?= $page ?>&edit=<?= $o['id_ot'] ?>" class="text-primary me-1"><i class="bi bi-pencil-fill"></i></a>
                                    <!-- 🔒 CORRECTION : suppression en POST + CSRF (voir admin/actions.php) -->
                                    <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet Ordre ?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="action" value="delete_ot">
                                        <input type="hidden" name="id" value="<?= $o['id_ot'] ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0 border-0 align-baseline"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">
                        Page <strong><?= $page ?></strong> sur <strong><?= $total_pages ?></strong>
                    </span>

                    <nav aria-label="Navigation de la pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?section=ot-zl&p=1" title="Première page">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?section=ot-zl&p=<?= $page - 1 ?>" title="Page précédente">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <li class="page-item active disabled">
                                <span class="page-link bg-secondary border-secondary text-white fw-bold">
                                    <?= $page ?>
                                </span>
                            </li>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?section=ot-zl&p=<?= $page + 1 ?>" title="Page suivante">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?section=ot-zl&p=<?= $total_pages ?>" title="Dernière page">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
