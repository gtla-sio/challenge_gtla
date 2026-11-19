<?php
// admin/includes/departements.php
//
// 🆕 Gestion de la table des départements limitrophes, utilisée par les
// futures pénalités géographiques (rechargement / km à vide hors zone —
// partie C du document de spécification, pas encore câblée).
//
// La liste a été pré-remplie automatiquement (voir
// bdd/migration_departements_limitrophes.sql) à partir de la géographie
// administrative réelle. Cette page permet de corriger une relation en
// quelques clics si une erreur est repérée, sans toucher au SQL.

$tous_departements = $pdo->query("SELECT code_dept, nom_dept FROM ref_departements ORDER BY code_dept")->fetchAll();

$dept_selectionne = $_GET['dept'] ?? ($tous_departements[0]['code_dept'] ?? null);

$voisins = [];
if ($dept_selectionne) {
    $stmt = $pdo->prepare(
        "SELECT r.code_dept, r.nom_dept
         FROM zl_departements_limitrophes l
         JOIN ref_departements r ON r.code_dept = l.code_dept_limitrophe
         WHERE l.code_dept = ?
         ORDER BY r.code_dept"
    );
    $stmt->execute([$dept_selectionne]);
    $voisins = $stmt->fetchAll();
}

$nom_dept_selectionne = '';
foreach ($tous_departements as $d) {
    if ($d['code_dept'] === $dept_selectionne) { $nom_dept_selectionne = $d['nom_dept']; break; }
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold"><i class="bi bi-geo me-2"></i>Choisir un département</div>
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="section" value="departements">
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($tous_departements as $d): ?>
                            <option value="<?= htmlspecialchars($d['code_dept']) ?>" <?= $d['code_dept'] === $dept_selectionne ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['code_dept']) ?> — <?= htmlspecialchars($d['nom_dept']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if ($dept_selectionne): ?>
                <hr>
                <form action="actions.php?action=add_limitrophe" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="code_dept" value="<?= htmlspecialchars($dept_selectionne) ?>">
                    <label class="form-label small fw-bold text-muted">Ajouter un département limitrophe à <?= htmlspecialchars($dept_selectionne) ?></label>
                    <div class="input-group">
                        <select name="code_dept_limitrophe" class="form-select form-select-sm" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($tous_departements as $d):
                                if ($d['code_dept'] === $dept_selectionne) continue; ?>
                                <option value="<?= htmlspecialchars($d['code_dept']) ?>"><?= htmlspecialchars($d['code_dept']) ?> — <?= htmlspecialchars($d['nom_dept']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-indigo">Ajouter</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                Départements limitrophes de <?= htmlspecialchars($dept_selectionne) ?> — <?= htmlspecialchars($nom_dept_selectionne) ?>
            </div>
            <div class="card-body">
                <?php if (empty($voisins)): ?>
                    <p class="text-muted small mb-0">Aucun département limitrophe enregistré.</p>
                <?php else: ?>
                    <?php foreach ($voisins as $v): ?>
                        <span class="badge bg-light text-dark border d-inline-flex align-items-center mb-2 me-2 p-2">
                            <?= htmlspecialchars($v['code_dept']) ?> — <?= htmlspecialchars($v['nom_dept']) ?>
                            <form action="actions.php?action=delete_limitrophe" method="POST" class="d-inline ms-2" onsubmit="return confirm('Supprimer cette relation de voisinage ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="code_dept" value="<?= htmlspecialchars($dept_selectionne) ?>">
                                <input type="hidden" name="code_dept_limitrophe" value="<?= htmlspecialchars($v['code_dept']) ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Retirer"><i class="bi bi-x-circle-fill"></i></button>
                            </form>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="form-text mt-2">Une relation est toujours symétrique : la retirer ou l'ajouter ici la retire/l'ajoute aussi dans l'autre sens.</div>
            </div>
        </div>
    </div>
</div>
