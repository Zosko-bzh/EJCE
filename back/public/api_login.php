<?php
// api_login.php - Gère la connexion avec la BDD
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email et mot de passe requis.']);
    exit;
}

$dsn = "mysql:host=mysql;dbname=ejce;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, 'ejce_user', 'ejce_password', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    
    $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($input['password'], $user['motDePasse'])) {
        // Authentifié avec succès !
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];

        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie.',
            'role' => $user['role_id'] == 3 ? 'admin' : 'adherent'
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Identifiants incorrects.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données.']);
}
