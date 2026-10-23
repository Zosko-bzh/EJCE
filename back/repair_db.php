<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    // 1. Nettoyer les orphelins (car les FK n'étaient pas activées)
    echo "Suppression des orphelins dans Eleve...\n";
    $pdo->exec("DELETE FROM Eleve WHERE utilisateur_id NOT IN (SELECT id FROM Utilisateur)");
    
    // 2. Créer les profils manquants pour les élèves
    echo "Création des profils manquants pour les utilisateurs de rôle 2...\n";
    $stmt = $pdo->query("SELECT id FROM Utilisateur WHERE role_id = 2 AND id NOT IN (SELECT utilisateur_id FROM Eleve)");
    $missingProfiles = $stmt->fetchAll();
    
    foreach ($missingProfiles as $user) {
        $pdo->prepare("INSERT INTO Eleve (utilisateur_id, dateNaissance, numeroLicence, progression) VALUES (?, ?, ?, ?)")
            ->execute([$user['id'], '2000-01-01', 'N/A', 0]);
        echo "Profil créé pour l'utilisateur ID: " . $user['id'] . "\n";
    }

    echo "Terminé avec succès.\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
