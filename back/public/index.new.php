<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/../src/security.php';

$request_uri = normalizeRequestUri();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$dsn = 'mysql:host=mysql;dbname=ejce;charset=utf8mb4';
$dbUser = 'ejce_user';
$dbPass = 'ejce_password';

$input = getJsonInput();

function getAdminEmail($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'contact_email'");
        $stmt->execute();
        $setting = $stmt->fetchColumn();
        if ($setting) {
            return $setting;
        }

        $stmt = $pdo->query("SELECT email FROM Utilisateur WHERE role_id = 3 LIMIT 1");
        $adminEmail = $stmt->fetchColumn();
        return $adminEmail ?: 'admin@ejce.fr';
    } catch (Exception $e) {
        return 'admin@ejce.fr';
    }
}

require_once __DIR__ . '/../src/routes/auth_routes.php';
require_once __DIR__ . '/../src/routes/admin_routes.php';
require_once __DIR__ . '/../src/routes/newsletter_routes.php';
require_once __DIR__ . '/../src/routes/content_routes.php';

http_response_code(404);
safeJsonResponse(['error' => 'Route non trouvée.']);
