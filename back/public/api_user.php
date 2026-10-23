<?php
// api_user.php - Retourne les infos de l'utilisateur connecté
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié.']);
    exit;
}

$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, 'ejce_user', 'ejce_password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    // Récupération des infos de base
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role_id FROM Utilisateur WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur introuvable.']);
        exit;
    }

    $data = [
        'id' => $user['id'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'email' => $user['email'],
        'role' => $user['role_id'] == 3 ? 'Professeur/Admin' : 'Elève / Adhérent'
    ];

    // Si on est élève, on récupère le profil Eleve
    if ($user['role_id'] == 2) {
        $stmtEleve = $pdo->prepare("SELECT * FROM Eleve WHERE utilisateur_id = ?");
        $stmtEleve->execute([$user['id']]);
        $eleve = $stmtEleve->fetch();
        if ($eleve) {
            $data['profil'] = [
                'numeroLicence' => $eleve['numeroLicence'],
                'progression' => $eleve['progression']
            ];
        }
    }

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD.']);
}
