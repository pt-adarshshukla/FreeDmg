<?php
/**
 * FreeDmg - Dedicated Safe Download Engine
 * Handles download streaming, counter increments, MIME types, and prevents blank white screens.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$pdo = get_db_connection();

$id = intval($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');

$software = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM software WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$id]);
    $software = $stmt->fetch();
} elseif (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM software WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $software = $stmt->fetch();
}

if (!$software) {
    header("HTTP/1.0 404 Not Found");
    die("Download Error: Software package not found or inactive.");
}

// Increment download counter and log transfer
increment_download_count($software['id']);

$localFile = $software['file_path'];
$externalUrl = $software['external_download_url'];

// Case 1: Local Uploaded File Exists
if (!empty($localFile) && file_exists(__DIR__ . '/' . ltrim($localFile, '/'))) {
    $fullPath = __DIR__ . '/' . ltrim($localFile, '/');
    $fileName = basename($fullPath);
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    $mimeTypes = [
        'dmg' => 'application/x-apple-diskimage',
        'pkg' => 'application/octet-stream',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z'  => 'application/x-7z-compressed',
        'gz'  => 'application/gzip'
    ];

    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

    // Clear output buffering to prevent corrupt downloads
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send proper download attachment headers
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));

    readfile($fullPath);
    exit;
}

// Case 2: External Direct Download Link
if (!empty($externalUrl)) {
    // If request comes from an iframe or browser directly
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) {
        header("Location: " . $externalUrl, true, 302);
        exit;
    }

    // Direct Browser Access Fallback View (Never Blank White Screen)
    $pageTitle = "Downloading " . $software['title'] . " - FreeDmg";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="flex-grow pt-32 pb-24 px-4 max-w-container-max mx-auto w-full text-center">
        <div class="glass-panel rounded-2xl p-10 max-w-lg mx-auto shadow-2xl border border-subtle">
            <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center mx-auto mb-4 text-primary">
                <span class="material-symbols-outlined text-3xl animate-bounce">cloud_download</span>
            </div>

            <h1 class="text-2xl font-bold text-on-surface mb-2">Starting Your Download...</h1>
            <p class="text-sm text-outline mb-6">
                Your transfer for <strong class="text-on-surface"><?= htmlspecialchars($software['title']) ?> (<?= htmlspecialchars($software['version']) ?>)</strong> is beginning now.
            </p>

            <a href="<?= htmlspecialchars($externalUrl) ?>" class="btn-electric w-full py-3.5 rounded-xl font-bold uppercase tracking-wider text-xs inline-flex items-center justify-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Click Here If Download Does Not Start
            </a>

            <div class="mt-4 text-xs text-outline">
                <a href="<?= $baseUrl ?>/app.php?slug=<?= urlencode($software['slug']) ?>" class="hover:underline text-primary">← Return to software details</a>
            </div>
        </div>
    </main>

    <script>
        // Automatic trigger
        setTimeout(() => {
            window.location.href = <?= json_encode($externalUrl) ?>;
        }, 1200);
    </script>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Case 3: No file configured
die("Download error: No file package or external URL configured for this release.");
