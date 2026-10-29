<?php
/**
 * API: Hapus Microsite
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

$siteId = (int)($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM microsites WHERE id = ?");
$stmt->execute([$siteId]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Microsite tidak ditemukan.']);
    exit;
}

if (!user_can_manage_microsite($currentUser, $site)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda tidak memiliki izin untuk menghapus microsite ini.']);
    exit;
}

$del = $pdo->prepare("DELETE FROM microsites WHERE id = ?");
$del->execute([$siteId]);

log_activity((int)$currentUser['id'], 'delete_microsite', "Menghapus microsite: {$site['title']} (slug: {$site['slug']})");

echo json_encode(['status' => 'success', 'message' => 'Microsite berhasil dihapus.']);
