<?php
/**
 * FreeDmg - Software Request Handler
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please refresh the page and try again.']);
    exit;
}

$softwareName = sanitize_text($_POST['software_name'] ?? '');
$version = sanitize_text($_POST['version'] ?? '');
$category = sanitize_text($_POST['category'] ?? '');
$note = sanitize_text($_POST['note'] ?? '');
$contact = sanitize_text($_POST['contact'] ?? '');

if (empty($softwareName)) {
    echo json_encode(['success' => false, 'error' => 'Please provide the name of the software you want to request.']);
    exit;
}

$pdo = get_db_connection();
try {
    $stmt = $pdo->prepare("INSERT INTO requests (software_name, version, category, note, contact, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$softwareName, $version, $category, $note, $contact]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
}
