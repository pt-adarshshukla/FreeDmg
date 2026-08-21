<?php
/**
 * FreeDmg - Dynamic Homepage
 */

require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Fetch filter format if set
$selectedFormat = strtoupper(trim($_GET['format'] ?? 'ALL'));
$selectedCategory = trim($_GET['cat'] ?? '');

// Fetch Categories for pills
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();

// Fetch Featured Software
$featuredQuery = "SELECT s.*, c.name AS category_name FROM software s 
                  LEFT JOIN categories c ON s.category_id = c.id 
                  WHERE s.is_active = 1 AND s.is_featured = 1 
                  ORDER BY s.downloads_count DESC LIMIT 4";
$featuredSoftware = $pdo->query($featuredQuery)->fetchAll();

// Fetch Latest Uploads with optional format filtering
$latestSql = "SELECT s.*, c.name AS category_name FROM software s 
              LEFT JOIN categories c ON s.category_id = c.id 
              WHERE s.is_active = 1";
$params = [];

if ($selectedFormat !== 'ALL' && in_array($selectedFormat, ['DMG', 'ZIP', 'RAR', 'PKG'])) {
    $latestSql .= " AND s.format = ?";
    $params[] = $selectedFormat;
}

if (!empty($selectedCategory)) {
    $latestSql .= " AND c.slug = ?";
    $params[] = $selectedCategory;
}

$latestSql .= " ORDER BY s.id DESC LIMIT 16";
$stmtLatest = $pdo->prepare($latestSql);
$stmtLatest->execute($params);
$latestSoftware = $stmtLatest->fetchAll();
?>

<!-- Main Content Canvas -->
<main class="flex-grow pt-28 pb-24 px-4 md:px-gutter max-w-container-max mx-auto w-full">

    <!-- Hero Section -->
    <section class="flex flex-col items-center text-center mt-6 mb-16 max-w-4xl mx-auto relative">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-subtle bg-surface-container/60 mb-6 backdrop-blur-md">
            <span class="w-2 h-2 rounded-full bg-success shadow-[0_0_8px_rgba(50,215,75,0.6)]"></span>
            <span class="text-[11px] font-bold text-on-surface-variant tracking-widest uppercase">System Online • High Speed CDN</span>
        </div>

        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight mb-5 leading-tight">
            The Ultimate <br>
            <span class="text-gradient">Mac Software Hub.</span>
        </h1>

        <p class="text-base sm:text-lg text-outline max-w-2xl mb-10 leading-relaxed">
            High-performance software distribution engineered for speed. Securely access the latest releases supporting 
            <span class="text-on-surface font-semibold">DMG</span>, 
            <span class="text-on-surface font-semibold">ZIP</span>, 
            <span class="text-on-surface font-semibold">RAR</span>, and 
            <span class="text-on-surface font-semibold">PKG</span> formats.
        </p>

        <!-- Large Search Bar -->
        <div class="w-full max-w-2xl relative group mb-10">
            <div class="absolute inset-0 bg-primary/10 rounded-full blur-xl group-focus-within:bg-primary/25 transition-all duration-500"></div>
            <form action="<?= $baseUrl ?>/search.php" method="GET" class="relative flex items-center bg-surface-container border border-subtle rounded-full px-5 py-3.5 transition-all group-focus-within:border-primary">
                <span class="material-symbols-outlined text-outline text-[26px] mr-3">search</span>
                <input name="q" class="w-full bg-transparent border-none text-on-surface text-base focus:ring-0 placeholder:text-outline outline-none" placeholder="Search applications, utilities, developer tools..." type="text">
                <button type="button" data-open-search class="ml-3 bg-surface-high hover:bg-surface text-on-surface text-xs font-semibold px-3 py-1.5 rounded-full border border-subtle transition-colors hidden sm:block">
                    ⌘ K
                </button>
            </form>
        </div>

        <!-- Categories Filter Pills -->
        <div class="flex flex-wrap justify-center gap-3">
            <?php foreach ($categories as $cat): ?>
                <a class="flex items-center gap-2 px-4 py-2 rounded-full bg-surface border border-subtle hover:border-primary/50 hover:bg-surface-container transition-all duration-200 text-xs font-semibold text-on-surface" href="<?= $baseUrl ?>/category.php?slug=<?= urlencode($cat['slug']) ?>">
                    <span class="material-symbols-outlined text-primary text-[18px]" style="font-variation-settings: 'FILL' 1;"><?= htmlspecialchars($cat['icon'] ?: 'folder') ?></span>
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Format Filter Tabs Bar -->
    <section class="mb-10 flex flex-col sm:flex-row justify-between items-center gap-4 pb-4 border-b border-subtle">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-on-surface">Latest Releases</h2>
            <p class="text-sm text-outline mt-0.5">Verified, notarized packages ready for instant download.</p>
        </div>

        <!-- Format Pills -->
        <div class="flex items-center bg-surface-container p-1 rounded-xl border border-subtle text-xs font-bold">
            <a href="<?= $baseUrl ?>/index.php" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'ALL' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">ALL</a>
            <a href="<?= $baseUrl ?>/index.php?format=DMG" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'DMG' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">DMG</a>
            <a href="<?= $baseUrl ?>/index.php?format=ZIP" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'ZIP' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">ZIP</a>
            <a href="<?= $baseUrl ?>/index.php?format=RAR" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'RAR' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">RAR</a>
            <a href="<?= $baseUrl ?>/index.php?format=PKG" class="px-3 py-1.5 rounded-lg transition-colors <?= $selectedFormat === 'PKG' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">PKG</a>
        </div>
    </section>

    <!-- Software Cards Grid -->
    <?php if (empty($latestSoftware)): ?>
        <div class="glass-panel rounded-2xl p-12 text-center my-8">
            <span class="material-symbols-outlined text-4xl text-outline mb-2">folder_off</span>
            <h3 class="text-lg font-bold text-on-surface">No software found</h3>
            <p class="text-sm text-outline mt-1 mb-6">No applications matching the selected format filter are currently listed.</p>
            <a href="<?= $baseUrl ?>/index.php" class="btn-electric px-6 py-2.5 rounded-full text-xs uppercase font-bold tracking-wider">View All Software</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($latestSoftware as $app): ?>
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
                    
                    <p class="text-xs font-semibold text-outline uppercase tracking-wider mt-1 mb-3">
                        <?= htmlspecialchars($app['category_name'] ?? 'General') ?>
                    </p>

                    <p class="text-xs text-on-surface-variant line-clamp-2 mb-5 leading-relaxed">
                        <?= htmlspecialchars($app['short_description'] ?? 'Download official verified package for Apple Silicon and Intel.') ?>
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
