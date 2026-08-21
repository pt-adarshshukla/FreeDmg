<?php
/**
 * FreeDmg - Authentication & Session Handler
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if admin is currently authenticated
 */
function is_admin_logged_in() {
    return !empty($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
}

/**
 * Require admin authentication on protected pages
 */
function require_admin_auth() {
    if (!is_admin_logged_in()) {
        $loginUrl = get_base_url() . '/admin/login.php';
        header("Location: " . $loginUrl);
        exit;
    }
}

/**
 * Attempt admin login with username & password
 */
function login_admin($username, $password) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Successful login
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'admin'
        ];

        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$user['id']]);

        // Regenerate session id for security
        session_regenerate_id(true);
        return true;
    }

    return false;
}

/**
 * Logout admin user
 */
function logout_admin() {
    unset($_SESSION['admin_user']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Get active admin user details
 */
function get_logged_in_user() {
    return $_SESSION['admin_user'] ?? null;
}
