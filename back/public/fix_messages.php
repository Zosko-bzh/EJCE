<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Trouver l'admin principal (probablement Rémi)
    // On va chercher le premier admin (role_id = 3) qui a déjà répondu à des messages, ou l'admin avec l'ID le plus bas.
    $stmt = $pdo->query("SELECT id FROM Utilisateur WHERE role_id = 3 ORDER BY id ASC LIMIT 1");
    $adminId = $stmt->fetchColumn();

    if ($adminId) {
        // Mettre à jour les messages envoyés par les adhérents (receiver_id NULL) pour les attribuer à l'admin principal
        $stmtUpdateAdherent = $pdo->prepare("UPDATE Message SET receiver_id = ? WHERE receiver_id IS NULL AND sender_id IN (SELECT id FROM Utilisateur WHERE role_id = 2)");
        $stmtUpdateAdherent->execute([$adminId]);

        // Mettre à jour les vieux messages "collectifs" envoyés par un admin sans destinataire précis (receiver_id NULL)
        // On pourrait les laisser NULL, car ils n'étaient pas P2P. Mais pour éviter de polluer, on peut les supprimer ou les attribuer à qqn.
        // On les laisse NULL, car dans l'API on ne les lit plus.
        
        echo "Migration terminée. Les anciens messages des adhérents ont été attribués à l'admin ID : " . $adminId . "\n";
    } else {
        echo "Aucun admin trouvé.\n";
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
