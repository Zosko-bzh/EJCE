<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$user = 'ejce_user';
$pass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS Newsletter (
        id SERIAL PRIMARY KEY,
        auteur_id BIGINT UNSIGNED NOT NULL,
        titre VARCHAR(255) NOT NULL,
        contenu TEXT NOT NULL,
        images JSON,
        statut ENUM('en_attente', 'approuve', 'refuse') DEFAULT 'en_attente',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_modif DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Table Newsletter: CREATED OK\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
