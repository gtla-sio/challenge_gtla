<?php
// includes/login.php
// Pas de modification nécessaire ici : ce formulaire ne modifie aucune
// donnée sensible pour un utilisateur déjà connecté (on n'est justement
// PAS connecté quand on le voit), il n'a donc pas besoin de jeton CSRF.
?>
<div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0 mt-5">
                   <div class="card-header bg-primary text-white text-center py-3">
                        <span class="fs-1 text-info"><i class="bi bi-person-workspace"></i></span><h5 class="mb-0">Challenge GTLA — Espace Équipe</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Identifiant équipe (Login)</label>
                                <input type="text" name="login_equipe" class="form-control" placeholder="Ex: equipe1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Mot de passe</label>
                                <input type="password" name="mot_de_passe" class="form-control" placeholder="••••••••" required>
                            </div>
                            <button type="submit" name="login_team_submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
