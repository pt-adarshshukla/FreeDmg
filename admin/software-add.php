<?php
/**
 * FreeDmg - Admin Add Software Release
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();

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
            // Handle Icon File Upload
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

            // Handle Package File Upload (.dmg, .zip, .rar, .pkg)
            $filePath = '';
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

                        // Auto-calculate exact file size if not custom or default
                        if (empty($fileSize) || $fileSize === '100 MB' || $fileSize === '120 MB') {
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
                // Check slug uniqueness
                $check = $pdo->prepare("SELECT COUNT(*) FROM software WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetchColumn() > 0) {
                    $slug .= '-' . time();
                }

                $stmt = $pdo->prepare("INSERT INTO software 
                    (category_id, title, slug, version, format, file_size, architecture, min_macos, icon_url, file_path, external_download_url, short_description, full_description, downloads_count, is_featured, is_active, release_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)");
                
                $stmt->execute([
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
                    $isFeatured,
                    $isActive,
                    $releaseDate
                ]);

                $softwareId = $pdo->lastInsertId();

                // Handle Screenshot Uploads
                if (!empty($_FILES['screenshot_files']['name'][0])) {
                    $stmtSc = $pdo->prepare("INSERT INTO software_screenshots (software_id, image_url, sort_order) VALUES (?, ?, ?)");
                    $order = 1;
                    $scTargetDir = __DIR__ . '/../uploads/screens/';
                    @mkdir($scTargetDir, 0755, true);

                    foreach ($_FILES['screenshot_files']['name'] as $k => $name) {
                        if (!empty($name)) {
                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                                $scName = 'sc_' . time() . '_' . rand(100, 999) . '_' . $k . '.' . $ext;
                                if (move_uploaded_file($_FILES['screenshot_files']['tmp_name'][$k], $scTargetDir . $scName)) {
                                    $scUrl = $baseUrl . '/uploads/screens/' . $scName;
                                    $stmtSc->execute([$softwareId, $scUrl, $order++]);
                                }
                            }
                        }
                    }
                }

                set_flash_message('success', 'Software package published successfully.');
                header("Location: software.php");
                exit;
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch categories for select
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = "Add Software Release";
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Add New Software Release</h2>
        <p class="text-xs text-outline mt-0.5">Upload and register a new DMG, ZIP, RAR, or PKG package.</p>
    </div>
    <a href="software.php" class="bg-surface-container hover:bg-surface-high text-on-surface px-4 py-2 rounded-xl text-xs font-bold transition-colors">
        Cancel & Return
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="mb-6 p-4 rounded-xl bg-error/15 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<!-- Form Container -->
<form method="POST" action="software-add.php" enctype="multipart/form-data" class="space-y-6">
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
                        <input type="text" name="title" required placeholder="e.g. JetBrains CLion 2024" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Custom Slug (Optional)</label>
                        <input type="text" name="slug" placeholder="e.g. jetbrains-clion-2024" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Category</label>
                        <select name="category_id" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                            <option value="0">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Version</label>
                        <input type="text" name="version" value="1.0.0" placeholder="e.g. 2024.1.2" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Release Date</label>
                        <input type="text" name="release_date" value="<?= date('F d, Y') ?>" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Short Summary / Tagline</label>
                    <input type="text" name="short_description" placeholder="A brief one-line description of the software features..." class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Full Detailed Description</label>
                    <textarea name="full_description" rows="5" placeholder="Detailed application overview, key features, changelog, installation notes..." class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none"></textarea>
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
                            <option value="DMG">DMG (Apple Disk Image)</option>
                            <option value="ZIP">ZIP (Zip Archive)</option>
                            <option value="RAR">RAR (WinRAR Archive)</option>
                            <option value="PKG">PKG (macOS Installer Package)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">File Size</label>
                        <input type="text" name="file_size" value="120 MB" placeholder="e.g. 850 MB, 1.4 GB" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Architecture</label>
                        <select name="architecture" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                            <option value="Apple Silicon & Intel">Apple Silicon & Intel (Universal)</option>
                            <option value="Apple Silicon Native">Apple Silicon Native (M1/M2/M3/M4)</option>
                            <option value="Intel x64 Only">Intel x64 Only</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Minimum macOS Version</label>
                    <input type="text" name="min_macos" value="macOS 12.0 Monterey or later" placeholder="e.g. macOS 13.0 Ventura or later" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-sm text-on-surface focus:border-primary outline-none">
                </div>
            </div>

        </div>

        <!-- Right 1 Col: File Uploads & Visibility -->
        <div class="space-y-6">
            
            <!-- Download Source Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-success flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">cloud_download</span>
                    Download Source
                </h3>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Upload Local Package File (.dmg, .zip, .rar, .pkg)</label>
                    <input type="file" name="package_file" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                    <p class="text-[10px] text-outline mt-1">Directly hosted on server storage</p>
                </div>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-subtle"></div>
                    <span class="flex-shrink mx-2 text-[10px] text-outline uppercase font-bold">OR External Link</span>
                    <div class="flex-grow border-t border-subtle"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">External Direct URL / Cloud Link</label>
                    <input type="url" name="external_download_url" placeholder="https://cdn.example.com/software.dmg" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2.5 text-xs text-on-surface focus:border-primary outline-none">
                </div>
            </div>

            <!-- Media / Icon Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">image</span>
                    App Icon & Gallery
                </h3>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Upload App Icon (PNG/WEBP/SVG)</label>
                    <input type="file" name="icon_file" accept="image/*" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Or Icon Image URL</label>
                    <input type="url" name="icon_url" placeholder="https://.../icon.png" class="w-full bg-surface-container border border-subtle rounded-xl px-4 py-2 text-xs text-on-surface focus:border-primary outline-none">
                </div>

                <div class="pt-2 border-t border-subtle">
                    <label class="block text-xs font-bold uppercase tracking-wider text-outline mb-1.5">Upload Screenshots (Multiple)</label>
                    <input type="file" name="screenshot_files[]" multiple accept="image/*" class="w-full text-xs text-outline file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-surface-container file:text-on-surface hover:file:bg-surface-high cursor-pointer">
                </div>
            </div>

            <!-- Visibility Settings Card -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 border border-subtle">
                <h3 class="text-sm font-bold uppercase tracking-wider text-warning flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">toggle_on</span>
                    Publishing
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded bg-surface-container border-subtle text-primary focus:ring-0">
                        <span class="text-xs font-semibold text-on-surface">Publish Immediately (Active)</span>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="rounded bg-surface-container border-subtle text-primary focus:ring-0">
                        <span class="text-xs font-semibold text-on-surface">Feature on Homepage</span>
                    </label>
                </div>

                <button type="submit" class="btn-electric w-full py-3.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg mt-4 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">publish</span>
                    Publish Software
                </button>
            </div>

        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
