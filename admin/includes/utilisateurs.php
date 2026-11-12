<?php
$users = $pdo->query("SELECT u.*, l.nom_lycee FROM utilisateurs u LEFT JOIN lycees l ON u.id_lycee = l.id_lycee ORDER BY u.id_utilisateur ASC")->fetchAll();
$lycees = $pdo->query("SELECT id_lycee, nom_lycee FROM lycees ORDER BY nom_lycee ASC")->fetchAll();

$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_user = $stmt->fetch();
}
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-admin-header text-white fw-bold">
                <?= $edit_user ? '<i class="bi bi-pencil-square me-2"></i> Modifier' : '<i class="bi bi-person-plus me-2"></i> Ajouter' ?> un Utilisateur
            </div>
            <div class="card-body">
                <form action="actions.php?action=save_utilisateur" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id_utilisateur" value="<?= $edit_user['id_utilisateur'] ?? '' ?>">

                    <div class="mb-2"><label class="small fw-bold text-muted">Identifiant (Login)</label><input type="text" name="identifiant" class="form-control form-control-sm" value="<?= htmlspecialchars($edit_user['identifiant'] ?? '') ?>" required></div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Mot de passe</label>
                        <!-- 🔒 Voir correction n°12 : plus de mot de passe fixe si laissé vide -->
                        <input type="password" name="mot_de_passe" class="form-control form-control-sm" placeholder="<?= $edit_user ? 'Laisser vide pour ne pas changer' : 'Laisser vide pour générer un mot de passe' ?>">
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold text-muted">Rôle</label>
                        <select name="role" class="form-select form-select-sm">
                            <option value="professeur" <?= isset($edit_user) && $edit_user['role'] === 'professeur' ? 'selected' : '' ?>>Professeur</option>
                            <option value="administrateur" <?= isset($edit_user) && $edit_user['role'] === 'administrateur' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted">Rattachement Établissement (Optionnel)</label>
                        <select name="id_lycee" class="form-select form-select-sm">
                            <option value="">-- Aucun --</option>
                            <?php foreach($lycees as $l): ?>
                                <option value="<?= $l['id_lycee'] ?>" <?= isset($edit_user) && $edit_user['id_lycee'] == $l['id_lycee'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nom_lycee']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-indigo btn-sm w-100 fw-bold">Enregistrer</button>
                    <?php if($edit_user): ?><a href="?section=utilisateurs" class="btn btn-light btn-sm w-100 mt-1">Annuler</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Comptes Enseignants & Administrateurs</div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-sm small text-center">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Login</th><th>Rôle</th><th>Lycée</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td><?= $u['id_utilisateur'] ?></td>
                            <td><code><?= htmlspecialchars($u['identifiant']) ?></code></td>
                            <td><span class="badge <?= $u['role'] === 'administrateur' ? 'bg-danger' : 'bg-primary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['nom_lycee'] ?? '— Global —') ?></td>
                            <td>
                                <a href="?section=utilisateurs&edit=<?= $u['id_utilisateur'] ?>" class="text-primary me-2"><i class="bi bi-pencil-fill"></i></a>
                                <!--
                                    🔒 CORRECTION : suppression en POST + CSRF (voir admin/actions.php).
                                    admin/actions.php empêche désormais aussi qu'un admin
                                    supprime son propre compte par erreur.
                                -->
                                <form action="actions.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="delete_utilisateur">
                                    <input type="hidden" name="id" value="<?= $u['id_utilisateur'] ?>">
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
