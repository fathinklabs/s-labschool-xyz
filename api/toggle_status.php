<?php
/**
 * API: Toggle Aktif / Non-aktif Tautan (Pro Feature)
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

// Validasi CSRF untuk autentikasi sesi browser
$authHeader = get_auth_header();
$isApiKeyAuth = preg_match('/Bearer\s+(labs_[a-zA-Z0-9_]+)/', $authHeader);
if (!$isApiKeyAuth) {
    $token = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token keamanan (CSRF) tidak valid atau sesi telah kedaluwarsa. Silakan muat ulang halaman.']);
        exit;
    }
}

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

$newStatus = ($link['is_active'] == 1) ? 0 : 1;
$up = $pdo->prepare("UPDATE short_urls SET is_active = ?, updated_at = NOW() WHERE id = ?");
$up->execute([$newStatus, $linkId]);

$actionText = $newStatus ? 'mengaktifkan' : 'menonaktifkan';
log_activity((int)$currentUser['id'], 'toggle_link_status', ucfirst($actionText) . " tautan: {$link['title']}");

echo json_encode([
    'status'    => 'success',
    'is_active' => $newStatus,
    'message'   => $newStatus ? 'Tautan berhasil diaktifkan.' : 'Tautan dinonaktifkan sementara.'
]);
