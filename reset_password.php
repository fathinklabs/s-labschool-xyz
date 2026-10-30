<?php
/**
 * Halaman Eksekusi Atur Ulang Kata Sandi (Reset Password Execution)
 * Memvalidasi token kriptografis unik dari database dan memperbarui kata sandi pengguna
 * SMP Labschool Jakarta — s.labschool.xyz
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValid = false;
$resetRecord = null;
$errorMessage = '';
$successMessage = '';

if (empty($token)) {
    $errorMessage = 'Token pengaturan ulang kata sandi tidak ditemukan atau tidak lengkap.';
} else {
    // Periksa token di basis data
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND is_used = 0 AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $resetRecord = $stmt->fetch();

        if ($resetRecord) {
            $tokenValid = true;
        } else {
            $errorMessage = 'Tautan pengaturan ulang kata sandi sudah kedaluwarsa atau telah digunakan. Silakan minta tautan baru.';
        }
    } catch (Throwable $e) {
        $errorMessage = 'Terjadi kesalahan sistem saat memverifikasi token.';
    }
}

// Proses submit password baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid && $resetRecord) {
    require_csrf();

    $newPass = (string)($_POST['password'] ?? '');
    $confirmPass = (string)($_POST['confirm_password'] ?? '');

    if (strlen($newPass) < 6) {
        $errorMessage = 'Kata sandi baru minimal harus terdiri dari 6 karakter.';
    } elseif ($newPass !== $confirmPass) {
        $errorMessage = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        try {
            // Hash kata sandi baru dengan BCRYPT cost 12
            $newHash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update kata sandi pengguna
            $upStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $upStmt->execute([$newHash, $resetRecord['email']]);

            // Tandai token ini sebagai telah digunakan
            $usedStmt = $pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?");
            $usedStmt->execute([$resetRecord['id']]);

            // Catat log aktivitas jika tabel tersedia
            try {
                $actStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                    SELECT id, 'password_reset', 'Kata sandi berhasil diatur ulang via tautan email', ?, NOW() FROM users WHERE email = ?");
                $actStmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $resetRecord['email']]);
            } catch (Throwable $ignore) {}

            $successMessage = 'Kata sandi Anda berhasil diperbarui! Anda sekarang dapat masuk menggunakan kata sandi baru.';
            $tokenValid = false; // Nonaktifkan form
        } catch (Throwable $e) {
            $errorMessage = 'Gagal memperbarui kata sandi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi — s.labschool.xyz</title>
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

    <!-- Header Navigation -->
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

    <!-- Main Card -->
    <main class="flex-1 flex items-center justify-center p-4 py-12">
        <div class="max-w-md w-full bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-900/5 border border-slate-200/80 space-y-6">

            <div class="text-center space-y-2">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2">
                    <span class="material-symbols-rounded text-3xl">key</span>
                </div>
                <h1 class="text-xl font-black text-slate-900">Atur Ulang Kata Sandi</h1>
                <p class="text-xs text-slate-500">
                    <?php if ($resetRecord): ?>
                        Membuat kata sandi baru untuk akun <strong class="text-slate-800 font-mono"><?= htmlspecialchars($resetRecord['email']) ?></strong>
                    <?php else: ?>
                        Verifikasi keamanan akun portal s.labschool.xyz
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold space-y-3 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto">
                        <span class="material-symbols-rounded text-2xl">check</span>
                    </div>
                    <p class="leading-relaxed"><?= htmlspecialchars($successMessage) ?></p>
                    <a href="/login.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition">
                        <span class="material-symbols-rounded text-base">login</span>
                        <span>Masuk Sekarang</span>
                    </a>
                </div>
            <?php elseif (!empty($errorMessage) && !$tokenValid): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-3 text-center">
                    <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center mx-auto">
                        <span class="material-symbols-rounded text-2xl">warning</span>
                    </div>
                    <p class="leading-relaxed"><?= htmlspecialchars($errorMessage) ?></p>
                    <a href="/forgot_password.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs transition">
                        <span class="material-symbols-rounded text-base">refresh</span>
                        <span>Minta Tautan Baru</span>
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($errorMessage)): ?>
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                        <span class="material-symbols-rounded text-base text-rose-600 shrink-0">error</span>
                        <span><?= htmlspecialchars($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/reset_password.php?token=<?= htmlspecialchars($token) ?>" class="space-y-4" onsubmit="return validateForm()">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div>
                        <label for="password" class="block text-xs font-extrabold text-slate-700 mb-1.5">Kata Sandi Baru *</label>
                        <div class="relative">
                            <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                            <input type="password" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition"
                                oninput="checkStrength(this.value)">
                        </div>
                        
                        <!-- Strength Meter -->
                        <div class="mt-2 space-y-1">
                            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div id="strengthBar" class="h-full w-0 bg-rose-500 transition-all duration-300 rounded-full"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px]">
                                <span id="strengthText" class="text-slate-400">Kekuatan kata sandi</span>
                                <span class="text-slate-400">Min. 6 karakter</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-extrabold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru *</label>
                        <div class="relative">
                            <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock_clock</span>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Ulangi kata sandi baru"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                        <span class="material-symbols-rounded text-base">save</span>
                        <span>Simpan Kata Sandi Baru</span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">
                    Perlu bantuan login? 
                    <a href="mailto:admin@labschool.xyz" class="font-extrabold text-blue-600 hover:underline">Hubungi Admin IT</a>
                </p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 py-6 text-center text-xs text-slate-400">
        <p>&copy; <?= date('Y') ?> SMP Labschool Jakarta &bull; Layanan Terproteksi Google Workspace XOAUTH2</p>
    </footer>

    <script>
        function checkStrength(val) {
            const bar = document.getElementById('strengthBar');
            const txt = document.getElementById('strengthText');
            if (!bar || !txt) return;

            let score = 0;
            if (val.length >= 6) score += 25;
            if (val.length >= 8) score += 25;
            if (/[0-9]/.test(val) && /[a-zA-Z]/.test(val)) score += 25;
            if (/[^a-zA-Z0-9]/.test(val)) score += 25;

            bar.style.width = score + '%';
            if (score <= 25) {
                bar.className = 'h-full bg-rose-500 transition-all duration-300 rounded-full';
                txt.innerText = 'Kata sandi lemah';
                txt.className = 'text-rose-600 font-bold';
            } else if (score <= 50) {
                bar.className = 'h-full bg-amber-500 transition-all duration-300 rounded-full';
                txt.innerText = 'Cukup';
                txt.className = 'text-amber-600 font-bold';
            } else if (score <= 75) {
                bar.className = 'h-full bg-blue-600 transition-all duration-300 rounded-full';
                txt.innerText = 'Baik';
                txt.className = 'text-blue-600 font-bold';
            } else {
                bar.className = 'h-full bg-emerald-600 transition-all duration-300 rounded-full';
                txt.innerText = 'Sangat kuat & aman';
                txt.className = 'text-emerald-600 font-bold';
            }
        }

        function validateForm() {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            if (p1 !== p2) {
                alert('Konfirmasi kata sandi tidak cocok!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
