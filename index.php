<?php
session_start();

// 1. INCLUSION DE LA CONNEXION À LA BASE DE DONNÉES
require_once 'db.php';

// 🔒 CORRECTION : les fonctions de conversion et les helpers de sécurité
// (CSRF, contrôle d'accès) sont maintenant dans functions.php, partagé
// avec actions.php, au lieu d'être dupliqués dans chaque fichier.
require_once 'functions.php';

// 🔒 CORRECTION SÉCURITÉ n°3 : jeton CSRF pour l'espace équipe
// -----------------------------------------------------------------------
// AVANT : seul l'espace admin générait un jeton CSRF. Rien ne protégeait
// les actions de l'espace équipe (ajout/modif/suppression d'une ligne
// d'activité), ce qui permettait à un attaquant de piéger une équipe
// connectée pour la faire agir à son insu (voir la review : suppression
// via une simple balise <img>).
// On génère donc désormais un jeton, exactement comme côté admin, et on
// l'injecte dans tous les formulaires de l'espace équipe (voir
// includes/otea-zl.php).
$csrf_token = genererCsrfToken();

// 2. GESTION DE LA DÉCONNEXION EQUIPE
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ./");
    exit;
}

// 3. TRAITEMENT DU FORMULAIRE DE CONNEXION ÉLÈVE
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_team_submit'])) {
    $login_saisi = trim($_POST['login_equipe']);
    $mdp_saisi   = $_POST['mot_de_passe'];

    if (!empty($login_saisi) && !empty($mdp_saisi)) {
        $stmt = $pdo->prepare('SELECT id_equipe, nom_equipe, mot_de_passe FROM equipes WHERE login_equipe = :login');
        $stmt->bindValue(':login', $login_saisi, PDO::PARAM_STR);
        $stmt->execute();
        $team = $stmt->fetch();

        // Vérification sécurisée du mot de passe
        if ($team && password_verify($mdp_saisi, $team['mot_de_passe'])) {
            // Bonne pratique : on régénère l'identifiant de session après
            // une authentification réussie, pour empêcher une attaque par
            // fixation de session (session_id() volée avant connexion).
            session_regenerate_id(true);

            $_SESSION['team_id']   = $team['id_equipe'];
            $_SESSION['team_nom']  = $team['nom_equipe'];
            header("Location: ./");
            exit;
        } else {
            $error = "Identifiant d'équipe ou mot de passe incorrect.";
        }

    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// 4. ETAT DE CONNEXION ET ROUTAGE DES SECTIONS
$is_connected = isset($_SESSION['team_id']);
$section      = isset($_GET['section']) ? $_GET['section'] : 'otea-zl';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Élèves — Challenge GTLA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        :root { --team-primary: #0284c7; --team-dark: #0f172a; --team-light: #f8fafc; }
        body { background-color: var(--team-light); font-family: 'Segoe UI', system-ui, sans-serif; }
        .bg-team-header { background-color: var(--team-primary) !important; }
        .card-team { border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); }
        .btn-sky { background-color: var(--team-primary); color: white; fw-bold; }
        .btn-sky:hover { background-color: #0369a1; color: white; }
    </style>
</head>
<body>

    <?php if ($is_connected): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-info" href="./"><i class="bi bi-truck me-2"></i>Challenge GTLA</a>
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= $section === 'otea-zl' ? 'active text-info' : 'text-white' ?>" href="?section=otea-zl">Planning</a></li>
                <!-- 🆕 Affrètement et Rentabilité séparés en deux pages -->
                <li class="nav-item"><a class="nav-link <?= $section === 'affretement' ? 'active text-info' : 'text-white' ?>" href="?section=affretement">Affrètement</a></li>
                <li class="nav-item"><a class="nav-link <?= $section === 'rentabilite' ? 'active text-info' : 'text-white' ?>" href="?section=rentabilite">Rentabilité</a></li>
            </ul>
            <span class="navbar-text text-white me-3 d-none d-md-inline">
                Équipe : <strong class="text-warning"><?= htmlspecialchars($_SESSION['team_nom']) ?></strong>
            </span>
            <div class="ms-auto">
                <a href="?logout=1" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-box-arrow-left me-1"></i>Déconnexion</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container mb-5">
        <?php if (!$is_connected): ?>
            <?php include 'includes/login.php'; ?>
        <?php else: ?>
            <?php
                // Alertes ou notifications globales (succès / échecs de saisie)
                if (isset($_GET['msg'])) {
                    $type = ($_GET['status'] ?? 'success') === 'success' ? 'success' : 'danger';
                    echo '<div class="alert alert-'.$type.' alert-dismissible fade show small fw-bold mb-4" role="alert">'.htmlspecialchars($_GET['msg']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                }

                // Chargement sécurisé de la vue demandée.
                // NOTE PÉDAGOGIQUE : $section vient directement de $_GET, donc
                // d'une entrée utilisateur non fiable. La construction du
                // chemin de fichier ci-dessous n'est PAS dangereuse ici
                // uniquement parce que la liste blanche plus bas compare la
                // valeur BRUTE de $section (et pas le chemin résolu) à une
                // liste figée ('otea-zl'). Ne jamais retirer cette liste
                // blanche, même si "ça n'a jamais posé de problème" : c'est
                // elle qui empêche une inclusion de fichier arbitraire
                // (Local File Inclusion).
                $file = "includes/{$section}.php";

                if (file_exists($file)) {
                    if (isset($_SESSION['team_id'])) {
                        // Liste blanche des pages autorisées pour l'Équipe
                        // 🆕 'affretement' et 'rentabilite' : deux pages distinctes (workflow / argent)
                        $pages_equipe = ['otea-zl', 'affretement', 'rentabilite']; // Ajoutez ici les pages équipe

                        if (!in_array($section, $pages_equipe, true)) {
                            die("Accès interdit : Vous n'avez pas accès à cette zone.");
                        }
                    }
                    else {
                        $file = "includes/login.php";
                    }

                    include $file;

                } else {
                    include "includes/otea-zl.php";
                }
            ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
