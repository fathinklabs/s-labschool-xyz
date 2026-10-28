<?php
/**
 * API: Hapus Tautan
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
$linkId = (int)($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, user_id FROM short_urls WHERE id = ?");
$stmt->execute([$linkId]);
$link = $stmt->fetch();

if (!$link) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Tautan tidak ditemukan.']);
    exit;
}

if ($link['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM short_urls WHERE id = ?");
$del->execute([$linkId]);

echo json_encode(['status' => 'success', 'message' => 'Tautan berhasil dihapus.']);
