<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$user = 'ejce_user';
$pass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS Competition_Participant (
        competition_id BIGINT UNSIGNED NOT NULL,
        utilisateur_id INTEGER NOT NULL,
        PRIMARY KEY (competition_id, utilisateur_id)
    )");
    echo "Table Competition_Participant OK\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
