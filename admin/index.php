<?php
session_start();
require_once '../db.php';
require_once '../functions.php'; // 🔒 fonctions communes (CSRF, constantes...)

// 🔒 On réutilise désormais la même fonction genererCsrfToken() que côté
// équipe, au lieu d'avoir deux bouts de code qui font la même chose.
$csrf_token = genererCsrfToken();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ./");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin_submit'])) {
    $identifiant_saisi = trim($_POST['identifiant']);
    $mdp_saisi         = $_POST['mot_de_passe'];

    if (!empty($identifiant_saisi) && !empty($mdp_saisi)) {
        $stmt = $pdo->prepare('SELECT id_utilisateur, identifiant,mot_de_passe, role FROM utilisateurs WHERE identifiant = :identifiant');
        $stmt->bindValue(':identifiant', $identifiant_saisi, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($mdp_saisi, $user['mot_de_passe'])) {
            // 🔒 Idem espace équipe : on régénère l'id de session après
            // connexion réussie (protection anti session-fixation).
            session_regenerate_id(true);

            $_SESSION['admin_user_id'] = $user['id_utilisateur'];
            $_SESSION['admin_identifiant']     = $user['identifiant'];
            $_SESSION['admin_role']    = $user['role'];

            if ($_SESSION['admin_role'] === 'professeur') {
                header("Location: ./?section=classement");
            } else {
                header("Location: ./");
            }
            exit;
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

$is_connected = isset($_SESSION['admin_user_id']);
$is_admin = $is_connected && ($_SESSION['admin_role'] === 'administrateur');
$is_professeur = $is_connected && ($_SESSION['admin_role'] === 'professeur');
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Sécurité stricte : Liste des sections exclusivement réservées aux administrateurs
$admin_sections = ['utilisateurs', 'lycees', 'equipes', 'ot-zl', 'config-zl', 'vehicules', 'departements', 'energies'];

if ($is_connected && !$is_admin && in_array($section, $admin_sections, true)) {
    $section = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Console d'Administration — Challenge GTLA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        :root { --admin-primary: #3730a3; --admin-sidebar: #1e1b4b; --admin-light: #f4f5fa; }
        body { background-color: var(--admin-light); }
        .bg-admin-header { background-color: var(--admin-primary) !important; }
        .sidebar { background-color: var(--admin-sidebar); min-height: 100vh; color: #e0e7ff; }
        .sidebar .nav-link { color: #c7d2fe; border-radius: 6px; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255, 255, 255, 0.1); color: #fff; font-weight: bold; }
        .btn-indigo { background-color: var(--admin-primary); color: white; }
        .btn-indigo:hover { background-color: #4338ca; color: white; }
        .text-indigo { color: var(--admin-primary); }
    </style>
</head>
<body>

<?php if (!$is_connected): ?>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <span class="fs-1 text-indigo"><i class="bi bi-shield-lock-fill"></i></span>
                    <h3 class="fw-bold text-dark mt-2">Console Administration Challenge GTLA</h3>
                    <p class="text-muted small">Espace Enseignants & Administrateurs</p>
                </div>
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-danger py-2 small fw-bold"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Identifiant de connexion</label>
                                <input type="text" name="identifiant" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Mot de passe</label>
                                <input type="password" name="mot_de_passe" class="form-control" required>
                            </div>
                            <button type="submit" name="login_admin_submit" class="btn btn-indigo w-100 fw-bold py-2">Se connecter</button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4"><a href="../index.php" class="text-secondary small text-decoration-none"><i class="bi bi-arrow-left"></i> Accès Équipe</a></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3 shadow">
                <div class="d-flex align-items-center mb-4 px-2">
                    <span class="fs-4 text-warning me-2"><i class="bi bi-shield-shaded"></i></span>
                    <span class="fw-bold fs-5 text-white">GTLA Admin</span>
                </div>
                <div class="bg-white bg-opacity-10 rounded p-2 mb-4 text-center">
                    <div class="fw-bold text-truncate text-warning"><?= htmlspecialchars($_SESSION['admin_identifiant']) ?></div>
                    <span class="badge bg-light text-dark small text-capitalize"><?= htmlspecialchars($_SESSION['admin_role']) ?></span>
                </div>
                <ul class="nav flex-column">
                    <?php if ($is_admin): ?>
                        <li class="nav-item"><a class="nav-link <?= $section === 'dashboard' ? 'active' : '' ?>" href="?section=dashboard"><i class="bi bi-speedometer2 me-2"></i>Vue d'ensemble</a></li>
                        <li class="nav-header text-white-50 small fw-bold mt-3 mb-2 px-2">STRUCTURE</li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'utilisateurs' ? 'active' : '' ?>" href="?section=utilisateurs"><i class="bi bi-people me-2"></i>Utilisateurs</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'lycees' ? 'active' : '' ?>" href="?section=lycees"><i class="bi bi-building me-2"></i>Lycées</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'equipes' ? 'active' : '' ?>" href="?section=equipes"><i class="bi bi-person-badge me-2"></i>Équipes Étudiants</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'vehicules' ? 'active' : '' ?>" href="?section=vehicules"><i class="bi bi-truck me-2"></i>Véhicules ZL</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'energies' ? 'active' : '' ?>" href="?section=energies"><i class="bi bi-lightning-charge-fill me-2"></i>Énergies</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'ot-zl' ? 'active' : '' ?>" href="?section=ot-zl"><i class="bi bi-scooter me-2"></i>Ordres Transport ZL</a></li>
                        <li class="nav-header text-white-50 small fw-bold mt-3 mb-2 px-2">RESULTATS</li>
                        <li class="nav-item"><a class="nav-link <?= ($section === 'classement') ? 'active' : '' ?>" href="?section=classement"><i class="bi bi-trophy me-2"></i>Classement ZL</a></li>
                        <li class="nav-header text-white-50 small fw-bold mt-3 mb-2 px-2">CONFIGURATION</li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'config-zl' ? 'active' : '' ?>" href="?section=config-zl"><i class="bi bi-gear me-2"></i>Paramètres ZL</a></li>
                        <li class="nav-item"><a class="nav-link <?= $section === 'departements' ? 'active' : '' ?>" href="?section=departements"><i class="bi bi-geo-alt-fill me-2"></i>Départements limitrophes</a></li>
                    <?php elseif ($is_professeur): ?>
                        <li class="nav-header text-white-50 small fw-bold mt-3 mb-2 px-2">RESULTATS</li>
                        <li class="nav-item"><a class="nav-link <?= ($section === 'classement') ? 'active' : '' ?>" href="?section=classement"><i class="bi bi-trophy me-2"></i>Classement ZL</a></li>
                    <?php endif; ?>
                </ul>
                <hr class="text-white-50"><a href="?logout=1" class="btn btn-outline-danger btn-sm w-100 fw-bold"><i class="bi bi-box-arrow-left me-2"></i>Déconnexion</a>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-3 border-bottom">
                    <h1 class="h2 fw-bold text-dark text-capitalize">
                        <?php
                            if($section === 'dashboard') echo "Tableau de bord général";
                            elseif($section === 'utilisateurs') echo "Gestion des Utilisateurs";
                            elseif($section === 'lycees') echo "Gestion des Lycées";
                            elseif($section === 'equipes') echo "Gestion des Équipes Élèves";
                            elseif($section === 'ot-zl') echo "Gestion des Ordres de Transport ZL";
                            elseif($section === 'classement') echo "Classement du Challenge ZL";
                            elseif($section === 'config-zl') echo "Paramètres Globaux ZL";
                            elseif($section === 'departements') echo "Départements Limitrophes";
                            elseif($section === 'energies') echo "Énergies";
                        ?>
                    </h1>
                </div>

                <?php
                if (isset($_GET['msg'])) {
                    $type = ($_GET['status'] ?? 'success') === 'success' ? 'success' : 'danger';
                    echo '<div class="alert alert-'.$type.' alert-dismissible fade show small fw-bold mb-4" role="alert">'.htmlspecialchars($_GET['msg']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }

                $file = "includes/{$section}.php";

                if (file_exists($file)) {
                    if ($section === 'classement') {
                        if (isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['administrateur', 'professeur'], true)) {
                            include $file;
                        } else {
                            echo '<div class="alert alert-danger m-4">Accès interdit.</div>';
                        }
                    }
                    else {
                        if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'administrateur') {
                            echo '<div class="alert alert-danger m-4">Accès interdit : vous devez être administrateur.</div>';
                        } else {
                            include $file;
                        }
                    }
                } else {
                    include "includes/dashboard.php";
                }
                ?>
            </main>
        </div>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
