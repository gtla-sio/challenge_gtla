<?php
// includes/rentabilite.php
//
// 🆕 Page dédiée à LA RENTABILITÉ (séparée du workflow d'affrètement, qui
// a sa propre page — includes/affretement.php). Ici : combien ça
// rapporte réellement (tous les OT en cours d'exécution, coût calculé
// via l'énergie du véhicule utilisé), et un simulateur pour estimer
// avant de décider.
if (!isset($_SESSION['team_id'])) { exit; }

$id_equipe = (int) $_SESSION['team_id'];

// Liste des véhicules (flotte globale) avec leur coût au km, pour le
// sélecteur du simulateur (JS) ET pour l'affichage du tableau réel.
$stmtVehicules = $pdo->query(
    "SELECT v.id_vehicule, v.libelle, re.libelle AS libelle_energie, re.cout_km
     FROM zl_vehicules v
     JOIN ref_energies re ON v.id_energie = re.id_energie
     ORDER BY v.libelle"
);
$vehicules = $stmtVehicules->fetchAll();

// -----------------------------------------------------------------------
// TABLEAU RÉEL : tous les OT que l'équipe exécute (ses propres OT clients
// non sous-traités + les OT affrétés acceptés), avec coût de revient
// calculé via l'énergie du/des véhicule(s) réellement utilisé(s).
// -----------------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT ot.id_ot, ot.numero_ot, ot.expediteur, ot.destinataire, ot.ca AS ca_client,
            NULL AS prix_affretement, 'propre' AS origine
     FROM zl_ordres_transport ot
     WHERE ot.id_equipe = ?
       AND NOT EXISTS (SELECT 1 FROM zl_affretements a WHERE a.id_ot = ot.id_ot AND a.statut = 'acceptee')
       AND EXISTS (SELECT 1 FROM zl_lignes_activite l WHERE l.id_ot = ot.id_ot)
     UNION ALL
     SELECT ot.id_ot, ot.numero_ot, ot.expediteur, ot.destinataire, NULL AS ca_client,
            a.prix_affretement, 'affrete' AS origine
     FROM zl_affretements a
     JOIN zl_ordres_transport ot ON a.id_ot = ot.id_ot
     WHERE a.id_equipe_affrete = ? AND a.statut = 'acceptee'
     ORDER BY numero_ot"
);
$stmt->execute([$id_equipe, $id_equipe]);
$mes_exploitations = $stmt->fetchAll();

