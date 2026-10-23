<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($uri, '/');

if ($path === '/api' || strpos($path, '/api/') === 0) {
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../back/public/index.php';
    require __DIR__ . '/../back/public/index.php';
    return;
}

if ($path === '/' || $path === '') {
    $target = __DIR__ . '/connexion.html';
    if (is_file($target)) {
        require $target;
        return;
    }
}

http_response_code(404);
echo 'Not Found';
