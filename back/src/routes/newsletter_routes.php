<?php
require_once __DIR__ . '/../security.php';
global $request_uri, $method, $input, $dsn, $dbUser, $dbPass;

if ($request_uri === '/api/newsletter' && $method === 'GET') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->query("SELECT n.*, u.prenom, u.nom FROM Newsletter n JOIN Utilisateur u ON n.auteur_id = u.id WHERE n.statut = 'approuve' ORDER BY n.date_creation DESC");
        $articles = $stmt->fetchAll();
        foreach ($articles as &$a) { $a['images'] = json_decode($a['images'], true) ?: []; }
        safeJsonResponse($articles);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

if ($request_uri === '/api/newsletter' && ($method === 'POST' || $method === 'PUT' || $method === 'DELETE')) {
    if (!requireAuth()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $images = isset($input['images']) ? json_encode(array_slice($input['images'], 0, 5)) : '[]';
        $statut = ($_SESSION['role_id'] == 3) ? 'approuve' : 'en_attente';

        if ($method === 'POST') {
            $stmt = $pdo->prepare("INSERT INTO Newsletter (auteur_id, titre, contenu, images, statut) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], sanitizePlainText($input['titre'] ?? ''), sanitizePlainText($input['contenu'] ?? ''), $images, $statut]);
            if ($statut === 'en_attente') {
                $adminEmail = getAdminEmail($pdo);
                $title = sanitizePlainText($input['titre'] ?? '');
                sendMail($adminEmail, "Nouvelle proposition d'article EJCE", "Un nouvel article intitulé '$title' a été proposé par un élève et attend votre validation.");
            }
            safeJsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId(), 'statut' => $statut]);
            exit;
        }

        if ($method === 'PUT') {
            $id = (int) ($input['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                safeJsonResponse(['error' => 'ID requis']);
                exit;
            }
            $stmtCheck = $pdo->prepare("SELECT auteur_id FROM Newsletter WHERE id = ?");
            $stmtCheck->execute([$id]);
            $article = $stmtCheck->fetch();
            if (!$article) {
                http_response_code(404);
                safeJsonResponse(['error' => 'Article non trouvé']);
                exit;
            }
            $isOwner = ($article['auteur_id'] == $_SESSION['user_id']);
            if ($_SESSION['role_id'] != 3 && !$isOwner) {
                http_response_code(403);
                safeJsonResponse(['error' => 'Accès refusé']);
                exit;
            }
            $fields = [];
            $params = [];
            if (isset($input['titre'])) { $fields[] = 'titre = ?'; $params[] = sanitizePlainText($input['titre']); }
            if (isset($input['contenu'])) { $fields[] = 'contenu = ?'; $params[] = sanitizePlainText($input['contenu']); }
            if (isset($input['images'])) { $fields[] = 'images = ?'; $params[] = json_encode(array_slice($input['images'], 0, 5)); }
            if (empty($fields)) {
                http_response_code(400);
                safeJsonResponse(['error' => 'Aucun champ à mettre à jour']);
                exit;
            }
            $params[] = $id;
            $sql = "UPDATE Newsletter SET " . implode(', ', $fields) . " WHERE id = ?";
            $pdo->prepare($sql)->execute($params);
            safeJsonResponse(['success' => true]);
            exit;
        }

        if ($method === 'DELETE' && isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmtCheck = $pdo->prepare("SELECT auteur_id FROM Newsletter WHERE id = ?");
            $stmtCheck->execute([$id]);
            $article = $stmtCheck->fetch();
            if (!$article) {
                http_response_code(404);
                safeJsonResponse(['error' => 'Article non trouvé']);
                exit;
            }
            $isOwner = ($article['auteur_id'] == $_SESSION['user_id']);
            if ($_SESSION['role_id'] != 3 && !$isOwner) {
                http_response_code(403);
                safeJsonResponse(['error' => 'Accès refusé']);
                exit;
            }
            $pdo->prepare("DELETE FROM Newsletter WHERE id = ?")->execute([$id]);
            safeJsonResponse(['success' => true]);
            exit;
        }

        http_response_code(400);
        safeJsonResponse(['error' => 'Méthode non supportée']);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}

if ($request_uri === '/api/newsletter/like' && $method === 'POST') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->prepare("UPDATE Newsletter SET likes = likes + 1 WHERE id = ?")->execute([(int) ($input['id'] ?? 0)]);
        safeJsonResponse(['success' => true]);
    } catch (PDOException $e) { http_response_code(500); safeJsonResponse(['error' => $e->getMessage()]); }
    exit;
}
