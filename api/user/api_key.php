<?php
/**
 * API: Manajemen API Key Pengguna (Tampilkan Hanya Sekali & Reset)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$currentUser = current_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Autentikasi diperlukan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = trim($data['action'] ?? 'generate');

if ($action === 'generate' || $action === 'reset') {
    $newKey = 'labs_' . bin2hex(random_bytes(24));

    $up = $pdo->prepare("UPDATE users SET api_key = ?, api_key_created_at = NOW() WHERE id = ?");
    $up->execute([$newKey, $currentUser['id']]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'API Key baru berhasil dibuat. Salin sekarang karena kunci ini hanya ditampilkan sekali demi keamanan!',
        'api_key' => $newKey,
        'created_at' => date('d M Y H:i') . ' WIB'
    ]);
    exit;
} elseif ($action === 'revoke') {
    $up = $pdo->prepare("UPDATE users SET api_key = NULL, api_key_created_at = NULL WHERE id = ?");
    $up->execute([$currentUser['id']]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'API Key berhasil dinonaktifkan / dicabut.'
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
