<?php
/**
 * FreeDmg - Admin System Settings & Password Manager
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();
$user = get_logged_in_user();

$error = '';
$success = '';

// Handle General Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $siteName = sanitize_text($_POST['site_name'] ?? 'FreeDmg');
    $siteTitle = sanitize_text($_POST['site_title'] ?? '');
    $siteTagline = sanitize_text($_POST['site_tagline'] ?? '');
    $developerName = sanitize_text($_POST['developer_name'] ?? 'Adarsh Shukla');
    $downloadDelay = intval($_POST['download_delay_seconds'] ?? 3);
    $contactEmail = sanitize_text($_POST['contact_email'] ?? '');
    $twitterUrl = sanitize_text($_POST['twitter_url'] ?? '');
    $githubUrl = sanitize_text($_POST['github_url'] ?? '');
    $discordUrl = sanitize_text($_POST['discord_url'] ?? '');

    update_setting('site_name', $siteName);
    update_setting('site_title', $siteTitle);
    update_setting('site_tagline', $siteTagline);
    update_setting('developer_name', $developerName);
    update_setting('download_delay_seconds', (string)$downloadDelay);
    update_setting('contact_email', $contactEmail);
    update_setting('twitter_url', $twitterUrl);
    update_setting('github_url', $githubUrl);
    update_setting('discord_url', $discordUrl);

    set_flash_message('success', 'General site settings saved successfully.');
    header("Location: settings.php");
    exit;
}

// Handle Admin Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser || !password_verify($currentPass, $dbUser['password_hash'])) {
        $error = "Current password does not match our records.";
    } elseif (strlen($newPass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $error = "New password confirmation does not match.";
    } else {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        set_flash_message('success', 'Admin password updated successfully.');
        header("Location: settings.php");
        exit;
    }
}

$pageTitle = "System Settings";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch current values
$siteNameVal = get_setting('site_name', 'FreeDmg');
$siteTitleVal = get_setting('site_title', 'FreeDmg - The Ultimate Mac Software Hub');
$siteTaglineVal = get_setting('site_tagline', 'High-performance software distribution engineered for speed.');
$devNameVal = get_setting('developer_name', 'Adarsh Shukla');
$downloadDelayVal = get_setting('download_delay_seconds', '3');
$contactEmailVal = get_setting('contact_email', 'contact@freedmg.local');
$twitterUrlVal = get_setting('twitter_url', 'https://twitter.com');
$githubUrlVal = get_setting('github_url', 'https://github.com');
$discordUrlVal = get_setting('discord_url', 'https://discord.com');
?>

<!-- Header -->
<div class="mb-6">
    <h2 class="text-xl font-bold text-on-surface">Platform Settings & Security</h2>
    <p class="text-xs text-outline mt-0.5">Configure global repository properties, developer credits, and admin password.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="mb-6 p-4 rounded-xl bg-error/15 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left 2 Cols: General Settings -->
    <div class="lg:col-span-2 space-y-6">
        <div class="glass-panel rounded-2xl p-6 border border-subtle">
            <h3 class="text-sm font-bold uppercase tracking-wider text-primary mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">tune</span>
                General Configuration
            </h3>

            <form method="POST" action="settings.php" class="space-y-4">
                <input type="hidden" name="action" value="save_settings">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($siteNameVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Developer Name Credit</label>
                        <input type="text" name="developer_name" value="<?= htmlspecialchars($devNameVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">SEO Title Tag</label>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($siteTitleVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Tagline / Meta Description</label>
                    <textarea name="site_tagline" rows="2" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none"><?= htmlspecialchars($siteTaglineVal) ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-subtle">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Download Timer (Seconds)</label>
                        <input type="number" min="0" max="30" name="download_delay_seconds" value="<?= htmlspecialchars($downloadDelayVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Contact Email</label>
                        <input type="email" name="contact_email" value="<?= htmlspecialchars($contactEmailVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div class="pt-2 border-t border-subtle space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-outline">Social & Community Links</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-outline mb-1">Twitter / X URL</label>
                            <input type="url" name="twitter_url" value="<?= htmlspecialchars($twitterUrlVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-3 py-2 text-xs text-on-surface focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-outline mb-1">GitHub URL</label>
                            <input type="url" name="github_url" value="<?= htmlspecialchars($githubUrlVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-3 py-2 text-xs text-on-surface focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-outline mb-1">Discord URL</label>
                            <input type="url" name="discord_url" value="<?= htmlspecialchars($discordUrlVal) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-3 py-2 text-xs text-on-surface focus:border-primary outline-none">
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn-electric px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow-md cursor-pointer">
                        Save Site Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right 1 Col: Admin Password Change -->
    <div class="space-y-6">
        <div class="glass-panel rounded-2xl p-6 border border-subtle h-fit">
            <h3 class="text-sm font-bold uppercase tracking-wider text-warning mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                Change Admin Password
            </h3>

            <form method="POST" action="settings.php" class="space-y-4">
                <input type="hidden" name="action" value="change_password">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Current Password</label>
                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">New Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <button type="submit" class="w-full bg-surface-container hover:bg-surface-high border border-subtle text-on-surface py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                    Update Password
                </button>
            </form>
        </div>

        <!-- Hosting Diagnostic Card -->
        <div class="glass-panel rounded-2xl p-6 border border-subtle text-xs text-outline space-y-2">
            <h4 class="font-bold text-on-surface uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[16px]">dns</span>
                Server Environment
            </h4>
            <p>PHP Version: <strong class="text-on-surface"><?= PHP_VERSION ?></strong></p>
            <p>Database Engine: <strong class="text-on-surface"><?= strtoupper($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) ?></strong></p>
            <p>Upload Max Filesize: <strong class="text-on-surface"><?= ini_get('upload_max_filesize') ?></strong></p>
            <p>Post Max Size: <strong class="text-on-surface"><?= ini_get('post_max_size') ?></strong></p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