foreach ($mes_exploitations as &$ligne) {
    $ca = $ligne['origine'] === 'propre' ? (float) $ligne['ca_client'] : (float) $ligne['prix_affretement'];
    $km = kmParcourusPourOt($pdo, (int) $ligne['id_ot']);
    $cout = coutRevientPourOt($pdo, (int) $ligne['id_ot']);
    $marge = round($ca - $cout, 2);

    $ligne['ca'] = $ca;
    $ligne['km'] = $km;
    $ligne['cout'] = round($cout, 2);
    $ligne['marge'] = $marge;
    $ligne['taux'] = $ca > 0 ? round(($marge / $ca) * 100, 2) : 0;

    // Véhicule(s) utilisé(s) sur cet OT (généralement un seul)
    $stmtVeh = $pdo->prepare(
        "SELECT DISTINCT v.libelle FROM zl_lignes_activite l
         JOIN zl_feuilles_otea f ON l.id_feuille = f.id_feuille
         JOIN zl_vehicules v ON f.id_vehicule = v.id_vehicule
         WHERE l.id_ot = ? AND f.id_equipe = ?"
    );
    $stmtVeh->execute([$ligne['id_ot'], $id_equipe]);
    $ligne['vehicules'] = implode(', ', $stmtVeh->fetchAll(PDO::FETCH_COLUMN));
}
unset($ligne);
?>
<h2 class="fw-bold text-dark mb-4">Rentabilité</h2>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-graph-up-arrow me-2"></i>Rentabilité réelle (OT en cours d'exécution)</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle text-center mb-0">
            <thead class="table-light">
                <tr><th>OT</th><th>Origine</th><th>Véhicule</th><th>CA</th><th>Km</th><th>Coût de revient</th><th>Marge</th><th>Taux</th></tr>
            </thead>
            <tbody>
                <?php if (empty($mes_exploitations)): ?>
                    <tr><td colspan="8" class="text-muted py-3">Aucun OT en cours d'exécution pour le moment. Rendez-vous sur la page "Affrètement" pour en prendre un en charge.</td></tr>
                <?php endif; ?>
                <?php foreach ($mes_exploitations as $l): ?>
                <tr>
                    <td class="fw-bold">OT-<?= htmlspecialchars($l['numero_ot']) ?></td>
                    <td><span class="badge <?= $l['origine'] === 'propre' ? 'bg-success' : 'bg-primary' ?>"><?= $l['origine'] === 'propre' ? 'Client propre' : 'Affrété reçu' ?></span></td>
                    <td><?= $l['vehicules'] ? htmlspecialchars($l['vehicules']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= number_format($l['ca'], 2, ',', ' ') ?> €</td>
                    <td><?= number_format($l['km'], 0, ',', ' ') ?> km</td>
                    <td><?= number_format($l['cout'], 2, ',', ' ') ?> €</td>
                    <td class="fw-bold <?= $l['marge'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($l['marge'], 2, ',', ' ') ?> €</td>
                    <td><?= $l['taux'] ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted">Le coût de revient utilise le coût au km de l'énergie du véhicule utilisé. Le détail des propositions d'affrètement est sur la page "Affrètement".</div>
</div>

<!-- ========================================================================
     SIMULATEUR — calcul 100% côté navigateur (JavaScript), donc instantané.
     🆕 Le choix du véhicule remplit automatiquement son coût au km.
======================================================================== -->
<div class="card shadow-sm border-info">
    <div class="card-header bg-info bg-opacity-25 fw-bold"><i class="bi bi-calculator me-2"></i>Simulateur de rentabilité (aucune saisie n'est enregistrée)</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">CA client de l'OT (€)</label>
                <input type="number" step="0.01" id="sim_ca" class="form-control" value="0" oninput="simulerRentabilite()">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Véhicule</label>
                <select id="sim_vehicule" class="form-select" onchange="appliquerCoutVehicule()">
                    <option value="">-- Choisir un véhicule --</option>
                    <?php foreach ($vehicules as $v): ?>
                        <option value="<?= htmlspecialchars($v['cout_km']) ?>">
                            <?= htmlspecialchars($v['libelle']) ?> — <?= htmlspecialchars($v['libelle_energie']) ?> (<?= number_format($v['cout_km'], 3, ',', ' ') ?> €/km)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Coût au km (€/km)</label>
                <!-- 🆕 Non modifiable manuellement : déterminé uniquement par le véhicule choisi -->
                <input type="number" step="0.001" id="sim_cout_km" class="form-control bg-light" value="" placeholder="—" readonly>
                <div class="form-text">Déterminé automatiquement par le véhicule choisi.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Distance estimée (km)</label>
                <input type="number" step="1" id="sim_km" class="form-control" value="0" oninput="simulerRentabilite()">
            </div>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-md-6 border-end">
                <h6 class="text-muted">Si j'exploite moi-même</h6>
                <div class="fs-4 fw-bold" id="sim_marge_exploitation">0,00 €</div>
                <div class="small text-muted">Marge = CA − (km × coût/km)</div>
                <div class="fw-bold" id="sim_taux_exploitation">0 %</div>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Si j'affrète à ce prix</h6>
                <input type="number" step="0.01" id="sim_prix_affretement" class="form-control form-control-sm mx-auto mb-2" style="max-width: 160px;" value="0" oninput="simulerRentabilite()" placeholder="Prix affrètement">
                <div class="fs-4 fw-bold" id="sim_marge_affretement">0,00 €</div>
                <div class="small text-muted">Marge = CA − prix payé au sous-traitant</div>
                <div class="fw-bold" id="sim_taux_affretement">0 %</div>
            </div>
        </div>
    </div>
</div>

<script>
function appliquerCoutVehicule() {
    const select = document.getElementById('sim_vehicule');
    if (select.value !== '') {
        document.getElementById('sim_cout_km').value = parseFloat(select.value).toFixed(3);
    }
    simulerRentabilite();
}

function simulerRentabilite() {
    const ca = parseFloat(document.getElementById('sim_ca').value) || 0;
    const coutKm = parseFloat(document.getElementById('sim_cout_km').value) || 0;
    const km = parseFloat(document.getElementById('sim_km').value) || 0;
    const prixAffretement = parseFloat(document.getElementById('sim_prix_affretement').value) || 0;

    // Sans véhicule choisi, le coût au km est inconnu : pas de calcul d'exploitation
    const vehiculeChoisi = document.getElementById('sim_vehicule').value !== '';
    const coutExploitation = km * coutKm;
    const margeExploitation = ca - coutExploitation;
    const tauxExploitation = ca > 0 ? (margeExploitation / ca) * 100 : 0;

    const margeAffretement = ca - prixAffretement;
    const tauxAffretement = ca > 0 ? (margeAffretement / ca) * 100 : 0;

    const fmt = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

    if (vehiculeChoisi) {
        document.getElementById('sim_marge_exploitation').textContent = fmt(margeExploitation);
        document.getElementById('sim_marge_exploitation').className = 'fs-4 fw-bold ' + (margeExploitation >= 0 ? 'text-success' : 'text-danger');
        document.getElementById('sim_taux_exploitation').textContent = tauxExploitation.toFixed(1) + ' %';
    } else {
        document.getElementById('sim_marge_exploitation').textContent = '—';
        document.getElementById('sim_marge_exploitation').className = 'fs-4 fw-bold text-muted';
        document.getElementById('sim_taux_exploitation').textContent = 'Choisissez un véhicule';
    }

    document.getElementById('sim_marge_affretement').textContent = fmt(margeAffretement);
    document.getElementById('sim_marge_affretement').className = 'fs-4 fw-bold ' + (margeAffretement >= 0 ? 'text-success' : 'text-danger');
    document.getElementById('sim_taux_affretement').textContent = tauxAffretement.toFixed(1) + ' %';
}
document.addEventListener('DOMContentLoaded', simulerRentabilite);
</script>
