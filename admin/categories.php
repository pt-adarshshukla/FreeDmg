<?php
/**
 * FreeDmg - Admin Category Management
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();

// Handle Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = intval($_GET['id']);
    $csrf = $_GET['csrf'] ?? '';
    if (verify_csrf_token($csrf)) {
        // Set software under this category to NULL
        $pdo->prepare("UPDATE software SET category_id = NULL WHERE category_id = ?")->execute([$delId]);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$delId]);
        set_flash_message('success', 'Category deleted successfully.');
    }
    header("Location: categories.php");
    exit;
}

// Handle Add / Edit Form Submission
$editId = intval($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_text($_POST['name'] ?? '');
    $slug = sanitize_text($_POST['slug'] ?? '');
    $icon = sanitize_text($_POST['icon'] ?? 'folder');
    $description = sanitize_text($_POST['description'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);

    if (empty($slug)) {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        try {
            if ($editCategory) {
                // Update
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, icon = ?, description = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $icon, $description, $sortOrder, $editCategory['id']]);
                set_flash_message('success', 'Category updated successfully.');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $icon, $description, $sortOrder]);
                set_flash_message('success', 'Category created successfully.');
            }
            header("Location: categories.php");
            exit;
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Category Management";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch all categories with software counts
$categories = $pdo->query("SELECT c.*, COUNT(s.id) AS software_count 
                           FROM categories c 
                           LEFT JOIN software s ON c.id = s.category_id 
                           GROUP BY c.id 
                           ORDER BY c.sort_order ASC, c.name ASC")->fetchAll();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Categories (<?= count($categories) ?>)</h2>
        <p class="text-xs text-outline mt-0.5">Organize software into discoverable repository channels.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="mb-6 p-4 rounded-xl bg-error/15 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form: Add or Edit Category -->
    <div class="glass-panel rounded-2xl p-6 border border-subtle h-fit">
        <h3 class="text-sm font-bold uppercase tracking-wider text-primary mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]"><?= $editCategory ? 'edit' : 'add_box' ?></span>
            <?= $editCategory ? 'Edit Category' : 'Create New Category' ?>
        </h3>

        <form method="POST" action="categories.php<?= $editCategory ? '?edit=' . $editCategory['id'] : '' ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Category Name <span class="text-error">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" required placeholder="e.g. Developer Tools" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Slug (URL friendly)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>" placeholder="e.g. developer-tools" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Material Icon</label>
                    <input type="text" name="icon" value="<?= htmlspecialchars($editCategory['icon'] ?? 'folder') ?>" placeholder="e.g. terminal, palette" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" value="<?= intval($editCategory['sort_order'] ?? 0) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Description</label>
                <textarea name="description" rows="3" placeholder="Brief summary of software in this category..." class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none"><?= htmlspecialchars($editCategory['description'] ?? '') ?></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="btn-electric flex-grow py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider shadow-md cursor-pointer">
                    <?= $editCategory ? 'Save Changes' : 'Create Category' ?>
                </button>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="bg-surface-container hover:bg-surface-high text-on-surface px-4 py-2.5 rounded-xl text-xs font-bold transition-colors">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table: Category List -->
    <div class="lg:col-span-2 glass-panel rounded-2xl overflow-hidden border border-subtle">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-container/50 text-[11px] uppercase font-bold text-outline border-b border-subtle">
                    <tr>
                        <th class="py-3.5 px-6">Icon & Name</th>
                        <th class="py-3.5 px-4">Slug</th>
                        <th class="py-3.5 px-4">Software</th>
                        <th class="py-3.5 px-4">Order</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-subtle">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-outline text-xs">No categories created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-surface-container/40 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[18px]"><?= htmlspecialchars($cat['icon'] ?: 'folder') ?></span>
                                        </div>
                                        <div>
                                            <span class="font-bold text-on-surface text-xs"><?= htmlspecialchars($cat['name']) ?></span>
                                            <p class="text-[10px] text-outline line-clamp-1 max-w-xs"><?= htmlspecialchars($cat['description'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-xs font-mono text-outline">
                                    <?= htmlspecialchars($cat['slug']) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="text-xs font-bold text-on-surface"><?= $cat['software_count'] ?> app(s)</span>
                                </td>
                                <td class="py-4 px-4 text-xs text-outline">
                                    <?= $cat['sort_order'] ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="../category.php?slug=<?= urlencode($cat['slug']) ?>" target="_blank" class="p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-on-surface transition-colors" title="View Public">
                                            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                        </a>
                                        <a href="categories.php?edit=<?= $cat['id'] ?>" class="p-1.5 rounded-lg hover:bg-surface-container text-outline hover:text-primary transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?= $cat['id'] ?>&csrf=<?= get_csrf_token() ?>" onclick="return confirm('Delete <?= htmlspecialchars(addslashes($cat['name'])) ?>? Software will become uncategorized.');" class="p-1.5 rounded-lg hover:bg-error/15 text-outline hover:text-error transition-colors" title="Delete">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
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

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
