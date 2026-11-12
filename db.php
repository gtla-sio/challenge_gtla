<?php
// db.php - Connexion centralisée à la base de données
//
// 🔒 CORRECTION SÉCURITÉ n°1 : identifiants de connexion
// -----------------------------------------------------------------------
// Idéalement, $host/$db/$user/$pass ne devraient JAMAIS être écrits en dur
// dans un fichier versionné dans Git (même en local avec un mot de passe
// vide comme ici : c'est l'HABITUDE qui compte, pas la gravité du cas
// particulier). En environnement pro, on lit ces valeurs depuis des
// variables d'environnement (getenv()) ou un fichier de config exclu du
// dépôt via .gitignore (voir le fichier .gitignore ajouté à la racine).
//
// Pour ce projet pédagogique on reste simple, mais on montre le principe :
$host    = getenv('GTLA_DB_HOST') ?: 'localhost';
$db      = getenv('GTLA_DB_NAME') ?: 'challenge_gtla';
$user    = getenv('GTLA_DB_USER') ?: 'root';   // À adapter selon environnement de production
$pass    = getenv('GTLA_DB_PASS') ?: '';       // À adapter selon environnement de production
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {

    // 🔒 CORRECTION SÉCURITÉ n°2 : fuite d'information dans les erreurs
    // ---------------------------------------------------------------
    // AVANT : die("Erreur de connexion à la base de données : " . $e->getMessage());
    // Le message d'exception PDO peut révéler le nom d'hôte, le nom de la
    // base, parfois des bouts de la requête -> autant d'informations
    // utiles à un attaquant pour préparer une attaque, et inutiles pour
    // un visiteur normal.
    //
    // On journalise l'erreur détaillée côté serveur (error_log), et on
    // n'affiche à l'utilisateur qu'un message générique.
    error_log('Erreur de connexion PDO : ' . $e->getMessage());
    http_response_code(500);
    die("Une erreur technique est survenue. Merci de réessayer plus tard ou de contacter un administrateur.");
}
