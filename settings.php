<?php
/**
 * Settings & Account Management: s.labschool.xyz
 * Platform: Enterprise Shortener & Microsites Labschool
 * Layout Parity: Settings, Account Details, Integrations & API
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();

$tab = $_GET['tab'] ?? 'account-details';

// Pesan Flash
$flashSuccess = '';
$flashError = '';

// Handle Update Profile
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    if (!empty($fullName)) {
        if (!empty($newPass)) {
            if (strlen($newPass) < 8) {
                $flashError = 'Password baru minimal 8 karakter.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$fullName, $hash, $user['id']]);
                $flashSuccess = 'Profil dan kata sandi berhasil diperbarui.';
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $user['id']]);
            $flashSuccess = 'Nama profil berhasil diperbarui.';
        }
        $user = current_user(); // reload
    }
}

// Data Organisasi & Kuota
$orgName = 'SMP Labschool Jakarta';
$orgId = 'labs_3v10apvpbq';
$planName = 'Enterprise Education Plan';
$totalSeats = 10;

// Scope Protection: Hak Akses Seluruh Pengguna Khusus Administrator
$isAdmin = in_array($user['role'] ?? '', ['superadmin', 'admin'], true);

if ($isAdmin) {
    // Administrator: Akses penuh ke seluruh daftar pengguna organisasi
    $usersStmt = $pdo->query("
        SELECT u.id, u.email, u.full_name, u.role, u.is_active, u.created_at,
               (SELECT COUNT(*) FROM short_urls WHERE user_id = u.id) as total_links
        FROM users u
        ORDER BY u.created_at ASC
    ");
    $allUsers = $usersStmt->fetchAll();
    $usedSeats = count($allUsers);
} else {
    // Pengguna non-admin: Scope dibatasi secara ketat hanya untuk akun milik sendiri
    $usersStmt = $pdo->prepare("
        SELECT u.id, u.email, u.full_name, u.role, u.is_active, u.created_at,
               (SELECT COUNT(*) FROM short_urls WHERE user_id = u.id) as total_links
        FROM users u
        WHERE u.id = ?
    ");
    $usersStmt->execute([$user['id']]);
    $allUsers = $usersStmt->fetchAll();
    $usedSeats = 1;
}

// Generate atau ambil API Key
$apiKeyStmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
$apiKeyStmt->execute([$user['id']]);
$apiKey = $apiKeyStmt->fetchColumn();

if (empty($apiKey)) {
    $apiKey = 'labs_' . bin2hex(random_bytes(24));
    $upStmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $upStmt->execute([$apiKey, $user['id']]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun & Organisasi - s.labschool.xyz</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0..1,0" />

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex font-sans overflow-x-hidden">

    <!-- 1. MAIN LEFT SIDEBAR (Standard Navigation) -->
    <aside class="w-64 bg-slate-900 text-white shrink-0 flex flex-col justify-between hidden lg:flex min-h-screen sticky top-0 z-30">
        <div class="p-5 space-y-6">
            <!-- Brand Logo -->
            <a href="/dashboard.php" class="flex items-center gap-3">
                <img src="/assets/img/logo-smp-labschool.png" alt="Labschool" class="h-9 w-auto">
                <div>
                    <div class="text-base font-black tracking-tight text-white leading-tight">s.labschool.xyz</div>
                    <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">Enterprise Platform</div>
                </div>
            </a>

            <!-- + Buat Baru Dropdown Button -->
            <a href="/dashboard.php" class="w-full py-3 px-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-lg shadow-blue-600/30 transition flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl">arrow_back</span>
                    <span>Kembali ke Dashboard</span>
                </span>
            </a>

            <!-- Navigation Links -->
            <nav class="space-y-1.5 text-xs font-bold">
                <a href="/dashboard.php" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">home</span>
                    <span>Home</span>
                </a>
                <a href="/dashboard.php?tab=links" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">link</span>
                    <span>Tautan (Links)</span>
                </a>
                <a href="/dashboard.php?tab=qr" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">qr_code</span>
                    <span>QR Codes & Frames</span>
                </a>
                <a href="/dashboard.php?tab=bios" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">view_stream</span>
                    <span>Halaman (Labs Pages)</span>
                </a>
                <a href="/analytics.php" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">bar_chart</span>
                    <span>Analitik</span>
                </a>
                <a href="/dashboard.php?tab=campaigns" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">label</span>
                    <span>Kampanye & Tag</span>
                </a>
                <a href="/settings.php?tab=integrations" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl <?= $tab === 'integrations' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?> transition">
                    <span class="material-symbols-rounded text-xl">widgets</span>
                    <span>Integrasi</span>
                </a>
                <a href="/settings.php?tab=account-details" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl <?= in_array($tab, ['account-details', 'users', 'billing', 'profile', 'api']) ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?> transition">
                    <span class="material-symbols-rounded text-xl">settings</span>
                    <span>Pengaturan</span>
                </a>
            </nav>
        </div>

        <!-- User Profile Card -->
        <div class="p-4 border-t border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3 truncate">
                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-black text-xs text-white uppercase shrink-0">
                    <?= substr($user['full_name'], 0, 2) ?>
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="text-[10px] text-slate-400 truncate"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>
            <a href="/logout.php" class="text-slate-400 hover:text-rose-400 p-2" title="Keluar">
                <span class="material-symbols-rounded text-lg">logout</span>
            </a>
        </div>
    </aside>

    <!-- 2. SETTINGS SUB-NAV & CONTENT CONTAINER -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Top Header Breadcrumb -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <a href="/dashboard.php" class="text-xs font-extrabold text-blue-600 hover:underline flex items-center gap-1">
                    <span>Dashboard</span>
                    <span class="material-symbols-rounded text-sm">chevron_right</span>
                </a>
                <span class="text-xs font-bold text-slate-400">Settings</span>
                <span class="material-symbols-rounded text-sm text-slate-300">chevron_right</span>
                <span class="text-xs font-black text-slate-800 capitalize"><?= str_replace('-', ' ', $tab) ?></span>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-sm">verified</span>
                    <span><?= htmlspecialchars($orgName) ?></span>
                </span>
                <a href="/dashboard.php" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Tutup Pengaturan">
                    <span class="material-symbols-rounded text-xl">close</span>
                </a>
            </div>
        </header>

        <!-- Flash Alert -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="mx-6 sm:mx-8 mt-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                <span class="material-symbols-rounded text-lg">check_circle</span>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="mx-6 sm:mx-8 mt-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
                <span class="material-symbols-rounded text-lg">error</span>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Layout Body: Sub-nav Left + Settings Page Right -->
        <div class="flex-1 flex flex-col md:flex-row min-w-0">
            
            <!-- Left Sub-Navigation for Settings (Enterprise Layout) -->
            <nav class="w-full md:w-64 bg-white border-r border-slate-200/80 p-5 space-y-6 shrink-0">
                <!-- Personal Settings Section -->
                <div>
                    <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-2">Personal settings</h3>
                    <ul class="space-y-1 text-xs font-bold">
                        <li>
                            <a href="/settings.php?tab=profile" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'profile' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Profile</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=integrations" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'integrations' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Integrations</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Account Settings Section -->
                <div>
                    <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-slate-400 mb-2">
                        <span>Account settings</span>
                        <span class="text-[10px] text-slate-400 font-mono"><?= $orgId ?></span>
                    </div>
                    <ul class="space-y-1 text-xs font-bold">
                        <li>
                            <a href="/settings.php?tab=account-details" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'account-details' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Account details</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=billing" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'billing' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Billing and usage</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=users" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'users' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Users</span>
                                <?php if ($isAdmin): ?>
                                    <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-[10px]"><?= $usedSeats ?>/<?= $totalSeats ?></span>
                                <?php else: ?>
                                    <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[9px] font-bold">Admin Only</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=groups" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'groups' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Groups</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=sso" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'sso' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Single sign-on (SSO)</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=mobile-deep-links" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'mobile-deep-links' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Mobile deep links</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=webhooks" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'webhooks' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Webhooks</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=bulk-upload" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'bulk-upload' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Bulk upload</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=activity-log" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'activity-log' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Activity log</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Developer Settings Section -->
                <div>
                    <h3 class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-2">Developer settings</h3>
                    <ul class="space-y-1 text-xs font-bold">
                        <li>
                            <a href="/settings.php?tab=api" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'api' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>API (Access Tokens)</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Dynamic Content Pane -->
            <main class="flex-1 p-6 sm:p-10 max-w-5xl">

                <!-- TAB 1: USERS (Account Details Users Table Parity) -->
                <?php if ($tab === 'users'): ?>
                    <div class="space-y-6">
                        
                        <?php if (!$isAdmin): ?>
                            <!-- Non-Admin Scope Restriction Banner -->
                            <div class="bg-amber-50 border border-amber-200/90 rounded-2xl p-5 flex items-start gap-4">
                                <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700 shrink-0">
                                    <span class="material-symbols-rounded text-2xl">admin_panel_settings</span>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-sm font-extrabold text-amber-900">Hak Akses Terbatas (Administrator Only)</h3>
                                    <p class="text-xs text-amber-800 leading-relaxed">
                                        Lingkup (scope) seluruh pengguna organisasi hanya dapat diakses, dikelola, dan diekspor oleh <strong>Administrator Akun</strong>. Tampilan di bawah ini dibatasi secara aman hanya untuk rincian akun Anda sendiri.
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Users</h1>
                                <p class="text-xs font-semibold text-slate-500 mt-1">
                                    <?php if ($isAdmin): ?>
                                        Using <span class="font-bold text-slate-800"><?= $usedSeats ?></span> of <?= $totalSeats ?> user seats 
                                        <span class="text-slate-400">(0 pending requests, 0 invites sent)</span>
                                    <?php else: ?>
                                        Scope Akun Anda: <span class="font-bold text-slate-800"><?= htmlspecialchars($user['email']) ?></span> (1 Akun Terdaftar)
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($isAdmin): ?>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="exportUsersCsv()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-extrabold text-xs shadow-sm transition flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-base">file_download</span>
                                        <span>Export</span>
                                    </button>
                                    <button type="button" onclick="openAddUserModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-base">add</span>
                                        <span>Add new user</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Active / Pending Filter Tabs & Search Bar -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-b border-slate-200">
                            <div class="flex items-center gap-6 text-xs font-extrabold">
                                <button class="pb-3 border-b-2 border-blue-600 text-blue-600">Active (<?= $usedSeats ?>)</button>
                                <button class="pb-3 text-slate-400 hover:text-slate-600">Pending (0)</button>
                            </div>
                            <?php if ($isAdmin): ?>
                                <div class="relative w-full sm:w-64 pb-2">
                                    <span class="material-symbols-rounded absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
                                    <input type="text" id="userSearchInput" onkeyup="filterUserTable()" placeholder="Cari user atau email..."
                                        class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-slate-100 border-none text-xs font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Users Table -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-600 divide-y divide-slate-100" id="usersTable">
                                    <thead class="bg-slate-50/80 font-bold text-slate-500 text-[11px] uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">User</th>
                                            <th class="py-3 px-4">Email</th>
                                            <th class="py-3 px-4">2FA</th>
                                            <th class="py-3 px-4">SSO</th>
                                            <th class="py-3 px-4">Created</th>
                                            <th class="py-3 px-4">Permissions</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium">
                                        <?php foreach ($allUsers as $u): ?>
                                            <tr class="hover:bg-slate-50/60 transition user-row">
                                                <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                                        <?= strtoupper(substr($u['full_name'], 0, 2)) ?>
                                                    </div>
                                                    <div class="truncate max-w-[150px]">
                                                        <div><?= htmlspecialchars($u['full_name']) ?></div>
                                                        <?php if ($u['id'] === $user['id']): ?>
                                                            <span class="text-[9px] font-extrabold text-blue-600 uppercase tracking-widest">(You)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="py-3.5 px-4 text-slate-700 font-mono text-[11px]">
                                                    <?= htmlspecialchars($u['email']) ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-slate-400">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                                        -
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4 text-slate-400">
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">
                                                        Google Workspace
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                                    <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    <?php if ($u['role'] === 'superadmin'): ?>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800">
                                                            Account admin
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700">
                                                            Editor
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <button type="button" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                                                        <span class="material-symbols-rounded text-base">more_horiz</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Enterprise Group Effort Callout -->
                        <div class="bg-gradient-to-r from-blue-900 to-indigo-950 rounded-2xl p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-amber-400 text-2xl">auto_awesome</span>
                                </div>
                                <div>
                                    <h4 class="text-sm font-extrabold">Kolaborasi Tim Guru & Civitas Akademika</h4>
                                    <p class="text-xs text-blue-200 mt-0.5">Kelola departemen, kepanitiaan, dan staf pengajar menggunakan kuota Enterprise Labschool.</p>
                                </div>
                            </div>
                            <button type="button" onclick="alert('Hubungi Administrator Labschool untuk penambahan kuota khusus.')" class="px-4 py-2 rounded-xl bg-white text-blue-900 font-extrabold text-xs shadow-md shrink-0 hover:bg-blue-50 transition">
                                Hubungi Dukungan IT
                            </button>
                        </div>
                    </div>

                <!-- TAB 2: INTEGRATIONS (Enterprise Directory) -->
                <?php elseif ($tab === 'integrations'): ?>
                    <div class="space-y-8">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Integrations</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Kelola integrasi pihak ketiga, aplikasi mobile, dan layanan yang terhubung dengan akun Anda.</p>
                        </div>

                        <!-- Connected Integrations Table -->
                        <div class="space-y-3">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Connected Integrations</h2>
                            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                                <table class="w-full text-left text-xs divide-y divide-slate-100">
                                    <thead class="bg-slate-50/80 font-bold text-slate-500 text-[11px] uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Nama Integrasi</th>
                                            <th class="py-3 px-4">Tanggal Terhubung</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium">
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                                <span class="material-symbols-rounded text-blue-600 text-lg">api</span>
                                                <span>Labschool REST API Engine</span>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">04/04/2022</td>
                                            <td class="py-3.5 px-4 text-right">
                                                <button type="button" onclick="alert('Akses API berhasil disegarkan.')" class="text-xs font-extrabold text-rose-600 hover:underline">Revoke access</button>
                                            </td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                                <span class="material-symbols-rounded text-emerald-600 text-lg">phone_android</span>
                                                <span>Official Labschool Mobile App</span>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">08/08/2019</td>
                                            <td class="py-3.5 px-4 text-right">
                                                <button type="button" onclick="alert('Akses Mobile berhasil disegarkan.')" class="text-xs font-extrabold text-rose-600 hover:underline">Revoke access</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- More Integrations Directory -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-base font-extrabold text-slate-900">More integrations</h2>
                                <span class="text-xs font-extrabold text-blue-600 cursor-pointer hover:underline">View all directory &rarr;</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Google Chrome Extension -->
                                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3 hover:border-blue-400 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                            <span class="material-symbols-rounded text-2xl">extension</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700">Popular</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-slate-900">Google Chrome Extension</h3>
                                        <p class="text-xs text-slate-500 mt-1">Buat tautan pendek dan unduh kode QR langsung dari tab peramban Anda.</p>
                                    </div>
                                    <button type="button" class="w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs transition">
                                        Pasang Ekstensi
                                    </button>
                                </div>

                                <!-- Labschool LMS Moodle -->
                                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3 hover:border-blue-400 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                            <span class="material-symbols-rounded text-2xl">school</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700">Official</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-slate-900">Labschool LMS (Moodle)</h3>
                                        <p class="text-xs text-slate-500 mt-1">Otomasi pembuatan tautan kuis, bank soal, dan Safe Exam Browser (SEB).</p>
                                    </div>
                                    <button type="button" class="w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs transition">
                                        Konfigurasi Plugin
                                    </button>
                                </div>

                                <!-- Google Workspace (GAM) -->
                                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3 hover:border-blue-400 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                                            <span class="material-symbols-rounded text-2xl">mail</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700">Workspace</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-slate-900">Google Workspace (GAM)</h3>
                                        <p class="text-xs text-slate-500 mt-1">Sinkronisasi tautan Google Classroom, Drive, dan formulir pendaftaran.</p>
                                    </div>
                                    <button type="button" class="w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs transition">
                                        Hubungkan Akun
                                    </button>
                                </div>

                                <!-- Claude & AI Assist -->
                                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-3 hover:border-blue-400 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                            <span class="material-symbols-rounded text-2xl">neurology</span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-50 text-purple-700">AI Assist</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-slate-900">Claude & AI Assistants</h3>
                                        <p class="text-xs text-slate-500 mt-1">Pemendek tautan cepat, QR kustom, dan rekap statistik langsung via obrolan AI.</p>
                                    </div>
                                    <button type="button" class="w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs transition">
                                        Aktifkan Integrasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- TAB 3: ACCOUNT DETAILS -->
                <?php elseif ($tab === 'account-details'): ?>
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Account details</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Kelola rincian unit sekolah, identitas organisasi, dan konfigurasi domain utama.</p>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Organisasi / Sekolah</label>
                                    <input type="text" value="<?= htmlspecialchars($orgName) ?>" readonly class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Organization ID</label>
                                    <input type="text" value="<?= htmlspecialchars($orgId) ?>" readonly class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-mono font-bold text-slate-800 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tipe Lisensi</label>
                                    <div class="flex items-center gap-2">
                                        <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 font-extrabold text-xs border border-emerald-200 flex items-center gap-1">
                                            <span class="material-symbols-rounded text-sm">verified</span>
                                            <span><?= $planName ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Custom Domain Utama</label>
                                    <input type="text" value="s.labschool.xyz" readonly class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-blue-600 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Administrator Penanggung Jawab</label>
                                    <input type="text" value="<?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)" readonly class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Zona Waktu Sistem</label>
                                    <input type="text" value="Asia/Jakarta (WIB - UTC+07:00)" readonly class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-800 outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- TAB 4: PROFILE -->
                <?php elseif ($tab === 'profile'): ?>
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Personal Profile</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Perbarui nama pengguna, kredensial masuk, dan foto profil Anda.</p>
                        </div>

                        <form method="POST" action="/settings.php?tab=profile" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6 max-w-xl">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-blue-600 text-white font-black text-xl flex items-center justify-center shrink-0 uppercase">
                                    <?= substr($user['full_name'], 0, 2) ?>
                                </div>
                                <div>
                                    <div class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($user['full_name']) ?></div>
                                    <div class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email (Akun)</label>
                                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly
                                        class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-medium text-slate-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru (Kosongkan jika tidak diubah)</label>
                                    <input type="password" name="new_password" placeholder="Minimal 8 karakter..."
                                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                <!-- TAB 5: API DEVELOPER SETTINGS -->
                <?php elseif ($tab === 'api'): ?>
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Developer API</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Gunakan token akses untuk melakukan integrasi otomatis pemendekan tautan melalui REST API.</p>
                        </div>

                        <!-- API Key Box -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Generic Access Token</h2>
                            <div class="flex items-center gap-3">
                                <input type="text" id="apiTokenInput" readonly value="<?= htmlspecialchars($apiKey) ?>"
                                    class="w-full px-4 py-3 rounded-xl bg-slate-100 border border-slate-200 font-mono text-xs font-bold text-slate-800 outline-none select-all">
                                <button type="button" onclick="copyToken()" class="px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition flex items-center gap-1.5 shrink-0">
                                    <span class="material-symbols-rounded text-base">content_copy</span>
                                    <span>Salin Token</span>
                                </button>
                            </div>
                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-slate-400">Gunakan header <code class="bg-slate-100 text-slate-800 px-1.5 py-0.5 rounded font-mono text-[11px]">Authorization: Bearer &lt;TOKEN&gt;</code></span>
                                <button type="button" onclick="regenerateToken()" class="text-rose-600 font-extrabold hover:underline">
                                    Generate New Token
                                </button>
                            </div>
                        </div>

                        <!-- Example Code snippet -->
                        <div class="bg-slate-900 rounded-2xl p-6 text-white space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Contoh cURL Pembuatan Tautan</span>
                                <span class="text-[10px] font-mono text-emerald-400 bg-emerald-950 px-2 py-0.5 rounded">POST /api/shorten.php</span>
                            </div>
                            <pre class="bg-slate-950/80 p-4 rounded-xl text-xs font-mono text-blue-300 overflow-x-auto leading-relaxed">curl -X POST https://s.labschool.xyz/api/shorten.php \
  -H "Authorization: Bearer <?= htmlspecialchars($apiKey) ?>" \
  -H "Content-Type: application/json" \
  -d '{"original_url": "https://smplabschooljakarta.sch.id/pengumuman", "custom_slug": "info-baru"}'</pre>
                        </div>
                    </div>

                <!-- DEFAULT TAB / FALLBACK -->
                <?php else: ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-10 text-center space-y-3">
                        <span class="material-symbols-rounded text-4xl text-slate-400">settings</span>
                        <h2 class="text-base font-extrabold text-slate-900 capitalize"><?= htmlspecialchars(str_replace('-', ' ', $tab)) ?></h2>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Fitur konfigurasi ini aktif dan terkelola secara otomatis oleh sistem Enterprise Labschool.</p>
                        <a href="/settings.php?tab=users" class="inline-block mt-2 px-4 py-2 rounded-xl bg-blue-600 text-white font-extrabold text-xs">Buka Manajemen Users</a>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Script Utilitas -->
    <script>
        function copyToken() {
            const input = document.getElementById('apiTokenInput');
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                alert('Token berhasil disalin ke clipboard!');
            });
        }

        async function regenerateToken() {
            if (!confirm('Peringatan: Membuat ulang token akan membatalkan API token lama Anda. Lanjutkan?')) return;
            try {
                const res = await fetch('/api/user/api_key.php', { method: 'POST' });
                const data = await res.json();
                if (data.status === 'success') {
                    document.getElementById('apiTokenInput').value = data.api_key;
                    alert('API token baru berhasil dibuat!');
                }
            } catch (e) {
                alert('Gagal membuat token baru.');
            }
        }

        function filterUserTable() {
            const q = document.getElementById('userSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(q) ? '' : 'none';
            });
        }

        function exportUsersCsv() {
            window.location.href = '/api/export_csv.php?type=users';
        }

        function openAddUserModal() {
            alert('Gunakan panel pendaftaran /register.php atau hubungi Admin UNJ Labschool untuk penambahan akun.');
        }
    </script>
</body>
</html>
