<?php
/**
 * FreeDmg - Admin Suite Header Template
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_auth();

$user = get_logged_in_user();
$siteName = get_setting('site_name', 'FreeDmg');
$baseUrl = get_base_url();

$pdo = get_db_connection();
$pendingRequestsCount = 0;
try {
    $pendingRequestsCount = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
} catch (Exception $e) {}

$activePage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Portal') ?> - <?= htmlspecialchars($siteName) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
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
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <script>
        const savedTheme = localStorage.getItem('freedmg_theme') || 'dark';
        document.documentElement.className = savedTheme;
    </script>
</head>
<body class="bg-bg-deep text-on-surface min-h-screen flex selection:bg-primary selection:text-on-primary">

<!-- Admin Left Sidebar -->
<aside class="w-64 bg-surface border-r border-subtle hidden lg:flex flex-col shrink-0 min-h-screen sticky top-0 z-40">
    <!-- Brand Header -->
    <div class="p-6 border-b border-subtle flex items-center justify-between">
        <a href="<?= $baseUrl ?>/admin/index.php" class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-primary text-3xl">admin_panel_settings</span>
            <div>
                <span class="text-xl font-extrabold tracking-tight text-on-surface"><?= htmlspecialchars($siteName) ?></span>
                <span class="block text-[10px] uppercase tracking-widest text-primary font-bold">Admin Console</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-grow p-4 space-y-1 text-sm font-medium">
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $activePage === 'index.php' ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            <span>Dashboard</span>
        </a>

        <a href="software.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= in_array($activePage, ['software.php', 'software-edit.php']) ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[20px]">apps</span>
            <span>Software Catalog</span>
        </a>

        <a href="software-add.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $activePage === 'software-add.php' ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[20px]">add_box</span>
            <span>Add New Software</span>
        </a>

        <a href="categories.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $activePage === 'categories.php' ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[20px]">category</span>
            <span>Categories</span>
        </a>

        <a href="requests.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-colors <?= $activePage === 'requests.php' ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[20px]">mark_email_unread</span>
                <span>User Requests</span>
            </div>
            <?php if ($pendingRequestsCount > 0): ?>
                <span class="bg-warning text-black text-[10px] font-extrabold px-2 py-0.5 rounded-full"><?= $pendingRequestsCount ?></span>
            <?php endif; ?>
        </a>

        <a href="settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors <?= $activePage === 'settings.php' ? 'bg-primary text-on-primary font-bold shadow' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>">
            <span class="material-symbols-outlined text-[20px]">settings</span>
            <span>Site Settings</span>
        </a>
    </nav>

    <!-- Admin Footer Section -->
    <div class="p-4 border-t border-subtle">
        <div class="flex items-center justify-between mb-3 px-2">
            <div class="flex items-center gap-2 overflow-hidden">
                <div class="w-8 h-8 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                    <?= strtoupper(substr($user['username'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-bold text-on-surface truncate"><?= htmlspecialchars($user['username'] ?? 'Admin') ?></p>
                    <p class="text-[10px] text-outline">Administrator</p>
                </div>
            </div>
            
            <div class="flex items-center gap-1">
                <!-- Theme Toggle -->
                <button class="theme-toggle-btn p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors" title="Toggle Theme">
                    <span class="material-symbols-outlined theme-toggle-icon text-[18px]">light_mode</span>
                </button>

                <!-- Logout Link -->
                <a href="logout.php" class="text-outline hover:text-error transition-colors p-1.5 rounded-lg hover:bg-surface-container" title="Logout">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </a>
            </div>
        </div>

        <a href="<?= $baseUrl ?>/index.php" target="_blank" class="w-full flex items-center justify-center gap-1.5 py-2 rounded-xl bg-surface-container hover:bg-surface-high border border-subtle text-xs font-semibold text-outline hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
            <span>View Public Website</span>
        </a>
    </div>
</aside>

<!-- Main Admin Content Area -->
<div class="flex-grow flex flex-col min-w-0 min-h-screen">

    <!-- Mobile Topbar Only (Hidden on Desktop) -->
    <header class="h-14 bg-surface/90 backdrop-blur-md border-b border-subtle px-4 flex lg:hidden items-center justify-between sticky top-0 z-30">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-2xl">admin_panel_settings</span>
            <span class="text-base font-bold text-on-surface"><?= htmlspecialchars($siteName) ?></span>
        </div>

        <div class="flex items-center gap-2">
            <button class="theme-toggle-btn p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-on-surface transition-colors" title="Toggle Theme">
                <span class="material-symbols-outlined theme-toggle-icon text-[18px]">light_mode</span>
            </button>
            <a href="logout.php" class="p-1.5 rounded-lg hover:bg-error/10 text-error transition-colors" title="Logout">
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </a>
        </div>
    </header>

    <!-- Page Body Container -->
    <main class="flex-grow p-6 md:p-8 max-w-7xl w-full mx-auto">
        <?php
        $flash = get_flash_message();
        if ($flash):
        ?>
            <div class="mb-6 p-4 rounded-xl border flex items-center justify-between <?= $flash['type'] === 'success' ? 'bg-success/15 border-success/30 text-success' : 'bg-error/15 border-error/30 text-error' ?>">
                <div class="flex items-center gap-2.5 text-sm font-semibold">
                    <span class="material-symbols-outlined"><?= $flash['type'] === 'success' ? 'check_circle' : 'error' ?></span>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-sm opacity-70 hover:opacity-100">&times;</button>
            </div>
        <?php endif; ?>
