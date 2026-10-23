<?php
// Script pour initialiser la DB avec le schéma de l'utilisateur et ajouter les 2 comptes.
$host = 'mysql';
$db   = 'ejce';
$user = 'ejce_user';
$pass = 'ejce_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "1. Connexion a MySQL réussie.\n";

    // Lecture du fichier SQL envoyé par l'utilisateur
    $sqlContent = file_get_contents('/var/www/html/public/db.sql');
    
    // Remplacement de SERIAL PRIMARY KEY (PostgreSQL) par BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE PRIMARY KEY (MySQL)
    $sqlContent = str_replace('SERIAL PRIMARY KEY', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', $sqlContent);

    // Supprimer les tables existantes pour repartir à propre
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Exécution du schéma SQL
    $pdo->exec($sqlContent);
    echo "2. Structure de la base de données créée avec succès.\n";

    // Insertion des utilisateurs de test !
    
    // Administrateur (Role 3 = PROFESSEUR ou on l'ajoute comme SUPER_ADMIN, mais d'après la DB on a 1 (VISITEUR), 2 (ELEVE), 3 (PROFESSEUR)).
    // Statut 1 = ACTIF
    $stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, prenom, email, motDePasse, role_id, statut_id, dateCreation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['Sensei', 'Maitre', 'admin@ejce.fr', password_hash('admin', PASSWORD_DEFAULT), 3, 1]);
    $adminId = $pdo->lastInsertId();
    
    $stmtProf = $pdo->prepare("INSERT INTO Professeur (utilisateur_id, grade, biographie) VALUES (?, ?, ?)");
    $stmtProf->execute([$adminId, 'Ceinture Noire 5e Dan', 'Professeur principal du club depuis 2001.']);

    // Adhérent / Eleve
    $stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, prenom, email, motDePasse, role_id, statut_id, dateCreation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['Dupont', 'Elliot', 'adherent@ejce.fr', password_hash('adherent', PASSWORD_DEFAULT), 2, 1]);
    $adherentId = $pdo->lastInsertId();

    $stmtEleve = $pdo->prepare("INSERT INTO Eleve (utilisateur_id, dateNaissance, numeroLicence, progression) VALUES (?, ?, ?, ?)");
    $stmtEleve->execute([$adherentId, '2000-01-01', 'EJCE001234', 50]);

    echo "3. Comptes Admin et Adhérent insérés avec succès !\n";

} catch (\PDOException $e) {
    echo "Erreur PDO : " . $e->getMessage() . "\n";
}
