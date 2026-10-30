<?php
/**
 * API: Simpan / Perbarui Halaman Page (Labs Pages) Beserta Seluruh Blok Komponennya
 * Modern Replacement for api/save_microsite.php
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

// Validasi CSRF untuk request berbasis sesi browser (bukan API key Bearer)
if (empty($apiKey)) {
    $token = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token keamanan (CSRF) tidak valid atau sesi telah kedaluwarsa. Silakan refresh halaman.']);
        exit;
    }
}

$pageId = (int)($data['id'] ?? 0);
$slug = sanitize_slug($data['slug'] ?? '');
$title = trim($data['title'] ?? '');
$bio = trim($data['bio'] ?? '');
$avatarUrl = sanitize_safe_url(trim($data['avatar_url'] ?? ''), '');
$theme = trim($data['theme'] ?? 'labs-navy');
$templateStyle = trim($data['template_style'] ?? 'genially-showcase');
$countdownDate = !empty($data['countdown_date']) ? date('Y-m-d H:i:s', strtotime($data['countdown_date'])) : null;
$countdownTitle = trim($data['countdown_title'] ?? '');
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
    echo json_encode(['status' => 'error', 'message' => 'Slug Page (contoh: osis, openhouse) wajib diisi.']);
    exit;
}

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Judul Page tidak boleh kosong.']);
    exit;
}

// Cek reserved keywords
$reserved = ['api', 'assets', 'dashboard', 'login', 'register', 'logout', 'bio', 'p', 'analytics', 'vcard', 'config', 'page_builder', 'settings', 'admin'];
if (in_array($slug, $reserved, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Slug '{$slug}' merupakan kata terlarang sistem."]);
    exit;
}

// Cek duplikasi slug
if ($pageId > 0) {
    $chk = $pdo->prepare("SELECT id FROM microsites WHERE slug = ? AND id != ?");
    $chk->execute([$slug, $pageId]);
} else {
    $chk = $pdo->prepare("SELECT id FROM microsites WHERE slug = ?");
    $chk->execute([$slug]);
}
if ($chk->fetch()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Slug '{$slug}' sudah digunakan oleh pengguna lain. Silakan pilih slug lain."]);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($pageId > 0) {
        // Verifikasi kepemilikan
        $checkOwner = $pdo->prepare("SELECT id, user_id FROM microsites WHERE id = ?");
        $checkOwner->execute([$pageId]);
        $existing = $checkOwner->fetch();

        if (!$existing) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Halaman Page tidak ditemukan.']);
            exit;
        }

        if ($existing['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin') {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses mengubah Page ini.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE microsites SET
                slug = ?, title = ?, bio = ?, avatar_url = ?, theme = ?, template_style = ?,
                countdown_date = ?, countdown_title = ?, custom_bg_type = ?, custom_bg_value = ?,
                button_style = ?, seo_title = ?, seo_description = ?, custom_footer = ?,
                social_links = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $slug, $title, $bio, $avatarUrl, $theme, $templateStyle,
            $countdownDate, $countdownTitle, $customBgType, $customBgValue,
            $buttonStyle, $seoTitle, $seoDescription, $customFooter,
            $socialLinks, $pageId
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO microsites (
                user_id, slug, title, bio, avatar_url, theme, template_style,
                countdown_date, countdown_title, custom_bg_type, custom_bg_value, button_style,
                seo_title, seo_description, custom_footer, social_links
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $currentUser['id'], $slug, $title, $bio, $avatarUrl, $theme, $templateStyle,
            $countdownDate, $countdownTitle, $customBgType, $customBgValue, $buttonStyle,
            $seoTitle, $seoDescription, $customFooter, $socialLinks
        ]);
        $pageId = (int)$pdo->lastInsertId();
    }

    // Hapus blok lama dan simpan blok baru
    $del = $pdo->prepare("DELETE FROM microsite_blocks WHERE microsite_id = ?");
    $del->execute([$pageId]);

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
        $bUrl = sanitize_safe_url(trim($b['target_url'] ?? ''), '');
        $bIcon = trim($b['icon'] ?? 'link');
        $bAnim = trim($b['animation'] ?? 'none');
        $bMeta = is_array($b['metadata'] ?? null) ? json_encode($b['metadata']) : null;

        $insBlock->execute([
            $pageId, $bType, $bTitle, $bSub, $bUrl, $bIcon, $bAnim, $bMeta, $order++
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Halaman Page berhasil disimpan!',
        'data'    => [
            'id'       => $pageId,
            'slug'     => $slug,
            'page_url' => base_url("p/{$slug}"),
            'bio_url'  => base_url("p/{$slug}")
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan Page: ' . $e->getMessage()]);
}
