<?php
/**
 * FreeDmg - Software Detail View
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$pdo = get_db_connection();

$slug = trim($_GET['slug'] ?? '');
$id = intval($_GET['id'] ?? 0);

if (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT s.*, c.name AS category_name, c.slug AS category_slug 
                           FROM software s 
                           LEFT JOIN categories c ON s.category_id = c.id 
                           WHERE s.slug = ? AND s.is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $software = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT s.*, c.name AS category_name, c.slug AS category_slug 
                           FROM software s 
                           LEFT JOIN categories c ON s.category_id = c.id 
                           WHERE s.id = ? AND s.is_active = 1 LIMIT 1");
    $stmt->execute([$id]);
    $software = $stmt->fetch();
} else {
    $software = null;
}

if (!$software) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = "Application Not Found - FreeDmg";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="flex-grow pt-32 pb-24 px-4 max-w-container-max mx-auto w-full text-center">
        <div class="glass-panel rounded-2xl p-12 max-w-lg mx-auto">
            <span class="material-symbols-outlined text-5xl text-outline mb-3">help_outline</span>
            <h1 class="text-2xl font-bold text-on-surface mb-2">Software Not Found</h1>
            <p class="text-sm text-outline mb-6">The application you are looking for does not exist or has been removed.</p>
            <a href="<?= $baseUrl ?>/index.php" class="btn-electric px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider">Back to Homepage</a>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch Screenshots
$stmtScreens = $pdo->prepare("SELECT image_url FROM software_screenshots WHERE software_id = ? ORDER BY sort_order ASC");
$stmtScreens->execute([$software['id']]);
$screenshots = $stmtScreens->fetchAll(PDO::FETCH_COLUMN);

// Fetch Related Software
$stmtRelated = $pdo->prepare("SELECT id, title, slug, version, format, file_size, icon_url FROM software WHERE category_id = ? AND id != ? AND is_active = 1 LIMIT 4");
$stmtRelated->execute([$software['category_id'], $software['id']]);
$relatedSoftware = $stmtRelated->fetchAll();

$pageTitle = $software['title'] . " " . $software['version'] . " for macOS - FreeDmg";
$pageDescription = $software['short_description'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Main Content Canvas -->
<main class="flex-grow pt-28 pb-20 px-4 md:px-gutter max-w-container-max mx-auto w-full">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2 text-outline text-xs uppercase font-semibold tracking-wider mb-6">
        <a class="hover:text-primary transition-colors" href="<?= $baseUrl ?>/index.php">Home</a>
        <span class="material-symbols-outlined text-[14px]">chevron_right</span>
        <?php if (!empty($software['category_name'])): ?>
            <a class="hover:text-primary transition-colors" href="<?= $baseUrl ?>/category.php?slug=<?= urlencode($software['category_slug']) ?>">
                <?= htmlspecialchars($software['category_name']) ?>
            </a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
        <?php endif; ?>
        <span class="text-on-surface"><?= htmlspecialchars($software['title']) ?></span>
    </nav>

    <!-- Software Bento Hero Header -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        <!-- Main Info Card -->
        <div class="lg:col-span-8 glass-panel rounded-2xl p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center gap-6 relative overflow-hidden">
            <div class="absolute -top-32 -left-32 w-80 h-80 bg-primary/10 rounded-full blur-[100px] pointer-events-none"></div>
            
            <!-- App Icon -->
            <div class="relative shrink-0">
                <?= get_software_icon_html($software, 'w-24 h-24 md:w-28 md:h-28', 'rounded-2xl') ?>
            </div>

            <!-- Title & Meta -->
            <div class="flex-grow z-10">
                <div class="flex flex-wrap items-center gap-2.5 mb-2">
                    <span class="px-2.5 py-0.5 rounded-md bg-secondary/15 text-secondary border border-secondary/30 text-[11px] font-bold uppercase tracking-wider">
                        <?= htmlspecialchars($software['category_name'] ?? 'General') ?>
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md bg-surface-high text-on-surface-variant text-[11px] font-semibold">
                        <?= htmlspecialchars($software['version']) ?>
                    </span>
                    <?= get_format_badge($software['format']) ?>
                </div>

                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mb-2">
                    <?= htmlspecialchars($software['title']) ?>
                </h1>

                <p class="text-sm md:text-base text-outline max-w-xl leading-relaxed">
                    <?= htmlspecialchars($software['short_description'] ?? 'Official macOS installer package with verified notarization and high-speed delivery.') ?>
                </p>
            </div>
        </div>

        <!-- Download Action Card -->
        <div class="lg:col-span-4 glass-panel rounded-2xl p-6 md:p-8 flex flex-col justify-center items-center text-center relative overflow-hidden border-primary/20">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-transparent pointer-events-none"></div>

            <h3 class="text-lg font-bold text-on-surface mb-4">Ready to Install?</h3>

            <button 
                data-download-id="<?= $software['id'] ?>"
                data-download-title="<?= htmlspecialchars($software['title']) ?>"
                data-download-format="<?= htmlspecialchars($software['format']) ?>"
                data-download-size="<?= htmlspecialchars($software['file_size']) ?>"
                class="w-full btn-electric py-3.5 rounded-xl flex items-center justify-center gap-2.5 text-base font-bold shadow-lg mb-3 cursor-pointer">
                <span class="material-symbols-outlined font-bold text-xl">download</span>
                Download <?= htmlspecialchars($software['format']) ?>
            </button>

            <p class="text-xs text-outline font-medium">
                Size: <?= htmlspecialchars($software['file_size']) ?> • <?= htmlspecialchars($software['architecture'] ?? 'Apple Silicon & Intel') ?>
            </p>
        </div>
    </section>

    <!-- Technical Specifications Bento Grid -->
    <section class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <!-- Spec 1: Architecture -->
        <div class="glass-card rounded-xl p-5 flex flex-col">
            <div class="flex items-center gap-2 text-primary mb-1">
                <span class="material-symbols-outlined text-[20px]">memory</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-outline">Architecture</span>
            </div>
            <p class="text-sm font-bold text-on-surface mt-auto"><?= htmlspecialchars($software['architecture'] ?? 'Apple Silicon & Intel') ?></p>
        </div>

        <!-- Spec 2: Minimum macOS -->
        <div class="glass-card rounded-xl p-5 flex flex-col">
            <div class="flex items-center gap-2 text-secondary mb-1">
                <span class="material-symbols-outlined text-[20px]">laptop_mac</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-outline">Compatibility</span>
            </div>
            <p class="text-sm font-bold text-on-surface mt-auto"><?= htmlspecialchars($software['min_macos'] ?? 'macOS 11.0+') ?></p>
        </div>

        <!-- Spec 3: Security Status -->
        <div class="glass-card rounded-xl p-5 flex flex-col">
            <div class="flex items-center gap-2 text-success mb-1">
                <span class="material-symbols-outlined text-[20px]">verified_user</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-outline">Security</span>
            </div>
            <p class="text-sm font-bold text-success mt-auto">Notarized & Malware-Free</p>
        </div>

        <!-- Spec 4: Downloads -->
        <div class="glass-card rounded-xl p-5 flex flex-col">
            <div class="flex items-center gap-2 text-warning mb-1">
                <span class="material-symbols-outlined text-[20px]">download_for_offline</span>
                <span class="text-xs font-semibold uppercase tracking-wider text-outline">Downloads</span>
            </div>
            <p class="text-sm font-bold text-on-surface mt-auto"><?= number_format($software['downloads_count']) ?> Transfers</p>
        </div>
    </section>

    <!-- Description & Screenshots Section -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12">
        <!-- Overview & Description -->
        <div class="lg:col-span-6 flex flex-col gap-6 text-on-surface-variant leading-relaxed">
            <div class="glass-panel rounded-2xl p-6 md:p-8">
                <h2 class="text-xl font-bold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">info</span>
                    Software Overview
                </h2>

                <div class="text-sm md:text-base space-y-4">
                    <?= nl2br(htmlspecialchars($software['full_description'] ?? $software['short_description'])) ?>
                </div>

                <div class="mt-8 pt-6 border-t border-subtle">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-on-surface mb-3">Package Details</h3>
                    <ul class="space-y-2 text-xs text-outline font-medium">
                        <li class="flex items-center justify-between">
                            <span>Release Date:</span>
                            <span class="text-on-surface"><?= htmlspecialchars($software['release_date'] ?? 'Recent') ?></span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span>Installer Type:</span>
                            <span class="text-on-surface"><?= htmlspecialchars($software['format']) ?> Archive</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span>License:</span>
                            <span class="text-on-surface">Full Freeware / Repack</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Gallery / Screenshots -->
        <div class="lg:col-span-6">
            <div class="glass-panel rounded-2xl p-6 md:p-8 h-full">
                <h2 class="text-xl font-bold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">image</span>
                    Visual Previews
                </h2>

                <?php if (!empty($screenshots)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($screenshots as $idx => $sc): ?>
                            <a href="<?= htmlspecialchars($sc) ?>" target="_blank" class="rounded-xl overflow-hidden border border-subtle hover:scale-[1.02] transition-all block h-44 group relative">
                                <img src="<?= htmlspecialchars($sc) ?>" class="w-full h-full object-cover group-hover:opacity-90 transition-opacity" alt="Screenshot preview">
                                <div class="absolute inset-0 bg-primary/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-3xl">zoom_in</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="h-64 rounded-xl border border-dashed border-subtle flex flex-col items-center justify-center text-outline p-6 text-center">
                        <span class="material-symbols-outlined text-4xl mb-2">image_not_supported</span>
                        <p class="text-xs">No screenshot previews available for this software release.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Related Software in Same Category -->
    <?php if (!empty($relatedSoftware)): ?>
        <section class="mb-12">
            <h2 class="text-xl font-bold text-on-surface mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">grid_view</span>
                More in <?= htmlspecialchars($software['category_name'] ?? 'Category') ?>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($relatedSoftware as $rel): ?>
                    <a href="<?= $baseUrl ?>/app.php?slug=<?= urlencode($rel['slug']) ?>" class="glass-card rounded-xl p-5 flex items-center gap-4 group">
                        <?= get_software_icon_html($rel, 'w-12 h-12', 'rounded-xl') ?>
                        <div class="overflow-hidden">
                            <h4 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors truncate"><?= htmlspecialchars($rel['title']) ?></h4>
                            <p class="text-xs text-outline mt-0.5"><?= htmlspecialchars($rel['version']) ?> • <?= htmlspecialchars($rel['format']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
