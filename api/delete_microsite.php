<?php
/**
 * API: Hapus Microsite
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$currentUser = current_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Autentikasi diperlukan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$siteId = (int)($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, user_id FROM microsites WHERE id = ?");
$stmt->execute([$siteId]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Microsite tidak ditemukan.']);
    exit;
}

if ($site['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM microsites WHERE id = ?");
$del->execute([$siteId]);

echo json_encode(['status' => 'success', 'message' => 'Microsite berhasil dihapus.']);
