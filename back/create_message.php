<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$user = 'ejce_user';
$pass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE Message (
        id SERIAL PRIMARY KEY,
        sender_id BIGINT UNSIGNED NOT NULL,
        receiver_id BIGINT UNSIGNED NULL,
        content TEXT NOT NULL,
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_read BOOLEAN DEFAULT FALSE
    )");
    echo "Table Message: CREATED\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
