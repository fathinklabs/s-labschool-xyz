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

// 7. Penanganan Form POST: Konfigurasi Global Platform
$flashSuccess = '';
$flashError = '';

$defaultSettings = [
    'site_name'                 => 's.labschool.xyz',
    'site_title'                => 'Labschool URL Shortener, QR Code & Pages',
    'default_domain'            => 's.labschool.xyz',
    'fallback_url'              => 'https://labschool.xyz',
    'allowed_email_domains'     => 'labschool.xyz, elabschool.sch.id',
    'allow_public_registration' => '0',
    'rate_limit_per_minute'     => '60',
    'session_timeout_days'      => '7',
    'maintenance_mode'          => '0',
    'maintenance_message'       => 'Platform s.labschool.xyz sedang dalam pemeliharaan sistem berkala. Silakan kembali dalam beberapa saat.'
];

// Pastikan tabel global_settings siap digunakan
try {
    $settingsRows = $pdo->query("SELECT setting_key, setting_value FROM global_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $currentSettings = array_merge($defaultSettings, $settingsRows ?: []);
} catch (Exception $e) {
    $currentSettings = $defaultSettings;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_global_settings') {
    require_csrf();
    try {
        $keys = array_keys($defaultSettings);
        $updateStmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value, category, updated_at, updated_by) 
            VALUES (?, ?, ?, NOW(), ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW(), updated_by = VALUES(updated_by)");

        foreach ($keys as $k) {
            if ($k === 'allow_public_registration' || $k === 'maintenance_mode') {
                $val = isset($_POST[$k]) && $_POST[$k] === '1' ? '1' : '0';
            } else {
                $val = trim((string)($_POST[$k] ?? $defaultSettings[$k]));
            }
            $cat = 'general';
            if (in_array($k, ['allowed_email_domains', 'allow_public_registration'])) $cat = 'auth';
            elseif (in_array($k, ['rate_limit_per_minute', 'session_timeout_days'])) $cat = 'security';
            elseif (in_array($k, ['maintenance_mode', 'maintenance_message'])) $cat = 'system';

            $updateStmt->execute([$k, $val, $cat, $adminUser['email']]);
            $currentSettings[$k] = $val;
        }
        $flashSuccess = 'Konfigurasi global platform berhasil diperbarui!';
    } catch (Exception $e) {
        $flashError = 'Gagal menyimpan konfigurasi: ' . $e->getMessage();
    }
}

// 8. Metrik Penggunaan Sistem & Penyimpanan (Tab 7)
$totalDisk = @disk_total_space(__DIR__) ?: (100 * 1024 * 1024 * 1024);
$freeDisk = @disk_free_space(__DIR__) ?: (50 * 1024 * 1024 * 1024);
$usedDisk = max(0, $totalDisk - $freeDisk);
$diskPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 1) : 0;

$assetsSize = 0;
$assetsDir = __DIR__ . '/assets/img';
if (is_dir($assetsDir)) {
    try {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetsDir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $assetsSize += $file->getSize();
        }
    } catch (Exception $e) {}
}

