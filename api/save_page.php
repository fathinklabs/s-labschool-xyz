<?php
/**
 * API: Simpan / Perbarui Halaman Page (Labs Pages) Beserta Seluruh Blok Komponennya
 * Modern Replacement for api/save_microsite.php
 * Mendukung Paritas Fitur Lengkap s.id:
 * - Cover Banner Header
 * - Custom Wallpaper / Background (Color/Gradient/Image, Blur, Dimming)
 * - Custom Settings (Title Bold/Italic, Bio Font & Styling)
 * - Blok Tautan Kaya (Custom Logo Image, Custom Button Colors, Alignment, Password Protect, Keep in Page)
 * - Blok Image / Banner Promosi
 * - SEO & Google Analytics (gtag_id)
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
$headerBgUrl = sanitize_safe_url(trim($data['header_bg_url'] ?? ''), '');
$theme = trim($data['theme'] ?? 'labs-navy');
$templateStyle = trim($data['template_style'] ?? 'genially-showcase');
$countdownDate = !empty($data['countdown_date']) ? date('Y-m-d H:i:s', strtotime($data['countdown_date'])) : null;
$countdownTitle = trim($data['countdown_title'] ?? '');

$customBgType = in_array($data['custom_bg_type'] ?? '', ['theme', 'color', 'gradient', 'image'], true)
    ? $data['custom_bg_type']
    : 'theme';
$customBgValue = trim($data['custom_bg_value'] ?? '');
$bgBlur = max(0, min(50, (int)($data['bg_blur'] ?? 0)));
$bgDark = max(0, min(100, (int)($data['bg_dark'] ?? 0)));

$buttonStyle = trim($data['button_style'] ?? 'rounded-xl');
$seoTitle = trim($data['seo_title'] ?? '');
$seoDescription = trim($data['seo_description'] ?? '');
$seoImage = sanitize_safe_url(trim($data['seo_image'] ?? ''), '');
$gtagId = trim($data['gtag_id'] ?? '');
$customFooter = trim($data['custom_footer'] ?? 'SMP Labschool Jakarta');

$socialLinks = is_array($data['social_links'] ?? null) ? json_encode($data['social_links']) : null;
$customSettings = is_array($data['custom_settings'] ?? null) 
    ? json_encode($data['custom_settings']) 
    : (!empty($data['custom_settings']) && is_string($data['custom_settings']) ? $data['custom_settings'] : null);

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
                slug = ?, title = ?, bio = ?, avatar_url = ?, header_bg_url = ?,
                theme = ?, template_style = ?,
                countdown_date = ?, countdown_title = ?,
                custom_bg_type = ?, custom_bg_value = ?, bg_blur = ?, bg_dark = ?,
                button_style = ?, seo_title = ?, seo_description = ?, seo_image = ?, gtag_id = ?,
                custom_footer = ?, social_links = ?, custom_settings = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $slug, $title, $bio, $avatarUrl, $headerBgUrl,
            $theme, $templateStyle,
            $countdownDate, $countdownTitle,
            $customBgType, $customBgValue, $bgBlur, $bgDark,
            $buttonStyle, $seoTitle, $seoDescription, $seoImage, $gtagId,
            $customFooter, $socialLinks, $customSettings,
            $pageId
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO microsites (
                user_id, slug, title, bio, avatar_url, header_bg_url,
                theme, template_style,
                countdown_date, countdown_title,
                custom_bg_type, custom_bg_value, bg_blur, bg_dark,
                button_style, seo_title, seo_description, seo_image, gtag_id,
                custom_footer, social_links, custom_settings
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
        ");
        $stmt->execute([
            $currentUser['id'], $slug, $title, $bio, $avatarUrl, $headerBgUrl,
            $theme, $templateStyle,
            $countdownDate, $countdownTitle,
            $customBgType, $customBgValue, $bgBlur, $bgDark,
            $buttonStyle, $seoTitle, $seoDescription, $seoImage, $gtagId,
            $customFooter, $socialLinks, $customSettings
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

        // Metadata gabungan (mendukung fitur s.id: logo image, warna tombol kustom, password protection, keep in page)
        $bMetaArr = is_array($b['metadata'] ?? null) ? $b['metadata'] : [];
        if (!empty($b['image_icon'])) {
            $bMetaArr['image_icon'] = sanitize_safe_url(trim($b['image_icon']), '');
        }
        if (!empty($b['button_color'])) {
            $bMetaArr['button_color'] = trim($b['button_color']);
        }
        if (!empty($b['button_text_color'])) {
            $bMetaArr['button_text_color'] = trim($b['button_text_color']);
        }
        if (!empty($b['button_border_color'])) {
            $bMetaArr['button_border_color'] = trim($b['button_border_color']);
        }
        if (!empty($b['button_type'])) {
            $bMetaArr['button_type'] = trim($b['button_type']);
        }
        if (!empty($b['text_position'])) {
            $bMetaArr['text_position'] = trim($b['text_position']);
        }
        if (isset($b['password_active'])) {
            $bMetaArr['password_active'] = (bool)$b['password_active'];
        }
        if (!empty($b['password'])) {
            $bMetaArr['password'] = trim($b['password']);
        }
        if (isset($b['keep_in_page'])) {
            $bMetaArr['keep_in_page'] = (bool)$b['keep_in_page'];
        }

        $bMeta = !empty($bMetaArr) ? json_encode($bMetaArr) : null;

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
