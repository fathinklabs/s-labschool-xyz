<?php
/**
 * API: Hapus Tautan
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

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
$linkId = (int)($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE id = ?");
$stmt->execute([$linkId]);
$link = $stmt->fetch();

if (!$link) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Tautan tidak ditemukan.']);
    exit;
}

if (!user_can_manage_link($currentUser, $link)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda belum memiliki hak akses atau belum ditugaskan ke grup yang sesuai.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM short_urls WHERE id = ?");
$del->execute([$linkId]);

log_activity((int)$currentUser['id'], 'delete_link', "Menghapus tautan: {$link['title']} (" . ($link['custom_slug'] ?: $link['short_code']) . ")");

echo json_encode(['status' => 'success', 'message' => 'Tautan berhasil dihapus.']);
