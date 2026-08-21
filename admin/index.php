<?php
/**
 * FreeDmg - Admin Dashboard Overview
 */

$pageTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/admin_header.php';

$pdo = get_db_connection();

// Metrics
$totalSoftware = $pdo->query("SELECT COUNT(*) FROM software")->fetchColumn();
$totalDownloads = $pdo->query("SELECT COALESCE(SUM(downloads_count), 0) FROM software")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();

// Recent Software Uploads
$recentSoftware = $pdo->query("SELECT s.*, c.name AS category_name 
                              FROM software s 
                              LEFT JOIN categories c ON s.category_id = c.id 
                              ORDER BY s.id DESC LIMIT 6")->fetchAll();

// Format distribution counts
$formatCounts = $pdo->query("SELECT format, COUNT(*) as count FROM software GROUP BY format")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!-- Overview Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Stat 1: Total Software -->
    <div class="glass-panel rounded-2xl p-6 border border-subtle relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-outline">Total Software</span>
            <div class="p-2.5 rounded-xl bg-primary/10 text-primary">
                <span class="material-symbols-outlined text-[22px]">apps</span>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-on-surface mb-1"><?= number_format($totalSoftware) ?></div>
        <p class="text-xs text-outline font-medium">Catalog items active</p>
    </div>

    <!-- Stat 2: Total Downloads -->
    <div class="glass-panel rounded-2xl p-6 border border-subtle relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-outline">Total Downloads</span>
            <div class="p-2.5 rounded-xl bg-secondary/10 text-secondary">
                <span class="material-symbols-outlined text-[22px]">download_for_offline</span>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-on-surface mb-1"><?= number_format($totalDownloads) ?></div>
        <p class="text-xs text-outline font-medium">Transfers completed</p>
    </div>

    <!-- Stat 3: Categories -->
    <div class="glass-panel rounded-2xl p-6 border border-subtle relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-outline">Categories</span>
            <div class="p-2.5 rounded-xl bg-success/10 text-success">
                <span class="material-symbols-outlined text-[22px]">category</span>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-on-surface mb-1"><?= number_format($totalCategories) ?></div>
        <p class="text-xs text-outline font-medium">Organized sections</p>
    </div>

    <!-- Stat 4: Pending Requests -->
    <div class="glass-panel rounded-2xl p-6 border border-subtle relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-outline">Pending Requests</span>
            <div class="p-2.5 rounded-xl bg-warning/10 text-warning">
                <span class="material-symbols-outlined text-[22px]">mark_email_unread</span>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-on-surface mb-1"><?= number_format($pendingRequests) ?></div>
        <p class="text-xs text-outline font-medium">Awaiting administrator action</p>
    </div>

</div>

<!-- Quick Action & Format Breakdown Bar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Quick Add Callout -->
    <div class="lg:col-span-2 glass-panel rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 border-primary/30 relative overflow-hidden">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-3xl">upload_file</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-on-surface">Publish New Software Package</h3>
                <p class="text-xs text-outline mt-0.5">Upload .DMG, .ZIP, .RAR, or .PKG releases with screenshots & metadata.</p>
            </div>
        </div>
        <a href="software-add.php" class="btn-electric px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-wider whitespace-nowrap shadow-lg flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            Add Software
        </a>
    </div>

    <!-- Format Distribution Pills -->
    <div class="glass-panel rounded-2xl p-6 flex flex-col justify-center">
        <h4 class="text-xs font-bold uppercase tracking-wider text-outline mb-3">Package Formats</h4>
        <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-surface-container rounded-xl p-2 border border-subtle">
                <span class="block text-[10px] font-bold text-primary">DMG</span>
                <span class="text-base font-extrabold text-on-surface"><?= $formatCounts['DMG'] ?? 0 ?></span>
            </div>
            <div class="bg-surface-container rounded-xl p-2 border border-subtle">
                <span class="block text-[10px] font-bold text-secondary">ZIP</span>
                <span class="text-base font-extrabold text-on-surface"><?= $formatCounts['ZIP'] ?? 0 ?></span>
            </div>
            <div class="bg-surface-container rounded-xl p-2 border border-subtle">
                <span class="block text-[10px] font-bold text-success">RAR</span>
                <span class="text-base font-extrabold text-on-surface"><?= $formatCounts['RAR'] ?? 0 ?></span>
            </div>
            <div class="bg-surface-container rounded-xl p-2 border border-subtle">
                <span class="block text-[10px] font-bold text-warning">PKG</span>
                <span class="text-base font-extrabold text-on-surface"><?= $formatCounts['PKG'] ?? 0 ?></span>
            </div>
        </div>
    </div>

</div>

<!-- Recent Uploads Table -->
<div class="glass-panel rounded-2xl overflow-hidden border border-subtle">
    <div class="p-6 border-b border-subtle flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-on-surface">Recent Software Uploads</h3>
            <p class="text-xs text-outline mt-0.5">Recently added macOS applications in repository</p>
        </div>
        <a href="software.php" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
            View All <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container/50 text-[11px] uppercase font-bold text-outline border-b border-subtle">
                <tr>
                    <th class="py-3.5 px-6">Software</th>
                    <th class="py-3.5 px-4">Category</th>
                    <th class="py-3.5 px-4">Format</th>
                    <th class="py-3.5 px-4">Size</th>
                    <th class="py-3.5 px-4">Downloads</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-subtle">
                <?php if (empty($recentSoftware)): ?>
                    <tr>
                        <td colspan="7" class="py-8 text-center text-outline text-xs">No software packages found in repository.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentSoftware as $app): ?>
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <?= get_software_icon_html($app, 'w-10 h-10', 'rounded-xl') ?>
                                    <div>
                                        <a href="../app.php?slug=<?= urlencode($app['slug']) ?>" target="_blank" class="font-bold text-on-surface hover:text-primary transition-colors">
                                            <?= htmlspecialchars($app['title']) ?>
                                        </a>
                                        <p class="text-xs text-outline"><?= htmlspecialchars($app['version']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-xs font-semibold text-outline">
                                <?= htmlspecialchars($app['category_name'] ?? 'General') ?>
                            </td>
                            <td class="py-4 px-4">
                                <?= get_format_badge($app['format']) ?>
                            </td>
                            <td class="py-4 px-4 text-xs text-outline font-medium">
                                <?= htmlspecialchars($app['file_size']) ?>
                            </td>
                            <td class="py-4 px-4 text-xs font-bold text-on-surface">
                                <?= number_format($app['downloads_count']) ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($app['is_active']): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-success bg-success/10 px-2 py-0.5 rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-outline bg-surface-container px-2 py-0.5 rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-outline"></span> Draft
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="software-edit.php?id=<?= $app['id'] ?>" class="p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-primary transition-colors" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <a href="software.php?action=delete&id=<?= $app['id'] ?>&csrf=<?= get_csrf_token() ?>" onclick="return confirm('Are you sure you want to delete this software release?');" class="p-1.5 rounded-lg hover:bg-error/15 text-outline hover:text-error transition-colors" title="Delete">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
