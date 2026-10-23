<?php
require_once __DIR__ . '/../security.php';
global $request_uri, $method, $input, $dsn, $dbUser, $dbPass;

if ($request_uri === '/api/belts' && $method === 'GET') {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    safeJsonResponse($pdo->query("SELECT * FROM Ceinture ORDER BY ordre ASC")->fetchAll());
    exit;
}
if ($request_uri === '/api/techniques' && $method === 'GET') {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    safeJsonResponse($pdo->query("SELECT t.*, c.couleur as belt_category FROM Technique t LEFT JOIN Technique_Ceinture tc ON t.id = tc.technique_id LEFT JOIN Ceinture c ON tc.ceinture_id = c.id")->fetchAll());
    exit;
}

if (strpos($request_uri, '/api/carousel') === 0) {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM HeroCarousel ORDER BY dateAjout DESC LIMIT 5");
            safeJsonResponse($stmt->fetchAll());
            exit;
        }

        if ($method === 'POST') {
            if (!requireAdmin()) {
                exit;
            }
            if (!isset($_FILES['file'])) {
                http_response_code(400);
                safeJsonResponse(['error' => 'Aucun fichier.']);
                exit;
            }
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                http_response_code(400);
                safeJsonResponse(['error' => 'Format non supporté.']);
                exit;
            }

            $newName = 'carousel_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $targetDir = __DIR__ . '/../../../front/uploads/carousel/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            if (move_uploaded_file($file['tmp_name'], $targetDir . $newName)) {
                $url = '/uploads/carousel/' . $newName;
                $count = $pdo->query("SELECT COUNT(*) FROM HeroCarousel")->fetchColumn();
                if ($count >= 5) {
                    $oldest = $pdo->query("SELECT * FROM HeroCarousel ORDER BY dateAjout ASC LIMIT 1")->fetch();
                    if ($oldest) {
                        $oldFile = __DIR__ . '/../../../front' . $oldest['urlStockage'];
                        if (file_exists($oldFile)) unlink($oldFile);
                        $pdo->prepare("DELETE FROM HeroCarousel WHERE id = ?")->execute([$oldest['id']]);
                    }
                }
                $pdo->prepare("INSERT INTO HeroCarousel (nomFichier, urlStockage) VALUES (?, ?)")->execute([sanitizePlainText($file['name']), $url]);
                safeJsonResponse(['success' => true, 'url' => $url]);
            } else {
                http_response_code(500);
                safeJsonResponse(['error' => 'Erreur upload.']);
            }
            exit;
        }

        if ($method === 'DELETE') {
            if (!requireAdmin()) {
                exit;
            }
            $id = (int) ($_GET['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("SELECT urlStockage FROM HeroCarousel WHERE id = ?");
                $stmt->execute([$id]);
                $img = $stmt->fetch();
                if ($img) {
                    $file = __DIR__ . '/../../../front' . $img['urlStockage'];
                    if (file_exists($file)) unlink($file);
                    $pdo->prepare("DELETE FROM HeroCarousel WHERE id = ?")->execute([$id]);
                }
            }
            safeJsonResponse(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}

if (strpos($request_uri, '/api/messages') === 0) {
    if (!requireAuth()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $myId = $_SESSION['user_id'];
        $isAdmin = ($_SESSION['role_id'] == 3);

        if ($method === 'GET') {
            if (isset($_GET['action']) && $_GET['action'] === 'get_admins') {
                $stmt = $pdo->query("SELECT id, prenom, nom FROM Utilisateur WHERE role_id = 3 ORDER BY nom ASC");
                safeJsonResponse($stmt->fetchAll());
                exit;
            }

            if ($isAdmin && isset($_GET['student_id'])) {
                $target = (int) $_GET['student_id'];
                $stmt = $pdo->prepare("SELECT m.*, u.prenom as sender_name, u.role_id as sender_role FROM Message m JOIN Utilisateur u ON m.sender_id = u.id WHERE (m.sender_id = :myId AND m.receiver_id = :target) OR (m.sender_id = :target AND m.receiver_id = :myId) ORDER BY m.date_envoi ASC");
                $stmt->execute(['target' => $target, 'myId' => $myId]);
                safeJsonResponse($stmt->fetchAll());
                exit;
            } elseif (!$isAdmin && isset($_GET['admin_id'])) {
                $adminId = (int) $_GET['admin_id'];
                $stmt = $pdo->prepare("SELECT m.*, u.prenom as sender_name, u.role_id as sender_role FROM Message m JOIN Utilisateur u ON m.sender_id = u.id WHERE (m.sender_id = :myId AND m.receiver_id = :adminId) OR (m.sender_id = :adminId AND m.receiver_id = :myId) ORDER BY m.date_envoi ASC");
                $stmt->execute(['adminId' => $adminId, 'myId' => $myId]);
                safeJsonResponse($stmt->fetchAll());
                exit;
            } elseif ($isAdmin) {
                $stmt = $pdo->prepare("SELECT u.id, u.nom, u.prenom, MAX(m.date_envoi) as last_msg FROM Utilisateur u LEFT JOIN Message m ON ((m.sender_id = u.id AND m.receiver_id = :myId) OR (m.receiver_id = u.id AND m.sender_id = :myId)) WHERE u.id != :myId2 GROUP BY u.id ORDER BY last_msg DESC, u.nom ASC");
                $stmt->execute(['myId' => $myId, 'myId2' => $myId]);
                safeJsonResponse($stmt->fetchAll());
                exit;
            }
            safeJsonResponse([]);
            exit;
        }
        if ($method === 'POST') {
            if (!empty($input['is_broadcast']) && $isAdmin) {
                $stmtAdherents = $pdo->query("SELECT id FROM Utilisateur WHERE role_id = 2");
                $adherents = $stmtAdherents->fetchAll();
                $stmtInsert = $pdo->prepare("INSERT INTO Message (sender_id, receiver_id, content, date_envoi) VALUES (?, ?, ?, NOW())");
                foreach ($adherents as $adherent) {
                    $stmtInsert->execute([$myId, $adherent['id'], sanitizePlainText($input['content'] ?? '')]);
                }
                safeJsonResponse(['success' => true]);
                exit;
            }

            $receiver = (int) ($input['receiver_id'] ?? 0);
            if (!$receiver && !$isAdmin) {
                http_response_code(400);
                safeJsonResponse(['error' => 'Veuillez sélectionner un destinataire.']);
                exit;
            }

            $content = sanitizePlainText($input['content'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO Message (sender_id, receiver_id, content, date_envoi) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$myId, $receiver, $content]);

            if ($receiver) {
                $stmtRec = $pdo->prepare("SELECT email, prenom, role_id FROM Utilisateur WHERE id = ?");
                $stmtRec->execute([$receiver]);
                $recUser = $stmtRec->fetch();
                if ($recUser) {
                    $stmtSend = $pdo->prepare("SELECT prenom, nom FROM Utilisateur WHERE id = ?");
                    $stmtSend->execute([$myId]);
                    $sendUser = $stmtSend->fetch();
                    $senderName = $sendUser ? "{$sendUser['prenom']} {$sendUser['nom']}" : 'Un utilisateur';

                    if ($recUser['role_id'] == 3) {
                        sendMail($recUser['email'], "Nouveau message de $senderName", "$senderName vous a envoyé un message depuis l'espace EJCE :<br><br><i>$content</i>");
                    } else {
                        sendMail($recUser['email'], "Nouveau message du club EJCE", "Bonjour {$recUser['prenom']},<br><br>Vous avez reçu un nouveau message de $senderName :<br><br><i>$content</i><br><br>Connectez-vous à votre espace pour répondre.");
                    }
                }
            }
            safeJsonResponse(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}

if ($request_uri === '/api/competitions' && $method === 'GET') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS Competition_Participant (competition_id BIGINT UNSIGNED NOT NULL, utilisateur_id INTEGER NOT NULL, PRIMARY KEY (competition_id, utilisateur_id))");
        $competitions = $pdo->query("SELECT * FROM Competition ORDER BY date ASC")->fetchAll();
        $stmt = $pdo->query("SELECT cp.competition_id, u.id, u.prenom, u.imageUrl, e.dateNaissance FROM Competition_Participant cp JOIN Utilisateur u ON cp.utilisateur_id = u.id LEFT JOIN Eleve e ON u.id = e.utilisateur_id");
        $participants = $stmt->fetchAll();

        $partsByComp = [];
        foreach ($participants as $p) {
            $categorie = 'Senior';
            if (!empty($p['dateNaissance'])) {
                $age = (new DateTime())->diff(new DateTime($p['dateNaissance']))->y;
                if ($age < 6) $categorie = 'Eveil';
                elseif ($age < 8) $categorie = 'M-Poussin';
                elseif ($age < 10) $categorie = 'Poussin';
                elseif ($age < 12) $categorie = 'Benjamin';
                elseif ($age < 14) $categorie = 'Minime';
                elseif ($age < 16) $categorie = 'Cadet';
                elseif ($age < 18) $categorie = 'Junior';
                elseif ($age < 40) $categorie = 'Senior';
                else $categorie = 'Vétéran';
            }
            $p['id'] = (int) $p['id'];
            $p['competition_id'] = (int) $p['competition_id'];
            $p['categorie'] = $categorie;
            $p['imageUrl'] = $p['imageUrl'] ?: '/img/profil.png';
            $partsByComp[$p['competition_id']][] = $p;
        }

        foreach ($competitions as &$c) {
            $c['id'] = (int) $c['id'];
            $c['participants'] = $partsByComp[$c['id']] ?? [];
        }
        safeJsonResponse($competitions);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

if ($request_uri === '/api/competitions/register' && $method === 'POST') {
    if (!requireAuth()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $compId = (int) ($input['competition_id'] ?? 0);
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT * FROM Competition_Participant WHERE competition_id = ? AND utilisateur_id = ?");
        $stmt->execute([$compId, $userId]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM Competition_Participant WHERE competition_id = ? AND utilisateur_id = ?")->execute([$compId, $userId]);
            safeJsonResponse(['success' => true, 'action' => 'unregistered']);
        } else {
            $pdo->prepare("INSERT INTO Competition_Participant (competition_id, utilisateur_id) VALUES (?, ?)")->execute([$compId, $userId]);
            $stmtUser = $pdo->prepare("SELECT prenom, nom FROM Utilisateur WHERE id = ?");
            $stmtUser->execute([$userId]);
            $u = $stmtUser->fetch();
            $stmtComp = $pdo->prepare("SELECT nom FROM Competition WHERE id = ?");
            $stmtComp->execute([$compId]);
            $c = $stmtComp->fetch();
            if ($u && $c) {
                $adminEmail = getAdminEmail($pdo);
                sendMail($adminEmail, "Nouvelle inscription événement", "L'élève {$u['prenom']} {$u['nom']} s'est inscrit à l'événement '{$c['nom']}'.");
            }
            safeJsonResponse(['success' => true, 'action' => 'registered']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

if (strpos($request_uri, '/api/admin/competitions') === 0) {
    if (!requireAdmin()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        if ($method === 'POST') {
            $nom = sanitizePlainText($input['nom'] ?? 'évènement');
            $stmt = $pdo->prepare("INSERT INTO Competition (nom, date, lieu, description, categorie) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, sanitizePlainText($input['date'] ?? ''), sanitizePlainText($input['lieu'] ?? ''), sanitizePlainText($input['description'] ?? ''), sanitizePlainText($input['categorie'] ?? '')]);
            safeJsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
            exit;
        }
        if ($method === 'DELETE' && isset($_GET['id'])) {
            $pdo->prepare("DELETE FROM Competition WHERE id = ?")->execute([(int) $_GET['id']]);
            safeJsonResponse(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}

if ($request_uri === '/api/upload' && $method === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Aucun fichier.']);
        exit;
    }
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Format de fichier non supporté.']);
        exit;
    }
    $newName = uniqid('img_', true) . '.' . $ext;
    $targetDir = __DIR__ . '/../../front/uploads/';
    if (!is_dir($targetDir)) {
        if (!@mkdir($targetDir, 0777, true)) {
            http_response_code(500);
            safeJsonResponse(['error' => 'Impossible de créer le dossier uploads.', 'path' => $targetDir]);
            exit;
        }
    }
    $targetPath = $targetDir . $newName;
    if (@move_uploaded_file($file['tmp_name'], $targetPath)) {
        safeJsonResponse(['success' => true, 'url' => '/uploads/' . $newName]);
    } else {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur lors du déplacement du fichier.']);
    }
    exit;
}

if ($request_uri === '/api/settings' && $method === 'GET') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
        $settings = $pdo->query("SELECT setting_key, setting_value FROM global_settings")->fetchAll();
        $res = [];
        foreach ($settings as $s) { $res[$s['setting_key']] = $s['setting_value']; }
        safeJsonResponse($res);
    } catch (PDOException $e) { http_response_code(500); safeJsonResponse(['error' => $e->getMessage()]); }
    exit;
}

if ($request_uri === '/api/admin/settings' && $method === 'POST') {
    if (!requireAdmin()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
        foreach ($input as $key => $value) {
            $safeKey = sanitizePlainText($key);
            $safeValue = sanitizePlainText($value);
            $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$safeKey, (string) $safeValue, (string) $safeValue]);
        }
        safeJsonResponse(['success' => true]);
    } catch (PDOException $e) { http_response_code(500); safeJsonResponse(['error' => $e->getMessage()]); }
    exit;
}

if ($request_uri === '/api/contact' && $method === 'POST') {
    $nom = sanitizePlainText($input['nom'] ?? '');
    $email = sanitizeEmail($input['email'] ?? '');
    $objet = sanitizePlainText($input['objet'] ?? 'Message depuis le site');
    $message = sanitizePlainText($input['message'] ?? '');
    if ($nom && $email && $message) {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $adminEmail = getAdminEmail($pdo);
        $body = "Nom: $nom<br>Email: $email<br>Objet: $objet<br>Message: <br>" . nl2br(escapeForHtml($message));
        sendMail($adminEmail, "Nouveau message de contact : $objet", $body);
        safeJsonResponse(['success' => true]);
    } else {
        http_response_code(400);
        safeJsonResponse(['error' => 'Données incomplètes.']);
    }
    exit;
}

if ($request_uri === '/api/adhesion' && $method === 'POST') {
    if (!isset($_FILES['dossier'])) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Aucun fichier fourni.']);
        exit;
    }

    $file = $_FILES['dossier'];
    $subject = 'Nouveau dépôt de dossier d\'inscription';
    $body = 'Un nouveau dossier d\'inscription a été déposé depuis le site web.';

    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $adminEmail = getAdminEmail($pdo);
    $success = sendMail($adminEmail, $subject, $body, $file['tmp_name'], safeFileName($file['name']));

    if ($success) {
        safeJsonResponse(['success' => true]);
    } else {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur envoi email']);
    }
    exit;
}

// ─── PDF d'inscription ──────────────────────────────────────────────────────

// GET  /api/adhesion-pdf  → retourne l'URL du PDF actuel (public)
if ($request_uri === '/api/adhesion-pdf' && $method === 'GET') {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'adhesion_pdf_url'");
        $stmt->execute();
        $row = $stmt->fetch();
        $url = $row ? $row['setting_value'] : null;
        safeJsonResponse(['url' => $url]);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

// POST /api/admin/adhesion-pdf  → upload d'un nouveau PDF (admin seulement)
if ($request_uri === '/api/admin/adhesion-pdf' && $method === 'POST') {
    if (!requireAdmin()) {
        exit;
    }
    if (!isset($_FILES['pdf'])) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Aucun fichier fourni.']);
        exit;
    }

    $file = $_FILES['pdf'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        http_response_code(400);
        safeJsonResponse(['error' => 'Seuls les fichiers PDF sont acceptés.']);
        exit;
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Le fichier est trop volumineux (max 10 Mo).']);
        exit;
    }

    $targetDir = __DIR__ . '/../../../front/uploads/docs/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Supprimer l'ancien PDF s'il existe
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS global_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)");
        $stmtOld = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'adhesion_pdf_url'");
        $stmtOld->execute();
        $oldRow = $stmtOld->fetch();
        if ($oldRow && $oldRow['setting_value']) {
            $oldPath = __DIR__ . '/../../../front' . $oldRow['setting_value'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    } catch (PDOException $e) {
        // continue même si on ne peut pas supprimer l'ancien
    }

    $newName = 'dossier_inscription_ejce_' . date('Ymd_His') . '.pdf';
    $targetPath = $targetDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur lors de l\'enregistrement du fichier.']);
        exit;
    }

    $url = '/uploads/docs/' . $newName;
    try {
        $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES ('adhesion_pdf_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$url, $url]);
        safeJsonResponse(['success' => true, 'url' => $url]);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
    }
    exit;
}

