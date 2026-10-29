<?php
/**
 * API: Update Short Link (Fitur Pro: Edit Destination URL, Custom Slug, Password, Expiration)
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
    // Cek header Authorization Bearer atau parameter api_key
    $authHeader = get_auth_header();
    $apiKey = null;
    if (preg_match('/Bearer\s+(labs_[a-zA-Z0-9_]+)/', $authHeader, $matches)) {
        $apiKey = $matches[1];
    }
    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT id, email, full_name, domain, role FROM users WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        $currentUser = $stmt->fetch();
    }
}

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Autentikasi diperlukan.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$linkId = (int)($data['id'] ?? 0);
$originalUrl = trim($data['original_url'] ?? '');
$title = trim($data['title'] ?? '');
$customSlug = trim($data['custom_slug'] ?? '');
$password = trim($data['password'] ?? '');
$removePassword = !empty($data['remove_password']);
$expiresAt = !empty($data['expires_at']) ? trim($data['expires_at']) : null;
$qrFgColor = trim($data['qr_fg_color'] ?? '#1E40AF');
$qrBgColor = trim($data['qr_bg_color'] ?? '#FFFFFF');
$tags = isset($data['tags']) ? trim($data['tags']) : null;
$iosUrl = isset($data['ios_url']) ? trim($data['ios_url']) : null;
$androidUrl = isset($data['android_url']) ? trim($data['android_url']) : null;
$qrFrame = isset($data['qr_frame']) ? trim($data['qr_frame']) : 'none';
$qrCtaText = isset($data['qr_cta_text']) ? trim($data['qr_cta_text']) : 'PINDAI SAYA';

// Verifikasi kepemilikan tautan
$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE id = ?");
$stmt->execute([$linkId]);
$link = $stmt->fetch();

if (!$link) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Tautan tidak ditemukan.']);
    exit;
}

if ($link['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk mengedit tautan ini.']);
    exit;
}

// Validasi original URL
if (!empty($originalUrl)) {
    if (!preg_match('/^https?:\/\//i', $originalUrl)) {
        $originalUrl = 'https://' . $originalUrl;
    }
    if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Format URL tujuan tidak valid.']);
        exit;
    }
} else {
    $originalUrl = $link['original_url'];
}

// Cek custom slug baru jika diubah
$newSlug = $link['custom_slug'];
if ($customSlug !== ($link['custom_slug'] ?? '')) {
    if (!empty($customSlug)) {
        $cleanSlug = sanitize_slug($customSlug);
        $chk = $pdo->prepare("SELECT id FROM short_urls WHERE (custom_slug = ? OR short_code = ?) AND id != ?");
        $chk->execute([$cleanSlug, $cleanSlug, $linkId]);
        if ($chk->fetch()) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => "Slug '{$cleanSlug}' sudah digunakan."]);
            exit;
        }
        $newSlug = $cleanSlug;
    } else {
        $newSlug = null;
    }
}

// Password
$newPasswordHash = $link['password_hash'];
if ($removePassword) {
    $newPasswordHash = null;
} elseif (!empty($password)) {
    $newPasswordHash = password_hash($password, PASSWORD_BCRYPT);
}

$expiresAtSql = !empty($expiresAt) ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;

try {
    $up = $pdo->prepare("
        UPDATE short_urls SET
            original_url = ?,
            title = ?,
            custom_slug = ?,
            password_hash = ?,
            expires_at = ?,
            tags = ?,
            ios_url = ?,
            android_url = ?,
            qr_fg_color = ?,
            qr_bg_color = ?,
            qr_frame = ?,
            qr_cta_text = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $up->execute([
        $originalUrl, $title, $newSlug,
        $newPasswordHash, $expiresAtSql,
        $tags ?? $link['tags'],
        $iosUrl !== null ? $iosUrl : $link['ios_url'],
        $androidUrl !== null ? $androidUrl : $link['android_url'],
        $qrFgColor, $qrBgColor,
        $qrFrame, $qrCtaText,
        $linkId
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Tautan berhasil diperbarui!',
        'data'    => [
            'id'           => $linkId,
            'original_url' => $originalUrl,
            'title'        => $title,
            'custom_slug'  => $newSlug,
            'short_url'    => base_url($newSlug ?: $link['short_code']),
            'has_password' => !empty($newPasswordHash)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
}
