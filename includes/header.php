<?php
/**
 * FreeDmg - Global Frontend Header Template
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$siteName = get_setting('site_name', 'FreeDmg');
$siteTitle = $pageTitle ?? get_setting('site_title', 'FreeDmg - The Ultimate Mac Software Hub');
$siteTagline = get_setting('site_tagline', 'High-performance software distribution engineered for speed.');
$baseUrl = get_base_url();

// Fetch active categories for navigation
$pdo = get_db_connection();
$navCategories = [];
try {
    $navCategories = $pdo->query("SELECT name, slug FROM categories ORDER BY sort_order ASC, name ASC LIMIT 6")->fetchAll();
} catch (Exception $e) {
    $navCategories = [];
}

$flashMessage = get_flash_message();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? $siteTagline) ?>">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Custom Theme Tokens -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-container": "var(--bg-surface-container)",
                        "surface": "var(--bg-surface)",
                        "surface-low": "var(--bg-surface-low)",
                        "surface-high": "var(--bg-surface-high)",
                        "on-surface": "var(--text-primary)",
                        "on-surface-variant": "var(--text-dim)",
                        "outline": "var(--text-muted)",
                        "primary": "var(--brand-primary)",
                        "primary-container": "var(--brand-primary-container)",
                        "secondary": "var(--accent-secondary)",
                        "tertiary": "var(--accent-tertiary)",
                        "success": "var(--color-success)",
                        "warning": "var(--color-warning)",
                        "error": "var(--color-error)",
                        "border-subtle": "var(--border-subtle)",
                        "bg-deep": "var(--bg-base)"
                    },
                    spacing: {
                        "gutter": "24px",
                        "container-max": "1280px",
                        "stack-lg": "32px",
                        "stack-md": "16px",
                        "stack-sm": "8px"
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <script>
        // Apply theme immediately to prevent flashing
        const savedTheme = localStorage.getItem('freedmg_theme') || 'dark';
        document.documentElement.className = savedTheme;
    </script>
</head>
<body class="bg-bg-deep text-on-surface min-h-screen flex flex-col selection:bg-primary selection:text-on-primary">

<!-- Top Navigation Bar -->
<nav class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-4 md:px-gutter py-3.5 h-20 bg-surface/80 backdrop-blur-xl border-b border-subtle">
    <!-- Left: Logo & Search Quick Trigger -->
    <div class="flex items-center gap-6">
        <a class="text-2xl md:text-3xl font-extrabold tracking-tighter text-primary scale-95 active:scale-90 transition-transform" href="<?= $baseUrl ?>/index.php">
            <?= htmlspecialchars($siteName) ?>
        </a>
        
        <button data-open-search class="hidden md:flex items-center gap-2 bg-surface-container hover:bg-surface-high border border-subtle rounded-full px-3.5 py-1.5 transition-colors text-left cursor-pointer group">
            <span class="material-symbols-outlined text-outline group-hover:text-primary text-[18px]">search</span>
            <span class="text-xs text-outline group-hover:text-on-surface font-medium w-36">Search software...</span>
            <kbd class="text-[10px] bg-surface-low border border-subtle px-1.5 py-0.5 rounded text-outline font-mono">⌘K</kbd>
        </button>
    </div>

    <!-- Center: Category Navigation Links -->
    <div class="hidden lg:flex items-center gap-6">
        <?php foreach ($navCategories as $cat): ?>
            <a class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant hover:text-primary transition-colors px-2 py-1" href="<?= $baseUrl ?>/category.php?slug=<?= urlencode($cat['slug']) ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Right: Actions (Search Mobile, Theme Toggle, Request, Admin) -->
    <div class="flex items-center gap-3">
        <!-- Mobile Search Button -->
        <button data-open-search class="md:hidden p-2 rounded-full hover:bg-surface-container text-outline hover:text-on-surface transition-colors" title="Search">
            <span class="material-symbols-outlined text-[22px]">search</span>
        </button>

        <!-- Theme Toggle Button -->
        <button class="theme-toggle-btn p-2 rounded-full hover:bg-surface-container border border-subtle text-on-surface-variant hover:text-primary transition-colors" title="Toggle Light/Dark Theme">
            <span class="material-symbols-outlined theme-toggle-icon text-[20px]">light_mode</span>
        </button>

        <!-- Request Software Button -->
        <button data-open-request class="btn-electric text-xs uppercase font-bold tracking-wider px-5 py-2 rounded-full transition-all duration-200 flex items-center gap-1.5 shadow-md">
            <span class="material-symbols-outlined text-[16px]">add_circle</span>
            <span>Request</span>
        </button>

        <?php if (is_admin_logged_in()): ?>
            <a href="<?= $baseUrl ?>/admin/index.php" class="hidden sm:inline-flex items-center gap-1.5 bg-primary/10 border border-primary/30 text-primary text-xs font-semibold px-3 py-1.5 rounded-full hover:bg-primary/20 transition-colors">
                <span class="material-symbols-outlined text-[16px]">dashboard</span>
                <span>Admin</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Flash Notification Banner -->
<?php if ($flashMessage): ?>
    <div class="pt-24 px-4 max-w-container-max mx-auto w-full">
        <div class="p-4 rounded-xl border flex items-center justify-between <?= $flashMessage['type'] === 'success' ? 'bg-success/10 border-success/30 text-success' : 'bg-error/10 border-error/30 text-error' ?>">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined"><?= $flashMessage['type'] === 'success' ? 'check_circle' : 'error' ?></span>
                <span class="text-sm font-medium"><?= htmlspecialchars($flashMessage['message']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-sm opacity-70 hover:opacity-100">&times;</button>
        </div>
    </div>
<?php endif; ?>
