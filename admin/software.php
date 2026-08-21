<?php
/**
 * FreeDmg - Admin Software Catalog Management
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = intval($_GET['id']);
    $csrf = $_GET['csrf'] ?? '';

    if (verify_csrf_token($csrf)) {
        // Fetch software to delete uploaded files if local
        $stmtFind = $pdo->prepare("SELECT file_path, icon_url FROM software WHERE id = ?");
        $stmtFind->execute([$delId]);
        $delItem = $stmtFind->fetch();

        if ($delItem) {
            // Delete record
            $stmtDel = $pdo->prepare("DELETE FROM software WHERE id = ?");
            $stmtDel->execute([$delId]);
            set_flash_message('success', 'Software package deleted successfully.');
        }
    } else {
        set_flash_message('error', 'Security token expired. Please try again.');
    }
    header("Location: software.php");
    exit;
}

// Handle Toggle Active/Draft or Featured
if (isset($_GET['action']) && $_GET['action'] === 'toggle_active' && isset($_GET['id'])) {
    $toggleId = intval($_GET['id']);
    $csrf = $_GET['csrf'] ?? '';
    if (verify_csrf_token($csrf)) {
        $pdo->prepare("UPDATE software SET is_active = (1 - is_active) WHERE id = ?")->execute([$toggleId]);
        set_flash_message('success', 'Software status updated.');
    }
    header("Location: software.php");
    exit;
}

$pageTitle = "Software Catalog";
require_once __DIR__ . '/includes/admin_header.php';

// Filtering & Search
$search = trim($_GET['search'] ?? '');
$filterCategory = intval($_GET['category'] ?? 0);
$filterFormat = strtoupper(trim($_GET['format'] ?? ''));

$sql = "SELECT s.*, c.name AS category_name 
        FROM software s 
        LEFT JOIN categories c ON s.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.title LIKE ? OR s.version LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterCategory > 0) {
    $sql .= " AND s.category_id = ?";
    $params[] = $filterCategory;
}

if (!empty($filterFormat) && in_array($filterFormat, ['DMG', 'ZIP', 'RAR', 'PKG'])) {
    $sql .= " AND s.format = ?";
    $params[] = $filterFormat;
}

$sql .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$softwareList = $stmt->fetchAll();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>

<!-- Header Actions Bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Manage Software (<?= count($softwareList) ?>)</h2>
        <p class="text-xs text-outline mt-0.5">Control published macOS applications, updates and direct files.</p>
    </div>
    <a href="software-add.php" class="btn-electric px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-lg">
        <span class="material-symbols-outlined text-[18px]">add_circle</span>
        Add Software
    </a>
</div>

<!-- Filter & Search Card -->
<div class="glass-panel rounded-2xl p-5 mb-6">
    <form method="GET" action="software.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <!-- Search Input -->
        <div class="sm:col-span-2">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-outline mb-1">Search Name / Version</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="e.g. Photoshop, CLion, 2024..." class="w-full bg-surface-container border border-subtle rounded-xl py-2 pl-9 pr-3 text-xs text-on-surface placeholder:text-outline outline-none">
            </div>
        </div>

        <!-- Category Filter -->
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wider text-outline mb-1">Category</label>
            <select name="category" class="w-full bg-surface-container border border-subtle rounded-xl py-2 px-3 text-xs text-on-surface outline-none">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Format Filter & Submit -->
        <div class="flex items-end gap-2">
            <div class="flex-grow">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-outline mb-1">Format</label>
                <select name="format" class="w-full bg-surface-container border border-subtle rounded-xl py-2 px-3 text-xs text-on-surface outline-none">
                    <option value="">All Formats</option>
                    <option value="DMG" <?= $filterFormat === 'DMG' ? 'selected' : '' ?>>DMG</option>
                    <option value="ZIP" <?= $filterFormat === 'ZIP' ? 'selected' : '' ?>>ZIP</option>
                    <option value="RAR" <?= $filterFormat === 'RAR' ? 'selected' : '' ?>>RAR</option>
                    <option value="PKG" <?= $filterFormat === 'PKG' ? 'selected' : '' ?>>PKG</option>
                </select>
            </div>
            <button type="submit" class="bg-surface-high hover:bg-surface border border-subtle text-on-surface px-4 py-2 rounded-xl text-xs font-bold transition-colors">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Software Table -->
<div class="glass-panel rounded-2xl overflow-hidden border border-subtle">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container/50 text-[11px] uppercase font-bold text-outline border-b border-subtle">
                <tr>
                    <th class="py-3.5 px-6">Software</th>
                    <th class="py-3.5 px-4">Category</th>
                    <th class="py-3.5 px-4">Format</th>
                    <th class="py-3.5 px-4">Size</th>
                    <th class="py-3.5 px-4">Downloads</th>
                    <th class="py-3.5 px-4">Featured</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-subtle">
                <?php if (empty($softwareList)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-outline text-xs">No software packages matched your filter criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($softwareList as $app): ?>
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <?= get_software_icon_html($app, 'w-10 h-10', 'rounded-xl') ?>
                                    <div>
                                        <a href="../app.php?slug=<?= urlencode($app['slug']) ?>" target="_blank" class="font-bold text-on-surface hover:text-primary transition-colors">
                                            <?= htmlspecialchars($app['title']) ?>
                                        </a>
                                        <p class="text-xs text-outline"><?= htmlspecialchars($app['version']) ?> • <?= htmlspecialchars($app['architecture'] ?? '') ?></p>
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
                                <?php if ($app['is_featured']): ?>
                                    <span class="material-symbols-outlined text-warning text-lg" style="font-variation-settings: 'FILL' 1;">star</span>
                                <?php else: ?>
                                    <span class="text-outline text-xs">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <a href="software.php?action=toggle_active&id=<?= $app['id'] ?>&csrf=<?= get_csrf_token() ?>" class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md transition-opacity hover:opacity-80 <?= $app['is_active'] ? 'text-success bg-success/10' : 'text-outline bg-surface-container' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $app['is_active'] ? 'bg-success' : 'bg-outline' ?>"></span>
                                    <?= $app['is_active'] ? 'Active' : 'Draft' ?>
                                </a>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="../app.php?slug=<?= urlencode($app['slug']) ?>" target="_blank" class="p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-on-surface transition-colors" title="View Page">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <a href="software-edit.php?id=<?= $app['id'] ?>" class="p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-primary transition-colors" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <a href="software.php?action=delete&id=<?= $app['id'] ?>&csrf=<?= get_csrf_token() ?>" onclick="return confirm('Delete <?= htmlspecialchars(addslashes($app['title'])) ?> permanently?');" class="p-1.5 rounded-lg hover:bg-error/15 text-outline hover:text-error transition-colors" title="Delete">
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
