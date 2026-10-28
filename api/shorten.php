<?php
/**
 * API: Pemendek Tautan Baru (Create Short URL)
 * Mendukung Session Auth & API Key Auth
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan POST.']);
    exit;
}

// 1. Ambil Input (JSON body atau form POST)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

// 2. Tentukan Identitas Pembuat Link (User Login atau API Key)
$currentUser = current_user();
if (!$currentUser) {
    // Cek header Authorization Bearer atau parameter api_key
    $authHeader = get_auth_header();
    $apiKey = null;
    if (preg_match('/Bearer\s+(labs_[a-zA-Z0-9_]+)/', $authHeader, $matches)) {
        $apiKey = $matches[1];
    } elseif (!empty($data['api_key'])) {
        $apiKey = trim($data['api_key']);
    } elseif (!empty($_GET['api_key'])) {
        $apiKey = trim($_GET['api_key']);
    }

    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT id, email, full_name, domain, role FROM users WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        $currentUser = $stmt->fetch();
    }
}

if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Autentikasi diperlukan. Silakan login menggunakan akun @labschool.xyz atau sertakan API Key.'
    ]);
    exit;
}

$originalUrl = trim($data['original_url'] ?? '');
$title = trim($data['title'] ?? '');
$customSlug = trim($data['custom_slug'] ?? '');
$password = trim($data['password'] ?? '');
$expiresAt = !empty($data['expires_at']) ? trim($data['expires_at']) : null;
$startsAt = !empty($data['starts_at']) ? trim($data['starts_at']) : null;
$utmSource = trim($data['utm_source'] ?? '');
$utmMedium = trim($data['utm_medium'] ?? '');
$utmCampaign = trim($data['utm_campaign'] ?? '');
$qrFgColor = trim($data['qr_fg_color'] ?? '#1E40AF');
$qrBgColor = trim($data['qr_bg_color'] ?? '#FFFFFF');

// Validasi URL Asli
if (empty($originalUrl)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tautan tujuan (original_url) tidak boleh kosong.']);
    exit;
}

if (!preg_match('/^https?:\/\//i', $originalUrl)) {
    $originalUrl = 'https://' . $originalUrl;
}

if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format tautan tujuan tidak valid. Pastikan dimulai dengan http:// atau https://.']);
    exit;
}

// Terapan UTM Builder jika diisi
if (!empty($utmSource) || !empty($utmMedium) || !empty($utmCampaign)) {
    $parsed = parse_url($originalUrl);
    $query = [];
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    if (!empty($utmSource))   $query['utm_source'] = $utmSource;
    if (!empty($utmMedium))   $query['utm_medium'] = $utmMedium;
    if (!empty($utmCampaign)) $query['utm_campaign'] = $utmCampaign;

    $newQuery = http_build_query($query);
    $originalUrl = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '') . ($parsed['path'] ?? '/') . '?' . $newQuery;
}

// Validasi dan Penanganan Custom Slug
$slug = null;
if (!empty($customSlug)) {
    $cleanSlug = sanitize_slug($customSlug);
    if (strlen($cleanSlug) < 2) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Custom slug minimal harus 2 karakter alfanumerik.']);
        exit;
    }

    // Larang reserved words sistem
    $reserved = ['api', 'assets', 'dashboard', 'login', 'register', 'logout', 'bio', 'p', 'analytics', 'vcard', 'config'];
    if (in_array($cleanSlug, $reserved, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Kata '{$cleanSlug}' adalah reserved slug sistem Labschool."]);
        exit;
    }

    // Cek duplikasi di short_urls atau microsites
    $stmt = $pdo->prepare("SELECT id FROM short_urls WHERE custom_slug = ? OR short_code = ?");
    $stmt->execute([$cleanSlug, $cleanSlug]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => "Slug '{$cleanSlug}' sudah digunakan. Silakan gunakan nama alias lain."]);
        exit;
    }

    $stmt2 = $pdo->prepare("SELECT id FROM microsites WHERE slug = ?");
    $stmt2->execute([$cleanSlug]);
    if ($stmt2->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => "Slug '{$cleanSlug}' sudah digunakan oleh halaman Microsite."]);
        exit;
    }

    $slug = $cleanSlug;
}

// Generate Short Code Unik
do {
    $shortCode = generate_short_code(5);
    $chk = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ? OR custom_slug = ?");
    $chk->execute([$shortCode, $shortCode]);
} while ($chk->fetch());

// Password Protection Hash
$passwordHash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;

// Format Tanggal
$expiresAtSql = !empty($expiresAt) ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;
$startsAtSql = !empty($startsAt) ? date('Y-m-d H:i:s', strtotime($startsAt)) : null;

if (empty($title)) {
    $title = parse_url($originalUrl, PHP_URL_HOST) ?: 'Tautan Singkat';
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO short_urls (
            user_id, short_code, original_url, title, custom_slug,
            password_hash, expires_at, starts_at, utm_source, utm_medium, utm_campaign,
            qr_fg_color, qr_bg_color
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $currentUser['id'], $shortCode, $originalUrl, $title, $slug,
        $passwordHash, $expiresAtSql, $startsAtSql, $utmSource, $utmMedium, $utmCampaign,
        $qrFgColor, $qrBgColor
    ]);
    $newId = (int)$pdo->lastInsertId();

    $effectiveSlug = $slug ?: $shortCode;
    $shortUrl = base_url($effectiveSlug);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Tautan berhasil diperpendek!',
        'data'    => [
            'id'            => $newId,
            'short_code'    => $shortCode,
            'custom_slug'   => $slug,
            'short_url'     => $shortUrl,
            'original_url'  => $originalUrl,
            'title'         => $title,
            'has_password'  => !empty($passwordHash),
            'expires_at'    => $expiresAtSql,
            'qr_fg_color'   => $qrFgColor,
            'qr_bg_color'   => $qrBgColor,
            'qr_url'        => base_url("api/qr.php?code=" . urlencode($effectiveSlug))
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan tautan: ' . $e->getMessage()]);
}
