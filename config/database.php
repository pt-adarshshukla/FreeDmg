<?php
/**
 * FreeDmg - Database Configuration & PDO Connection
 * Supports MySQL / MariaDB (standard cPanel/PHP hosting) and SQLite (zero-config local/shared).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration settings
// For MySQL, fill in the parameters below. If DB_TYPE is 'mysql' and connection fails or host is empty,
// it will gracefully fall back to SQLite stored in database/freedmg.sqlite.
define('DB_TYPE', 'sqlite'); // Options: 'mysql' or 'sqlite'

// MySQL Settings (for cPanel / Web Hosting)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'freedmg_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// SQLite Path
define('SQLITE_FILE', __DIR__ . '/../database/freedmg.sqlite');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $db_dir = dirname(SQLITE_FILE);
    if (!is_dir($db_dir)) {
        @mkdir($db_dir, 0755, true);
    }

    try {
        if (DB_TYPE === 'mysql' && !empty(DB_HOST)) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        }
    } catch (PDOException $e) {
        // If MySQL fails, fallback to SQLite for local development or zero-config hosting
        error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite.");
    }

    // Default or Fallback: SQLite
    try {
        $dsn = "sqlite:" . SQLITE_FILE;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Enable foreign keys and WAL mode in SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $pdo->exec("PRAGMA journal_mode = WAL;");
        return $pdo;
    } catch (PDOException $e) {
        die("Fatal Database Connection Error: " . htmlspecialchars($e->getMessage()));
    }
}

// Auto initialize tables if not exists
function ensure_database_initialized() {
    $pdo = get_db_connection();
    
    // Check if software table exists
    $tableExists = false;
    try {
        $check = $pdo->query("SELECT 1 FROM software LIMIT 1");
        if ($check !== false) {
            $tableExists = true;
        }
    } catch (Exception $e) {
        $tableExists = false;
    }

    if (!$tableExists) {
        require_once __DIR__ . '/../database/seed.php';
        seed_default_database($pdo);
    }
}
