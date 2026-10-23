<?php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$publicDir = __DIR__;
$target = $publicDir . $path;

if ($path === '/' || $path === '') {
    require $publicDir . '/index.php';
    return;
}

if ($path === '/api' || strpos($path, '/api/') === 0) {
    require $publicDir . '/index.php';
    return;
}

if (is_file($target)) {
    return false;
}

if (is_dir($target)) {
    $indexFile = $target . '/index.php';
    if (is_file($indexFile)) {
        require $indexFile;
        return;
    }
}

require $publicDir . '/index.php';
