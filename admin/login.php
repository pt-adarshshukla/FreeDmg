<?php
/**
 * FreeDmg - Admin Portal Login
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to dashboard
if (is_admin_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if (login_admin($username, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid credentials. Please verify your ID and password.";
        }
    }
}

$siteName = get_setting('site_name', 'FreeDmg');
$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($siteName) ?> - Admin Portal Login</title>
    
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
                        "error": "var(--color-error)",
                        "border-subtle": "var(--border-subtle)",
                        "bg-deep": "var(--bg-base)"
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    
    <style>
        .ambient-bg {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(75, 142, 255, 0.08) 0%, transparent 55%);
            animation: pulse 8s infinite alternate;
            z-index: 0;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 1; }
        }

        .input-glow:focus {
            border-color: #adc6ff;
            box-shadow: 0 0 15px rgba(173, 198, 255, 0.25);
            outline: none;
        }
    </style>
</head>
<body class="bg-bg-deep text-on-surface min-h-screen flex items-center justify-center relative overflow-hidden selection:bg-primary selection:text-on-primary">

<div class="ambient-bg"></div>

<!-- Theme toggle in top right -->
<div class="absolute top-6 right-6 z-20">
    <button class="theme-toggle-btn p-2.5 rounded-full hover:bg-surface-container border border-subtle text-on-surface-variant hover:text-primary transition-colors" title="Toggle Theme">
        <span class="material-symbols-outlined theme-toggle-icon text-[20px]">light_mode</span>
    </button>
</div>

<!-- Back to site in top left -->
<div class="absolute top-6 left-6 z-20">
    <a href="<?= $baseUrl ?>/index.php" class="flex items-center gap-1.5 text-xs uppercase font-bold tracking-wider text-outline hover:text-primary transition-colors">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Return to Website
    </a>
</div>

<main class="w-full max-w-md px-4 relative z-10 my-8">
    <div class="glass-panel rounded-3xl p-8 md:p-10 w-full flex flex-col items-center shadow-2xl border border-subtle">
        
        <!-- Branding Header -->
        <div class="mb-8 flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center mb-4 text-primary">
                <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">admin_panel_settings</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface"><?= htmlspecialchars($siteName) ?></h1>
            <p class="text-sm font-semibold text-primary mt-1 uppercase tracking-wider">Admin Portal Control</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="w-full mb-6 p-4 rounded-xl bg-error/15 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
                <span class="material-symbols-outlined text-base">error</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" class="w-full flex flex-col gap-5">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-outline uppercase tracking-wider" for="username">ID (Username)</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">person</span>
                    <input class="w-full bg-surface-container border border-subtle rounded-xl py-3 pl-11 pr-4 text-sm text-on-surface placeholder:text-outline input-glow transition-all outline-none" id="username" name="username" placeholder="FreeDmg" required type="text" autofocus autocomplete="username" />
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-outline uppercase tracking-wider" for="password">Password</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px]">lock</span>
                    <input class="w-full bg-surface-container border border-subtle rounded-xl py-3 pl-11 pr-4 text-sm text-on-surface placeholder:text-outline input-glow transition-all outline-none" id="password" name="password" placeholder="••••••••" required type="password" autocomplete="current-password" />
                </div>
            </div>

            <button class="btn-electric w-full rounded-xl py-3.5 mt-2 flex items-center justify-center gap-2 text-sm font-bold uppercase tracking-wider text-white shadow-lg cursor-pointer" type="submit">
                <span>Secure Login</span>
                <span class="material-symbols-outlined text-[18px]">login</span>
            </button>
        </form>

        <!-- Security Note -->
        <div class="mt-8 flex items-center gap-2 text-outline text-xs font-medium">
            <span class="material-symbols-outlined text-[16px] text-warning">shield</span>
            <span class="uppercase tracking-wider">Authorized Administrative Access</span>
        </div>
    </div>
</main>

<script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
