-- ==========================================================
-- FreeDmg Database Schema
-- Compatible with MySQL (5.7+, 8.0+) and SQLite 3
-- ==========================================================

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'folder',
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Users / Admin Table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'admin',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- Software Applications Table
CREATE TABLE IF NOT EXISTS software (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    version VARCHAR(50) DEFAULT '1.0.0',
    format VARCHAR(20) DEFAULT 'DMG', -- DMG, ZIP, RAR, PKG
    file_size VARCHAR(50) DEFAULT '100 MB',
    architecture VARCHAR(100) DEFAULT 'Apple Silicon & Intel', -- Apple Silicon, Intel, Universal
    min_macos VARCHAR(100) DEFAULT 'macOS 12.0 or later',
    icon_url TEXT,
    file_path TEXT, -- Local upload path
    external_download_url TEXT, -- External direct / cloud link
    short_description TEXT,
    full_description TEXT,
    downloads_count INT DEFAULT 0,
    is_featured INT DEFAULT 0, -- 1 for featured, 0 for normal
    is_active INT DEFAULT 1, -- 1 for published, 0 for draft
    release_date VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Software Screenshots Table
CREATE TABLE IF NOT EXISTS software_screenshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_id INT NOT NULL,
    image_url TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE
);

-- Software Requests Table
CREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_name VARCHAR(200) NOT NULL,
    version VARCHAR(50),
    category VARCHAR(100),
    note TEXT,
    contact VARCHAR(100),
    status VARCHAR(30) DEFAULT 'Pending', -- Pending, Completed, Rejected
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Site Settings Table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

-- Download Audit Logs
CREATE TABLE IF NOT EXISTS download_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    software_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for maximum query performance
CREATE INDEX IF NOT EXISTS idx_software_slug ON software(slug);
CREATE INDEX IF NOT EXISTS idx_software_category ON software(category_id);
CREATE INDEX IF NOT EXISTS idx_software_format ON software(format);
CREATE INDEX IF NOT EXISTS idx_software_downloads ON software(downloads_count);
