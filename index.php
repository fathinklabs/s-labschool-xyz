<?php
/**
 * Public Landing Page: s.labschool.xyz
 * Platform: s.id Clone Labschool
 * Visual Design matched to smplabschooljakarta.sch.id & jadwal.labschool.xyz
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>s.labschool.xyz — Platform Tautan Singkat & Microsite SMP Labschool Jakarta</title>
    <meta name="description" content="Layanan resmi pemendek tautan (URL shortener), microsite bio link, QR code dinamis, dan analitik klik untuk civitas akademika SMP Labschool Jakarta.">
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">

    <!-- Google Fonts: Nunito Sans (Identical to smplabschooljakarta.sch.id) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,400;0,6..12,500;0,6..12,600;0,6..12,700;0,6..12,800;0,6..12,900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Nunito Sans"', 'sans-serif']
                    },
                    colors: {
                        labs: {
                            blue: '#2563EB',
                            dark: '#1E40AF',
                            amber: '#F59E0B',
                            gold: '#D97706'
                        }
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans selection:bg-blue-600 selection:text-white">

    <!-- Decorative Ambient Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full bg-blue-400/10 blur-3xl"></div>
        <div class="absolute top-1/3 -right-32 w-[500px] h-[500px] rounded-full bg-amber-400/10 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/4 w-[500px] h-[500px] rounded-full bg-purple-400/10 blur-3xl"></div>
    </div>

    <!-- Header Glassmorphism (Matched to smplabschooljakarta.sch.id & jadwal.labschool.xyz) -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <img src="/assets/img/logo-smp-labschool.png" alt="Logo SMP Labschool Jakarta" class="h-11 w-auto transition transform group-hover:scale-105">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black tracking-tight text-slate-900">s.labschool.xyz</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 uppercase">Resmi</span>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 hidden sm:block">SMP Labschool Jakarta &bull; Link Management</div>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <?php if ($user): ?>
                    <a href="/dashboard.php" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>Buka Dashboard</span>
                    </a>
                <?php else: ?>
                    <button type="button" onclick="openAuthModal('login')" class="px-4 py-2 rounded-xl text-slate-700 hover:text-blue-600 font-bold text-sm transition">
                        Masuk
                    </button>
                    <button type="button" onclick="openAuthModal('register')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition">
                        Daftar Akun
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 flex flex-col items-center">
        <!-- Hero Section -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-extrabold bg-blue-50 border border-blue-200/60 text-blue-700 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                <span>Platform Penyingkat Tautan & Bio Link Resmi</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                Satu Tautan Ringkas untuk Seluruh Kegiatan <span class="bg-gradient-to-r from-blue-600 via-blue-700 to-amber-500 bg-clip-text text-transparent">Labschool</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Platform pemendek URL, halaman bio profil multi-link, dynamic QR code berlogo sekolah, dan analitik klik real-time dengan domain terpercaya <strong>s.labschool.xyz</strong>.
            </p>
        </div>

        <!-- Hero Card Pemendek URL Cepat -->
        <div class="w-full max-w-2xl bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xl shadow-blue-900/5 mb-14 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-blue-700 to-amber-500"></div>

            <form id="heroShortenForm" onsubmit="handleHeroShorten(event)" class="space-y-4">
                <div class="space-y-2">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Tempel Tautan Panjang (URL)</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="url" id="heroUrl" placeholder="https://drive.google.com/file/d/..." required
                            class="flex-1 px-4 py-3.5 rounded-2xl border border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 outline-none text-sm font-semibold transition">
                        <button type="submit" id="heroBtn"
                            class="py-3.5 px-8 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-extrabold text-sm shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2">
                            <span>Perpendek</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Custom Alias / Slug Opsi Cepat -->
                <div class="flex items-center gap-2 pt-1 text-xs text-slate-500">
                    <span class="font-bold text-slate-700">Custom Alias:</span>
                    <span class="font-mono bg-slate-100 px-2 py-1 rounded-lg">s.labschool.xyz/</span>
                    <input type="text" id="heroSlug" placeholder="nama-custom (opsional)" class="flex-1 px-3 py-1 rounded-lg border border-slate-300 outline-none font-bold text-slate-800">
                </div>
            </form>

            <!-- Result Box -->
            <div id="heroResultBox" class="hidden mt-6 pt-6 border-t border-slate-100 space-y-4">
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="truncate max-w-sm text-left">
                        <div class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-widest">Tautan Pendek Siap Dipakai</div>
                        <a id="resShortLink" href="#" target="_blank" class="text-base font-black text-emerald-800 hover:underline"></a>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="button" onclick="copyHeroLink()" class="flex-1 sm:flex-none px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                            Salin Tautan
                        </button>
                        <a id="resQrBtn" href="#" class="px-3 py-2 rounded-xl bg-white border border-emerald-300 text-emerald-700 font-bold text-xs hover:bg-emerald-50 transition" title="Unduh QR Code">
                            Unduh QR
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notice Domain Restriction -->
            <div class="mt-4 pt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>Pendaftaran akun eksklusif untuk email <strong>@labschool.xyz</strong> dan <strong>@elabschool.sch.id</strong>.</span>
            </div>
        </div>

        <!-- 4 Fitur Unggulan Paritas s.id Pro -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 w-full mb-16">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Edit URL Kapan Saja</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Salah memasukkan tujuan? Ganti target URL asli kapan saja tanpa mengubah link pendek atau QR Code yang telah disebar ke siswa dan wali murid.
                </p>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Proteksi PIN / Password</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Kunci berkas soal, link zoom rapat guru, atau edaran terbatas dengan PIN keamanan agar hanya dapat diakses oleh pihak berwenang.
                </p>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Microsite Bio Link</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Buat satu halaman profil khusus untuk OSIS, MPK, Ekskul, dan Kepanitiaan lengkap dengan video YouTube, unduh vCard, dan tombol link.
                </p>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Analitik All-Time</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pantau jumlah klik, perangkat (HP vs Komputer), kota pengunjung, sumber perujuk (WhatsApp, IG), dan ekspor laporan ke format CSV.
                </p>
            </div>
        </div>
    </main>

    <!-- Modal Login & Register (Restricted to @labschool.xyz & @elabschool.sch.id) -->
    <div id="authModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5 relative">
            <button type="button" onclick="closeAuthModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            
            <div class="text-center">
                <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-12 mx-auto mb-2 object-contain">
                <h3 class="text-lg font-black text-slate-900" id="authModalTitle">Masuk ke s.labschool.xyz</h3>
                <p class="text-xs text-slate-500 mt-1">Khusus Civitas Akademika SMP Labschool Jakarta</p>
            </div>

            <!-- Tab Toggle: Login vs Register -->
            <div class="flex p-1 bg-slate-100 rounded-2xl text-xs font-bold">
                <button type="button" onclick="setAuthTab('login')" id="authTabLogin" class="flex-1 py-2 rounded-xl bg-white text-slate-900 shadow-sm transition">Masuk</button>
                <button type="button" onclick="setAuthTab('register')" id="authTabRegister" class="flex-1 py-2 rounded-xl text-slate-500 hover:text-slate-900 transition">Daftar Akun Baru</button>
            </div>

            <!-- Form Login -->
            <form id="formLogin" onsubmit="handleLoginSubmit(event)" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Sekolah (@labschool.xyz / @elabschool.sch.id) *</label>
                    <input type="email" id="loginEmail" required placeholder="nama@labschool.xyz"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-slate-700">Kata Sandi *</label>
                        <a href="/forgot_password.php" class="text-[11px] text-blue-600 font-extrabold hover:underline">Lupa kata sandi?</a>
                    </div>
                    <input type="password" id="loginPassword" required placeholder="Masukkan kata sandi..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <button type="submit" id="btnLogin" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-md shadow-blue-500/25 transition">
                    Masuk Sekarang
                </button>
            </form>

            <!-- Form Register -->
            <form id="formRegister" onsubmit="handleRegisterSubmit(event)" class="space-y-3 text-xs hidden">
                <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-[11px] font-semibold leading-relaxed">
                    <div class="font-extrabold flex items-center gap-1.5 mb-1 text-blue-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Pendaftaran Khusus Civitas Labschool</span>
                    </div>
                    Hanya email <strong>@labschool.xyz</strong> atau <strong>@elabschool.sch.id</strong> yang didukung. Tautan verifikasi akan dikirimkan ke email Anda untuk mengaktifkan akun sebelum dapat login.
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Guru / Staff / Siswa *</label>
                    <input type="text" id="regName" required placeholder="contoh: Budi Santoso, M.Pd"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Resmi Sekolah *</label>
                    <input type="email" id="regEmail" required placeholder="guru@labschool.xyz"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi (Minimal 6 Karakter) *</label>
                    <input type="password" id="regPassword" required placeholder="Buat kata sandi..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                </div>
                <button type="submit" id="btnRegister" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-extrabold text-sm shadow-md shadow-blue-500/25 transition">
                    Daftar Akun Labschool
                </button>
            </form>
        </div>
    </div>

    <!-- Footer Identitas Resmi (Matched to smplabschooljakarta.sch.id) -->
    <footer class="border-t border-slate-200 bg-white py-8 text-center text-xs text-slate-500 space-y-2">
        <div class="flex items-center justify-center gap-2 font-bold text-slate-800">
            <img src="/assets/img/logo-smp-labschool.png" alt="Logo" class="h-6 w-auto">
            <span>SMP Labschool Jakarta</span>
        </div>
        <p>Jl. Pemuda Komplek Universitas Negeri Jakarta (UNJ), Rawamangun, Jakarta Timur 13220</p>
        <p class="text-[11px] text-slate-400">&copy; <?= date('Y') ?> Unit TI & Sistem Informasi SMP Labschool Jakarta. Seluruh Hak Cipta Dilindungi.</p>
    </footer>

    <script>
        const isLoggedIn = <?= $user ? 'true' : 'false' ?>;

        function openAuthModal(tab) {
            document.getElementById('authModal').classList.remove('hidden');
            setAuthTab(tab);
        }

        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
        }

        function setAuthTab(tab) {
            const loginForm = document.getElementById('formLogin');
            const regForm = document.getElementById('formRegister');
            const tabLogin = document.getElementById('authTabLogin');
            const tabReg = document.getElementById('authTabRegister');
            const title = document.getElementById('authModalTitle');

            if (tab === 'register') {
                loginForm.classList.add('hidden');
                regForm.classList.remove('hidden');
                tabReg.className = 'flex-1 py-2 rounded-xl bg-white text-slate-900 shadow-sm transition';
                tabLogin.className = 'flex-1 py-2 rounded-xl text-slate-500 hover:text-slate-900 transition';
                title.innerText = 'Daftar Akun s.labschool.xyz';
            } else {
                regForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                tabLogin.className = 'flex-1 py-2 rounded-xl bg-white text-slate-900 shadow-sm transition';
                tabReg.className = 'flex-1 py-2 rounded-xl text-slate-500 hover:text-slate-900 transition';
                title.innerText = 'Masuk ke s.labschool.xyz';
            }
        }

        async function handleLoginSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerText = 'Memverifikasi...';

            const payload = {
                action: 'login',
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value
            };

            try {
                const res = await fetch('/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    window.location.href = '/dashboard.php';
                } else {
                    if (json.needs_verification) {
                        if (confirm(json.message + '\n\nApakah Anda ingin membuka halaman petunjuk verifikasi & kirim ulang email?')) {
                            window.location.href = '/verify_email.php?sent=1&email=' + encodeURIComponent(payload.email);
                        }
                    } else {
                        alert('Gagal Masuk: ' + json.message);
                    }
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Masuk Sekarang';
            }
        }

        async function handleRegisterSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRegister');
            btn.disabled = true;
            btn.innerText = 'Mendaftarkan...';

            const payload = {
                action: 'register',
                full_name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value
            };

            try {
                const res = await fetch('/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    if (json.needs_verification) {
                        alert(json.message);
                        window.location.href = '/verify_email.php?sent=1&email=' + encodeURIComponent(payload.email);
                    } else {
                        window.location.href = '/dashboard.php';
                    }
                } else {
                    alert('Gagal Daftar: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Daftar Akun Labschool';
            }
        }

        async function handleHeroShorten(e) {
            e.preventDefault();
            if (!isLoggedIn) {
                openAuthModal('login');
                return;
            }

            const btn = document.getElementById('heroBtn');
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            const payload = {
                original_url: document.getElementById('heroUrl').value,
                custom_slug: document.getElementById('heroSlug').value
            };

            try {
                const res = await fetch('/api/shorten.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    const box = document.getElementById('heroResultBox');
                    const linkEl = document.getElementById('resShortLink');
                    const qrBtn = document.getElementById('resQrBtn');

                    linkEl.href = json.data.short_url;
                    linkEl.innerText = json.data.short_url;
                    qrBtn.href = '/api/qr.php?code=' + encodeURIComponent(json.data.custom_slug || json.data.short_code) + '&download=1';
                    box.classList.remove('hidden');
                } else {
                    alert('Gagal: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Perpendek</span>';
            }
        }

        function copyHeroLink() {
            const linkText = document.getElementById('resShortLink').innerText;
            navigator.clipboard.writeText(linkText);
            alert('Tautan disalin ke clipboard: ' + linkText);
        }
    </script>
</body>
</html>
