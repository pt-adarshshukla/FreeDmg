<?php
/**
 * FreeDmg - Admin Edit Software Release
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: software.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM software WHERE id = ?");
$stmt->execute([$id]);
$software = $stmt->fetch();

if (!$software) {
    set_flash_message('error', 'Software package not found.');
    header("Location: software.php");
    exit;
}

// Fetch screenshots
$stmtSc = $pdo->prepare("SELECT * FROM software_screenshots WHERE software_id = ? ORDER BY sort_order ASC");
$stmtSc->execute([$id]);
$existingScreenshots = $stmtSc->fetchAll();

// Handle Screenshot Delete
if (isset($_GET['del_sc'])) {
    $scId = intval($_GET['del_sc']);
    $pdo->prepare("DELETE FROM software_screenshots WHERE id = ? AND software_id = ?")->execute([$scId, $id]);
    set_flash_message('success', 'Screenshot removed.');
    header("Location: software-edit.php?id=" . $id);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "Uploaded file size (" . format_file_size_bytes($_SERVER['CONTENT_LENGTH']) . ") exceeded server upload limits. Ensure PHP post_max_size & upload_max_filesize are configured.";
    } else {
        $title = sanitize_text($_POST['title'] ?? '');
        $slug = sanitize_text($_POST['slug'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $version = sanitize_text($_POST['version'] ?? '1.0.0');
        $format = strtoupper(sanitize_text($_POST['format'] ?? 'DMG'));
        $fileSize = sanitize_text($_POST['file_size'] ?? '100 MB');
        $architecture = sanitize_text($_POST['architecture'] ?? 'Apple Silicon & Intel');
        $minMacos = sanitize_text($_POST['min_macos'] ?? 'macOS 12.0 or later');
        $releaseDate = sanitize_text($_POST['release_date'] ?? date('F d, Y'));
        $iconUrl = trim($_POST['icon_url'] ?? '');
        $extDownloadUrl = trim($_POST['external_download_url'] ?? '');
        $shortDesc = sanitize_text($_POST['short_description'] ?? '');
        $fullDesc = trim($_POST['full_description'] ?? '');
        $downloadsCount = intval($_POST['downloads_count'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($slug)) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    if (empty($title)) {
        $error = "Software title is required.";
    } else {
        // Handle Icon File Upload if new file provided
        if (!empty($_FILES['icon_file']['name'])) {
            $iconExt = strtolower(pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION));
            if (in_array($iconExt, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'ico'])) {
                $iconName = 'icon_' . time() . '_' . rand(100, 999) . '.' . $iconExt;
                $targetDir = __DIR__ . '/../uploads/icons/';
                @mkdir($targetDir, 0755, true);
                if (move_uploaded_file($_FILES['icon_file']['tmp_name'], $targetDir . $iconName)) {
                    $iconUrl = $baseUrl . '/uploads/icons/' . $iconName;
                }
            }
        }

        // Handle Package File Upload if new file provided
        $filePath = $software['file_path'];
        if (!empty($_FILES['package_file']['name'])) {
            $pkgExt = strtolower(pathinfo($_FILES['package_file']['name'], PATHINFO_EXTENSION));
            if (in_array($pkgExt, ['dmg', 'zip', 'rar', 'pkg', '7z', 'gz', 'iso', 'tar'])) {
                $cleanName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['package_file']['name']);
                $pkgFileName = time() . '_' . $cleanName;
                $targetPkgDir = __DIR__ . '/../uploads/files/';
                @mkdir($targetPkgDir, 0755, true);
                $targetFilePath = $targetPkgDir . $pkgFileName;
                if (move_uploaded_file($_FILES['package_file']['tmp_name'], $targetFilePath)) {
                    $filePath = 'uploads/files/' . $pkgFileName;
                    
                    // Auto-calculate exact file size if updated or empty
                    if (empty($fileSize) || $fileSize === $software['file_size']) {
                        $fileSize = format_file_size_bytes(filesize($targetFilePath));
                    }

                    // Auto-set format from uploaded extension
                    if (in_array(strtoupper($pkgExt), ['DMG', 'ZIP', 'RAR', 'PKG'])) {
                        $format = strtoupper($pkgExt);
                    }
                }
            }
        }

        try {
            $stmtUpdate = $pdo->prepare("UPDATE software SET 
                category_id = ?, 
                title = ?, 
                slug = ?, 
                version = ?, 
                format = ?, 
                file_size = ?, 
                architecture = ?, 
                min_macos = ?, 
                icon_url = ?, 
                file_path = ?, 
                external_download_url = ?, 
                short_description = ?, 
                full_description = ?, 
                downloads_count = ?,
                is_featured = ?, 
                is_active = ?, 
                release_date = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");

            $stmtUpdate->execute([
                $categoryId ?: null,
                $title,
                $slug,
                $version,
                $format,
                $fileSize,
                $architecture,
                $minMacos,
                $iconUrl,
                $filePath,
                $extDownloadUrl,
                $shortDesc,
                $fullDesc,
                $downloadsCount,
                $isFeatured,
                $isActive,
                $releaseDate,
                $id
            ]);

            // Handle New Screenshot Uploads
            if (!empty($_FILES['screenshot_files']['name'][0])) {
                $stmtNewSc = $pdo->prepare("INSERT INTO software_screenshots (software_id, image_url, sort_order) VALUES (?, ?, ?)");
                $order = count($existingScreenshots) + 1;
                $scTargetDir = __DIR__ . '/../uploads/screens/';
                @mkdir($scTargetDir, 0755, true);

                foreach ($_FILES['screenshot_files']['name'] as $k => $name) {
                    if (!empty($name)) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                            $scName = 'sc_' . time() . '_' . rand(100, 999) . '_' . $k . '.' . $ext;
                            if (move_uploaded_file($_FILES['screenshot_files']['tmp_name'][$k], $scTargetDir . $scName)) {
                                $scUrl = $baseUrl . '/uploads/screens/' . $scName;
                                $stmtNewSc->execute([$id, $scUrl, $order++]);
                            }
                        }
                    }
                }
            }

            set_flash_message('success', 'Software release updated successfully.');
            header("Location: software.php");
            exit;
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
}

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = "Edit Software Release";
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Edit: <?= htmlspecialchars($software['title']) ?></h2>
        <p class="text-xs text-outline mt-0.5">Modify metadata, replace package files, update version details.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="../app.php?slug=<?= urlencode($software['slug']) ?>" target="_blank" class="bg-surface-container hover:bg-surface-high text-primary px-4 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">visibility</span>
            View Live
        </a>
        <a href="software.php" class="bg-surface-container hover:bg-surface-high text-on-surface px-4 py-2 rounded-xl text-xs font-bold transition-colors">
            Return
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="mb-6 p-4 rounded-xl bg-error/15 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<!-- Form -->
<form method="POST" action="software-edit.php?id=<?= $id ?>" enctype="multipart/form-data" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Main Metadata -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Basic Details Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    General Information
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Application Title <span class="text-error">*</span></label>
                        <input type="text" name="title" value="<?= htmlspecialchars($software['title']) ?>" required class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Custom Slug</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($software['slug']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Category</label>
                        <select name="category_id" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                            <option value="0">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $software['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Version</label>
                        <input type="text" name="version" value="<?= htmlspecialchars($software['version']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Release Date</label>
                        <input type="text" name="release_date" value="<?= htmlspecialchars($software['release_date']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Short Summary / Tagline</label>
                    <input type="text" name="short_description" value="<?= htmlspecialchars($software['short_description']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Full Detailed Description</label>
                    <textarea name="full_description" rows="5" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none"><?= htmlspecialchars($software['full_description']) ?></textarea>
                </div>
            </div>

            <!-- Technical Specifications Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-secondary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">memory</span>
                    Technical Specifications
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Format Type</label>
                        <select name="format" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface font-bold focus:border-primary outline-none">
                            <option value="DMG" <?= $software['format'] === 'DMG' ? 'selected' : '' ?>>DMG (Apple Disk Image)</option>
                            <option value="ZIP" <?= $software['format'] === 'ZIP' ? 'selected' : '' ?>>ZIP (Zip Archive)</option>
                            <option value="RAR" <?= $software['format'] === 'RAR' ? 'selected' : '' ?>>RAR (WinRAR Archive)</option>
                            <option value="PKG" <?= $software['format'] === 'PKG' ? 'selected' : '' ?>>PKG (macOS Installer Package)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">File Size</label>
                        <input type="text" name="file_size" value="<?= htmlspecialchars($software['file_size']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Total Downloads</label>
                        <input type="number" name="downloads_count" value="<?= intval($software['downloads_count']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Architecture</label>
                        <select name="architecture" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                            <option value="Apple Silicon & Intel" <?= $software['architecture'] === 'Apple Silicon & Intel' ? 'selected' : '' ?>>Apple Silicon & Intel (Universal)</option>
                            <option value="Apple Silicon Native" <?= $software['architecture'] === 'Apple Silicon Native' ? 'selected' : '' ?>>Apple Silicon Native (M1/M2/M3/M4)</option>
                            <option value="Intel x64 Only" <?= $software['architecture'] === 'Intel x64 Only' ? 'selected' : '' ?>>Intel x64 Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Minimum macOS Version</label>
                        <input type="text" name="min_macos" value="<?= htmlspecialchars($software['min_macos']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>
            </div>

            <!-- Existing Screenshots Manager -->
            <?php if (!empty($existingScreenshots)): ?>
                <div class="glass-panel rounded-2xl p-6 border border-subtle">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-outline mb-3">Current Screenshot Previews</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php foreach ($existingScreenshots as $sc): ?>
                            <div class="relative group rounded-xl overflow-hidden border border-subtle h-24">
                                <img src="<?= htmlspecialchars($sc['image_url']) ?>" class="w-full h-full object-cover">
                                <a href="software-edit.php?id=<?= $id ?>&del_sc=<?= $sc['id'] ?>" onclick="return confirm('Remove this screenshot?');" class="absolute inset-0 bg-error/75 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs font-bold">
                                    Delete
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right 1 Col: File Uploads & Settings -->
        <div class="space-y-6">
            
            <!-- Download Source Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-success flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">cloud_download</span>
                    Download Source
                </h3>

                <?php if (!empty($software['file_path'])): ?>
                    <div class="p-3 rounded-xl bg-surface-container border border-subtle text-xs">
                        <span class="text-outline block mb-1 font-semibold">Current Local File:</span>
                        <span class="text-primary font-mono break-all"><?= htmlspecialchars(basename($software['file_path'])) ?></span>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Replace Local Package File</label>
                    <input type="file" name="package_file" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                </div>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-subtle"></div>
                    <span class="flex-shrink mx-2 text-[10px] text-outline uppercase font-bold">OR External URL</span>
                    <div class="flex-grow border-t border-subtle"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">External Direct / Cloud URL</label>
                    <input type="url" name="external_download_url" value="<?= htmlspecialchars($software['external_download_url']) ?>" placeholder="https://cdn.example.com/software.dmg" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>
            </div>

            <!-- Icon & Media Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">image</span>
                    App Icon & Gallery
                </h3>

                <div class="flex items-center gap-3 p-3 rounded-xl bg-surface-container border border-subtle">
                    <?= get_software_icon_html($software, 'w-12 h-12', 'rounded-xl') ?>
                    <div class="text-xs overflow-hidden">
                        <span class="text-outline block font-semibold">Active Icon</span>
                        <span class="text-[11px] text-primary truncate block"><?= !empty($software['icon_url']) ? htmlspecialchars($software['icon_url']) : 'Dynamic 3D Mac Icon' ?></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Replace App Icon File</label>
                    <input type="file" name="icon_file" accept="image/*" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Or Icon Image URL</label>
                    <input type="url" name="icon_url" value="<?= htmlspecialchars($software['icon_url']) ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <div class="pt-2 border-t border-subtle">
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Add More Screenshots</label>
                    <input type="file" name="screenshot_files[]" multiple accept="image/*" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                </div>
            </div>

            <!-- Publishing Options -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-warning flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">toggle_on</span>
                    Publishing
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" <?= $software['is_active'] ? 'checked' : '' ?> class="rounded bg-surface-container border-subtle text-primary focus:ring-0">
                        <span class="text-xs font-semibold text-on-surface">Publish Immediately (Active)</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= $software['is_featured'] ? 'checked' : '' ?> class="rounded bg-surface-container border-subtle text-primary focus:ring-0">
                        <span class="text-xs font-semibold text-on-surface">Feature on Homepage</span>
                    </label>
                </div>

                <button type="submit" class="btn-electric w-full py-3.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg mt-4 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Save Changes
                </button>
            </div>

        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
