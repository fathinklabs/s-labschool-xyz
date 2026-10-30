<?php
/**
 * Halaman & Endpoint Lupa Kata Sandi (Request Password Reset)
 * Mengirimkan tautan reset token unik via XOAUTH2 Google Workspace (admin@labschool.xyz)
 * SMP Labschool Jakarta — s.labschool.xyz
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/mail.php';

// Jika sudah login, arahkan ke dashboard
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flashError = 'Format alamat email tidak valid.';
    } elseif (!is_allowed_domain($email)) {
        $flashError = 'Hanya alamat email resmi civitas Labschool (@labschool.xyz / @elabschool.sch.id) yang didukung.';
    } else {
        // Rate limiting: Cek permintaan dari IP dan email dalam 1 jam terakhir (maksimal 5 kali)
        try {
            $rlStmt = $pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE (ip_address = ? OR email = ?) AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $rlStmt->execute([$ip, $email]);
            $requestCount = (int)$rlStmt->fetchColumn();

            if ($requestCount >= 5) {
                $flashError = 'Terlalu banyak permintaan atur ulang kata sandi. Silakan tunggu 1 jam sebelum mencoba kembali.';
            } else {
                // Cari pengguna di basis data
                $uStmt = $pdo->prepare("SELECT id, email, full_name, is_active FROM users WHERE email = ? LIMIT 1");
                $uStmt->execute([$email]);
                $user = $uStmt->fetch();

                if ($user && (int)$user['is_active'] === 1) {
                    // Nonaktifkan token lama yang belum terpakai untuk email ini
                    $expireOldStmt = $pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0");
                    $expireOldStmt->execute([$email]);

                    // Generate token aman kriptografis (64 karakter hex)
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 60)); // Berlaku 30 menit

                    $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token, ip_address, user_agent, is_used, expires_at, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())");
                    $insStmt->execute([$email, $token, $ip, substr($ua, 0, 250), $expiresAt]);

                    // Kirim email resmi melalui Google Workspace XOAUTH2
                    $resetLink = 'https://s.labschool.xyz/reset_password.php?token=' . urlencode($token);
                    $mailResult = send_password_reset_email($user['email'], $user['full_name'], $resetLink, $ip);

                    if (!$mailResult['success']) {
                        error_log("[Password Reset Error] Gagal mengirim email ke {$email}: " . $mailResult['message']);
                    }
                }

                // Selalu tampilkan pesan sukses generik untuk mencegah user enumeration
                $flashSuccess = 'Jika email Anda terdaftar di sistem kami, tautan pengaturan ulang kata sandi telah dikirimkan ke kotak masuk email resmi Anda. Tautan berlaku selama 30 menit.';
            }
        } catch (Throwable $e) {
            $flashError = 'Terjadi kesalahan pada sistem saat memproses permintaan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
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
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between font-sans antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-9 w-auto">
                <div>
                    <span class="text-base font-black text-slate-900 tracking-tight">s.labschool.xyz</span>
                    <span class="text-[10px] text-blue-600 block font-bold leading-none">SMP Labschool Jakarta</span>
                </div>
            </a>
            <a href="/login.php" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition flex items-center gap-1.5">
                <span class="material-symbols-rounded text-base">arrow_back</span>
                <span>Kembali ke Login</span>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center p-4 py-12">
        <div class="max-w-md w-full bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-900/5 border border-slate-200/80 space-y-6">
            
            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2">
                    <span class="material-symbols-rounded text-3xl">lock_reset</span>
                </div>
                <h1 class="text-xl font-black text-slate-900">Lupa Kata Sandi?</h1>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Masukkan email resmi sekolah Anda. Kami akan mengirimkan tautan aman untuk membuat kata sandi baru.
                </p>
            </div>

            <?php if (!empty($flashSuccess)): ?>
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold space-y-1">
                    <div class="flex items-center gap-2 font-bold text-emerald-900">
                        <span class="material-symbols-rounded text-base text-emerald-600">check_circle</span>
                        <span>Tautan Terkirim!</span>
                    </div>
                    <p class="leading-relaxed"><?= htmlspecialchars($flashSuccess) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-rounded text-base text-rose-600 shrink-0">error</span>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($flashSuccess)): ?>
                <form method="POST" action="/forgot_password.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label for="email" class="block text-xs font-extrabold text-slate-700 mb-1.5">Email Civitas Labschool *</label>
                        <div class="relative">
                            <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">mail</span>
                            <input type="email" id="email" name="email" required placeholder="nama@labschool.xyz"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Didukung: <strong>@labschool.xyz</strong> dan <strong>@elabschool.sch.id</strong></span>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                        <span class="material-symbols-rounded text-base">send</span>
                        <span>Kirim Tautan Atur Ulang</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="pt-2 text-center">
                    <a href="/login.php" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs transition shadow-sm">
                        <span class="material-symbols-rounded text-base">login</span>
                        <span>Menuju Halaman Masuk</span>
                    </a>
                </div>
            <?php endif; ?>

            <div class="pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">
                    Ingat kata sandi Anda? 
                    <a href="/login.php" class="font-extrabold text-blue-600 hover:underline">Masuk di sini</a>
                </p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400">
        <p>&copy; <?= date('Y') ?> SMP Labschool Jakarta &bull; Layanan Terproteksi Google Workspace XOAUTH2</p>
    </footer>

</body>
</html>
