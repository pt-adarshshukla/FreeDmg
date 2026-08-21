<?php
/**
 * FreeDmg - Category Browse View
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$pdo = get_db_connection();

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

$stmtCat = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmtCat->execute([$slug]);
$category = $stmtCat->fetch();

if (!$category) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = "Category Not Found - FreeDmg";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="flex-grow pt-32 pb-24 px-4 max-w-container-max mx-auto w-full text-center">
        <div class="glass-panel rounded-2xl p-12 max-w-lg mx-auto">
            <h1 class="text-2xl font-bold text-on-surface mb-2">Category Not Found</h1>
            <p class="text-sm text-outline mb-6">The category you requested does not exist.</p>
            <a href="<?= $baseUrl ?>/index.php" class="btn-electric px-6 py-2.5 rounded-full text-xs font-bold uppercase tracking-wider">Browse All Categories</a>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$selectedFormat = strtoupper(trim($_GET['format'] ?? 'ALL'));
$sort = trim($_GET['sort'] ?? 'downloads');

$sql = "SELECT * FROM software WHERE category_id = ? AND is_active = 1";
$params = [$category['id']];

if ($selectedFormat !== 'ALL' && in_array($selectedFormat, ['DMG', 'ZIP', 'RAR', 'PKG'])) {
    $sql .= " AND format = ?";
    $params[] = $selectedFormat;
}

if ($sort === 'newest') {
    $sql .= " ORDER BY id DESC";
} elseif ($sort === 'title') {
    $sql .= " ORDER BY title ASC";
} else {
    $sql .= " ORDER BY downloads_count DESC";
}

$stmtSoftware = $pdo->prepare($sql);
$stmtSoftware->execute($params);
$softwareList = $stmtSoftware->fetchAll();

$pageTitle = $category['name'] . " Software Downloads for macOS - FreeDmg";
$pageDescription = $category['description'];

require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-grow pt-28 pb-24 px-4 md:px-gutter max-w-container-max mx-auto w-full">

    <!-- Category Header Banner -->
    <section class="glass-panel rounded-2xl p-8 mb-10 relative overflow-hidden">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($category['icon'] ?: 'folder') ?></span>
                </div>
                <div>
                    <h1 class="text-3xl font-extrabold text-on-surface"><?= htmlspecialchars($category['name']) ?></h1>
                    <p class="text-sm text-outline mt-1 max-w-xl"><?= htmlspecialchars($category['description'] ?: 'Browse all tested and verified software in this category.') ?></p>
                </div>
            </div>

            <!-- Sort & Format Bar -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center bg-surface-container p-1 rounded-xl border border-subtle text-xs font-bold">
                    <a href="category.php?slug=<?= urlencode($slug) ?>" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'ALL' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">ALL</a>
                    <a href="category.php?slug=<?= urlencode($slug) ?>&format=DMG" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'DMG' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">DMG</a>
                    <a href="category.php?slug=<?= urlencode($slug) ?>&format=ZIP" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'ZIP' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">ZIP</a>
                    <a href="category.php?slug=<?= urlencode($slug) ?>&format=RAR" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'RAR' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">RAR</a>
                    <a href="category.php?slug=<?= urlencode($slug) ?>&format=PKG" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'PKG' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">PKG</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Software Grid -->
    <?php if (empty($softwareList)): ?>
        <div class="glass-panel rounded-2xl p-12 text-center my-8">
            <span class="material-symbols-outlined text-4xl text-outline mb-2">folder_off</span>
            <h3 class="text-lg font-bold text-on-surface">No applications in this category yet</h3>
            <p class="text-sm text-outline mt-1 mb-6">Be the first to request an application for <?= htmlspecialchars($category['name']) ?>.</p>
            <button data-open-request class="btn-electric px-6 py-2.5 rounded-full text-xs uppercase font-bold tracking-wider">Request Software</button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($softwareList as $app): ?>
                <div class="glass-card rounded-2xl p-6 flex flex-col relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-5">
                        <?= get_software_icon_html($app, 'w-16 h-16', 'rounded-2xl') ?>
                        <?= get_format_badge($app['format']) ?>
                    </div>

                    <a href="<?= $baseUrl ?>/app.php?slug=<?= urlencode($app['slug']) ?>" class="block">
                        <h3 class="text-lg font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1">
                            <?= htmlspecialchars($app['title']) ?>
                        </h3>
                    </a>

                    <p class="text-xs text-on-surface-variant line-clamp-2 mt-2 mb-5 leading-relaxed">
                        <?= htmlspecialchars($app['short_description'] ?? 'Download package for Apple Silicon and Intel.') ?>
                    </p>

                    <div class="mt-auto flex justify-between items-center pt-4 border-t border-subtle">
                        <span class="text-xs font-medium text-outline"><?= htmlspecialchars($app['version']) ?> • <?= htmlspecialchars($app['file_size']) ?></span>
                        
                        <button 
                            data-download-id="<?= $app['id'] ?>"
                            data-download-title="<?= htmlspecialchars($app['title']) ?>"
                            data-download-format="<?= htmlspecialchars($app['format']) ?>"
                            data-download-size="<?= htmlspecialchars($app['file_size']) ?>"
                            class="p-2 rounded-full bg-surface-container group-hover:bg-primary group-hover:text-on-primary text-primary transition-all duration-200" 
                            title="Download <?= htmlspecialchars($app['title']) ?>">
                            <span class="material-symbols-outlined text-[18px]">download</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
