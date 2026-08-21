<?php
/**
 * FreeDmg - Search Page and Live Search JSON API
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$pdo = get_db_connection();

$query = trim($_GET['q'] ?? '');
$isAjax = !empty($_GET['ajax']);

$results = [];
if (!empty($query)) {
    $searchPattern = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT s.*, c.name AS category_name 
                           FROM software s 
                           LEFT JOIN categories c ON s.category_id = c.id 
                           WHERE s.is_active = 1 AND (
                               s.title LIKE ? OR 
                               s.short_description LIKE ? OR 
                               s.full_description LIKE ? OR
                               c.name LIKE ?
                           )
                           ORDER BY s.downloads_count DESC LIMIT 20");
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    $results = $stmt->fetchAll();
}

// Handle AJAX request for modal live search
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results);
    exit;
}

$pageTitle = "Search: " . htmlspecialchars($query) . " - FreeDmg";
require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-grow pt-28 pb-24 px-4 md:px-gutter max-w-container-max mx-auto w-full">

    <!-- Search Header -->
    <section class="glass-panel rounded-2xl p-8 mb-10">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-on-surface mb-4">
            Search Results for <span class="text-gradient">"<?= htmlspecialchars($query) ?>"</span>
        </h1>

        <!-- Search Input Form -->
        <form action="search.php" method="GET" class="flex items-center bg-surface-container border border-subtle rounded-full px-5 py-3 max-w-2xl">
            <span class="material-symbols-outlined text-outline text-2xl mr-3">search</span>
            <input name="q" value="<?= htmlspecialchars($query) ?>" class="w-full bg-transparent border-none text-on-surface text-base focus:ring-0 placeholder:text-outline outline-none" placeholder="Search applications..." type="text">
            <button type="submit" class="btn-electric text-xs uppercase font-bold px-5 py-2 rounded-full">Search</button>
        </form>
    </section>

    <!-- Results Grid -->
    <?php if (empty($results)): ?>
        <div class="glass-panel rounded-2xl p-12 text-center my-8">
            <span class="material-symbols-outlined text-4xl text-outline mb-2">search_off</span>
            <h3 class="text-lg font-bold text-on-surface">No applications found</h3>
            <p class="text-sm text-outline mt-1 mb-6">We couldn't find any software matching "<strong><?= htmlspecialchars($query) ?></strong>".</p>
            <button data-open-request class="btn-electric px-6 py-2.5 rounded-full text-xs uppercase font-bold tracking-wider">Request This Software</button>
        </div>
    <?php else: ?>
        <p class="text-xs uppercase font-semibold tracking-wider text-outline mb-6">Found <?= count($results) ?> matching package(s)</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($results as $app): ?>
                <div class="glass-card rounded-2xl p-6 flex flex-col relative overflow-hidden group">
                    <div class="flex items-start justify-between mb-5">
                        <?php if (!empty($app['icon_url'])): ?>
                            <img class="w-16 h-16 rounded-2xl object-cover shadow-lg border border-subtle group-hover:scale-105 transition-transform duration-300" src="<?= htmlspecialchars($app['icon_url']) ?>" alt="<?= htmlspecialchars($app['title']) ?>">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-2xl bg-surface-container flex items-center justify-center border border-subtle text-2xl font-bold text-primary shadow-lg group-hover:scale-105 transition-transform duration-300">
                                <?= strtoupper(substr($app['title'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>

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
                        <?= htmlspecialchars($app['short_description'] ?? '') ?>
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
