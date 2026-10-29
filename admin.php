<?php
/**
 * Admin Panel: s.labschool.xyz
 * Pengelolaan Menyeluruh Seluruh Fitur & Pengguna Platform s.labschool.xyz
 * Akses Terbatas: Role 'superadmin'
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_admin();
$adminUser = current_user();

// 1. Data Statistik Global
$stats = [
    'users'      => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'links'      => (int)$pdo->query("SELECT COUNT(*) FROM short_urls")->fetchColumn(),
    'microsites' => (int)$pdo->query("SELECT COUNT(*) FROM microsites")->fetchColumn(),
    'clicks'     => (int)$pdo->query("SELECT COUNT(*) FROM click_logs")->fetchColumn(),
];

// 2. Daftar Seluruh Pengguna
$usersStmt = $pdo->query("
    SELECT u.id, u.email, u.full_name, u.domain, u.role, u.is_active, u.created_at,
           (SELECT COUNT(*) FROM short_urls WHERE user_id = u.id) as total_links,
           (SELECT COUNT(*) FROM microsites WHERE user_id = u.id) as total_bios
    FROM users u
    ORDER BY u.created_at DESC
");
$allUsers = $usersStmt->fetchAll();

// 3. Daftar Seluruh Tautan Pendek (Semua User)
$linksStmt = $pdo->query("
    SELECT s.*, u.email as owner_email, u.full_name as owner_name
    FROM short_urls s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
");
$allLinks = $linksStmt->fetchAll();

// 4. Daftar Seluruh Microsite
$biosStmt = $pdo->query("
    SELECT m.*, u.email as owner_email, u.full_name as owner_name
    FROM microsites m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC
");
$allBios = $biosStmt->fetchAll();

// 5. Tren Klik Global 14 Hari Terakhir
$trendStmt = $pdo->query("
    SELECT DATE(clicked_at) as cdate, COUNT(*) as total
    FROM click_logs
    GROUP BY DATE(clicked_at)
    ORDER BY cdate ASC
    LIMIT 30
");
$trendRows = $trendStmt->fetchAll();
$dates = [];
$counts = [];
foreach ($trendRows as $r) {
    $dates[] = date('d M', strtotime($r['cdate']));
    $counts[] = (int)$r['total'];
}

// 6. 30 Log Klik Global Terbaru
$recentClicks = $pdo->query("
    SELECT c.*, s.short_code, s.custom_slug, s.title as url_title
    FROM click_logs c
    JOIN short_urls s ON c.url_id = s.id
    ORDER BY c.clicked_at DESC
    LIMIT 30
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Header Glassmorphism Admin -->
    <header class="sticky top-0 z-40 bg-slate-900 text-white border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/admin.php" class="flex items-center gap-2.5">
                    <img src="/assets/img/logo-smp-labschool.png" alt="Logo" class="h-9 w-auto">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-white">s.labschool.xyz</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-500/20 text-red-300 border border-red-500/30 uppercase">Superadmin</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-semibold">Master Control & Admin Panel</div>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <a href="/dashboard.php" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Dashboard Saya</span>
                </a>
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-bold text-white"><?= htmlspecialchars($adminUser['full_name']) ?></div>
                    <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($adminUser['email']) ?></div>
                </div>
                <a href="/logout.php" class="p-2 text-slate-400 hover:text-red-400 transition" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 w-full space-y-6 flex-1">
        <!-- 4 Metrik Ringkasan Utama -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Pengguna</div>
                <div class="text-2xl font-black text-slate-900"><?= number_format($stats['users']) ?></div>
                <div class="text-[11px] text-blue-600 font-semibold mt-1">Civitas Labschool Aktif</div>
            </div>

            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Tautan Dibuat</div>
                <div class="text-2xl font-black text-blue-600"><?= number_format($stats['links']) ?></div>
                <div class="text-[11px] text-slate-500 font-semibold mt-1">Short URLs di Sistem</div>
            </div>

            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Halaman Microsite</div>
                <div class="text-2xl font-black text-purple-600"><?= number_format($stats['microsites']) ?></div>
                <div class="text-[11px] text-slate-500 font-semibold mt-1">Profil Bio Link</div>
            </div>

            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Akumulasi Klik</div>
                <div class="text-2xl font-black text-emerald-600"><?= number_format($stats['clicks']) ?></div>
                <div class="text-[11px] text-slate-500 font-semibold mt-1">Seluruh Trafik Masuk</div>
            </div>
        </div>

        <!-- Tab Navigasi Admin -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
            <button onclick="switchAdminTab('overview')" id="tabBtnOverview" class="px-4 py-2 font-black text-sm rounded-xl bg-slate-900 text-white shadow-sm transition">
                Ringkasan & Tren
            </button>
            <button onclick="switchAdminTab('users')" id="tabBtnUsers" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Kelola Pengguna (<?= count($allUsers) ?>)
            </button>
            <button onclick="switchAdminTab('links')" id="tabBtnLinks" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Semua Tautan (<?= count($allLinks) ?>)
            </button>
            <button onclick="switchAdminTab('bios')" id="tabBtnBios" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Semua Microsite (<?= count($allBios) ?>)
            </button>
            <button onclick="switchAdminTab('logs')" id="tabBtnLogs" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition">
                Log Trafik Global
            </button>
        </div>

        <!-- TAB 1: Ringkasan & Grafik Tren Global -->
        <div id="tabOverview" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>Tren Frekuensi Klik Global Seluruh Tautan (Real-time Timeline)</span>
                </h3>
                <div class="h-64 w-full">
                    <canvas id="globalChart"></canvas>
                </div>
            </div>
        </div>

        <!-- TAB 2: Kelola Pengguna (User Management) -->
        <div id="tabUsers" class="hidden space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Daftar Pengguna Civitas Labschool</h3>
                        <p class="text-xs text-slate-500">Kelola hak akses, peranan, pemblokiran akun, dan kata sandi.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-bold">Nama Lengkap & Email</th>
                                <th class="py-3 px-4 font-bold">Domain</th>
                                <th class="py-3 px-4 font-bold">Peran (Role)</th>
                                <th class="py-3 px-4 font-bold">Tautan & Bio</th>
                                <th class="py-3 px-4 font-bold">Status</th>
                                <th class="py-3 px-4 font-bold">Terdaftar</th>
                                <th class="py-3 px-4 font-bold text-right">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <?php foreach ($allUsers as $u): ?>
                                <tr id="user-row-<?= $u['id'] ?>" class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-extrabold text-slate-900 text-sm"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-700">@<?= htmlspecialchars($u['domain']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <select onchange="changeUserRole(<?= $u['id'] ?>, this.value)" class="px-2 py-1 rounded-lg border border-slate-200 bg-white font-bold text-xs uppercase text-slate-700 outline-none">
                                            <option value="teacher" <?= $u['role'] === 'teacher' ? 'selected' : '' ?>>Guru</option>
                                            <option value="staff" <?= $u['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                            <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Siswa</option>
                                            <option value="superadmin" <?= $u['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                        </select>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-700">
                                        <?= $u['total_links'] ?> link &bull; <?= $u['total_bios'] ?> bio
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <button onclick="toggleUserStatus(<?= $u['id'] ?>, this)" class="px-2.5 py-1 rounded-full text-[11px] font-bold transition <?= $u['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' ?>">
                                            <?= $u['is_active'] ? 'Aktif' : 'Diblokir' ?>
                                        </button>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-400"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="promptResetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')" class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-[11px] transition">
                                                Reset Sandi
                                            </button>
                                            <?php if ((int)$u['id'] !== (int)$adminUser['id']): ?>
                                                <button type="button" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email']) ?>')" class="p-1.5 text-slate-400 hover:text-red-600 transition" title="Hapus Pengguna">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: Kelola Seluruh Tautan (All Short Links) -->
        <div id="tabLinks" class="hidden space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Seluruh Tautan Pendek Sekolah</h3>
                        <p class="text-xs text-slate-500">Pantau dan kelola semua link yang dibuat oleh guru, staff, dan siswa.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-bold">Short Link & Judul</th>
                                <th class="py-3 px-4 font-bold">URL Tujuan Asli</th>
                                <th class="py-3 px-4 font-bold">Pemilik</th>
                                <th class="py-3 px-4 font-bold">Klik</th>
                                <th class="py-3 px-4 font-bold">Proteksi</th>
                                <th class="py-3 px-4 font-bold">Status</th>
                                <th class="py-3 px-4 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <?php foreach ($allLinks as $l): ?>
                                <?php $slug = $l['custom_slug'] ?: $l['short_code']; ?>
                                <tr id="link-row-<?= $l['id'] ?>" class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-4">
                                        <a href="/<?= htmlspecialchars($slug) ?>" target="_blank" class="font-extrabold text-blue-600 hover:underline flex items-center gap-1 text-sm">
                                            <span>s.labschool.xyz/<?= htmlspecialchars($slug) ?></span>
                                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <div class="font-semibold text-slate-800 text-xs mt-0.5"><?= htmlspecialchars($l['title']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 truncate max-w-xs text-slate-500 font-mono text-[11px]"><?= htmlspecialchars($l['original_url']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($l['owner_name']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($l['owner_email']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 font-black text-blue-600"><?= number_format($l['clicks_count']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <?php if (!empty($l['password_hash'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">PIN</span>
                                        <?php else: ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <button onclick="toggleLinkStatus(<?= $l['id'] ?>, this)" class="px-2.5 py-1 rounded-full text-[11px] font-bold transition <?= $l['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-200 text-slate-600' ?>">
                                            <?= $l['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </button>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="/analytics.php?id=<?= $l['id'] ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Lihat Analitik">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                            </a>
                                            <button onclick="deleteLinkAdmin(<?= $l['id'] ?>)" class="p-1.5 text-slate-400 hover:text-red-600 transition" title="Hapus Link">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: Kelola Seluruh Microsite (All Microsites) -->
        <div id="tabBios" class="hidden space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Seluruh Halaman Microsite</h3>
                        <p class="text-xs text-slate-500">Kelola bio link kepanitiaan, ekskul, dan profil yang terdaftar.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-bold">Slug & Judul</th>
                                <th class="py-3 px-4 font-bold">Pemilik</th>
                                <th class="py-3 px-4 font-bold">Tema</th>
                                <th class="py-3 px-4 font-bold">Total Views</th>
                                <th class="py-3 px-4 font-bold">Status</th>
                                <th class="py-3 px-4 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <?php foreach ($allBios as $b): ?>
                                <tr id="bio-row-<?= $b['id'] ?>" class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-4">
                                        <a href="/p/<?= htmlspecialchars($b['slug']) ?>" target="_blank" class="font-extrabold text-purple-600 hover:underline flex items-center gap-1 text-sm">
                                            <span>s.labschool.xyz/p/<?= htmlspecialchars($b['slug']) ?></span>
                                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <div class="font-semibold text-slate-800 text-xs mt-0.5"><?= htmlspecialchars($b['title']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($b['owner_name']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($b['owner_email']) ?></div>
                                    </td>
                                    <td class="py-3.5 px-4 capitalize font-semibold text-slate-700"><?= htmlspecialchars($b['theme']) ?></td>
                                    <td class="py-3.5 px-4 font-black text-purple-600"><?= number_format($b['clicks_count']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $b['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-200 text-slate-600' ?>">
                                            <?= $b['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="/p/<?= htmlspecialchars($b['slug']) ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                                                Buka
                                            </a>
                                            <button onclick="deleteBioAdmin(<?= $b['id'] ?>)" class="p-1.5 text-slate-400 hover:text-red-600 transition" title="Hapus Microsite">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 5: Log Trafik Global -->
        <div id="tabLogs" class="hidden space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">30 Aktivitas Klik Terkini (Global Log)</h3>
                        <p class="text-xs text-slate-500">Rekap jejak kunjungan pengunjung secara real-time.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 font-bold">Waktu (WIB)</th>
                                <th class="py-3 px-4 font-bold">Tautan Target</th>
                                <th class="py-3 px-4 font-bold">IP & Lokasi</th>
                                <th class="py-3 px-4 font-bold">Perangkat / OS</th>
                                <th class="py-3 px-4 font-bold">Browser</th>
                                <th class="py-3 px-4 font-bold">Referrer</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <?php foreach ($recentClicks as $c): ?>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-800"><?= date('d/m H:i:s', strtotime($c['clicked_at'])) ?></td>
                                    <td class="py-3 px-4 font-bold text-blue-600">s.labschool.xyz/<?= htmlspecialchars($c['custom_slug'] ?: $c['short_code']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($c['city'] ?: 'Jakarta') ?>, <?= htmlspecialchars($c['country'] ?: 'ID') ?></td>
                                    <td class="py-3 px-4 capitalize font-semibold"><?= htmlspecialchars($c['device_type']) ?> (<?= htmlspecialchars($c['os'] ?: 'OS') ?>)</td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($c['browser'] ?: 'Browser') ?></td>
                                    <td class="py-3 px-4 truncate max-w-xs"><?= htmlspecialchars($c['referrer'] ?: 'Direct') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchAdminTab(tab) {
            ['overview', 'users', 'links', 'bios', 'logs'].forEach(t => {
                const el = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
                const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
                if (el) el.classList.add('hidden');
                if (btn) btn.className = 'px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition';
            });

            const activeEl = document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1));
            const activeBtn = document.getElementById('tabBtn' + tab.charAt(0).toUpperCase() + tab.slice(1));
            if (activeEl) activeEl.classList.remove('hidden');
            if (activeBtn) activeBtn.className = 'px-4 py-2 font-black text-sm rounded-xl bg-slate-900 text-white shadow-sm transition';
        }

        async function toggleUserStatus(id, btn) {
            try {
                const res = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_status', user_id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    btn.innerText = json.is_active ? 'Aktif' : 'Diblokir';
                    btn.className = json.is_active ? 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 transition' : 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-600 transition';
                } else {
                    alert(json.message);
                }
            } catch (e) {
                alert('Gagal mengubah status.');
            }
        }

        async function changeUserRole(id, newRole) {
            try {
                const res = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'change_role', user_id: id, role: newRole })
                });
                const json = await res.json();
                alert(json.message);
            } catch (e) {
                alert('Gagal mengubah peranan.');
            }
        }

        async function promptResetPassword(id, email) {
            const newPass = prompt('Masukkan kata sandi baru untuk akun ' + email + ': (minimal 6 karakter)');
            if (!newPass) return;
            if (newPass.length < 6) {
                alert('Kata sandi minimal 6 karakter.');
                return;
            }

            try {
                const res = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'reset_password', user_id: id, new_password: newPass })
                });
                const json = await res.json();
                alert(json.message);
            } catch (e) {
                alert('Gagal mereset kata sandi.');
            }
        }

        async function deleteUser(id, email) {
            if (!confirm('YAKIN HAPUS PENGGUNA ' + email + '?\nSeluruh short link dan microsite miliknya akan terhapus permanen!')) return;

            try {
                const res = await fetch('/api/admin/users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_user', user_id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('user-row-' + id)?.remove();
                }
                alert(json.message);
            } catch (e) {
                alert('Gagal menghapus pengguna.');
            }
        }

        async function toggleLinkStatus(id, btn) {
            try {
                const res = await fetch('/api/toggle_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    btn.innerText = json.is_active ? 'Aktif' : 'Nonaktif';
                    btn.className = json.is_active ? 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 transition' : 'px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-200 text-slate-600 transition';
                }
            } catch (e) {
                alert('Gagal mengubah status link.');
            }
        }

        async function deleteLinkAdmin(id) {
            if (!confirm('Hapus tautan ini secara permanen?')) return;
            try {
                const res = await fetch('/api/delete_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('link-row-' + id)?.remove();
                } else {
                    alert(json.message);
                }
            } catch (e) {
                alert('Gagal menghapus tautan.');
            }
        }

        async function deleteBioAdmin(id) {
            if (!confirm('Hapus halaman microsite ini secara permanen?')) return;
            try {
                const res = await fetch('/api/delete_microsite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('bio-row-' + id)?.remove();
                } else {
                    alert(json.message);
                }
            } catch (e) {
                alert('Gagal menghapus microsite.');
            }
        }

        // Render Grafik Tren Global
        const ctx = document.getElementById('globalChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: 'Total Klik Seluruh Sistem',
                    data: <?= json_encode($counts) ?>,
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#1E40AF',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
