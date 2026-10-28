<?php
/**
 * API: Simpan / Perbarui Halaman Microsite (Bio Link) Beserta Blok-Bloknya
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$currentUser = current_user();
if (!$currentUser) {
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
    echo json_encode(['status' => 'error', 'message' => 'Autentikasi diperlukan.']);
    exit;
}

$micrositeId = (int)($data['id'] ?? 0);
$slug = sanitize_slug($data['slug'] ?? '');
$title = trim($data['title'] ?? '');
$bio = trim($data['bio'] ?? '');
$avatarUrl = trim($data['avatar_url'] ?? '');
$theme = trim($data['theme'] ?? 'labs-navy');
$customBgType = trim($data['custom_bg_type'] ?? 'theme');
$customBgValue = trim($data['custom_bg_value'] ?? '');
$buttonStyle = trim($data['button_style'] ?? 'rounded-xl');
$seoTitle = trim($data['seo_title'] ?? '');
$seoDescription = trim($data['seo_description'] ?? '');
$customFooter = trim($data['custom_footer'] ?? 'SMP Labschool Jakarta');
$socialLinks = is_array($data['social_links'] ?? null) ? json_encode($data['social_links']) : null;
$blocks = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Slug microsite (contoh: osis, pramuka) wajib diisi.']);
    exit;
}

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Judul halaman profil tidak boleh kosong.']);
    exit;
}

// Cek reserved keywords
$reserved = ['api', 'assets', 'dashboard', 'login', 'register', 'logout', 'bio', 'p', 'analytics', 'vcard', 'config'];
if (in_array($slug, $reserved, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Slug '{$slug}' merupakan kata terlarang sistem."]);
    exit;
}

// Cek duplikasi slug
if ($micrositeId > 0) {
    $chk = $pdo->prepare("SELECT id FROM microsites WHERE slug = ? AND id != ?");
    $chk->execute([$slug, $micrositeId]);
} else {
    $chk = $pdo->prepare("SELECT id FROM microsites WHERE slug = ?");
    $chk->execute([$slug]);
}
if ($chk->fetch()) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => "Slug '{$slug}' sudah dipakai oleh halaman bio lain."]);
    exit;
}

// Cek juga di short_urls agar tidak bentrok
$chkUrl = $pdo->prepare("SELECT id FROM short_urls WHERE custom_slug = ? OR short_code = ?");
$chkUrl->execute([$slug, $slug]);
if ($chkUrl->fetch()) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan oleh short link."]);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($micrositeId > 0) {
        // Cek kepemilikan
        $stmt = $pdo->prepare("SELECT id, user_id FROM microsites WHERE id = ?");
        $stmt->execute([$micrositeId]);
        $existing = $stmt->fetch();
        if (!$existing || ($existing['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin')) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
            exit;
        }

        $up = $pdo->prepare("
            UPDATE microsites SET
                slug = ?, title = ?, bio = ?, avatar_url = ?, theme = ?,
                custom_bg_type = ?, custom_bg_value = ?, button_style = ?,
                seo_title = ?, seo_description = ?, custom_footer = ?,
                social_links = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $up->execute([
            $slug, $title, $bio, $avatarUrl, $theme,
            $customBgType, $customBgValue, $buttonStyle,
            $seoTitle, $seoDescription, $customFooter,
            $socialLinks, $micrositeId
        ]);
    } else {
        $ins = $pdo->prepare("
            INSERT INTO microsites (
                user_id, slug, title, bio, avatar_url, theme,
                custom_bg_type, custom_bg_value, button_style,
                seo_title, seo_description, custom_footer, social_links
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $currentUser['id'], $slug, $title, $bio, $avatarUrl, $theme,
            $customBgType, $customBgValue, $buttonStyle,
            $seoTitle, $seoDescription, $customFooter, $socialLinks
        ]);
        $micrositeId = (int)$pdo->lastInsertId();
    }

    // Hapus blok lama dan simpan blok baru
    $del = $pdo->prepare("DELETE FROM microsite_blocks WHERE microsite_id = ?");
    $del->execute([$micrositeId]);

    $insBlock = $pdo->prepare("
        INSERT INTO microsite_blocks (
            microsite_id, block_type, title, subtitle, target_url, icon, animation, metadata, display_order, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $order = 0;
    foreach ($blocks as $b) {
        $bType = in_array($b['block_type'] ?? '', ['link', 'social', 'youtube', 'text', 'faq', 'vcard', 'image', 'pdf'])
            ? $b['block_type']
            : 'link';
        $bTitle = trim($b['title'] ?? 'Tautan');
        $bSub = trim($b['subtitle'] ?? '');
        $bUrl = trim($b['target_url'] ?? '');
        $bIcon = trim($b['icon'] ?? 'link');
        $bAnim = trim($b['animation'] ?? 'none');
        $bMeta = is_array($b['metadata'] ?? null) ? json_encode($b['metadata']) : null;

        $insBlock->execute([
            $micrositeId, $bType, $bTitle, $bSub, $bUrl, $bIcon, $bAnim, $bMeta, $order++
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Halaman Microsite berhasil disimpan!',
        'data'    => [
            'id'       => $micrositeId,
            'slug'     => $slug,
            'bio_url'  => base_url("p/{$slug}")
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan microsite: ' . $e->getMessage()]);
}
