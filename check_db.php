<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $stmt = $pdo->query("DESCRIBE Eleve");
    print_r($stmt->fetchAll());
    
    $stmt = $pdo->query("SELECT * FROM Utilisateur");
    print_r($stmt->fetchAll());
    
    $stmt = $pdo->query("SELECT * FROM Eleve");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo $e->getMessage();
}
