<?php
/**
 * FreeDmg - Admin Software Requests Manager
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_auth();

$pdo = get_db_connection();

// Status Change Handler
if (isset($_GET['status']) && isset($_GET['id'])) {
    $reqId = intval($_GET['id']);
    $newStatus = sanitize_text($_GET['status']);
    $csrf = $_GET['csrf'] ?? '';

    if (verify_csrf_token($csrf) && in_array($newStatus, ['Pending', 'Completed', 'Rejected'])) {
        $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?")->execute([$newStatus, $reqId]);
        set_flash_message('success', "Request marked as {$newStatus}.");
    }
    header("Location: requests.php");
    exit;
}

// Delete Handler
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $reqId = intval($_GET['id']);
    $csrf = $_GET['csrf'] ?? '';

    if (verify_csrf_token($csrf)) {
        $pdo->prepare("DELETE FROM requests WHERE id = ?")->execute([$reqId]);
        set_flash_message('success', "Request deleted.");
    }
    header("Location: requests.php");
    exit;
}

$pageTitle = "User Software Requests";
require_once __DIR__ . '/includes/admin_header.php';

// Filter
$statusFilter = sanitize_text($_GET['filter'] ?? 'ALL');
$sql = "SELECT * FROM requests";
$params = [];

if ($statusFilter !== 'ALL' && in_array($statusFilter, ['Pending', 'Completed', 'Rejected'])) {
    $sql .= " WHERE status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Software Requests (<?= count($requests) ?>)</h2>
        <p class="text-xs text-outline mt-0.5">Community requests submitted through the request modal.</p>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center bg-surface-container p-1 rounded-xl border border-subtle text-xs font-bold">
        <a href="requests.php" class="px-3 py-1.5 rounded-lg transition-colors <?= $statusFilter === 'ALL' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">ALL</a>
        <a href="requests.php?filter=Pending" class="px-3 py-1.5 rounded-lg transition-colors <?= $statusFilter === 'Pending' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">Pending</a>
        <a href="requests.php?filter=Completed" class="px-3 py-1.5 rounded-lg transition-colors <?= $statusFilter === 'Completed' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">Completed</a>
        <a href="requests.php?filter=Rejected" class="px-3 py-1.5 rounded-lg transition-colors <?= $statusFilter === 'Rejected' ? 'bg-primary text-on-primary shadow' : 'text-outline hover:text-on-surface' ?>">Rejected</a>
    </div>
</div>

<!-- Requests Table -->
<div class="glass-panel rounded-2xl overflow-hidden border border-subtle">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container/50 text-[11px] uppercase font-bold text-outline border-b border-subtle">
                <tr>
                    <th class="py-3.5 px-6">Software Name</th>
                    <th class="py-3.5 px-4">Version / Cat</th>
                    <th class="py-3.5 px-6">Notes / Link</th>
                    <th class="py-3.5 px-4">Contact</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-subtle">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-outline text-xs">No software requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <td class="py-4 px-6 font-bold text-on-surface text-xs">
                                <?= htmlspecialchars($req['software_name']) ?>
                                <p class="text-[10px] text-outline font-normal mt-0.5"><?= date('M d, Y H:i', strtotime($req['created_at'])) ?></p>
                            </td>
                            <td class="py-4 px-4 text-xs text-outline font-medium">
                                <?= htmlspecialchars($req['version'] ?: 'Latest') ?>
                                <?php if (!empty($req['category'])): ?>
                                    <span class="block text-[10px] text-secondary"><?= htmlspecialchars($req['category']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-xs text-on-surface-variant max-w-xs">
                                <p class="line-clamp-2"><?= htmlspecialchars($req['note'] ?: '&mdash;') ?></p>
                            </td>
                            <td class="py-4 px-4 text-xs text-outline">
                                <?= htmlspecialchars($req['contact'] ?: '&mdash;') ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($req['status'] === 'Completed'): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-success bg-success/15 px-2 py-0.5 rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Completed
                                    </span>
                                <?php elseif ($req['status'] === 'Rejected'): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-error bg-error/15 px-2 py-0.5 rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Rejected
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-warning bg-warning/15 px-2 py-0.5 rounded-md">
                                        <span class="w-1.5 h-1.5 rounded-full bg-warning"></span> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <?php if ($req['status'] !== 'Completed'): ?>
                                        <a href="requests.php?status=Completed&id=<?= $req['id'] ?>&csrf=<?= get_csrf_token() ?>" class="p-1.5 rounded-lg hover:bg-success/15 text-outline hover:text-success transition-colors" title="Mark as Completed">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($req['status'] !== 'Rejected'): ?>
                                        <a href="requests.php?status=Rejected&id=<?= $req['id'] ?>&csrf=<?= get_csrf_token() ?>" class="p-1.5 rounded-lg hover:bg-warning/15 text-outline hover:text-warning transition-colors" title="Reject Request">
                                            <span class="material-symbols-outlined text-[18px]">cancel</span>
                                        </a>
                                    <?php endif; ?>

                                    <a href="software-add.php?req_title=<?= urlencode($req['software_name']) ?>" class="p-1.5 rounded-lg hover:bg-primary/15 text-outline hover:text-primary transition-colors" title="Add This Software Release">
                                        <span class="material-symbols-outlined text-[18px]">add_box</span>
                                    </a>

                                    <a href="requests.php?action=delete&id=<?= $req['id'] ?>&csrf=<?= get_csrf_token() ?>" onclick="return confirm('Delete this request permanently?');" class="p-1.5 rounded-lg hover:bg-error/15 text-outline hover:text-error transition-colors" title="Delete">
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
