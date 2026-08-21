<?php
/**
 * FreeDmg - Database Seeder
 * Populates initial schema, categories, default admin (FreeDmg / freedmg@2007), and software catalog.
 */

require_once __DIR__ . '/../config/database.php';

function seed_default_database($pdo) {
    // 1. Create Tables
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        
        // If MySQL is used, convert AUTOINCREMENT to AUTO_INCREMENT
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
            $sql = str_replace('DATETIME DEFAULT CURRENT_TIMESTAMP', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $sql);
        }
        
        // Execute statements
        $pdo->exec($sql);
    }

    // 2. Seed Default Admin User: FreeDmg / freedmg@2007
    $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $checkUser->execute(['FreeDmg']);
    if ($checkUser->fetchColumn() == 0) {
        $hashedPass = password_hash('freedmg@2007', PASSWORD_BCRYPT);
        $insertAdmin = $pdo->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
        $insertAdmin->execute(['FreeDmg', $hashedPass, 'admin@freedmg.local', 'admin']);
    }

    // 3. Seed Default Settings
    $defaultSettings = [
        'site_name' => 'FreeDmg',
        'site_title' => 'FreeDmg - The Ultimate Mac Software Hub',
        'site_tagline' => 'High-performance software distribution engineered for speed. Access DMG, ZIP, RAR, and PKG releases safely.',
        'developer_name' => 'Adarsh Shukla',
        'download_delay_seconds' => '4',
        'contact_email' => 'contact@freedmg.local',
        'allow_requests' => '1',
        'twitter_url' => 'https://twitter.com',
        'github_url' => 'https://github.com',
        'discord_url' => 'https://discord.com',
        'telegram_url' => 'https://telegram.org',
    ];

    $stmtSetting = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $stmtSetting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    }

    foreach ($defaultSettings as $k => $v) {
        $stmtSetting->execute([$k, $v]);
    }

    // 4. Seed Categories
    $categories = [
        ['Development', 'development', 'terminal', 'Code editors, IDEs, compilers, dev tools and CLI utilities.', 1],
        ['Graphics', 'graphics', 'palette', 'Photo editing, 3D rendering, vector graphics, and motion design.', 2],
        ['Utilities', 'utilities', 'build', 'System optimizers, archivers, cleaners, and automation software.', 3],
        ['Multimedia', 'multimedia', 'movie', 'Video editors, converters, streaming tools, and media players.', 4],
        ['Productivity', 'productivity', 'trending_up', 'Office suites, notes, organization, and task management.', 5],
        ['Games', 'games', 'sports_esports', 'High performance native macOS games, emulators, and gaming tools.', 6],
        ['Audio & Music', 'audio-music', 'music_note', 'DAWs, synthesizers, audio mastering, and recording tools.', 7],
        ['Live Wallpapers', 'live-wallpapers', 'wallpaper', 'Dynamic desktop enhancements and visual customizers.', 8]
    ];

    $checkCat = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($checkCat == 0) {
        $stmtCat = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($categories as $cat) {
            $stmtCat->execute($cat);
        }
    }

    // 5. Seed Default Software
    $checkSoftware = $pdo->query("SELECT COUNT(*) FROM software")->fetchColumn();
    if ($checkSoftware == 0) {
        // Fetch category IDs
        $catMap = [];
        $rows = $pdo->query("SELECT id, slug FROM categories")->fetchAll();
        foreach ($rows as $r) {
            $catMap[$r['slug']] = $r['id'];
        }

        $sampleSoftware = [
            [
                'cat' => 'development',
                'title' => 'JetBrains CLion 2024',
                'slug' => 'jetbrains-clion-2024',
                'version' => '2024.1.2',
                'format' => 'DMG',
                'file_size' => '850 MB',
                'arch' => 'Apple Silicon & Intel',
                'min_macos' => 'macOS 12.0 or later',
                'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBhAUQC4pNtSwWUgXuT5pElMGGKIZqGi9ks-ZIAXvrHIYfQPLfRWJGKOgCAvo8D49UHvlaz-SLSPMGgliYsEZYCZ_Ol0R-jZJVb01mhbnXuUL5n47291S40WuK9GIwfao5UBXOneDLyGoLQdes7jrEVdAffU8qjmhX0ksnuLgSzd_UXHu8ijYqVTMjcDG2VTgynFJ2yFEFDAViMLlnsTZSsrcrlqFaRp4NhIqfta4opYrpKe0OffG19zQ',
                'file_path' => '',
                'ext_url' => 'https://download.jetbrains.com/cpp/CLion-2024.1.2.dmg',
                'short' => 'A cross-platform IDE for C and C++ developers with deep code analysis, CMake integration, and dynamic profiling.',
                'full' => 'CLion is a dedicated C and C++ IDE by JetBrains. It brings smart coding assistance, on-the-fly code generation, instant refactorings, dynamic memory profiling, and built-in remote development capabilities. Optimized natively for Apple Silicon (M1/M2/M3/M4) and Intel Macs.',
                'downloads' => 14820,
                'featured' => 1,
                'release_date' => 'April 18, 2024',
                'screenshots' => [
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuBNzBOyXRPT5EsTmmdE-vfGUNfC2pxMc1vDDrBR0ZwIDA9Rjwm8xIUoAKX3b9fCoE_YYBQQjvMWMKwujyn5fZ7U1zw-NLeaTbDbWaYoaJ-KApiDVFb89UFn3_KN6CcuawdZa3i6yecaAoPyYOOaoP7cJHgqMjnfBLP-8LM9CO3gPu_BBwlbTC2uuvfZRIGrwHILCEO6lIfUv3L6SxL_oEGwJCzol8BlTOH_hT1gJzYqJCxuGpyg9qXJxQ',
                    'https://lh3.googleusercontent.com/aida-public/AB6AXuDVDAJicolbIAN41C8Bm6pLNSl9gwcWGaNy6IBZfDYgCq0PI9UoUIKK4DCgWpXUJufZinar62VyNwKIScbDAvSmyoHkajvrWD4OmiCJ_EBfj5nMCWajyiHqDcuoivy3gQ9-McTN85MIUku9p9VauBpyUQyVgA3T_qqTd7I9AlUO6AbTadQj6y_hAqOdPtR0CRpm8w8wnLtl6nvQphy4Y7AxH6kaPd3K2S1XklezCm1ZQgZrVQX0CKEgog'
                ]
            ],
            [
                'cat' => 'graphics',
                'title' => 'Affinity Designer 2',
                'slug' => 'affinity-designer-2',
                'version' => 'v2.4.0',
                'format' => 'ZIP',
                'file_size' => '620 MB',
                'arch' => 'Universal (M1/M2 & Intel)',
                'min_macos' => 'macOS 11.0 Big Sur or later',
                'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCYZrUXtT-__qP3G2uXrmntO6mZV_RzPdLssOyPvsmHiXcPZkTqrS9vNd-_6OSykM23aRGQyZvDbImly8unudbyYqH_jn_0cUo4bu-gEH4pyOw9Cg_z8RZmxBMAjZBl7_sqyhnnVF6mUymSn_nl10j6a3gsErYNbYZYIqfSx2xgzX7ex6S-FF1oEDxwDiw8vLobHuV0NSB_RwLhYIfFdLG0YIKygue1gDvNGpibcFCpwgAQeuTHg4wK0A',
                'file_path' => '',
                'ext_url' => 'https://store-resources.serif.com/affinity/designer/AffinityDesigner2.zip',
                'short' => 'Next-generation vector graphic design software optimized for concept art, print projects, logos, icons, and UI design.',
                'full' => 'Affinity Designer 2 sets the new industry standard in vector graphics on macOS. Smooth, lightning-fast zooming up to 1,000,000%, live pixel previews, vector warp tools, shape builder tool, and complete non-destructive adjustments.',
                'downloads' => 12400,
                'featured' => 1,
                'release_date' => 'March 25, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'utilities',
                'title' => 'DaisyDisk 4',
                'slug' => 'daisydisk-4',
                'version' => 'v4.32.1',
                'format' => 'DMG',
                'file_size' => '32 MB',
                'arch' => 'Apple Silicon & Intel',
                'min_macos' => 'macOS 10.15 or later',
                'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBXEJoaURw0hbG-DjOj8J1EvnWjNTJFYGh1_g3gQtYQpdFXBgXdGAqOlNUQIYxuQ7n6NnM6PcTeOLwmbiDOPIb9OD8VXxMgS34jHxowqqwDbz7hDE5G-LnnyXeWH9CjB0pNSf0hHUrv36vs2JY89UNPl0qhTYWVHXUynphcDc-LQfasOaNPJfpAT39PO08nxMfVJFdMCH_Z7w---F0mQOFBLVGMMnXiNvPfZWAgqzWOUALOSNbCqv6xCA',
                'file_path' => '',
                'ext_url' => 'https://daisydiskapp.com/download/DaisyDisk.dmg',
                'short' => 'Visual disk space analyzer and cleaner for Mac. Recover gigabytes of wasted storage effortlessly.',
                'full' => 'DaisyDisk gives you a visual interactive map of all connected disks (internal SSD, external drives, Thunderbolt arrays). Discover large hidden files, purge system caches, and free up valuable Mac storage space with a modern circular breakdown.',
                'downloads' => 9320,
                'featured' => 1,
                'release_date' => 'May 02, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'development',
                'title' => 'Warp Terminal',
                'slug' => 'warp-terminal',
                'version' => 'v0.2024.03',
                'format' => 'RAR',
                'file_size' => '88 MB',
                'arch' => 'Apple Silicon Native',
                'min_macos' => 'macOS 12.0 or later',
                'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuALLZklDVMZ5nvMRFP2Xot9wnAGP-G9Sd7HAy6a85bnF4bJvlnqFEIZAjoMPAqIXi0HzeL8sJdKJhNTm5OCo6SokffW6vD2gLSNGxXSriTjr7YjTN9cAZ2yMUaRuO9t5VvN-Ilkysz2VAcQuSeXd99M9gn40DeRaTaleMbav5SAM99xHWmKp8LPSeSvkG5C8XAiZDeQkXC39zomF2Sa6oqghuuy-6Yt6WYzqkz_8vuVW6_Voc2neIwWgQ',
                'file_path' => '',
                'ext_url' => 'https://app.warp.dev/get_warp_mac.rar',
                'short' => 'The 21st-century Rust-based terminal with modern text editor features, AI assistance, and team workflows.',
                'full' => 'Warp reimagines the terminal emulator from the ground up in native Rust and Metal rendering. Features block-based execution, full mouse cursor positioning, smart command suggestions, workflow sharing, and integrated AI generation.',
                'downloads' => 8190,
                'featured' => 1,
                'release_date' => 'May 10, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'graphics',
                'title' => 'Adobe Photoshop 2024',
                'slug' => 'adobe-photoshop-2024',
                'version' => 'v25.5.0',
                'format' => 'PKG',
                'file_size' => '3.8 GB',
                'arch' => 'Universal',
                'min_macos' => 'macOS 13.0 Ventura or later',
                'icon' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCYZrUXtT-__qP3G2uXrmntO6mZV_RzPdLssOyPvsmHiXcPZkTqrS9vNd-_6OSykM23aRGQyZvDbImly8unudbyYqH_jn_0cUo4bu-gEH4pyOw9Cg_z8RZmxBMAjZBl7_sqyhnnVF6mUymSn_nl10j6a3gsErYNbYZYIqfSx2xgzX7ex6S-FF1oEDxwDiw8vLobHuV0NSB_RwLhYIfFdLG0YIKygue1gDvNGpibcFCpwgAQeuTHg4wK0A',
                'file_path' => '',
                'ext_url' => 'https://adobe.com/downloads/photoshop-installer.pkg',
                'short' => 'The world leading digital imaging and photo editing software with next-gen Generative Fill tools.',
                'full' => 'Create stunning photos, rich graphics, and incredible art with Adobe Photoshop 2024 for macOS. Packed with Adobe Firefly generative AI, parametric filters, adjustment presets, and multi-threaded rendering designed specifically for Apple M-series chips.',
                'downloads' => 28400,
                'featured' => 1,
                'release_date' => 'February 12, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'audio-music',
                'title' => 'Ableton Live 12 Suite',
                'slug' => 'ableton-live-12-suite',
                'version' => 'v12.0.5',
                'format' => 'DMG',
                'file_size' => '2.9 GB',
                'arch' => 'Universal',
                'min_macos' => 'macOS 12.0 Monterey or later',
                'icon' => '',
                'file_path' => '',
                'ext_url' => 'https://cdn-downloads.ableton.com/live-12-suite.dmg',
                'short' => 'Fast, fluid, and flexible software for music creation, production, and live electronic audio performance.',
                'full' => 'Ableton Live 12 comes with fresh sound design capabilities, MIDI transformation tools, subtle tuning systems, new synthesizer instruments (Meld, Roar), and seamless workflow improvements designed for modern music producers.',
                'downloads' => 11200,
                'featured' => 0,
                'release_date' => 'March 05, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'multimedia',
                'title' => 'Final Cut Pro X',
                'slug' => 'final-cut-pro-x',
                'version' => 'v10.7.1',
                'format' => 'DMG',
                'file_size' => '4.2 GB',
                'arch' => 'Apple Silicon Optimized',
                'min_macos' => 'macOS 13.5 or later',
                'icon' => '',
                'file_path' => '',
                'ext_url' => 'https://apple.com/final-cut-pro/installer.dmg',
                'short' => 'Apple revolutionary professional video editor with magnetic timeline and high-throughput ProRes encoding.',
                'full' => 'Final Cut Pro combines revolutionary video editing with powerful media organization and incredible performance for 4K and 8K HDR video. Optimized with neural engine machine learning for automatic tracking and voice isolation.',
                'downloads' => 19750,
                'featured' => 1,
                'release_date' => 'January 28, 2024',
                'screenshots' => []
            ],
            [
                'cat' => 'development',
                'title' => 'Visual Studio Code',
                'slug' => 'visual-studio-code',
                'version' => 'v1.89.0',
                'format' => 'ZIP',
                'file_size' => '115 MB',
                'arch' => 'Universal (Apple Silicon & Intel)',
                'min_macos' => 'macOS 10.15 or later',
                'icon' => '',
                'file_path' => '',
                'ext_url' => 'https://code.visualstudio.com/sha/download?build=stable&os=darwin-universal',
                'short' => 'Lightweight, powerful, and customizable source-code editor with built-in debugging, Git, and extensions.',
                'full' => 'Visual Studio Code is a code editor redefined and optimized for building and debugging modern web and cloud applications on macOS. Includes thousands of extensions, IntelliSense, Copilot AI, and terminal integration.',
                'downloads' => 34500,
                'featured' => 0,
                'release_date' => 'May 08, 2024',
                'screenshots' => []
            ]
        ];

        $stmtSoftware = $pdo->prepare("INSERT INTO software 
            (category_id, title, slug, version, format, file_size, architecture, min_macos, icon_url, file_path, external_download_url, short_description, full_description, downloads_count, is_featured, is_active, release_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");

        $stmtScreenshot = $pdo->prepare("INSERT INTO software_screenshots (software_id, image_url, sort_order) VALUES (?, ?, ?)");

        foreach ($sampleSoftware as $item) {
            $catId = $catMap[$item['cat']] ?? 1;
            $stmtSoftware->execute([
                $catId,
                $item['title'],
                $item['slug'],
                $item['version'],
                $item['format'],
                $item['file_size'],
                $item['arch'],
                $item['min_macos'],
                $item['icon'],
                $item['file_path'],
                $item['ext_url'],
                $item['short'],
                $item['full'],
                $item['downloads'],
                $item['featured'],
                $item['release_date']
            ]);

            $softwareId = $pdo->lastInsertId();

            if (!empty($item['screenshots'])) {
                $order = 1;
                foreach ($item['screenshots'] as $sc) {
                    $stmtScreenshot->execute([$softwareId, $sc, $order++]);
                }
            }
        }
    }
}

// If invoked from command line
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $pdo = get_db_connection();
    seed_default_database($pdo);
    echo "Database seeded successfully.\nAdmin: FreeDmg / freedmg@2007\n";
}
