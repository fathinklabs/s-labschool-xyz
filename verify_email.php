<?php
/**
 * Halaman & Endpoint Verifikasi Alamat Email
 * SMP Labschool Jakarta — s.labschool.xyz
 * Verifikasi wajib sebelum pengguna dapat masuk (login) ke dashboard.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/mail.php';

$flashSuccess = '';
$flashError = '';

$token = trim($_GET['token'] ?? '');
$isSentPage = isset($_GET['sent']) && $_GET['sent'] === '1';
$presetEmail = trim($_GET['email'] ?? '');

$verificationStatus = 'idle'; // idle | success | already_verified | expired | invalid

// 1. Tangani Permintaan Kirim Ulang Verifikasi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $resendEmail = strtolower(trim($_POST['email'] ?? ''));
    if (empty($resendEmail)) {
        $flashError = 'Silakan masukkan alamat email resmi sekolah Anda.';
    } else {
        $res = resend_verification_email($resendEmail);
        if ($res['success']) {
            $flashSuccess = $res['message'];
            $presetEmail = $resendEmail;
            $isSentPage = true;
        } else {
            $flashError = $res['message'];
            $presetEmail = $resendEmail;
        }
    }
}

// 2. Tangani Verifikasi via Token (GET)
if (!empty($token) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT ev.*, u.full_name, u.is_active as user_active, u.email_verified_at as u_verified_at
            FROM email_verifications ev
            JOIN users u ON ev.user_id = u.id
            WHERE ev.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $record = $stmt->fetch();

        if (!$record) {
            $verificationStatus = 'invalid';
        } elseif (!empty($record['u_verified_at']) || (int)$record['is_verified'] === 1) {
            $verificationStatus = 'already_verified';
            $presetEmail = $record['email'];
        } elseif (strtotime($record['expires_at']) < time()) {
            $verificationStatus = 'expired';
            $presetEmail = $record['email'];
        } else {
            // Token valid! Eksekusi verifikasi akun
            $pdo->beginTransaction();

            // Update record verifikasi
            $upVerif = $pdo->prepare("UPDATE email_verifications SET is_verified = 1, verified_at = NOW() WHERE id = ?");
            $upVerif->execute([$record['id']]);

            // Update user: set email_verified_at dan aktifkan akun
            $upUser = $pdo->prepare("UPDATE users SET email_verified_at = NOW(), is_active = 1 WHERE id = ?");
            $upUser->execute([$record['user_id']]);

            $pdo->commit();

            $verificationStatus = 'success';
            $presetEmail = $record['email'];
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $verificationStatus = 'invalid';
        $flashError = 'Terjadi kesalahan sistem saat memvalidasi token: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Alamat Email — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Nunito Sans"', 'sans-serif'] },
                    colors: {
                        brand: {
                            blue: '#2563EB',
                            dark: '#0F172A',
                            emerald: '#059669',
                            amber: '#D97706',
                            rose: '#E11D48'
                        }
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen text-slate-800 flex flex-col justify-between p-4 sm:p-6 selection:bg-blue-500 selection:text-white">

    <!-- Header Logo -->
    <header class="w-full max-w-xl mx-auto flex items-center justify-between py-4">
        <a href="/" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur border border-white/20 p-1 flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                <img src="/assets/img/logo-smp-labschool.png" alt="SMP Labschool Jakarta" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="text-white font-black text-lg tracking-tight block">s.labschool.xyz</span>
                <span class="text-blue-300/80 text-[11px] font-semibold block leading-none">SMP Labschool Jakarta</span>
            </div>
        </a>
        <a href="/index.php?action=login" class="text-xs font-bold text-white/80 hover:text-white bg-white/10 hover:bg-white/20 px-4 py-2 rounded-xl border border-white/10 backdrop-blur transition">
            Masuk &rarr;
        </a>
    </header>

    <!-- Main Container -->
    <main class="w-full max-w-md mx-auto my-auto py-6">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-8 backdrop-blur-xl">

            <!-- Flash Alert -->
            <?php if (!empty($flashSuccess)): ?>
                <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="leading-relaxed"><?= htmlspecialchars($flashSuccess) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="leading-relaxed"><?= htmlspecialchars($flashError) ?></div>
                </div>
            <?php endif; ?>

            <!-- KASUS 1: BERHASIL DIVERIFIKASI (SUCCESS) -->
            <?php if ($verificationStatus === 'success'): ?>
                <div class="text-center py-2">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-100 border border-emerald-200 text-emerald-600 mx-auto flex items-center justify-center mb-4 shadow-lg shadow-emerald-500/10">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-extrabold uppercase tracking-wider mb-2">
                        &check; Terverifikasi
                    </span>
                    <h1 class="text-xl font-black text-slate-900 mb-2">Email Berhasil Diverifikasi!</h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-6">
                        Alamat email <strong class="text-slate-800 font-mono"><?= htmlspecialchars($presetEmail) ?></strong> telah berhasil dikonfirmasi. Akun civitas akademika Anda kini aktif sepenuhnya.
                    </p>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-left text-xs mb-6 space-y-2">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Status Akun:</span>
                            <span class="font-bold text-emerald-600">Aktif &bull; Terverifikasi</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Hak Akses:</span>
                            <span class="font-bold text-slate-700">Shortener, QR Code &amp; Pages</span>
                        </div>
                    </div>

                    <a href="/index.php?action=login" class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-sm shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2">
                        <span>Masuk ke Akun Sekarang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <!-- KASUS 2: SUDAH PERNAH DIVERIFIKASI (ALREADY VERIFIED) -->
            <?php elseif ($verificationStatus === 'already_verified'): ?>
                <div class="text-center py-2">
                    <div class="w-16 h-16 rounded-3xl bg-blue-100 border border-blue-200 text-blue-600 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 mb-2">Akun Sudah Aktif</h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-6">
                        Alamat email <strong class="text-slate-800 font-mono"><?= htmlspecialchars($presetEmail) ?></strong> sudah terverifikasi sebelumnya. Anda dapat langsung masuk menggunakan kata sandi Anda.
                    </p>
                    <a href="/index.php?action=login" class="w-full py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-sm shadow-md transition flex items-center justify-center gap-2">
                        <span>Masuk ke Dashboard</span>
                    </a>
                </div>

            <!-- KASUS 3: TOKEN KEDALUWARSA ATAU INVALID (EXPIRED / INVALID) -->
            <?php elseif ($verificationStatus === 'expired' || $verificationStatus === 'invalid'): ?>
                <div class="text-center py-2">
                    <div class="w-16 h-16 rounded-3xl bg-amber-100 border border-amber-200 text-amber-600 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h1 class="text-xl font-black text-slate-900 mb-2">
                        <?= $verificationStatus === 'expired' ? 'Tautan Verifikasi Kedaluwarsa' : 'Tautan Tidak Valid' ?>
                    </h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-6">
                        <?= $verificationStatus === 'expired' 
                            ? 'Tautan verifikasi hanya berlaku selama 24 jam demi keamanan akun. Silakan masukkan email Anda untuk mendapatkan tautan baru.' 
                            : 'Tautan verifikasi ini tidak ditemukan atau telah digunakan. Anda dapat meminta tautan verifikasi baru di bawah ini.' ?>
                    </p>

                    <!-- Form Minta Link Baru -->
                    <form method="POST" action="/verify_email.php" class="space-y-4 text-left">
                        <?= csrf_field() ?>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Email Sekolah (@labschool.xyz / @elabschool.sch.id) *</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($presetEmail) ?>" placeholder="nama@labschool.xyz"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs font-bold">
                        </div>
                        <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/25 transition">
                            Kirim Ulang Tautan Verifikasi
                        </button>
                    </form>
                </div>

            <!-- KASUS 4: SETELAH PENDAFTARAN ATAU EMAIL TERKIRIM (SENT PAGE) -->
            <?php else: ?>
                <div class="text-center py-2">
                    <div class="w-16 h-16 rounded-3xl bg-blue-50 border border-blue-200 text-blue-600 mx-auto flex items-center justify-center mb-4 relative">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white animate-pulse"></span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-extrabold uppercase tracking-wider mb-2">
                        Menunggu Verifikasi
                    </span>
                    <h1 class="text-xl font-black text-slate-900 mb-2">Periksa Kotak Masuk Email Anda</h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-5">
                        Kami telah mengirimkan tautan verifikasi akun resmi ke:
                        <br><strong class="text-slate-900 font-mono text-xs"><?= htmlspecialchars($presetEmail ?: 'alamat email Anda') ?></strong>
                    </p>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-left text-xs text-slate-600 space-y-2 mb-6">
                        <div class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">1.</span>
                            <span>Buka aplikasi Gmail atau peramban email sekolah Anda.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">2.</span>
                            <span>Cari email dari <strong>admin@labschool.xyz</strong> dengan subjek <em>"Verifikasi Alamat Email Akun s.labschool.xyz"</em>.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-blue-600 font-bold">3.</span>
                            <span>Klik tombol <strong>"Verifikasi Email Saya"</strong> di dalam pesan untuk mengaktifkan akun.</span>
                        </div>
                        <div class="pt-1 text-[11px] text-slate-400">
                            *Jika email tidak ada di Kotak Masuk, harap periksa tab <em>Spam</em> atau <em>Promosi</em>.
                        </div>
                    </div>

                    <!-- Kirim Ulang Tautan -->
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-500 mb-3 font-semibold">Belum menerima email verifikasi?</p>
                        <form method="POST" action="/verify_email.php" class="space-y-3 text-left">
                            <?= csrf_field() ?>
                            <div>
                                <input type="email" name="email" required value="<?= htmlspecialchars($presetEmail) ?>" placeholder="nama@labschool.xyz"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs font-bold text-slate-800">
                            </div>
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs transition">
                                Kirim Ulang Tautan Verifikasi
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <a href="/index.php" class="text-xs font-extrabold text-blue-600 hover:underline inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Halaman Beranda</span>
                </a>
            </div>

        </div>
    </main>

    <!-- Footer Security Note -->
    <footer class="w-full max-w-xl mx-auto text-center py-4 text-xs text-slate-400/80">
        &copy; <?= date('Y') ?> SMP Labschool Jakarta &bull; Enterprise URL Shortener, Dynamic QR Code &amp; Pages.
    </footer>

</body>
</html>
