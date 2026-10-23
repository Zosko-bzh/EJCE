<?php
require_once __DIR__ . '/../security.php';
global $request_uri, $method, $input, $dsn, $dbUser, $dbPass;

if ($request_uri === '/api/login' && $method === 'POST') {
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Email et mot de passe requis.']);
        exit;
    }

    $email = sanitizeEmail($input['email'] ?? '');
    $password = trim((string) ($input['password'] ?? ''));

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['motDePasse'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            safeJsonResponse(['success' => true, 'role' => $user['role_id'] == 3 ? 'admin' : 'adherent']);
        } else {
            http_response_code(401);
            safeJsonResponse(['error' => 'Identifiants incorrects.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur de base de données.']);
    }
    exit;
}

if ($request_uri === '/api/forgot-password' && $method === 'POST') {
    $email = sanitizeEmail($input['email'] ?? '');
    if (!$email) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Email requis.']);
        exit;
    }

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->prepare("SELECT id, prenom, email FROM Utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $code = random_int(100000, 999999);
            $_SESSION['reset_email'] = $user['email'];
            $_SESSION['reset_code'] = $code;
            $_SESSION['reset_time'] = time();
            $body = "Bonjour {$user['prenom']},<br><br>Voici votre code de vérification pour réinitialiser votre mot de passe : <b style='font-size:20px;'>$code</b><br>Saisissez ce code sur le site pour définir votre nouveau mot de passe.";
            sendMail($email, "Réinitialisation de votre mot de passe EJCE", $body);
        }
        safeJsonResponse(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur interne.']);
    }
    exit;
}

if ($request_uri === '/api/forgot-password-verify' && $method === 'POST') {
    $code = trim((string) ($input['code'] ?? ''));
    $newPass = trim((string) ($input['new_password'] ?? ''));
    if (!$code || !$newPass) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Données incomplètes.']);
        exit;
    }
    if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email'])) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Aucune demande en cours.']);
        exit;
    }
    if (time() - $_SESSION['reset_time'] > 900) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Le code a expiré.']);
        exit;
    }
    if ((string) $code !== (string) $_SESSION['reset_code']) {
        http_response_code(400);
        safeJsonResponse(['error' => 'Code incorrect.']);
        exit;
    }

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE Utilisateur SET motDePasse = ? WHERE email = ?");
        $stmt->execute([$hash, $_SESSION['reset_email']]);

        unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_time']);
        safeJsonResponse(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        safeJsonResponse(['error' => 'Erreur serveur.']);
    }
    exit;
}

