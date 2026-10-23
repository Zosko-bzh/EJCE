<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$user = 'ejce_user';
$pass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS Competition (
        id SERIAL PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        lieu VARCHAR(255),
        description TEXT,
        categorie VARCHAR(100)
    )");
    
    // On vide avant de remettre les tests pour éviter les doublons si on relance
    $pdo->exec("DELETE FROM Competition");
    
    $stmt = $pdo->prepare("INSERT INTO Competition (nom, date, lieu, description, categorie) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Tournoi Interne Saint-André', '2026-04-15', 'Dojo Fleurimont', 'Petit tournoi amical pour toutes les ceintures.', 'Toutes']);
    $stmt->execute(['Coupe des Ceintures Noires', '2026-05-20', 'Gymnase de l Est', 'Compétition régionale réservée aux gradés.', 'Senior']);
    $stmt->execute(['Open Minimes de la Réunion', '2026-06-10', 'Stade de l Est', 'Tournoi de sélection régionale.', 'Minime']);
    
    echo "Table Competition OK\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
