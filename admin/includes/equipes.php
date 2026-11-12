<?php
// 1. Récupération des équipes avec une jointure pour obtenir le nom du lycée rattaché
// 🗑️ Zones retirées (voir migration_suppression_zones_dept_adresses.sql)
$equipes = $pdo->query("
    SELECT e.*, l.nom_lycee, l.ville
    FROM equipes e
    LEFT JOIN lycees l ON e.id_lycee = l.id_lycee
    ORDER BY e.nom_equipe ASC
")->fetchAll();

$lycees = $pdo->query("SELECT * FROM lycees ORDER BY nom_lycee ASC")->fetchAll();

$edit_eq = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id_equipe = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_eq = $stmt->fetch();
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_eq ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-person-badge me-2"></i> Ajouter' ?> une Équipe
            </div>
            <div class="card-body">
                <form action="actions.php?action=save_equipe" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_equipe" value="<?= $edit_eq['id_equipe'] ?? '' ?>">

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Nom de l'Équipe</label>
                        <input type="text" name="nom_equipe" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_eq['nom_equipe'] ?? '') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Identifiant de session</label>
                        <input type="text" name="login_equipe" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_eq['login_equipe'] ?? '') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Lycée Rattaché</label>
                        <select name="id_lycee" class="form-select form-select-sm" required>
                            <option value="">-- Choisir un établissement --</option>
                            <?php foreach($lycees as $l): ?>
                                <option value="<?= $l['id_lycee'] ?>" <?= isset($edit_eq) && $edit_eq['id_lycee'] == $l['id_lycee'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['nom_lycee']) ?> (<?= htmlspecialchars($l['ville'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Mot de passe</label>
                        <!--
                            🔒 Voir la correction n°12 (admin/actions.php) : si ce
                            champ est laissé vide À LA CRÉATION, un mot de passe
                            aléatoire est désormais généré côté serveur et affiché
                            une seule fois, au lieu d'un mot de passe fixe codé en
                            dur dans le code source.
                        -->
                        <input type="password" name="mot_de_passe" class="form-control form-control-sm" placeholder="<?= $edit_eq ? 'Laisser vide pour inchangé' : 'Laisser vide pour générer un mot de passe' ?>">
                    </div>

                    <button type="submit" class="btn btn-indigo btn-sm w-100 fw-bold">Enregistrer</button>
                    <?php if($edit_eq): ?><a href="?section=equipes" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Groupes Élèves Enregistrés</div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-sm small text-center">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom Équipe</th>
                            <th>Lycée d'origine</th>
                            <th>Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($equipes as $e): ?>
                        <tr>
                            <td><?= $e['id_equipe'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($e['nom_equipe']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($e['nom_lycee'] ?? 'Non assigné') ?></strong>
                                <?php if (!empty($e['ville'])): ?>
                                    <br><span class="text-muted x-small">(<?= htmlspecialchars($e['ville']) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($e['login_equipe']) ?></code></td>
                            <td>
                                <a href="?section=equipes&edit=<?= $e['id_equipe'] ?>" class="text-primary me-2"><i class="bi bi-pencil-fill"></i></a>
                                <!--
                                    🔒 CORRECTION : suppression en formulaire POST +
                                    jeton CSRF, au lieu d'un lien <a> (GET) — voir
                                    l'explication détaillée en tête de
                                    admin/actions.php (correction n°11).
                                -->
                                <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette équipe ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="delete_equipe">
                                    <input type="hidden" name="id" value="<?= $e['id_equipe'] ?>">
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
