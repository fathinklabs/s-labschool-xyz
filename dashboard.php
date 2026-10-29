<?php
/**
 * User Dashboard: s.labschool.xyz
 * Paritas s.id Pro Link & Microsite Management
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();

// 1. Ambil daftar Short URLs milik user
$urlStmt = $pdo->prepare("SELECT * FROM short_urls WHERE user_id = ? ORDER BY created_at DESC");
$urlStmt->execute([$user['id']]);
$urls = $urlStmt->fetchAll();

// 2. Ambil daftar Microsites milik user
$bioStmt = $pdo->prepare("SELECT * FROM microsites WHERE user_id = ? ORDER BY created_at DESC");
$bioStmt->execute([$user['id']]);
$microsites = $bioStmt->fetchAll();

// 3. Ringkasan Statistik Global
$totalClicks = array_sum(array_column($urls, 'clicks_count'));
$totalLinks  = count($urls);
$totalBios   = count($microsites);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Tautan — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-9 w-auto">
                    <div>
                        <div class="text-sm font-black text-slate-900 leading-tight">s.labschool.xyz</div>
                        <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Portal Tautan & Bio</div>
                    </div>
                </a>
            </div>

            <!-- Profile & Logout -->
            <div class="flex items-center gap-3">
                <?php if ($user['role'] === 'superadmin'): ?>
                    <a href="/admin.php" class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md shadow-red-500/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Admin Panel</span>
                    </a>
                <?php endif; ?>
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-bold text-slate-900"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="text-[11px] text-slate-500 font-mono"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <div class="p-1 rounded-full bg-blue-50 border border-blue-200">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-xs uppercase">
                        <?= substr($user['full_name'], 0, 2) ?>
                    </span>
                </div>
                <a href="/logout.php" class="p-2 text-slate-400 hover:text-red-600 transition" title="Keluar / Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 w-full space-y-8 flex-1">
        <!-- 3 Statistik Ringkas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Tautan</div>
                    <div class="text-2xl font-black text-slate-900"><?= number_format($totalLinks) ?></div>
                </div>
                <div class="p-3.5 rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Klik Pengunjung</div>
                    <div class="text-2xl font-black text-blue-600"><?= number_format($totalClicks) ?></div>
                </div>
                <div class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Halaman Microsite</div>
                    <div class="text-2xl font-black text-purple-600"><?= number_format($totalBios) ?></div>
                </div>
                <div class="p-3.5 rounded-2xl bg-purple-50 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
        </div>

        <!-- Pemendek Tautan Cepat (Fast Shorten Box) -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="p-1.5 rounded-xl bg-blue-100 text-blue-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </span>
                <span>Buat Tautan Pendek Baru</span>
            </h2>

            <form id="formShorten" onsubmit="handleShorten(event)" class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="url" id="inpUrl" placeholder="Tempel tautan panjang di sini (https://...)..." required
                        class="flex-1 px-4 py-3.5 rounded-2xl border border-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100 outline-none text-sm transition font-medium">
                    <button type="submit" id="btnShorten"
                        class="py-3.5 px-8 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2">
                        <span>Perpendek</span>
                    </button>
                </div>

                <!-- Opsi Tambahan Pro (Expandable) -->
                <div class="pt-2">
                    <button type="button" onclick="toggleProOptions()" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <span>Opsi Lanjutan s.id Pro (Custom Alias, PIN Proteksi, Kadaluarsa, UTM)</span>
                        <span id="proArrow">&darr;</span>
                    </button>

                    <div id="proOptions" class="hidden mt-4 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Custom Alias / Slug</label>
                            <div class="flex items-center rounded-xl border border-slate-300 overflow-hidden bg-slate-50">
                                <span class="px-2 text-slate-400">s.labschool.xyz/</span>
                                <input type="text" id="inpSlug" placeholder="contoh: ujian26" class="w-full py-2 pr-2 font-bold bg-transparent outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Proteksi Kata Sandi / PIN</label>
                            <input type="password" id="inpPass" placeholder="Kosongkan jika publik" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Kadaluarsa</label>
                            <input type="datetime-local" id="inpExp" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tab Navigasi: Tautan vs Microsite vs API Key -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <button onclick="switchTab('links')" id="tabBtnLinks" class="px-4 py-2 font-black text-sm rounded-xl bg-blue-600 text-white shadow-sm transition">
                Tautan Saya (<?= count($urls) ?>)
            </button>
            <button onclick="switchTab('bios')" id="tabBtnBios" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Microsite Bio Link (<?= count($microsites) ?>)
            </button>
            <button onclick="switchTab('api')" id="tabBtnApi" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Developer API Key
            </button>
        </div>

        <!-- Tab 1: Daftar Tautan Saya -->
        <div id="tabContentLinks" class="space-y-4">
            <?php if (empty($urls)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-200">
                    <p class="text-sm text-slate-500 font-semibold mb-2">Anda belum memiliki tautan pendek.</p>
                    <p class="text-xs text-slate-400">Gunakan formulir di atas untuk membuat tautan pendek pertama Anda!</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden divide-y divide-slate-100">
                    <?php foreach ($urls as $u): ?>
                        <?php
                            $uSlug = $u['custom_slug'] ?: $u['short_code'];
                            $uShort = base_url($uSlug);
                        ?>
                        <div class="p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:bg-slate-50/60 transition" id="row-link-<?= $u['id'] ?>">
                            <div class="space-y-1 max-w-xl">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="<?= $uShort ?>" target="_blank" class="font-extrabold text-base text-blue-600 hover:underline flex items-center gap-1.5">
                                        <span>s.labschool.xyz/<?= htmlspecialchars($uSlug) ?></span>
                                        <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <?php if (!empty($u['password_hash'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700" title="Dilindungi Password">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                            PIN
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400">&bull; <?= time_ago($u['created_at']) ?></span>
                                </div>
                                <div class="text-sm font-bold text-slate-800"><?= htmlspecialchars($u['title']) ?></div>
                                <div class="text-xs text-slate-400 truncate max-w-md"><?= htmlspecialchars($u['original_url']) ?></div>
                            </div>

                            <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                <!-- Klik Counter & Link Analitik -->
                                <a href="/analytics.php?id=<?= $u['id'] ?>" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition text-xs font-bold" title="Buka Detail Analitik">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    <span><?= number_format($u['clicks_count']) ?> Klik</span>
                                </a>

                                <!-- Tombol Salin -->
                                <button type="button" onclick="copyLink('<?= $uShort ?>')" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Salin Tautan">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>

                                <!-- Tombol Unduh QR -->
                                <a href="/api/qr.php?code=<?= urlencode($uSlug) ?>&download=1" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Unduh QR Code">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </a>

                                <!-- Edit Target Destination (Pro Feature) -->
                                <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-2 rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition" title="Edit Destination URL (Pro)">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <!-- Toggle Aktif / Non-aktif -->
                                <button type="button" onclick="toggleStatus(<?= $u['id'] ?>, this)" class="px-2.5 py-1 rounded-lg text-xs font-bold transition <?= $u['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-200 text-slate-600' ?>">
                                    <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </button>

                                <!-- Hapus Tautan -->
                                <button type="button" onclick="deleteLink(<?= $u['id'] ?>)" class="p-2 rounded-xl text-slate-400 hover:text-red-600 transition" title="Hapus">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Daftar Microsite Bio Link -->
        <div id="tabContentBios" class="hidden space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-xs text-slate-500 font-semibold">Kelola halaman bio link organisasi, kepanitiaan, atau ekskul Labschool.</p>
                <a href="/bio_editor.php" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Buat Halaman Microsite Baru</span>
                </a>
            </div>

            <?php if (empty($microsites)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-slate-200">
                    <p class="text-sm text-slate-500 font-semibold mb-2">Belum ada halaman microsite yang dibuat.</p>
                    <a href="/bio_editor.php" class="inline-block mt-2 px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs">Buat Sekarang</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($microsites as $m): ?>
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                                        s.labschool.xyz/p/<?= htmlspecialchars($m['slug']) ?>
                                    </span>
                                    <span class="text-xs text-slate-400 font-bold"><?= number_format($m['clicks_count']) ?> Views</span>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900"><?= htmlspecialchars($m['title']) ?></h3>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-1"><?= htmlspecialchars($m['bio'] ?: 'Tidak ada bio.') ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <a href="/p/<?= htmlspecialchars($m['slug']) ?>" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">
                                    Lihat Halaman Publik &rarr;
                                </a>
                                <div class="flex items-center gap-2">
                                    <a href="/bio_editor.php?id=<?= $m['id'] ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition">
                                        Edit Desain & Blok
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Developer API Key (On-Demand & Tampilkan Sekali) -->
        <div id="tabContentApi" class="hidden">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Developer REST API (Fitur Integrasi Sistem)</h2>
                    <p class="text-xs text-slate-500 mt-1">Gunakan API Key ini untuk membuat short link secara terprogram dari Moodle CBT, Portal SISFO, atau sistem internal Labschool.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status Kunci API</div>
                            <?php if (!empty($user['api_key'])): ?>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        API Key Aktif
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">
                                        <?= !empty($user['api_key_created_at']) ? 'Dibuat: ' . date('d M Y H:i', strtotime($user['api_key_created_at'])) . ' WIB' : '' ?>
                                    </span>
                                </div>
                                <div class="font-mono text-xs text-slate-600 font-bold mt-2 tracking-wider bg-slate-200/70 px-3 py-1.5 rounded-lg inline-block select-none">
                                    labs_••••••••••••••••••••••••••••••••••••••••
                                </div>
                            <?php else: ?>
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-600">
                                    Belum Ada API Key
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Anda belum menerbitkan API Key. Klik tombol di samping untuk membuat kunci pertama Anda.</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php if (!empty($user['api_key'])): ?>
                                <button type="button" onclick="requestApiKey('reset')" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs shadow-sm transition flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Reset & Buat Kunci Baru</span>
                                </button>
                                <button type="button" onclick="revokeApiKey()" class="px-3.5 py-2.5 rounded-xl bg-slate-200 hover:bg-red-50 hover:text-red-600 text-slate-600 font-bold text-xs transition" title="Cabut Kunci">
                                    Cabut
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="requestApiKey('generate')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Terbitkan API Key</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-100 flex items-start gap-2.5 text-xs text-blue-900">
                        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="leading-relaxed">
                            <strong>Kebijakan Keamanan Kunci:</strong> Demi menjaga keamanan hak akses akun Anda, nilai rahasia API Key <strong>hanya dimunculkan sekali</strong> pada jendela dialog saat Anda meminta pembuatan atau reset kunci. Jika Anda kehilangan kunci lama, silakan gunakan tombol <em>Reset & Buat Kunci Baru</em>.
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Format Pemanggilan REST API:</h4>
                    <pre class="p-4 rounded-2xl bg-slate-900 text-emerald-400 text-xs font-mono overflow-x-auto">curl -X POST https://s.labschool.xyz/api/shorten.php \
  -H "Authorization: Bearer labs_your_secret_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"original_url": "https://smplabschooljakarta.sch.id/pengumuman", "custom_slug": "agenda-baru"}'</pre>
                </div>
            </div>
        </div>
  -H "Authorization: Bearer <?= htmlspecialchars($user['api_key']) ?>" \
  -H "Content-Type: application/json" \
  -d '{"original_url": "https://smplabschooljakarta.sch.id/pengumuman", "custom_slug": "info-baru"}'</pre>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Edit Destination URL (Pro Feature) -->
    <div id="editModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Tautan (Fitur s.id Pro)</h3>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form id="formEdit" onsubmit="handleEditSubmit(event)" class="space-y-4 text-xs">
                <input type="hidden" id="editId">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">URL Tujuan Asli (Bisa Diubah Kapan Saja) *</label>
                    <input type="url" id="editOriginalUrl" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none text-sm">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Judul Tautan *</label>
                    <input type="text" id="editTitle" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Custom Slug</label>
                    <input type="text" id="editSlug" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ubah Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" id="editPassword" placeholder="PIN / Password baru..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    <label class="mt-1 flex items-center gap-1.5 text-slate-500 font-semibold">
                        <input type="checkbox" id="editRemovePassword"> Hapus proteksi kata sandi
                    </label>
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                    <button type="submit" id="btnSaveEdit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md shadow-blue-500/20">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reveal API Key (Tampilkan Sekali) -->
    <div id="apiKeyModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center gap-3 text-emerald-600">
                <div class="p-2.5 rounded-2xl bg-emerald-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 leading-tight">API Key Berhasil Diterbitkan!</h3>
                    <p class="text-xs text-slate-500">Kunci rahasia integrasi REST API Anda</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 font-semibold leading-relaxed">
                ⚠️ <strong>PENTING:</strong> Salin dan simpan kunci ini di tempat aman sekarang. Demi keamanan akun, kunci rahasia ini <strong>hanya ditampilkan sekali ini saja</strong> dan tidak akan dapat dilihat kembali setelah Anda menutup dialog ini!
            </div>

            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Your Secret API Key</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="revealedApiKey" readonly class="w-full px-3.5 py-3 rounded-xl bg-slate-100 font-mono font-bold text-xs text-slate-900 border border-slate-300 outline-none select-all">
                    <button type="button" onclick="copyRevealedApiKey()" class="px-4 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shrink-0">
                        Salin
                    </button>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" onclick="closeApiKeyModal()" class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                    Saya Sudah Menyalin Kunci & Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleProOptions() {
            const el = document.getElementById('proOptions');
            const arr = document.getElementById('proArrow');
            el.classList.toggle('hidden');
            arr.innerHTML = el.classList.contains('hidden') ? '&darr;' : '&uarr;';
        }

        function switchTab(tab) {
            document.getElementById('tabContentLinks').classList.add('hidden');
            document.getElementById('tabContentBios').classList.add('hidden');
            document.getElementById('tabContentApi').classList.add('hidden');

            document.getElementById('tabBtnLinks').className = 'px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition';
            document.getElementById('tabBtnBios').className = 'px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition';
            document.getElementById('tabBtnApi').className = 'px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition';

            if (tab === 'links') {
                document.getElementById('tabContentLinks').classList.remove('hidden');
                document.getElementById('tabBtnLinks').className = 'px-4 py-2 font-black text-sm rounded-xl bg-blue-600 text-white shadow-sm transition';
            } else if (tab === 'bios') {
                document.getElementById('tabContentBios').classList.remove('hidden');
                document.getElementById('tabBtnBios').className = 'px-4 py-2 font-black text-sm rounded-xl bg-purple-600 text-white shadow-sm transition';
            } else if (tab === 'api') {
                document.getElementById('tabContentApi').classList.remove('hidden');
                document.getElementById('tabBtnApi').className = 'px-4 py-2 font-black text-sm rounded-xl bg-slate-900 text-white shadow-sm transition';
            }
        }

        async function handleShorten(e) {
            e.preventDefault();
            const btn = document.getElementById('btnShorten');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                original_url: document.getElementById('inpUrl').value,
                custom_slug: document.getElementById('inpSlug').value,
                password: document.getElementById('inpPass').value,
                expires_at: document.getElementById('inpExp').value
            };

            try {
                const res = await fetch('/api/shorten.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('Tautan pendek berhasil dibuat: ' + json.data.short_url);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Perpendek';
            }
        }

        function copyLink(text) {
            navigator.clipboard.writeText(text);
            alert('Tautan disalin ke clipboard: ' + text);
        }

        async function toggleStatus(id, btn) {
            try {
                const res = await fetch('/api/toggle_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    btn.innerText = json.is_active ? 'Aktif' : 'Nonaktif';
                    btn.className = json.is_active ? 'px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 transition' : 'px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-200 text-slate-600 transition';
                }
            } catch (e) {
                alert('Gagal mengubah status.');
            }
        }

        async function deleteLink(id) {
            if (!confirm('Yakin ingin menghapus tautan ini?')) return;
            try {
                const res = await fetch('/api/delete_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('row-link-' + id)?.remove();
                } else {
                    alert('Gagal menghapus: ' + json.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        function openEditModal(link) {
            document.getElementById('editId').value = link.id;
            document.getElementById('editOriginalUrl').value = link.original_url;
            document.getElementById('editTitle').value = link.title;
            document.getElementById('editSlug').value = link.custom_slug || '';
            document.getElementById('editPassword').value = '';
            document.getElementById('editRemovePassword').checked = false;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        async function handleEditSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveEdit');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                id: document.getElementById('editId').value,
                original_url: document.getElementById('editOriginalUrl').value,
                title: document.getElementById('editTitle').value,
                custom_slug: document.getElementById('editSlug').value,
                password: document.getElementById('editPassword').value,
                remove_password: document.getElementById('editRemovePassword').checked ? 1 : 0
            };

            try {
                const res = await fetch('/api/update_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('Tautan berhasil diperbarui!');
                    window.location.reload();
                } else {
                    alert('Gagal: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan Perubahan';
            }
        }

        async function requestApiKey(action) {
            const msg = action === 'reset' 
                ? 'Apakah Anda yakin ingin mereset API Key? Kunci lama akan otomatis tidak berlaku dan tidak dapat digunakan lagi.'
                : 'Terbitkan API Key baru untuk akun Anda?';
            if (!confirm(msg)) return;

            try {
                const res = await fetch('/api/user/api_key.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('revealedApiKey').value = json.api_key;
                    document.getElementById('apiKeyModal').classList.remove('hidden');
                } else {
                    alert('Gagal: ' + json.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        async function revokeApiKey() {
            if (!confirm('Apakah Anda yakin ingin mencabut / menonaktifkan API Key Anda? Sistem yang terhubung tidak akan dapat membuat link lagi.')) return;

            try {
                const res = await fetch('/api/user/api_key.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'revoke' })
                });
                const json = await res.json();
                alert(json.message);
                window.location.reload();
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        function copyRevealedApiKey() {
            const input = document.getElementById('revealedApiKey');
            navigator.clipboard.writeText(input.value);
            alert('API Key berhasil disalin ke clipboard!');
        }

        function closeApiKeyModal() {
            document.getElementById('apiKeyModal').classList.add('hidden');
            document.getElementById('revealedApiKey').value = '';
            window.location.reload();
        }
    </script>
</body>
</html>
