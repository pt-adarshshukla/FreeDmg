<?php
/**
 * FreeDmg - PHP Built-in Server LAN & Device Compatibility Router
 * Handles all clean URLs, static assets, and device access over LAN (0.0.0.0).
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rawurldecode($uri);

// Block direct access to hidden files and sensitive directories
if (preg_match('#^/(\.git|\.env|config/|database/.*\.sqlite|database/.*\.sql|.*\.db)#i', $uri)) {
    http_response_code(403);
    echo "403 Forbidden: Access Denied";
    exit;
}

$requestedFile = __DIR__ . $uri;

// 1. Static file check (CSS, JS, Images, Fonts, Archives, DMGs, etc.)
if ($uri !== '/' && is_file($requestedFile)) {
    $ext = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    if ($ext !== 'php') {
        // Let PHP built-in server serve static files with native MIME types
        return false;
    }
    // Direct PHP file execution (e.g. /admin/software.php, /admin/login.php)
    $_SERVER['SCRIPT_NAME'] = $uri;
    require $requestedFile;
    exit;
}

// 2. Directory check with index.php (e.g. /admin or /admin/)
if (is_dir($requestedFile)) {
    $indexFile = rtrim($requestedFile, '/') . '/index.php';
    if (file_exists($indexFile)) {
        $_SERVER['SCRIPT_NAME'] = rtrim($uri, '/') . '/index.php';
        require $indexFile;
        exit;
    }
}

// 3. Clean Route: Software Detail Page (/app/{slug})
if (preg_match('#^/app/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/app.php';
    require __DIR__ . '/app.php';
    exit;
}

// 4. Clean Route: Category Browse Page (/category/{slug})
if (preg_match('#^/category/([a-zA-Z0-9_-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/category.php';
    require __DIR__ . '/category.php';
    exit;
}

// 5. Clean Route: Safe Download Stream (/download/{id})
if (preg_match('#^/download/([0-9]+)/?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/download.php';
    require __DIR__ . '/download.php';
    exit;
}

// 6. Clean Route: Search Page (/search)
if (preg_match('#^/search/?$#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/search.php';
    require __DIR__ . '/search.php';
    exit;
}

// 7. Clean Route: Request Software (/request)
if (preg_match('#^/request/?$#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/request.php';
    require __DIR__ . '/request.php';
    exit;
}

// 8. Clean Route: Admin Portal (/admin)
if (preg_match('#^/admin/?$#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
    require __DIR__ . '/admin/index.php';
    exit;
}

// 9. Root fallback or 404
if ($uri === '/' || $uri === '/index.html') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . '/index.php';
    exit;
}

// Default fallback
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
