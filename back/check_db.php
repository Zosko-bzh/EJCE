<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    echo "--- TABLE ROLES ---\n";
    $stmt = $pdo->query("SELECT * FROM Role");
    print_r($stmt->fetchAll());
    
    echo "--- USERS ---\n";
    $stmt = $pdo->query("SELECT id, nom, prenom, email, role_id FROM Utilisateur");
    print_r($stmt->fetchAll());
    
    echo "--- ELEVES ---\n";
    $stmt = $pdo->query("SELECT * FROM Eleve");
    print_r($stmt->fetchAll());
    
    echo "--- PROFESSEURS ---\n";
    $stmt = $pdo->query("SELECT * FROM Professeur");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo $e->getMessage();
}
