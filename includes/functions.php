<?php
/**
 * FreeDmg - Core Functions & Utility Library
 */

require_once __DIR__ . '/../config/database.php';

// Ensure database tables exist
ensure_database_initialized();

/**
 * Get setting value from database
 */
function get_setting($key, $default = '') {
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Update setting value
 */
function update_setting($key, $value) {
    $pdo = get_db_connection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    } else {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    }
    return $stmt->execute([$key, $value]);
}

/**
 * Clean & Sanitize user inputs
 */
function sanitize_text($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL friendly slug
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Generate Format Badge HTML with styled colors
 */
function get_format_badge($format) {
    $format = strtoupper(trim($format));
    switch ($format) {
        case 'DMG':
            return '<span class="badge-format badge-dmg">DMG</span>';
        case 'ZIP':
            return '<span class="badge-format badge-zip">ZIP</span>';
        case 'RAR':
            return '<span class="badge-format badge-rar">RAR</span>';
        case 'PKG':
            return '<span class="badge-format badge-pkg">PKG</span>';
        default:
            return '<span class="badge-format badge-default">' . htmlspecialchars($format) . '</span>';
    }
}

/**
 * Generate Architecture Badge
 */
function get_arch_badge($arch) {
    return '<span class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-surface-container border border-subtle text-on-surface-variant flex items-center gap-1.5"><span class="material-symbols-outlined text-[13px] text-primary">memory</span>' . htmlspecialchars($arch) . '</span>';
}

/**
 * Resolve full valid Icon URL
 */
function resolve_icon_url($iconUrl) {
    if (empty($iconUrl)) return '';
    $iconUrl = trim($iconUrl);
    if (strpos($iconUrl, 'http://') === 0 || strpos($iconUrl, 'https://') === 0 || strpos($iconUrl, '//') === 0 || strpos($iconUrl, 'data:') === 0) {
        return $iconUrl;
    }
    return get_base_url() . '/' . ltrim($iconUrl, '/');
}

/**
 * Generate Beautiful Software Icon HTML with automatic gradient fallback
 */
function get_software_icon_html($app, $sizeClass = 'w-16 h-16', $roundedClass = 'rounded-2xl', $extraClass = '') {
    $title = $app['title'] ?? 'App';
    $iconUrl = resolve_icon_url($app['icon_url'] ?? '');
    
    // Choose dynamic gradient based on app title hash
    $gradients = [
        ['from' => 'from-blue-600 to-indigo-800', 'text' => 'text-blue-200', 'icon' => 'terminal'],
        ['from' => 'from-purple-600 to-pink-700', 'text' => 'text-purple-200', 'icon' => 'palette'],
        ['from' => 'from-emerald-600 to-teal-800', 'text' => 'text-emerald-200', 'icon' => 'code'],
        ['from' => 'from-amber-600 to-orange-700', 'text' => 'text-amber-200', 'icon' => 'build'],
        ['from' => 'from-cyan-600 to-blue-700', 'text' => 'text-cyan-200', 'icon' => 'laptop_mac'],
        ['from' => 'from-rose-600 to-red-800', 'text' => 'text-rose-200', 'icon' => 'sports_esports']
    ];
    $idx = abs(crc32($title)) % count($gradients);
    $grad = $gradients[$idx];

    $fallbackHtml = '
        <div class="' . $sizeClass . ' ' . $roundedClass . ' bg-gradient-to-br ' . $grad['from'] . ' flex flex-col items-center justify-center border border-white/20 shadow-lg shrink-0 relative overflow-hidden group-hover:scale-105 transition-transform duration-300 ' . $extraClass . '">
            <div class="absolute inset-0 bg-white/10 opacity-50 backdrop-blur-[1px]"></div>
            <span class="text-white font-extrabold text-lg tracking-tight z-10 drop-shadow-md">' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 2) ?: 'AP') . '</span>
        </div>';

    if (!empty($iconUrl)) {
        return '
            <div class="relative shrink-0 ' . $sizeClass . '">
                <img class="' . $sizeClass . ' ' . $roundedClass . ' object-cover shadow-lg border border-subtle group-hover:scale-105 transition-transform duration-300 ' . $extraClass . '" 
                     src="' . htmlspecialchars($iconUrl) . '" 
                     alt="' . htmlspecialchars($title) . '" 
                     onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
                <div style="display:none;" class="w-full h-full">' . $fallbackHtml . '</div>
            </div>';
    }

    return $fallbackHtml;
}

/**
 * Format bytes to readable size (MB, GB, etc.)
 */
function format_file_size_bytes($bytes, $precision = 1) {
    if ($bytes <= 0) return '0 MB';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get Base URL of the website (Supports root domains, MAMP subdirectories, custom ports, and SSL)
 */
function get_base_url() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Normalize path across OS and subdirectories
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $script = rtrim($script, '/');
    
    // Pop /admin subdirectory to always point to root application
    if (preg_match('#/admin$#i', $script)) {
        $script = preg_replace('#/admin$#i', '', $script);
    }
    
    return rtrim($protocol . $host . $script, '/');
}

/**
 * Flash Message Notifications
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * CSRF Protection
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safe Download Logger & Counter Incrementor
 */
function increment_download_count($software_id) {
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->prepare("UPDATE software SET downloads_count = downloads_count + 1 WHERE id = ?");
        $stmt->execute([$software_id]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $logStmt = $pdo->prepare("INSERT INTO download_logs (software_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $logStmt->execute([$software_id, $ip, substr($ua, 0, 500)]);
    } catch (Exception $e) {
        error_log("Failed to log download: " . $e->getMessage());
    }
}
