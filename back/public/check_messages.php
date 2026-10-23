<?php
$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$stmt = $pdo->query("SELECT m.*, u.prenom, u.role_id FROM Message m JOIN Utilisateur u ON m.sender_id = u.id ORDER BY m.date_envoi ASC");
$all = $stmt->fetchAll();
foreach($all as $m) {
    echo "ID: {$m['id']} | Date: {$m['date_envoi']} | Sender: {$m['prenom']} (Role: {$m['role_id']}) | Receiver: {$m['receiver_id']} | Content: {$m['content']}\n";
}
