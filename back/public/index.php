<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
} else {
    header("Access-Control-Allow-Origin: *");
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/security.php';


function emitJsonError(string $message, int $statusCode = 500, array $context = []): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

    $payload = ['error' => $message];
    if (!empty($context)) {
        $payload['details'] = $context;
    }

    safeJsonResponse($payload, $statusCode);
    exit;
}

set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    emitJsonError('Erreur interne du serveur.', 500, [
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ]);
    return true;
});

set_exception_handler(function (Throwable $exception): void {
    emitJsonError('Erreur interne du serveur.', 500, [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        emitJsonError('Erreur interne du serveur.', 500, [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    }
});

if (file_exists(__DIR__ . '/mailer.php') && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/mailer.php';
}

if (!function_exists('sendMail')) {
    function sendMail($to, $subject, $body, $attachmentPath = null, $attachmentName = null) {
        error_log('Mailer indisponible pour l’envoi de mail.');
        return false;
    }
}

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
