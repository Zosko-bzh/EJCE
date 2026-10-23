<?php

function normalizeRequestUri(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
    return '/' . trim($uri, '/');
}

function sanitizePlainText($value, bool $allowTags = false): string {
    if ($value === null) {
        return '';
    }
    if (is_array($value)) {
        return '';
    }

    $text = trim((string) $value);
    if (!$allowTags) {
        $text = strip_tags($text);
    }
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    return $text;
}

function sanitizeEmail($value): string {
    $email = sanitizePlainText($value);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : '';
}

function sanitizeInputValue($value) {
    if (is_array($value)) {
        $sanitized = [];
        foreach ($value as $key => $item) {
            $sanitized[$key] = sanitizeInputValue($item);
        }
        return $sanitized;
    }

    if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
        return $value;
    }

    return sanitizePlainText($value);
}

function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    return sanitizeInputValue($decoded);
}

function safeJsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function escapeForHtml($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function safeFileName(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return strtolower($name);
}

function requireAuth(): bool {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        safeJsonResponse(['error' => 'Non authentifié.']);
        return false;
    }
    return true;
}

function requireAdmin(): bool {
    if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
        http_response_code(403);
        safeJsonResponse(['error' => 'Accès refusé.']);
        return false;
    }
    return true;
}