if ($request_uri === '/api/user/update' && $method === 'POST') {
    if (!requireAuth()) {
        exit;
    }

    $target_id = (int) ($input['user_id'] ?? $_SESSION['user_id']);
    $is_self = ($target_id == $_SESSION['user_id']);
    $is_admin = ($_SESSION['role_id'] == 3);

    if (!$is_admin && !$is_self) {
        http_response_code(403);
        safeJsonResponse(['error' => 'Action non autorisée.']);
        exit;
    }

    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        if (!$is_admin) {
            if (!empty($input['password'])) {
                if (empty($input['old_password'])) {
                    http_response_code(400);
                    safeJsonResponse(['error' => 'Ancien mot de passe requis pour le modifier.']);
                    exit;
                }
                $stmt = $pdo->prepare("SELECT motDePasse FROM Utilisateur WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $currUser = $stmt->fetch();
                if (!$currUser || !password_verify(trim((string) ($input['old_password'] ?? '')), $currUser['motDePasse'])) {
                    http_response_code(403);
                    safeJsonResponse(['error' => 'Ancien mot de passe incorrect.']);
                    exit;
                }
                $hash = password_hash(trim((string) ($input['password'] ?? '')), PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE Utilisateur SET motDePasse = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            }

            if (!empty($input['imageUrl'])) {
                $pdo->prepare("UPDATE Utilisateur SET imageUrl = ? WHERE id = ?")->execute([sanitizePlainText($input['imageUrl']), $_SESSION['user_id']]);
            }
            safeJsonResponse(['success' => true, 'message' => 'Profil mis à jour.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT role_id FROM Utilisateur WHERE id = ?");
        $stmt->execute([$target_id]);
        $target_user = $stmt->fetch();
        if (!$target_user) {
            http_response_code(404);
            safeJsonResponse(['error' => 'Utilisateur introuvable.']);
            exit;
        }

        $sql = "UPDATE Utilisateur SET nom = ?, prenom = ?, email = ?, telephone = ?, imageUrl = ?" . (!empty($input['password']) ? ", motDePasse = ?" : "") . " WHERE id = ?";
        $params = [
            sanitizePlainText($input['nom'] ?? ''),
            sanitizePlainText($input['prenom'] ?? ''),
            sanitizeEmail($input['email'] ?? ''),
            sanitizePlainText($input['telephone'] ?? null),
            sanitizePlainText($input['imageUrl'] ?? null)
        ];
        if (!empty($input['password'])) {
            $params[] = password_hash(trim((string) ($input['password'] ?? '')), PASSWORD_BCRYPT);
        }
        $params[] = $target_id;
        $pdo->prepare($sql)->execute($params);

        if ($target_user['role_id'] == 2) {
            $dob = !empty($input['dateNaissance']) ? sanitizePlainText($input['dateNaissance']) : null;
            $licence = !empty($input['numeroLicence']) ? sanitizePlainText($input['numeroLicence']) : 'N/A';
            $belt = !empty($input['belt_id']) ? (int) $input['belt_id'] : null;
            $dan = !empty($input['dan']) ? (int) $input['dan'] : null;
            $beltDate = !empty($input['belt_date']) ? sanitizePlainText($input['belt_date']) : null;
            $pdo->prepare("INSERT INTO Eleve (utilisateur_id, dateNaissance, numeroLicence, ceinture_id, dan, progression, date_ceinture) VALUES (?, ?, ?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE dateNaissance=VALUES(dateNaissance), numeroLicence=VALUES(numeroLicence), ceinture_id=VALUES(ceinture_id), dan=VALUES(dan), date_ceinture=VALUES(date_ceinture)")
                ->execute([$target_id, $dob, $licence, $belt, $dan, $beltDate]);
        } elseif ($target_user['role_id'] == 3) {
            $profBelt = !empty($input['prof_belt_id']) ? (int) $input['prof_belt_id'] : null;
            $profDan = !empty($input['prof_dan']) ? (int) $input['prof_dan'] : null;
            $profBeltDate = !empty($input['prof_belt_date']) ? sanitizePlainText($input['prof_belt_date']) : null;
            $pdo->prepare("INSERT INTO Professeur (utilisateur_id, ceinture_id, dan, date_ceinture) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE ceinture_id=VALUES(ceinture_id), dan=VALUES(dan), date_ceinture=VALUES(date_ceinture)")
                ->execute([$target_id, $profBelt, $profDan, $profBeltDate]);
        }
        safeJsonResponse(['success' => true]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => $e->getMessage()]);
        exit;
    }
}

if ($request_uri === '/api/user' && $method === 'GET') {
    if (!requireAuth()) {
        exit;
    }
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, imageUrl, role_id FROM Utilisateur WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            http_response_code(404);
            safeJsonResponse(['error' => 'Moi introuvable.']);
            exit;
        }

        $roleName = ($user['role_id'] == 3) ? 'Admin' : (($user['role_id'] == 2) ? 'Élève' : 'Visiteur');
        $data = ['id' => (int) $user['id'], 'nom' => sanitizePlainText($user['nom']), 'prenom' => sanitizePlainText($user['prenom']), 'email' => sanitizeEmail($user['email']), 'imageUrl' => sanitizePlainText($user['imageUrl']) ?: '/img/profil.png', 'role_id' => (int) $user['role_id'], 'role_name' => $roleName];

        if ($user['role_id'] == 2) {
            $stmtEleve = $pdo->prepare("SELECT e.*, c.couleur as colorName FROM Eleve e LEFT JOIN Ceinture c ON e.ceinture_id = c.id WHERE e.utilisateur_id = ?");
            $stmtEleve->execute([$user['id']]);
            $eleve = $stmtEleve->fetch();
            if ($eleve) {
                $data['profil'] = ['numeroLicence' => sanitizePlainText($eleve['numeroLicence']), 'progression' => (int) ($eleve['progression'] ?? 0), 'dateNaissance' => sanitizePlainText($eleve['dateNaissance']), 'dan' => $eleve['dan'], 'beltName' => sanitizePlainText($eleve['colorName']) ?? 'Blanche', 'dateCeinture' => sanitizePlainText($eleve['date_ceinture']) ?? null];
            }
        } elseif ($user['role_id'] == 3) {
            $stmtProf = $pdo->prepare("SELECT * FROM Professeur WHERE utilisateur_id = ?");
            $stmtProf->execute([$user['id']]);
            $prof = $stmtProf->fetch();
            if ($prof) {
                $data['profil'] = ['numeroLicence' => 'Admin', 'beltName' => sanitizePlainText($prof['grade']) ?? 'Noire'];
            }
        }
        safeJsonResponse($data);
    } catch (PDOException $e) {
        http_response_code(500);
        safeJsonResponse(['error' => 'DB Error']);
    }
    exit;
}
