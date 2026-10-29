<?php
/**
 * Admin API: Manajemen Pengguna (Users Management)
 * Hanya dapat diakses oleh role superadmin
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$currentUser = current_user();
if (!$currentUser || $currentUser['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Khusus superadmin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = trim($data['action'] ?? '');
$targetUserId = (int)($data['user_id'] ?? 0);

if ($targetUserId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pengguna tidak valid.']);
    exit;
}

// Cek target user
$stmt = $pdo->prepare("SELECT id, email, full_name, role, is_active FROM users WHERE id = ?");
$stmt->execute([$targetUserId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak ditemukan.']);
    exit;
}

// 1. Toggle Status Aktif / Non-aktif
if ($action === 'toggle_status') {
    // Jangan izinkan menonaktifkan diri sendiri
    if ($targetUserId === (int)$currentUser['id']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        exit;
    }

    $newStatus = ($targetUser['is_active'] == 1) ? 0 : 1;
    $up = $pdo->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$newStatus, $targetUserId]);

    echo json_encode([
        'status'    => 'success',
        'is_active' => $newStatus,
        'message'   => $newStatus ? 'Akun pengguna berhasil diaktifkan.' : 'Akun pengguna berhasil diblokir/dinonaktifkan.'
    ]);
    exit;
}

// 2. Ubah Role Pengguna
if ($action === 'change_role') {
    $allowedRoles = ['superadmin', 'teacher', 'staff', 'student'];
    $newRole = trim($data['role'] ?? '');

    if (!in_array($newRole, $allowedRoles, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Peran (role) tidak valid.']);
        exit;
    }

    $up = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$newRole, $targetUserId]);

    echo json_encode([
        'status'  => 'success',
        'role'    => $newRole,
        'message' => "Peran akun {$targetUser['email']} diubah menjadi " . strtoupper($newRole) . "."
    ]);
    exit;
}

// 3. Reset Kata Sandi Pengguna
if ($action === 'reset_password') {
    $newPass = trim($data['new_password'] ?? '');
    if (strlen($newPass) < 6) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Kata sandi baru minimal 6 karakter.']);
        exit;
    }

    $cleanPass = str_replace(["\0", "\x00"], '', $newPass);
    $hash = password_hash($cleanPass, PASSWORD_DEFAULT);

    $up = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$hash, $targetUserId]);

    echo json_encode([
        'status'  => 'success',
        'message' => "Kata sandi akun {$targetUser['email']} berhasil diperbarui."
    ]);
    exit;
}

// 4. Hapus Pengguna Secara Permanen
if ($action === 'delete_user') {
    if ($targetUserId === (int)$currentUser['id']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        exit;
    }

    $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $del->execute([$targetUserId]);

    echo json_encode([
        'status'  => 'success',
        'message' => "Akun {$targetUser['email']} beserta seluruh link dan micrositenya telah dihapus."
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