$dbTables = [];
$totalDbSize = 0;
try {
    $tableStatsStmt = $pdo->query("
        SELECT table_name, table_rows, data_length, (data_length + index_length) as total_size
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
        ORDER BY total_size DESC
    ");
    $dbTables = $tableStatsStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalDbSize = array_sum(array_column($dbTables, 'total_size'));
} catch (Exception $e) {}

$clicksToday = 0;
$clicks7Days = 0;
try {
    $clicksToday = (int)$pdo->query("SELECT COUNT(*) FROM click_logs WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
    $clicks7Days = (int)$pdo->query("SELECT COUNT(*) FROM click_logs WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {}

$domainStats = [];
try {
    $domainStats = $pdo->query("
        SELECT domain, COUNT(*) as count 
        FROM users 
        GROUP BY domain 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$roleStats = [];
try {
    $roleStats = $pdo->query("
        SELECT role, COUNT(*) as count 
        FROM users 
        GROUP BY role 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$deviceStats = [];
try {
    $deviceStats = $pdo->query("
        SELECT device_type, COUNT(*) as count 
        FROM click_logs 
        GROUP BY device_type 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
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

        <!-- Flash Alert Notifikasi -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">&times;</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">&times;</button>
            </div>
        <?php endif; ?>

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
            <button onclick="switchAdminTab('config')" id="tabBtnConfig" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Konfigurasi Global</span>
            </button>
            <button onclick="switchAdminTab('metrics')" id="tabBtnMetrics" class="px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition flex items-center gap-1.5">
                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Metrik Penggunaan</span>
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

        <!-- TAB 6: Konfigurasi Global Sistem (Global Configuration) -->
        <div id="tabConfig" class="hidden space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="border-b border-slate-100 pb-5 mb-6">
                    <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Konfigurasi Global Platform s.labschool.xyz</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Kelola parameter operasional, kebijakan pendaftaran, domain shortener, dan keamanan sistem.</p>
                </div>

                <form method="POST" action="/admin.php?tab=config" class="space-y-8">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_global_settings">

                    <!-- Section 1: Identitas & Domain -->
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-blue-600 mb-4 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span>1. Identitas Platform & Domain Default</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Brand Singkat</label>
                                <input type="text" name="site_name" value="<?= htmlspecialchars($currentSettings['site_name']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Contoh: s.labschool.xyz</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Lengkap Platform</label>
                                <input type="text" name="site_title" value="<?= htmlspecialchars($currentSettings['site_title']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Judul header & meta-tag HTML</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Domain Pemendek Utama</label>
                                <input type="text" name="default_domain" value="<?= htmlspecialchars($currentSettings['default_domain']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Domain dasar pembuatan short links dan bio</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Fallback Redirect URL (Jika 404 / Tidak Ditemukan)</label>
                                <input type="url" name="fallback_url" value="<?= htmlspecialchars($currentSettings['fallback_url']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Tujuan redirect jika kode tautan atau slug tidak ada</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Kebijakan Autentikasi & Registrasi -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-600 mb-4 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                            <span>2. Kebijakan Pendaftaran & Autentikasi Civitas</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Whitelist Domain Email Registrasi</label>
                                <input type="text" name="allowed_email_domains" value="<?= htmlspecialchars($currentSettings['allowed_email_domains']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold font-mono text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Pisahkan dengan koma (contoh: labschool.xyz, elabschool.sch.id)</span>
                            </div>
                            <div class="flex flex-col justify-center">
                                <label class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                                    <input type="checkbox" name="allow_public_registration" value="1" <?= $currentSettings['allow_public_registration'] === '1' ? 'checked' : '' ?> class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                    <div>
                                        <div class="text-xs font-extrabold text-slate-900">Buka Pendaftaran Akun Mandiri</div>
                                        <div class="text-[10px] text-slate-500">Jika aktif, civitas dapat mendaftar mandiri via register.php sesuai domain whitelist.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Keamanan, Sesi & Rate Limiting -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-black uppercase tracking-wider text-amber-600 mb-4 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                            <span>3. Keamanan, Sesi & Anti-DDoS Rate Limiting</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Rate Limit Klik / Request per Menit per IP</label>
                                <input type="number" name="rate_limit_per_minute" min="10" max="1000" value="<?= htmlspecialchars($currentSettings['rate_limit_per_minute']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">Perlindungan dari bot scraping & flood traffic</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Masa Berlaku Sesi Login (Hari)</label>
                                <input type="number" name="session_timeout_days" min="1" max="90" value="<?= htmlspecialchars($currentSettings['session_timeout_days']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                                <span class="text-[10px] text-slate-400">TTL sesi cookie di peramban dan Redis</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Mode Pemeliharaan (Maintenance Mode) -->
                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-black uppercase tracking-wider text-rose-600 mb-4 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            <span>4. Mode Pemeliharaan (Maintenance Mode)</span>
                        </h4>
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 p-3 rounded-2xl bg-rose-50/50 border border-rose-200 cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" <?= $currentSettings['maintenance_mode'] === '1' ? 'checked' : '' ?> class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300">
                                <div>
                                    <div class="text-xs font-extrabold text-rose-900">Aktifkan Status Maintenance Mode</div>
                                    <div class="text-[10px] text-rose-600">Jika diaktifkan, seluruh pengguna non-admin dialihkan ke pesan pemeliharaan.</div>
                                </div>
                            </label>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pesan Banner Pemeliharaan</label>
                                <textarea name="maintenance_message" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-none focus:border-blue-500"><?= htmlspecialchars($currentSettings['maintenance_message']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan Perubahan Konfigurasi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 7: Metrik Penggunaan & Kesehatan Sistem (Metrics Usage) -->
        <div id="tabMetrics" class="hidden space-y-6">
            <!-- 4 Quick Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kapasitas Disk Server</div>
                    <div class="text-xl font-black text-slate-900"><?= format_bytes($usedDisk) ?> <span class="text-xs font-bold text-slate-400">/ <?= format_bytes($totalDisk) ?></span></div>
                    <div class="text-[11px] font-bold text-blue-600 mt-1"><?= $diskPercent ?>% Terpakai</div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Folder Aset / Upload</div>
                    <div class="text-xl font-black text-purple-600"><?= format_bytes($assetsSize) ?></div>
                    <div class="text-[11px] text-slate-500 font-semibold mt-1">Logo, Banner & Gambar Bio</div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Ukuran Basis Data</div>
                    <div class="text-xl font-black text-emerald-600"><?= format_bytes($totalDbSize) ?></div>
                    <div class="text-[11px] text-slate-500 font-semibold mt-1">Total Tabel MariaDB</div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Klik Hari Ini</div>
                    <div class="text-xl font-black text-amber-600"><?= number_format($clicksToday) ?></div>
                    <div class="text-[11px] text-slate-500 font-semibold mt-1"><?= number_format($clicks7Days) ?> klik dlm 7 hari</div>
                </div>
            </div>

            <!-- Disk Usage Progress Bar -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-700">Penggunaan Ruang Disk Server (Storage Partition)</span>
                    <span class="text-blue-600"><?= format_bytes($usedDisk) ?> dari <?= format_bytes($totalDisk) ?> (<?= $diskPercent ?>%)</span>
                </div>
                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full <?= $diskPercent > 85 ? 'bg-rose-500' : ($diskPercent > 65 ? 'bg-amber-500' : 'bg-blue-600') ?> rounded-full transition-all" style="width: <?= min(100, $diskPercent) ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Sisa Ruang Kosong: <strong class="text-slate-600"><?= format_bytes($freeDisk) ?></strong></span>
                    <span>Status: <strong class="<?= $diskPercent > 85 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= $diskPercent > 85 ? 'Kritis' : 'Normal / Sehat' ?></strong></span>
                </div>
            </div>

            <!-- 2 Columns: Database Tables & User Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Database Tables Breakdown -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Rincian Ukuran Tabel Basis Data</h4>
                            <p class="text-[11px] text-slate-400">Informasi kapasitas storage tabel `s_labschool_xyz`</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                                    <th class="py-2.5 px-4 font-bold">Tabel</th>
                                    <th class="py-2.5 px-4 font-bold">Baris Data</th>
                                    <th class="py-2.5 px-4 font-bold text-right">Ukuran Data</th>
                                    <th class="py-2.5 px-4 font-bold text-right">Total (Index + Data)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-600">
                                <?php foreach ($dbTables as $dt): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-2.5 px-4 font-mono font-bold text-slate-800"><?= htmlspecialchars($dt['table_name']) ?></td>
                                        <td class="py-2.5 px-4"><?= number_format($dt['table_rows']) ?></td>
                                        <td class="py-2.5 px-4 text-right"><?= format_bytes((int)$dt['data_length']) ?></td>
                                        <td class="py-2.5 px-4 text-right font-bold text-blue-600"><?= format_bytes((int)$dt['total_size']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Distribution & Demographics -->
                <div class="space-y-6">
                    <!-- User Domains -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                        <h4 class="text-sm font-extrabold text-slate-900 mb-3">Distribusi Domain Akun Civitas</h4>
                        <div class="space-y-3">
                            <?php foreach ($domainStats as $ds): ?>
                                <?php $domPct = $stats['users'] > 0 ? round(($ds['count'] / $stats['users']) * 100, 1) : 0; ?>
                                <div>
                                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                                        <span class="font-mono text-slate-800">@<?= htmlspecialchars($ds['domain'] ?: 'other') ?></span>
                                        <span class="text-blue-600"><?= $ds['count'] ?> akun (<?= $domPct ?>%)</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: <?= $domPct ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Role Breakdown & Device Breakdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">Peran Akun (Roles)</h4>
                            <div class="space-y-2">
                                <?php foreach ($roleStats as $rs): ?>
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="capitalize text-slate-700"><?= htmlspecialchars($rs['role']) ?></span>
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 text-[11px]"><?= $rs['count'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">Perangkat Pengunjung</h4>
                            <div class="space-y-2">
                                <?php foreach ($deviceStats as $dev): ?>
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="capitalize text-slate-700"><?= htmlspecialchars($dev['device_type'] ?: 'Unknown') ?></span>
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-800 text-[11px]"><?= $dev['count'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        function switchAdminTab(tab) {
            const allTabs = ['overview', 'users', 'links', 'bios', 'logs', 'config', 'metrics'];
            allTabs.forEach(t => {
                const el = document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1));
                const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
                if (el) el.classList.add('hidden');
                if (btn) btn.className = 'px-4 py-2 font-bold text-sm rounded-xl text-slate-600 hover:bg-slate-100 transition flex items-center gap-1.5';
            });

            const activeEl = document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1));
            const activeBtn = document.getElementById('tabBtn' + tab.charAt(0).toUpperCase() + tab.slice(1));
            if (activeEl) activeEl.classList.remove('hidden');
            if (activeBtn) activeBtn.className = 'px-4 py-2 font-black text-sm rounded-xl bg-slate-900 text-white shadow-sm transition flex items-center gap-1.5';
        }

        // Auto-switch tab if specified in URL query (?tab=config) or hash (#metrics)
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const queryTab = urlParams.get('tab') || window.location.hash.replace('#', '');
            if (queryTab && ['overview', 'users', 'links', 'bios', 'logs', 'config', 'metrics'].includes(queryTab)) {
                switchAdminTab(queryTab);
            }
        });

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
        const chartCanvas = document.getElementById('globalChart');
        if (chartCanvas && typeof Chart !== 'undefined') {
            const ctx = chartCanvas.getContext('2d');
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
        }
    </script>
</body>
</html>
