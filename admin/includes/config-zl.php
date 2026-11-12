<?php
// Sécurité : double vérification du rôle admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'administrateur') {
    echo '<div class="alert alert-danger">Accès interdit.</div>';
    exit;
}

// Récupération de la configuration active (id_config = 1)
$stmt = $pdo->prepare("SELECT * FROM zl_config_parametres WHERE id_config = 1");
$stmt->execute();
$config = $stmt->fetch();

// Si aucune ligne n'existe encore, on en crée une par défaut pour éviter les erreurs
if (!$config) {
    $pdo->query("INSERT INTO zl_config_parametres (id_config, vitesse_moyenne_kmh, temps_chargement_h, temps_dechargement_h, temps_pause_h, heure_prise_service_defaut, date_application, date_debut_challenge_zl, date_fin_challenge_zl, penalite_jour_non_roule) VALUES (1, 75, 1.00, 1.00, 0.75, 5.00, NOW(), CURDATE(), NULL, 0.00)");
    $config = $pdo->query("SELECT * FROM zl_config_parametres WHERE id_config = 1")->fetch();
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-1">Attention !</h5>
                <p class="mb-0">Les paramètres globaux ne doivent <strong>pas</strong> être modifiés lorsque le challenge est en cours.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-indigo text-white h-100" style="background-color: var(--admin-primary) !important;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-warning me-2"></i>Variables Globales ZL</h5>
                    <p class="small text-white-50">Ces paramètres sont indispensables au moteur de calcul de l'application.</p>
                    <p class="small text-white-50">Ils servent de valeurs de référence lors de la génération automatique ou de la validation des lignes d'activité des feuilles de route des élèves (OTEA).</p>
                </div>
                <div class="border-top border-white-20 pt-3 mt-3">
                    <span class="small text-white-50 d-block">Dernière modification :</span>
                    <span class="fw-bold font-monospace text-warning"><?= date('d/m/Y', strtotime($config['date_application'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold text-indigo py-3 border-bottom">
                <i class="bi bi-sliders me-2"></i>Ajustement des paramètres de calcul métier ZL
            </div>
            <div class="card-body p-4">
                <form action="actions.php?action=save_config" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="id_config" value="<?= $config['id_config'] ?>">

                    <!-- Ligne pour la date de début et la date de fin sur la même ligne avec des tailles identiques -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Date de début du Challenge ZL</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" name="date_debut_challenge_zl" class="form-control text-center" 
                                    value="<?= htmlspecialchars($config['date_debut_challenge_zl'] ?? date('Y-m-d')) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Date de fin du Challenge ZL</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                                <input type="date" name="date_fin_challenge_zl" class="form-control text-center" 
                                    value="<?= htmlspecialchars($config['date_fin_challenge_zl'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Vitesse moyenne (km/h)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-speedometer"></i></span>
                                <input type="number" name="vitesse_moyenne_kmh" class="form-control fw-bold text-center" value="<?= htmlspecialchars($config['vitesse_moyenne_kmh']) ?>" required min="1">
                                <span class="input-group-text bg-light">km/h</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Prise de service par défaut (ex: 5.50 = 05h30)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                                <input type="number" step="0.01" name="heure_prise_service_defaut" class="form-control fw-bold text-center" value="<?= htmlspecialchars($config['heure_prise_service_defaut']) ?>" required min="0" max="24">
                                <span class="input-group-text bg-light">h (décimal)</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Temps de Chargement (h décimal)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                <input type="number" step="0.01" name="temps_chargement_h" class="form-control text-center" value="<?= htmlspecialchars($config['temps_chargement_h']) ?>" required min="0">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Temps de Déchargement (h décimal)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-reply"></i></span>
                                <input type="number" step="0.01" name="temps_dechargement_h" class="form-control text-center" value="<?= htmlspecialchars($config['temps_dechargement_h']) ?>" required min="0">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Temps de Pause standard (h décimal)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-cup-hot"></i></span>
                                <input type="number" step="0.01" name="temps_pause_h" class="form-control text-center" value="<?= htmlspecialchars($config['temps_pause_h']) ?>" required min="0">
                            </div>
                        </div>
                    </div>
                    <!-- Ligne dédiée aux pénalités par jour non roulé -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Pénalité par jour non roulé</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-cash-stack"></i></span>
                                <input type="number" step="1.00" name="penalite_jour_non_roule" class="form-control fw-bold text-center" 
                                    value="<?= htmlspecialchars($config['penalite_jour_non_roule'] ?? '0.00') ?>" required min="0">
                                <span class="input-group-text bg-light">€</span>
                            </div>
                        </div>
                        <!-- 🆕  minimum : durée minimale entre la fin d'une journée et la prise de service du lendemain -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark"> minimum</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-moon-stars"></i></span>
                                <input type="number" step="0.25" name="repos_journalier_min_h" class="form-control fw-bold text-center" value="<?= htmlspecialchars($config['repos_journalier_min_h'] ?? '9.00') ?>" required min="0">
                                <span class="input-group-text bg-light">h</span>
                            </div>
                        </div>
                    </div>

                    <!-- 🆕 Barème des malus du classement pondéré (fixe = points, voir CLASSEMENT_README.md) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Malus / infraction conduite-repos (pts)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-exclamation-triangle"></i></span>
                                <input type="number" step="0.5" name="malus_infraction_conduite_repos" class="form-control text-center" value="<?= htmlspecialchars($config['malus_infraction_conduite_repos'] ?? '50.00') ?>" required min="0">
                            </div>
                        </div>
                        <!-- 🆕 Réintégré (comparaison dept. destinataire -> dept. expéditeur de l'OT suivant) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Malus / rechargement hors zone (pts)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="number" step="0.5" name="malus_rechargement_hors_zone" class="form-control text-center" value="<?= htmlspecialchars($config['malus_rechargement_hors_zone'] ?? '50.00') ?>" required min="0">
                            </div>
                        </div>
                    </div>

                    <!-- <input type="hidden" name="date_application" value="<?//= date('Y-m-d') ?>"> -->

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-danger btn-sm fw-bold px-4 shadow-sm" 
                                onclick="return confirm('⚠️ ATTENTION : Cette action est irréversible et ne doit jamais être effectuée en cours de challenge !\n\nÊtes-vous absolument sûr de vouloir continuer ?');">
                            <i class="bi bi-exclamation-triangle me-2"></i>Enregistrer les variables globales
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>