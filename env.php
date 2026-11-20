<?php
// env.php - Charge le fichier .env (s'il existe) dans les variables d'environnement
//
// Le fichier .env contient la configuration propre à un serveur (hôte de la
// base, mot de passe...). Il n'est JAMAIS versionné (voir .gitignore) : chaque
// poste et chaque serveur a le sien, le code reste identique partout.
//
// Format attendu : une variable par ligne, NOM=valeur ; les lignes vides et
// celles qui commencent par # sont ignorées.
//
// Une variable déjà définie (par Docker, ou par SetEnv dans Apache) n'est pas
// remplacée : la configuration du serveur reste prioritaire sur le fichier.

function chargerEnv(string $chemin): void
{
    if (!is_readable($chemin)) {
        return; // pas de .env : on garde les variables existantes ou les valeurs par défaut de db.php
    }

    foreach (file($chemin, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
        $ligne = trim($ligne);
        if ($ligne === '' || str_starts_with($ligne, '#') || !str_contains($ligne, '=')) {
            continue;
        }

        [$nom, $valeur] = explode('=', $ligne, 2);
        $nom = trim($nom);
        $valeur = trim(trim($valeur), "\"'"); // retire les guillemets éventuels

        if ($nom !== '' && getenv($nom) === false && !isset($_ENV[$nom])) {
            @putenv("$nom=$valeur");
            $_ENV[$nom] = $valeur;
            $_SERVER[$nom] = $valeur;
        }
    }
}

chargerEnv(__DIR__ . '/.env');
