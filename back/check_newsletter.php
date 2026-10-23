<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$user = 'ejce_user';
$pass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    // Check table
    $stmt = $pdo->query("SHOW TABLES LIKE 'Newsletter'");
    if ($stmt->fetch()) {
        echo "Table Newsletter: EXISTS\n";
        $cols = $pdo->query("DESCRIBE Newsletter")->fetchAll();
        foreach ($cols as $c) { echo "  - " . $c['Field'] . " (" . $c['Type'] . ")\n"; }
        
        // Test query
        $stmt2 = $pdo->query("SELECT n.*, u.prenom, u.nom FROM Newsletter n JOIN Utilisateur u ON n.auteur_id = u.id WHERE n.statut = 'approuve' ORDER BY n.date_creation DESC");
        echo "Query OK, rows: " . count($stmt2->fetchAll()) . "\n";
    } else {
        echo "Table Newsletter: MISSING\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
