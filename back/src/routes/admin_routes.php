<?php
require_once __DIR__ . '/../security.php';
global $request_uri, $method, $input, $dsn, $dbUser, $dbPass;

if ($request_uri === '/api/admin/newsletter' && $method === 'GET') {
    if (!requireAdmin()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->query("SELECT n.*, u.prenom, u.nom FROM Newsletter n JOIN Utilisateur u ON n.auteur_id = u.id WHERE n.statut = 'en_attente' ORDER BY n.date_creation DESC");
        $articles = $stmt->fetchAll();
        foreach ($articles as &$a) {
            $a['images'] = json_decode($a['images'], true) ?: [];
        }
        safeJsonResponse($articles);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

if ($request_uri === '/api/admin/newsletter/moderate' && ($method === 'POST' || $method === 'PUT' || $method === 'DELETE')) {
    if (!requireAdmin()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $id = (int) ($input['id'] ?? ($_GET['id'] ?? 0));
        $action = sanitizePlainText($input['action'] ?? null);
        if ($method === 'DELETE' || $action === 'refuse') {
            $pdo->prepare("DELETE FROM Newsletter WHERE id = ?")->execute([$id]);
        } else {
            $contenu = sanitizePlainText($input['contenu'] ?? null);
            $titre = sanitizePlainText($input['titre'] ?? null);
            if ($contenu && $titre) {
                $pdo->prepare("UPDATE Newsletter SET statut = 'approuve', titre = ?, contenu = ? WHERE id = ?")->execute([$titre, $contenu, $id]);
            } else {
                $pdo->prepare("UPDATE Newsletter SET statut = 'approuve' WHERE id = ?")->execute([$id]);
            }
        }
        safeJsonResponse(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

if (strpos($request_uri, '/api/admin/') === 0) {
    if (!requireAdmin()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        if (strpos($request_uri, '/api/admin/users') === 0) {
            try { $pdo->exec("ALTER TABLE Eleve ADD COLUMN date_ceinture DATE NULL"); } catch (PDOException $e) { }
            try { $pdo->exec("ALTER TABLE Professeur ADD COLUMN ceinture_id BIGINT UNSIGNED NULL"); } catch (PDOException $e) { }
            try { $pdo->exec("ALTER TABLE Professeur ADD COLUMN dan INTEGER NULL"); } catch (PDOException $e) { }
            try { $pdo->exec("ALTER TABLE Professeur ADD COLUMN date_ceinture DATE NULL"); } catch (PDOException $e) { }
            try { $pdo->exec("ALTER TABLE Utilisateur ADD COLUMN telephone VARCHAR(20) NULL"); } catch (PDOException $e) { }

            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT u.id, u.nom, u.prenom, u.email, u.imageUrl, u.role_id, u.telephone,
                                        e.dateNaissance, e.numeroLicence, e.progression, e.dan, e.ceinture_id, e.date_ceinture,
                                        p.ceinture_id as prof_ceinture_id, p.dan as prof_dan, p.date_ceinture as prof_date_ceinture,
                                        c.couleur as colorName
                                    FROM Utilisateur u
                                    LEFT JOIN Eleve e ON u.id = e.utilisateur_id
                                    LEFT JOIN Professeur p ON u.id = p.utilisateur_id
                                    LEFT JOIN Ceinture c ON (COALESCE(e.ceinture_id, p.ceinture_id) = c.id)
                                    ORDER BY u.nom ASC");
                $users = $stmt->fetchAll();
                $result = [];
                foreach ($users as $u) {
                    $age = 'N/A'; $categorie = 'N/A';
                    if ($u['dateNaissance']) {
                        $birthDate = new DateTime($u['dateNaissance']); $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        if ($age < 6) $categorie = 'Eveil';
                        elseif ($age < 8) $categorie = 'M-Poussin';
                        elseif ($age < 10) $categorie = 'Poussin';
                        elseif ($age < 12) $categorie = 'Benjamin';
                        elseif ($age < 14) $categorie = 'Minime';
                        elseif ($age < 16) $categorie = 'Cadet';
                        elseif ($age < 18) $categorie = 'Junior';
                        else $categorie = 'Senior';
                    }
                    $u['imageUrl'] = $u['imageUrl'] ?: '/img/profil.png';
                    $result[] = array_merge($u, ['age' => $age, 'categorie' => $categorie]);
                }
                safeJsonResponse($result);
                exit;
            }
            if ($method === 'POST') {
                $stmt = $pdo->prepare("INSERT INTO Utilisateur (nom, prenom, email, imageUrl, motDePasse, role_id, statut_id, telephone, dateCreation) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())");
                $rawPass = trim((string) ($input['password'] ?? 'ejce123'));
                $hash = password_hash($rawPass, PASSWORD_BCRYPT);
                $stmt->execute([
                    sanitizePlainText($input['nom'] ?? ''),
                    sanitizePlainText($input['prenom'] ?? ''),
                    sanitizeEmail($input['email'] ?? ''),
                    sanitizePlainText($input['imageUrl'] ?? null),
                    $hash,
                    (int) ($input['role_id'] ?? 2),
                    sanitizePlainText($input['telephone'] ?? null)
                ]);
                $userId = $pdo->lastInsertId();
                sendMail(sanitizeEmail($input['email'] ?? ''), "Bienvenue au club EJCE", "Bonjour {$input['prenom']},<br><br>Votre compte a été créé par l'administration.<br>Vous pouvez vous connecter avec votre email et ce mot de passe par défaut : <b>$rawPass</b><br>Veuillez le modifier rapidement.");

                if ((int) ($input['role_id'] ?? 0) == 2) {
                    $dob = !empty($input['dateNaissance']) ? sanitizePlainText($input['dateNaissance']) : null;
                    $licence = !empty($input['numeroLicence']) ? sanitizePlainText($input['numeroLicence']) : 'N/A';
                    $belt = !empty($input['belt_id']) ? (int) $input['belt_id'] : null;
                    $dan = !empty($input['dan']) ? (int) $input['dan'] : null;
                    $beltDate = !empty($input['belt_date']) ? sanitizePlainText($input['belt_date']) : null;
                    $stmtEleve = $pdo->prepare("INSERT INTO Eleve (utilisateur_id, dateNaissance, numeroLicence, ceinture_id, dan, progression, date_ceinture) VALUES (?, ?, ?, ?, ?, 0, ?)");
                    $stmtEleve->execute([$userId, $dob, $licence, $belt, $dan, $beltDate]);
                } elseif ((int) ($input['role_id'] ?? 0) == 3) {
                    $profBelt = !empty($input['prof_belt_id']) ? (int) $input['prof_belt_id'] : null;
                    $profDan = !empty($input['prof_dan']) ? (int) $input['prof_dan'] : null;
                    $profBeltDate = !empty($input['prof_belt_date']) ? sanitizePlainText($input['prof_belt_date']) : null;
                    $pdo->prepare("INSERT INTO Professeur (utilisateur_id, ceinture_id, dan, date_ceinture) VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE ceinture_id=VALUES(ceinture_id), dan=VALUES(dan), date_ceinture=VALUES(date_ceinture)")
                        ->execute([$userId, $profBelt, $profDan, $profBeltDate]);
                }
                safeJsonResponse(['success' => true, 'id' => (int) $userId]);
                exit;
            }
            if ($method === 'DELETE' && isset($_GET['id'])) {
                $pdo->prepare("DELETE FROM Utilisateur WHERE id = ?")->execute([(int) $_GET['id']]);
                safeJsonResponse(['success' => true]);
                exit;
            }
        }

        if (strpos($request_uri, '/api/admin/techniques') === 0) {
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT t.*, c.couleur as belt_color FROM Technique t LEFT JOIN Technique_Ceinture tc ON t.id = tc.technique_id LEFT JOIN Ceinture c ON tc.ceinture_id = c.id");
                safeJsonResponse($stmt->fetchAll());
                exit;
            }
            if ($method === 'POST') {
                $stmt = $pdo->prepare("INSERT INTO Technique (nom, description, videoUrl, imageUrl) VALUES (?, ?, ?, ?)");
                $stmt->execute([sanitizePlainText($input['nom'] ?? ''), sanitizePlainText($input['description'] ?? ''), sanitizePlainText($input['video_url'] ?? ''), sanitizePlainText($input['image_url'] ?? '')]);
                $techId = $pdo->lastInsertId();
                if (!empty($input['belt_id'])) {
                    $pdo->prepare("INSERT INTO Technique_Ceinture (technique_id, ceinture_id) VALUES (?, ?)")->execute([$techId, (int) $input['belt_id']]);
                }
                safeJsonResponse(['success' => true, 'id' => (int) $techId]);
                exit;
            }
            if ($method === 'DELETE' && isset($_GET['id'])) {
                $pdo->prepare("DELETE FROM Technique_Ceinture WHERE technique_id = ?")->execute([(int) $_GET['id']]);
                $pdo->prepare("DELETE FROM Technique WHERE id = ?")->execute([(int) $_GET['id']]);
                safeJsonResponse(['success' => true]);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("API Admin Error: " . $e->getMessage());
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}
