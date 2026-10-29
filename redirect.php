<?php
/**
 * High-Speed Redirection Engine & Password Protection
 * Domain: s.labschool.xyz
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    // Jika tidak ada slug, redirect ke beranda
    header('Location: /index.php');
    exit;
}

$cleanSlug = sanitize_slug($slug);

// 1. Cek apakah cocok dengan Halaman Microsite terlebih dahulu
$stmtBio = $pdo->prepare("SELECT id, slug, is_active FROM microsites WHERE slug = ? LIMIT 1");
$stmtBio->execute([$cleanSlug]);
$bioRow = $stmtBio->fetch();

if ($bioRow) {
    if ((int)$bioRow['is_active'] !== 1) {
        render_error_page('Halaman Dinonaktifkan', 'Halaman profil microsite ini sedang dinonaktifkan oleh pengelola.');
    }
    // Increment microsite click
    $pdo->prepare("UPDATE microsites SET clicks_count = clicks_count + 1 WHERE id = ?")->execute([$bioRow['id']]);
    $_GET['slug'] = $cleanSlug;
    require __DIR__ . '/bio.php';
    exit;
}

// 2. Cek apakah cocok dengan Short URL
$stmtUrl = $pdo->prepare("
    SELECT id, short_code, custom_slug, original_url, title, password_hash,
           expires_at, starts_at, is_active, clicks_count, ios_url, android_url
    FROM short_urls
    WHERE short_code = ? OR custom_slug = ?
    LIMIT 1
");
$stmtUrl->execute([$cleanSlug, $cleanSlug]);
$urlRow = $stmtUrl->fetch();

if (!$urlRow) {
    render_error_page('Tautan Tidak Ditemukan', "Tautan pendek <strong>s.labschool.xyz/{$cleanSlug}</strong> tidak ditemukan di sistem kami. Pastikan alamat yang Anda masukkan sudah benar.", 404);
}

// Validasi Status Aktif
if ((int)$urlRow['is_active'] !== 1) {
    render_error_page('Tautan Dinonaktifkan', 'Tautan ini dinonaktifkan sementara oleh pemilik tautan.');
}

// Validasi Jadwal Mulai Berlaku
if (!empty($urlRow['starts_at']) && strtotime($urlRow['starts_at']) > time()) {
    $starts = date('d M Y H:i', strtotime($urlRow['starts_at']));
    render_error_page('Tautan Belum Dimulai', "Tautan ini dijadwalkan baru dapat diakses pada <strong>{$starts} WIB</strong>.");
}

// Validasi Tanggal Kadaluarsa
if (!empty($urlRow['expires_at']) && strtotime($urlRow['expires_at']) < time()) {
    $expired = date('d M Y H:i', strtotime($urlRow['expires_at']));
    render_error_page('Tautan Kadaluarsa', "Masa berlaku tautan ini telah berakhir pada <strong>{$expired} WIB</strong>.");
}

// Validasi Password Protection (Fitur Pro s.id)
if (!empty($urlRow['password_hash'])) {
    $sessionKey = 'auth_link_' . $urlRow['id'];
    $isAuthed = !empty($_SESSION[$sessionKey]);

    if (!$isAuthed) {
        $errorMessage = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_password'])) {
            $inputPass = (string)$_POST['link_password'];
            if (password_verify($inputPass, $urlRow['password_hash'])) {
                $_SESSION[$sessionKey] = true;
                $isAuthed = true;
            } else {
                $errorMessage = 'Kata sandi atau PIN yang Anda masukkan salah!';
            }
        }

        if (!$isAuthed) {
            render_password_prompt($urlRow, $errorMessage);
            exit;
        }
    }
}

// Catat log klik analitik (IP, Geolokasi CF, User Agent, Referrer)
record_click((int)$urlRow['id']);

// Cek Device-based Routing (Deep Linking Native Apps)
$targetUrl = $urlRow['original_url'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!empty($urlRow['ios_url']) && preg_match('/(iPhone|iPad|iPod)/i', $userAgent)) {
    $targetUrl = $urlRow['ios_url'];
} elseif (!empty($urlRow['android_url']) && preg_match('/Android/i', $userAgent)) {
    $targetUrl = $urlRow['android_url'];
}

// Eksekusi Redirect Langsung HTTP 302
header("Location: {$targetUrl}", true, 302);
header("Cache-Control: no-cache, no-store, must-revalidate");
exit;


/**
 * Tampilkan formulir proteksi password bergaya Labschool
 */
function render_password_prompt(array $link, string $error = ''): void {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tautan Terproteksi — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Nunito Sans"', 'sans-serif'] },
                    colors: {
                        labs: { blue: '#2563EB', dark: '#1E40AF', amber: '#F59E0B' }
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 text-center relative overflow-hidden">
        <!-- Accent Top Bar -->
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-blue-600 via-blue-700 to-amber-500"></div>
        
        <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-16 mx-auto mb-4 object-contain">
        <h1 class="text-xl font-extrabold text-slate-900 mb-1">Tautan Ini Dilindungi Sandi</h1>
        <p class="text-sm text-slate-500 mb-6">Pemilik tautan <strong><?= htmlspecialchars($link['title']) ?></strong> memproteksi akses menuju halaman ini.</p>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-semibold">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <input type="password" name="link_password" placeholder="Masukkan PIN / Kata Sandi..." required autofocus
                    class="w-full px-4 py-3 text-center tracking-widest text-lg font-bold rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 outline-none transition">
            </div>
            <button type="submit"
                class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5">
                Buka Tautan Menuju Halaman
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-xs text-slate-400">
            <span>Official Link Protection</span> &bull; <span>SMP Labschool Jakarta</span>
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * Tampilkan Halaman Error / Peringatan
 */
function render_error_page(string $title, string $message, int $code = 400): void {
    http_response_code($code);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 text-center">
        <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-16 mx-auto mb-4 object-contain">
        <div class="inline-flex p-3 rounded-2xl bg-amber-50 text-amber-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-900 mb-2"><?= htmlspecialchars($title) ?></h1>
        <p class="text-sm text-slate-500 mb-6 leading-relaxed"><?= $message ?></p>
        <a href="/" class="inline-flex items-center justify-center py-3 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition shadow-lg shadow-blue-500/20 text-sm">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
<?php
    exit;
}
