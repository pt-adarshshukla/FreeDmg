<?php
/**
 * FreeDmg - Global Frontend Footer Template
 */
$devName = get_setting('developer_name', 'Adarsh Shukla');
$twitterUrl = get_setting('twitter_url', 'https://twitter.com');
$githubUrl = get_setting('github_url', 'https://github.com');
$discordUrl = get_setting('discord_url', 'https://discord.com');
$siteName = get_setting('site_name', 'FreeDmg');
$siteTagline = get_setting('site_tagline', 'High-performance software distribution engineered for speed.');
$baseUrl = get_base_url();

$isAdmin = is_admin_logged_in();
$adminUser = get_logged_in_user();

// Fetch categories for footer navigation
$footerCategories = [];
try {
    $pdo = get_db_connection();
    $footerCategories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY sort_order ASC, name ASC LIMIT 6")->fetchAll();
} catch (Exception $e) {
    $footerCategories = [];
}
?>
<!-- Footer -->
<footer class="w-full border-t border-subtle bg-surface-container/40 backdrop-blur-xl mt-auto pt-16 pb-12 px-4 md:px-gutter">
    <div class="max-w-container-max mx-auto">
        <!-- Main Footer Grid (4 Columns) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 pb-12 border-b border-subtle">
            
            <!-- Col 1: Brand & Overview -->
            <div class="space-y-4">
                <a class="flex items-center gap-2.5 text-2xl font-extrabold tracking-tighter text-primary group" href="<?= $baseUrl ?>/index.php">
                    <span class="w-7 h-7 rounded-xl bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-bg-deep shadow-md shadow-primary/20">
                        <span class="material-symbols-outlined text-[18px] font-bold" style="font-variation-settings: 'FILL' 1;">cloud_download</span>
                    </span>
                    <span>Free<span class="text-on-surface">Dmg</span></span>
                </a>
                <p class="text-xs text-outline leading-relaxed max-w-sm">
                    <?= htmlspecialchars($siteTagline) ?> Verified macOS disk images and installer packages with instant high-speed mirror delivery.
                </p>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface border border-subtle text-[11px] text-outline">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                    <span>Status: CDN Operational</span>
                </div>
            </div>

            <!-- Col 2: Categories / Browse -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-primary">category</span>
                    Browse Software
                </h3>
                <ul class="space-y-2 text-xs text-outline">
                    <?php if (!empty($footerCategories)): ?>
                        <?php foreach ($footerCategories as $fcat): ?>
                            <li>
                                <a href="<?= $baseUrl ?>/category.php?slug=<?= urlencode($fcat['slug']) ?>" class="hover:text-primary transition-colors flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[15px] text-outline-variant"><?= htmlspecialchars($fcat['icon'] ?: 'folder') ?></span>
                                    <span><?= htmlspecialchars($fcat['name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="<?= $baseUrl ?>/index.php" class="hover:text-primary transition-colors">All Applications</a></li>
                        <li><a href="<?= $baseUrl ?>/category.php?slug=development" class="hover:text-primary transition-colors">Developer Tools</a></li>
                        <li><a href="<?= $baseUrl ?>/category.php?slug=productivity" class="hover:text-primary transition-colors">Productivity</a></li>
                        <li><a href="<?= $baseUrl ?>/category.php?slug=games" class="hover:text-primary transition-colors">Games</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Col 3: Formats & Quick Actions -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-primary">extension</span>
                    Formats & Tools
                </h3>
                <ul class="space-y-2 text-xs text-outline">
                    <li><a href="<?= $baseUrl ?>/index.php?format=DMG" class="hover:text-primary transition-colors flex items-center gap-2"><span class="badge-format badge-dmg text-[9px]">DMG</span> <span>Apple Disk Images</span></a></li>
                    <li><a href="<?= $baseUrl ?>/index.php?format=ZIP" class="hover:text-primary transition-colors flex items-center gap-2"><span class="badge-format badge-zip text-[9px]">ZIP</span> <span>Universal Archives</span></a></li>
                    <li><a href="<?= $baseUrl ?>/index.php?format=RAR" class="hover:text-primary transition-colors flex items-center gap-2"><span class="badge-format badge-rar text-[9px]">RAR</span> <span>Compressed Bundles</span></a></li>
                    <li><a href="<?= $baseUrl ?>/index.php?format=PKG" class="hover:text-primary transition-colors flex items-center gap-2"><span class="badge-format badge-pkg text-[9px]">PKG</span> <span>macOS Installers</span></a></li>
                    <li class="pt-1"><button data-open-request class="hover:text-primary transition-colors flex items-center gap-1.5 text-left"><span class="material-symbols-outlined text-[15px] text-primary">add_circle</span> <span>Request New Software</span></button></li>
                </ul>
            </div>

            <!-- Col 4: DEDICATED ADMIN MANAGEMENT SECTION -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-primary">admin_panel_settings</span>
                    Admin & Management
                </h3>
                
                <?php if ($isAdmin): ?>
                    <!-- Admin Logged In Quick Control Panel -->
                    <div class="p-3.5 rounded-2xl bg-surface border border-primary/30 space-y-2.5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-primary flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px]">verified_user</span>
                                <span><?= htmlspecialchars($adminUser['username'] ?? 'Admin') ?></span>
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-success/15 text-success font-semibold border border-success/30">Active</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 pt-1 text-[11px]">
                            <a href="<?= $baseUrl ?>/admin/index.php" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-high text-on-surface text-center font-medium transition-colors">
                                Dashboard
                            </a>
                            <a href="<?= $baseUrl ?>/admin/software-add.php" class="p-1.5 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary text-center font-semibold transition-colors">
                                + Add App
                            </a>
                            <a href="<?= $baseUrl ?>/admin/software.php" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-high text-on-surface text-center font-medium transition-colors">
                                Catalog
                            </a>
                            <a href="<?= $baseUrl ?>/admin/requests.php" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-high text-on-surface text-center font-medium transition-colors">
                                Requests
                            </a>
                            <a href="<?= $baseUrl ?>/admin/settings.php" class="p-1.5 rounded-lg bg-surface-container hover:bg-surface-high text-on-surface text-center font-medium transition-colors">
                                Settings
                            </a>
                            <a href="<?= $baseUrl ?>/admin/logout.php" class="p-1.5 rounded-lg bg-error/10 hover:bg-error/20 text-error text-center font-medium transition-colors">
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Admin Login Access Card -->
                    <div class="p-4 rounded-2xl bg-surface border border-subtle hover:border-primary/40 transition-colors space-y-3 shadow-sm">
                        <p class="text-xs text-outline leading-relaxed">
                            Authorized personnel portal to upload packages, manage releases, and review user requests.
                        </p>
                        <a href="<?= $baseUrl ?>/admin/login.php" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-surface-container hover:bg-surface-high border border-subtle text-xs font-bold text-on-surface hover:text-primary transition-all group">
                            <span class="material-symbols-outlined text-[16px] text-primary group-hover:scale-110 transition-transform">lock</span>
                            <span>Admin Portal Login</span>
                        </a>
                        <div class="text-[10px] text-outline text-center flex items-center justify-center gap-1 pt-0.5">
                            <span class="material-symbols-outlined text-[12px] text-success">security</span>
                            <span>Zero-Trust Encrypted Session</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Bottom Sub-Footer Bar -->
        <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-outline">
            <div>
                <p>© <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved. <span class="ml-1 text-on-surface font-medium">Developed by <?= htmlspecialchars($devName) ?></span></p>
            </div>
            
            <!-- Social / Quick Links -->
            <div class="flex items-center gap-5 uppercase font-semibold tracking-wider text-[11px]">
                <?php if ($twitterUrl): ?>
                    <a class="hover:text-primary transition-colors" href="<?= htmlspecialchars($twitterUrl) ?>" target="_blank" rel="noopener">Twitter</a>
                <?php endif; ?>
                <?php if ($githubUrl): ?>
                    <a class="hover:text-primary transition-colors" href="<?= htmlspecialchars($githubUrl) ?>" target="_blank" rel="noopener">GitHub</a>
                <?php endif; ?>
                <?php if ($discordUrl): ?>
                    <a class="hover:text-primary transition-colors" href="<?= htmlspecialchars($discordUrl) ?>" target="_blank" rel="noopener">Discord</a>
                <?php endif; ?>
                <a class="hover:text-primary transition-colors flex items-center gap-1" href="<?= $baseUrl ?>/admin/login.php">
                    <span class="material-symbols-outlined text-[14px]">shield</span>
                    <span>Admin</span>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- 1. Global Live Search Modal (⌘K) -->
<div id="search-modal" class="modal-overlay fixed inset-0 z-50 hidden items-start justify-center pt-20 px-4">
    <div class="glass-panel w-full max-w-2xl rounded-2xl p-6 shadow-2xl border border-subtle relative animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between pb-4 border-b border-subtle">
            <div class="flex items-center gap-3 flex-grow">
                <span class="material-symbols-outlined text-primary text-2xl">search</span>
                <input id="modal-search-input" type="text" placeholder="Search macOS applications, DMG, ZIP, PKG..." class="w-full bg-transparent border-none text-on-surface text-lg focus:ring-0 outline-none placeholder:text-outline">
            </div>
            <button onclick="closeSearchModal()" class="text-outline hover:text-on-surface p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="modal-search-results" class="mt-4 max-h-96 overflow-y-auto">
            <p class="text-sm text-outline p-4 text-center">Type at least 2 characters to search applications...</p>
        </div>
    </div>
</div>

<!-- 2. Safe Download Modal (Timer + Save Prompt + Direct Stream) -->
<div id="download-modal" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md rounded-2xl p-8 shadow-2xl border border-subtle text-center relative">
        <button onclick="closeDownloadModal()" class="absolute top-4 right-4 text-outline hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center mx-auto mb-5 text-primary">
            <span class="material-symbols-outlined text-3xl animate-bounce">downloading</span>
        </div>

        <h3 id="dl-modal-title" class="text-xl font-bold text-on-surface mb-1">Preparing Download</h3>
        <p id="dl-modal-meta" class="text-xs text-outline mb-6">Package Integrity Verified</p>

        <!-- Countdown Display -->
        <div class="my-6 flex flex-col items-center">
            <div class="relative w-20 h-20 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                    <path class="text-surface-container" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="text-primary transition-all duration-1000" stroke-dasharray="100, 100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <span id="dl-timer-num" class="absolute text-2xl font-bold text-primary">3</span>
            </div>
        </div>

        <div id="dl-status-msg" class="text-sm min-h-[48px] flex items-center justify-center">
            <span class="text-primary font-medium animate-pulse">Initializing direct transfer...</span>
        </div>

        <div id="dl-direct-link-container" class="mt-6 pt-4 border-t border-subtle hidden">
            <p class="text-xs text-outline mb-2">Did your download not start automatically?</p>
            <a id="dl-direct-link" href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-[16px]">file_download</span>
                Click here to download directly
            </a>
        </div>
    </div>
</div>

<!-- 3. Software Request Modal -->
<div id="request-modal" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="glass-panel w-full max-w-lg rounded-2xl p-8 shadow-2xl border border-subtle relative">
        <button onclick="closeRequestModal()" class="absolute top-4 right-4 text-outline hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="p-2.5 rounded-xl bg-primary/10 border border-primary/20 text-primary">
                <span class="material-symbols-outlined text-2xl">rocket_launch</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-on-surface">Request Software</h3>
                <p class="text-xs text-outline">Need a specific Mac app? Tell us and we will add it.</p>
            </div>
        </div>

        <form id="software-request-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-outline mb-1.5">Application Name <span class="text-error">*</span></label>
                <input type="text" name="software_name" required placeholder="e.g. Sketch, DaVinci Resolve, Sublime Text" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-outline mb-1.5">Version (Optional)</label>
                    <input type="text" name="version" placeholder="e.g. 2024 or Latest" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-outline mb-1.5">Category</label>
                    <input type="text" name="category" placeholder="e.g. Graphics, Video" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-outline mb-1.5">Additional Notes / Official URL</label>
                <textarea name="note" rows="3" placeholder="Provide official website link or specific requirements..." class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-outline mb-1.5">Your Email (Optional, for notification)</label>
                <input type="email" name="contact" placeholder="your@email.com" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
            </div>

            <button type="submit" class="btn-electric w-full py-3 rounded-xl text-sm font-bold uppercase tracking-wider flex items-center justify-center gap-2 mt-4">
                <span class="material-symbols-outlined text-[18px]">send</span>
                Submit Request
            </button>
        </form>
    </div>
</div>

<!-- JavaScript Includes -->
<script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
