<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($uri, '/');
$projectRoot = __DIR__;
$frontDir = $projectRoot . '/front';
$backDir = $projectRoot . '/back/public';

if (strpos($path, '/api') === 0) {
    $_SERVER['SCRIPT_FILENAME'] = $backDir . '/index.php';
    require $backDir . '/index.php';
    return;
}

if ($path === '/' || $path === '') {
    $path = '/connexion.html';
}

$target = $frontDir . $path;
if (is_file($target)) {
    $mime = 'text/plain';
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    $mimes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'pdf' => 'application/pdf',
    ];
    if (isset($mimes[$ext])) {
        $mime = $mimes[$ext];
    }
    header('Content-Type: ' . $mime);
    readfile($target);
    return;
}

if (is_dir($target)) {
    $indexFile = $target . '/index.html';
    if (is_file($indexFile)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexFile);
        return;
    }
}

http_response_code(404);
echo 'Not Found';
