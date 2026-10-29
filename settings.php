<?php
/**
 * Settings & Organization Management: s.labschool.xyz
 * Platform: Enterprise Shortener & Microsites Labschool
 * Layout & Workspace Parity:
 * - Users: Organization Workspace Collaborators (Invite registered users to collaborate on links/pages)
 * - Groups: Scope Tag-based Permissions (Max 10 groups per user, gates link/page access)
 * - Bulk Upload: Pemendek massal & riwayat batch upload
 * - Activity Log: Riwayat aktivitas akun
 * - Disabled Tabs: SSO, Mobile Deep Links, Webhooks, Billing
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();
$userId = (int)$user['id'];

$tab = $_GET['tab'] ?? 'account-details';
$disabledTabs = ['sso', 'mobile-deep-links', 'webhooks', 'billing'];

// Pesan Flash
$flashSuccess = '';
$flashError = '';
$bulkResults = [];

// ==========================================
// 1. ACTION HANDLERS (POST)
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $action = $_POST['action'];

    // 1A. Update Profile Pribadi
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        if (!empty($fullName)) {
            if (!empty($newPass)) {
                if (strlen($newPass) < 8) {
                    $flashError = 'Password baru minimal 8 karakter.';
                } else {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$fullName, $hash, $userId]);
                    log_activity($userId, 'update_profile', "Memperbarui nama profil dan kata sandi akun.");
                    $flashSuccess = 'Profil dan kata sandi berhasil diperbarui.';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$fullName, $userId]);
                log_activity($userId, 'update_profile', "Memperbarui nama profil menjadi: {$fullName}.");
                $flashSuccess = 'Nama profil berhasil diperbarui.';
            }
            $user = current_user(); // reload
        }
    }

    // 1B. Invite User Terdaftar ke Organisasi Akun Ini
    elseif ($action === 'invite_user') {
        $collaboratorId = (int)($_POST['collaborator_id'] ?? 0);
        $role = in_array($_POST['role'] ?? '', ['editor', 'viewer']) ? $_POST['role'] : 'editor';

        if ($collaboratorId <= 0 || $collaboratorId === $userId) {
            $flashError = 'Pilih pengguna terdaftar yang valid untuk diundang.';
        } else {
            // Cek target user
            $targetStmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
            $targetStmt->execute([$collaboratorId]);
            $targetUser = $targetStmt->fetch();

            if (!$targetUser) {
                $flashError = 'Pengguna tidak ditemukan di database Labschool.';
            } else {
                // Cek apakah sudah diundang sebelumnya
                $checkStmt = $pdo->prepare("SELECT id FROM organization_collaborators WHERE owner_id = ? AND collaborator_id = ?");
                $checkStmt->execute([$userId, $collaboratorId]);
                if ($checkStmt->fetch()) {
                    $flashError = "Pengguna {$targetUser['full_name']} ({$targetUser['email']}) sudah terdaftar dalam organisasi Anda.";
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO organization_collaborators (owner_id, collaborator_id, role, status, invited_at)
                        VALUES (?, ?, ?, 'active', NOW())
                    ");
                    $ins->execute([$userId, $collaboratorId, $role]);

                    // Assign group langsung jika dipilih
                    $initialGroup = (int)($_POST['initial_group_id'] ?? 0);
                    if ($initialGroup > 0) {
                        $grpCheck = $pdo->prepare("SELECT id FROM user_groups WHERE id = ? AND owner_id = ?");
                        $grpCheck->execute([$initialGroup, $userId]);
                        if ($grpCheck->fetch()) {
                            $addMember = $pdo->prepare("INSERT IGNORE INTO user_group_members (group_id, user_id, added_at) VALUES (?, ?, NOW())");
                            $addMember->execute([$initialGroup, $collaboratorId]);
                        }
                    }

                    log_activity($userId, 'invite_collaborator', "Mengundang {$targetUser['full_name']} ({$targetUser['email']}) ke organisasi dengan peran " . strtoupper($role));
                    $flashSuccess = "Pengguna {$targetUser['full_name']} ({$targetUser['email']}) berhasil bergabung ke organisasi Anda. Pastikan di-assign ke dalam Group agar dapat mengelola link/pages.";
                }
            }
        }
    }

    // 1C. Hapus Kolaborator dari Organisasi
    elseif ($action === 'remove_collaborator') {
        $collabId = (int)($_POST['collaborator_id'] ?? 0);
        if ($collabId > 0) {
            // Hapus dari group members milik owner
            $delGrp = $pdo->prepare("
                DELETE FROM user_group_members 
                WHERE user_id = ? AND group_id IN (SELECT id FROM user_groups WHERE owner_id = ?)
            ");
            $delGrp->execute([$collabId, $userId]);

            // Hapus dari organization_collaborators
            $delCollab = $pdo->prepare("DELETE FROM organization_collaborators WHERE owner_id = ? AND collaborator_id = ?");
            $delCollab->execute([$userId, $collabId]);

            log_activity($userId, 'remove_collaborator', "Menghapus akses kolaborator user ID #{$collabId} dari organisasi.");
            $flashSuccess = 'Kolaborator berhasil dihapus dari organisasi Anda.';
        }
    }

    // 1D. Buat Group Baru (Maksimal 10 Group per User)
    elseif ($action === 'create_group') {
        // Cek kuota group user
        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM user_groups WHERE owner_id = ?");
        $cntStmt->execute([$userId]);
        $currentGroupCount = (int)$cntStmt->fetchColumn();

        if ($currentGroupCount >= 10) {
            $flashError = 'Batas maksimal 10 group per akun telah tercapai. Hapus grup yang tidak terpakai untuk membuat baru.';
        } else {
            $grpName = trim($_POST['group_name'] ?? '');
            $grpDesc = trim($_POST['group_description'] ?? '');
            $grpTags = trim($_POST['group_tags'] ?? '');
            $memberIds = $_POST['member_ids'] ?? [];

            if (empty($grpName)) {
                $flashError = 'Nama Group wajib diisi.';
            } else {
                $insGrp = $pdo->prepare("
                    INSERT INTO user_groups (owner_id, name, description, tags, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insGrp->execute([$userId, $grpName, $grpDesc, $grpTags]);
                $newGroupId = (int)$pdo->lastInsertId();

                // Masukkan anggota yang dicentang
                if (!empty($memberIds) && is_array($memberIds)) {
                    $insMbr = $pdo->prepare("INSERT IGNORE INTO user_group_members (group_id, user_id, added_at) VALUES (?, ?, NOW())");
                    foreach ($memberIds as $mId) {
                        $mId = (int)$mId;
                        if ($mId > 0) {
                            $insMbr->execute([$newGroupId, $mId]);
                        }
                    }
                }

                log_activity($userId, 'create_group', "Membuat grup kerja '{$grpName}' dengan scope tag: {$grpTags}");
                $flashSuccess = "Grup '{$grpName}' berhasil dibuat (" . ($currentGroupCount + 1) . "/10).";
            }
        }
    }

    // 1E. Hapus Group
    elseif ($action === 'delete_group') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        if ($groupId > 0) {
            $delG = $pdo->prepare("DELETE FROM user_groups WHERE id = ? AND owner_id = ?");
            $delG->execute([$groupId, $userId]);
            log_activity($userId, 'delete_group', "Menghapus grup kerja ID #{$groupId}.");
            $flashSuccess = 'Grup berhasil dihapus.';
        }
    }

    // 1F. Update Anggota Group
    elseif ($action === 'update_group_members') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        $memberIds = $_POST['member_ids'] ?? [];

        // Validasi kepemilikan group
        $checkG = $pdo->prepare("SELECT id, name FROM user_groups WHERE id = ? AND owner_id = ?");
        $checkG->execute([$groupId, $userId]);
        $grpData = $checkG->fetch();

        if ($grpData) {
            // Hapus anggota lama
            $delOld = $pdo->prepare("DELETE FROM user_group_members WHERE group_id = ?");
            $delOld->execute([$groupId]);

            // Masukkan anggota baru
            if (!empty($memberIds) && is_array($memberIds)) {
                $insMbr = $pdo->prepare("INSERT IGNORE INTO user_group_members (group_id, user_id, added_at) VALUES (?, ?, NOW())");
                foreach ($memberIds as $mId) {
                    $mId = (int)$mId;
                    if ($mId > 0) {
                        $insMbr->execute([$groupId, $mId]);
                    }
                }
            }

            log_activity($userId, 'update_group_members', "Memperbarui daftar anggota pada grup '{$grpData['name']}'.");
            $flashSuccess = "Anggota grup '{$grpData['name']}' berhasil diperbarui.";
        }
    }

    // 1G. Pemendekan Tautan Massal (Bulk Shorten) langsung di Tab Bulk Upload
    elseif ($action === 'bulk_shorten_submit') {
        $rawText = trim($_POST['bulk_urls'] ?? '');
        $globalTags = trim($_POST['bulk_tags'] ?? '');
        $lines = array_filter(array_map('trim', explode("\n", $rawText)));

        if (empty($lines)) {
            $flashError = 'Masukkan minimal satu URL yang valid.';
        } elseif (count($lines) > 50) {
            $flashError = 'Maksimal 50 tautan per proses upload massal.';
        } else {
            $successCount = 0;
            $failCount = 0;
            $createdSlugs = [];

            foreach ($lines as $lineUrl) {
                if (!filter_var($lineUrl, FILTER_VALIDATE_URL)) {
                    $bulkResults[] = ['url' => $lineUrl, 'status' => 'error', 'message' => 'URL tidak valid'];
                    $failCount++;
                    continue;
                }

                $pageTitle = fetch_url_title($lineUrl);
                $shortCode = generate_unique_slug(6);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO short_urls (user_id, original_url, title, short_code, custom_slug, tags, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $lineUrl, $pageTitle, $shortCode, $shortCode, $globalTags]);
                    $shortUrl = base_url($shortCode);
                    $bulkResults[] = [
                        'url' => $lineUrl,
                        'short_url' => $shortUrl,
                        'slug' => $shortCode,
                        'title' => $pageTitle,
                        'status' => 'success'
                    ];
                    $createdSlugs[] = $shortUrl;
                    $successCount++;
                } catch (Exception $e) {
                    $bulkResults[] = ['url' => $lineUrl, 'status' => 'error', 'message' => $e->getMessage()];
                    $failCount++;
                }
            }

            if ($successCount > 0) {
                $batchName = 'Batch ' . date('d M Y H:i:s');
                $sampleText = implode(', ', array_slice($createdSlugs, 0, 5));
                $bStmt = $pdo->prepare("
                    INSERT INTO bulk_upload_logs (user_id, batch_name, total_links, tags, sample_links, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $bStmt->execute([$userId, $batchName, $successCount, $globalTags, $sampleText]);

                log_activity($userId, 'bulk_upload', "Membuat {$successCount} short link melalui Bulk Upload Settings (Tag: " . ($globalTags ?: 'None') . ")");
                $flashSuccess = "Berhasil memendekkan {$successCount} tautan secara massal" . ($failCount > 0 ? " ({$failCount} gagal)." : ".");
            }
        }
    }
}

// ==========================================
// 2. FETCH DATA UNTUK SETIAP TAB
// ==========================================

// 2A. Data Organisasi & Kuota
$orgName = 'SMP Labschool Jakarta';
$orgId = 'labs_3v10apvpbq';
$planName = 'Enterprise Education Plan';

// 2B. Data Groups Pengguna Ini (Maksimal 10)
$groupsStmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM user_group_members WHERE group_id = g.id) as member_count
    FROM user_groups g
    WHERE g.owner_id = ?
    ORDER BY g.created_at DESC
");
$groupsStmt->execute([$userId]);
$myGroups = $groupsStmt->fetchAll();
$totalMyGroups = count($myGroups);

// 2C. Data Kolaborator di Organisasi Pengguna Ini
$collabStmt = $pdo->prepare("
    SELECT c.id as collab_record_id, c.role as org_role, c.status as org_status, c.invited_at,
           u.id as user_id, u.email, u.full_name, u.role as system_role,
           (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
            FROM user_group_members ugm
            JOIN user_groups g ON ugm.group_id = g.id
            WHERE ugm.user_id = u.id AND g.owner_id = ?
           ) as assigned_groups
    FROM organization_collaborators c
    JOIN users u ON c.collaborator_id = u.id
    WHERE c.owner_id = ?
    ORDER BY c.invited_at DESC
");
$collabStmt->execute([$userId, $userId]);
$myCollaborators = $collabStmt->fetchAll();

// 2D. Daftar Seluruh Pengguna Labschool Terdaftar (yang belum diundang) untuk dropdown Invite
$availUsersStmt = $pdo->prepare("
    SELECT id, email, full_name, role 
    FROM users 
    WHERE id != ? 
      AND id NOT IN (SELECT collaborator_id FROM organization_collaborators WHERE owner_id = ?)
    ORDER BY full_name ASC
");
$availUsersStmt->execute([$userId, $userId]);
$availableUsersToInvite = $availUsersStmt->fetchAll();

// 2E. Riwayat Bulk Upload Pengguna Ini
$bulkLogsStmt = $pdo->prepare("SELECT * FROM bulk_upload_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$bulkLogsStmt->execute([$userId]);
$myBulkLogs = $bulkLogsStmt->fetchAll();

// 2F. Riwayat Log Aktivitas Pengguna Ini
$activityStmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$activityStmt->execute([$userId]);
$myActivities = $activityStmt->fetchAll();

// 2G. API Key
$apiKeyStmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
$apiKeyStmt->execute([$userId]);
$apiKey = $apiKeyStmt->fetchColumn();

if (empty($apiKey)) {
    $apiKey = 'labs_' . bin2hex(random_bytes(24));
    $upStmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $upStmt->execute([$apiKey, $userId]);
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

            <!-- Kembali ke Dashboard -->
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
                    <span>QR Codes</span>
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
                    <span>Tag & Kampanye</span>
                </a>
                <a href="/settings.php?tab=integrations" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl <?= $tab === 'integrations' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?> transition">
                    <span class="material-symbols-rounded text-xl">widgets</span>
                    <span>Integrasi</span>
                </a>
                <a href="/settings.php?tab=account-details" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl <?= in_array($tab, ['account-details', 'users', 'groups', 'bulk-upload', 'activity-log', 'profile', 'api']) ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?> transition">
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
            
            <!-- Left Sub-Navigation for Settings -->
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
                        
                        <!-- Users (Organization Workspace Collaborators) -->
                        <li>
                            <a href="/settings.php?tab=users" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'users' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Users</span>
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px]"><?= count($myCollaborators) + 1 ?></span>
                            </a>
                        </li>

                        <!-- Groups (Tag-based Scope Permissions - Max 10) -->
                        <li>
                            <a href="/settings.php?tab=groups" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'groups' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Groups</span>
                                <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-[10px]"><?= $totalMyGroups ?>/10</span>
                            </a>
                        </li>

                        <!-- Bulk Upload (Pemendek Massal & Riwayat) -->
                        <li>
                            <a href="/settings.php?tab=bulk-upload" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'bulk-upload' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Bulk upload</span>
                            </a>
                        </li>

                        <!-- Activity Log (Riwayat Aktivitas Akun) -->
                        <li>
                            <a href="/settings.php?tab=activity-log" class="flex items-center justify-between px-3 py-2 rounded-xl transition <?= $tab === 'activity-log' ? 'bg-blue-50 text-blue-700 font-extrabold' : 'text-slate-600 hover:bg-slate-50' ?>">
                                <span>Activity log</span>
                            </a>
                        </li>

                        <!-- DISABLED TABS: Billing, SSO, Mobile Deep Links, Webhooks -->
                        <li class="pt-2 border-t border-slate-100 text-slate-400">
                            <span class="block px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-300">Disabled Features</span>
                        </li>
                        <li>
                            <a href="/settings.php?tab=billing" class="flex items-center justify-between px-3 py-2 rounded-xl transition text-slate-400 hover:bg-slate-50">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-sm">lock</span>
                                    <span>Billing & usage</span>
                                </span>
                                <span class="text-[9px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded font-mono">Disabled</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=sso" class="flex items-center justify-between px-3 py-2 rounded-xl transition text-slate-400 hover:bg-slate-50">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-sm">lock</span>
                                    <span>Single sign-on</span>
                                </span>
                                <span class="text-[9px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded font-mono">Disabled</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=mobile-deep-links" class="flex items-center justify-between px-3 py-2 rounded-xl transition text-slate-400 hover:bg-slate-50">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-sm">lock</span>
                                    <span>Mobile deep links</span>
                                </span>
                                <span class="text-[9px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded font-mono">Disabled</span>
                            </a>
                        </li>
                        <li>
                            <a href="/settings.php?tab=webhooks" class="flex items-center justify-between px-3 py-2 rounded-xl transition text-slate-400 hover:bg-slate-50">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-sm">lock</span>
                                    <span>Webhooks</span>
                                </span>
                                <span class="text-[9px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded font-mono">Disabled</span>
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

                <!-- ========================================================
                     TAB: USERS (Workspace Organization Collaborators)
                     ======================================================== -->
                <?php if ($tab === 'users'): ?>
                    <div class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Organization Users</h1>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">Workspace Kolaborasi</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-500 mt-1">
                                    Undang pengguna terdaftar di Labschool untuk bergabung ke organisasi Anda dan berkolaborasi mengelola tautan & halaman.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="document.getElementById('modalInviteUser').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                    <span class="material-symbols-rounded text-base">person_add</span>
                                    <span>Invite User ke Organisasi</span>
                                </button>
                            </div>
                        </div>

                        <!-- Info Banner Penting Mengenai Scope Group -->
                        <div class="bg-blue-50/90 border border-blue-200/90 rounded-2xl p-4 sm:p-5 flex items-start gap-4 text-xs text-blue-950">
                            <span class="material-symbols-rounded text-blue-600 text-2xl shrink-0 mt-0.5">info</span>
                            <div class="space-y-1">
                                <h4 class="font-extrabold text-sm">Aturan Akses & Scope Grup Kolaborasi:</h4>
                                <p class="leading-relaxed text-blue-900">
                                    Pengguna yang Anda undang <strong>belum dapat melihat maupun mengelola tautan atau halaman</strong> milik Anda sebelum akun mereka dimasukkan ke dalam minimal satu Group di menu <a href="/settings.php?tab=groups" class="font-extrabold underline text-blue-700">Groups</a>. Setiap grup membatasi akses kolaborator berdasarkan <em>Tags</em> yang telah ditentukan.
                                </p>
                            </div>
                        </div>

                        <!-- Users / Collaborators Table -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-600 divide-y divide-slate-100">
                                    <thead class="bg-slate-50/80 font-bold text-slate-500 text-[11px] uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Pengguna</th>
                                            <th class="py-3 px-4">Email</th>
                                            <th class="py-3 px-4">Peran Organisasi</th>
                                            <th class="py-3 px-4">Grup & Scope Tag</th>
                                            <th class="py-3 px-4">Status</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium">
                                        <!-- Row 1: Pemilik Akun (Owner) -->
                                        <tr class="bg-blue-50/30">
                                            <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div><?= htmlspecialchars($user['full_name']) ?></div>
                                                    <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest">(Organisasi Anda)</span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-700 font-mono text-[11px]">
                                                <?= htmlspecialchars($user['email']) ?>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800">
                                                    Organization Owner
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                                                    <span class="material-symbols-rounded text-xs">all_inclusive</span>
                                                    <span>Seluruh Scope (Full Access)</span>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                                    Active
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right text-slate-400 text-xs italic">
                                                Pemilik
                                            </td>
                                        </tr>

                                        <!-- Rows: Invited Collaborators -->
                                        <?php if (empty($myCollaborators)): ?>
                                            <tr>
                                                <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                                    Belum ada kolaborator yang diundang. Klik tombol <strong>Invite User ke Organisasi</strong> untuk mulai berkolaborasi.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($myCollaborators as $collab): ?>
                                                <tr class="hover:bg-slate-50/60 transition">
                                                    <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-slate-700 text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                                            <?= strtoupper(substr($collab['full_name'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <div><?= htmlspecialchars($collab['full_name']) ?></div>
                                                            <div class="text-[10px] text-slate-400"><?= ucfirst($collab['system_role']) ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-slate-700 font-mono text-[11px]">
                                                        <?= htmlspecialchars($collab['email']) ?>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-800 uppercase tracking-wide">
                                                            <?= htmlspecialchars($collab['org_role']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <?php if (!empty($collab['assigned_groups'])): ?>
                                                            <div class="flex items-center gap-1 flex-wrap">
                                                                <?php foreach (explode(',', $collab['assigned_groups']) as $grpName): ?>
                                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                                        <?= htmlspecialchars(trim($grpName)) ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full" title="Belum dapat mengelola link/pages sebelum dimasukkan group">
                                                                <span class="material-symbols-rounded text-xs">warning</span>
                                                                <span>Belum Diassign Grup</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                                            <?= ucfirst($collab['org_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-right">
                                                        <form method="POST" onsubmit="return confirm('Hapus kolaborator ini dari organisasi Anda?');" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="action" value="remove_collaborator">
                                                            <input type="hidden" name="collaborator_id" value="<?= $collab['user_id'] ?>">
                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs p-1">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Invite User -->
                    <div id="modalInviteUser" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">Invite User ke Organisasi</h3>
                                    <p class="text-xs text-slate-400">Pilih dari pengguna terdaftar di Labschool</p>
                                </div>
                                <button type="button" onclick="document.getElementById('modalInviteUser').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                            </div>

                            <form method="POST" action="/settings.php?tab=users" class="space-y-4 text-xs">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="invite_user">

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Pilih Pengguna Labschool Terdaftar *</label>
                                    <?php if (empty($availableUsersToInvite)): ?>
                                        <div class="p-3 rounded-xl bg-slate-100 text-slate-500 text-xs">
                                            Seluruh pengguna terdaftar di Labschool sudah bergabung di organisasi Anda.
                                        </div>
                                    <?php else: ?>
                                        <select name="collaborator_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-medium outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">-- Pilih Pengguna Terdaftar --</option>
                                            <?php foreach ($availableUsersToInvite as $au): ?>
                                                <option value="<?= $au['id'] ?>">
                                                    <?= htmlspecialchars($au['full_name']) ?> (<?= htmlspecialchars($au['email']) ?>) - <?= ucfirst($au['role']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Peran dalam Organisasi</label>
                                        <select name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-medium outline-none">
                                            <option value="editor">Editor (Dapat Mengelola)</option>
                                            <option value="viewer">Viewer (Hanya Melihat)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-700 mb-1">Assign Langsung ke Grup (Opsional)</label>
                                        <select name="initial_group_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-medium outline-none">
                                            <option value="">-- Belum Diassign --</option>
                                            <?php foreach ($myGroups as $mg): ?>
                                                <option value="<?= $mg['id'] ?>"><?= htmlspecialchars($mg['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                                    <strong>Penting:</strong> User yang diundang baru dapat melihat atau mengelola links/pages setelah dimasukkan ke minimal satu grup pada tab Groups.
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('modalInviteUser').classList.add('hidden')" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold shadow-md shadow-blue-500/20">
                                        Tambahkan ke Organisasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: GROUPS (Scope Permissions - Max 10 Groups)
                     ======================================================== -->
                <?php elseif ($tab === 'groups'): ?>
                    <div class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Groups & Scope Permissions</h1>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        <?= $totalMyGroups ?> dari Maksimal 10 Groups
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-slate-500 mt-1">
                                    Batasi akses kolaborator terhadap link & halaman berdasarkan Tags. User hanya dapat mengelola konten dengan tag yang sesuai grupnya.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($totalMyGroups < 10): ?>
                                    <button type="button" onclick="document.getElementById('modalCreateGroup').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs shadow-md shadow-purple-500/20 transition flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-base">add</span>
                                        <span>Buat Group Baru</span>
                                    </button>
                                <?php else: ?>
                                    <button type="button" disabled class="px-5 py-2.5 rounded-xl bg-slate-300 text-slate-500 font-extrabold text-xs cursor-not-allowed flex items-center gap-1.5" title="Maksimal 10 groups">
                                        <span class="material-symbols-rounded text-base">block</span>
                                        <span>Batas Maksimal 10 Groups</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Grid Daftar Group -->
                        <?php if (empty($myGroups)): ?>
                            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm space-y-3">
                                <div class="w-16 h-16 rounded-3xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto">
                                    <span class="material-symbols-rounded text-3xl">groups</span>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-800">Belum Ada Grup Dibuat</h3>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">Buat grup kerja pertama Anda untuk membatasi akses kolaborator berdasarkan tag link dan halaman.</p>
                                <button type="button" onclick="document.getElementById('modalCreateGroup').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-purple-600 text-white font-extrabold text-xs shadow-md">
                                    + Buat Group Pertama
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($myGroups as $grp): ?>
                                    <?php
                                        // Ambil anggota group ini
                                        $gmStmt = $pdo->prepare("
                                            SELECT u.id, u.full_name, u.email 
                                            FROM user_group_members gm
                                            JOIN users u ON gm.user_id = u.id
                                            WHERE gm.group_id = ?
                                        ");
                                        $gmStmt->execute([$grp['id']]);
                                        $grpMembers = $gmStmt->fetchAll();
                                        $tagsArr = array_filter(array_map('trim', explode(',', $grp['tags'] ?? '')));
                                    ?>
                                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-5 space-y-4 flex flex-col justify-between hover:border-purple-300 transition">
                                        <div class="space-y-3">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h3 class="text-base font-extrabold text-slate-900"><?= htmlspecialchars($grp['name']) ?></h3>
                                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($grp['description'] ?: 'Tidak ada deskripsi.') ?></p>
                                                </div>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-50 text-purple-700">
                                                    <?= count($grpMembers) ?> Anggota
                                                </span>
                                            </div>

                                            <!-- Scope Tags -->
                                            <div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Scope Tags Terhubung:</div>
                                                <?php if (!empty($tagsArr)): ?>
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <?php foreach ($tagsArr as $tag): ?>
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                                                #<?= htmlspecialchars($tag) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400 italic">Semua tag diizinkan untuk grup ini</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Anggota Terdaftar -->
                                            <div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Anggota Kolaborator:</div>
                                                <?php if (empty($grpMembers)): ?>
                                                    <span class="text-[11px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded font-bold">Belum ada anggota yang di-assign</span>
                                                <?php else: ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($grpMembers as $gm): ?>
                                                            <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                                                <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[9px] flex items-center justify-center font-black">
                                                                    <?= strtoupper(substr($gm['full_name'], 0, 2)) ?>
                                                                </span>
                                                                <span><?= htmlspecialchars($gm['full_name']) ?></span>
                                                                <span class="text-[10px] text-slate-400 font-mono">(<?= htmlspecialchars($gm['email']) ?>)</span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Aksi Group -->
                                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                            <span class="text-[10px] text-slate-400 font-mono">Dibuat <?= date('d M Y', strtotime($grp['created_at'])) ?></span>
                                            <div class="flex items-center gap-2">
                                                <form method="POST" onsubmit="return confirm('Hapus group ini?');" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete_group">
                                                    <input type="hidden" name="group_id" value="<?= $grp['id'] ?>">
                                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold p-1">Hapus</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Modal Buat Group Baru -->
                    <div id="modalCreateGroup" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">Buat Group Kerja Baru</h3>
                                    <p class="text-xs text-slate-400">Grup <?= $totalMyGroups + 1 ?> dari maksimal 10 groups</p>
                                </div>
                                <button type="button" onclick="document.getElementById('modalCreateGroup').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                            </div>

                            <form method="POST" action="/settings.php?tab=groups" class="space-y-4 text-xs">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="create_group">

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nama Group *</label>
                                    <input type="text" name="group_name" required placeholder="Contoh: Panitia PPDB 2027, Tim Matematika"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-bold outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Deskripsi Group</label>
                                    <input type="text" name="group_description" placeholder="Deskripsi singkat tugas grup ini..."
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-medium outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Scope Tags (Pisahkan Koma) *</label>
                                    <input type="text" name="group_tags" placeholder="Contoh: PPDB, Ujian, MTK"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-medium outline-none">
                                    <p class="text-[10px] text-slate-400 mt-1">Anggota grup ini hanya dapat mengelola tautan atau halaman yang memiliki tag di atas.</p>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Pilih Anggota Kolaborator yang Di-assign:</label>
                                    <?php if (empty($myCollaborators)): ?>
                                        <p class="text-slate-400 text-[11px] italic">Belum ada kolaborator di organisasi Anda. Undang user terlebih dahulu di tab Users.</p>
                                    <?php else: ?>
                                        <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 bg-slate-50 rounded-xl border border-slate-200">
                                            <?php foreach ($myCollaborators as $c): ?>
                                                <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white transition cursor-pointer">
                                                    <input type="checkbox" name="member_ids[]" value="<?= $c['user_id'] ?>" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($c['full_name']) ?></span>
                                                    <span class="text-slate-400 font-mono text-[10px]">(<?= htmlspecialchars($c['email']) ?>)</span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('modalCreateGroup').classList.add('hidden')" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-extrabold shadow-md shadow-purple-500/20">
                                        Buat Grup
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: BULK UPLOAD (Pemendek Tautan Massal & Riwayat)
                     ======================================================== -->
                <?php elseif ($tab === 'bulk-upload'): ?>
                    <div class="space-y-8">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Bulk Upload (Pemendek Massal)</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Perpendek puluhan tautan sekaligus dalam satu langkah dan pantau riwayat proses batch sebelumnya.</p>
                        </div>

                        <!-- Form Bulk Shorten -->
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                            <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span class="material-symbols-rounded text-blue-600">auto_awesome</span>
                                <span>Input Tautan Massal Baru</span>
                            </h2>

                            <form method="POST" action="/settings.php?tab=bulk-upload" class="space-y-4 text-xs">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="bulk_shorten_submit">

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tempel Daftar URL Panjang (Satu URL per baris, maks 50 tautan) *</label>
                                    <textarea name="bulk_urls" rows="6" required placeholder="https://link-pertama.com&#10;https://link-kedua.com&#10;https://link-ketiga.com"
                                        class="w-full p-3.5 rounded-xl border border-slate-300 font-mono text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"></textarea>
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Tag / Label Bersama (Opsional)</label>
                                    <input type="text" name="bulk_tags" placeholder="Contoh: CBT2026, Ujian, SMP"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                                </div>

                                <div>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-base">publish</span>
                                        <span>Proses Pemendekan Massal</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Hasil Realtime Jika Baru Submit -->
                        <?php if (!empty($bulkResults)): ?>
                            <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6 space-y-3">
                                <h3 class="text-sm font-extrabold text-emerald-900 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-emerald-600">check_circle</span>
                                    <span>Hasil Pemendekan Massal Terkini</span>
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs divide-y divide-slate-100 font-medium">
                                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                                            <tr>
                                                <th class="py-2.5 px-3">Judul / URL Asli</th>
                                                <th class="py-2.5 px-3">Short URL Hasil</th>
                                                <th class="py-2.5 px-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php foreach ($bulkResults as $res): ?>
                                                <tr>
                                                    <td class="py-2.5 px-3 max-w-xs truncate"><?= htmlspecialchars($res['title'] ?? $res['url']) ?></td>
                                                    <td class="py-2.5 px-3 font-mono font-bold text-blue-600">
                                                        <?php if (!empty($res['short_url'])): ?>
                                                            <a href="<?= $res['short_url'] ?>" target="_blank" class="hover:underline"><?= $res['short_url'] ?></a>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-2.5 px-3">
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $res['status'] === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                                            <?= ucfirst($res['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tabel Riwayat / Logs Bulk Upload -->
                        <div class="space-y-3">
                            <h2 class="text-base font-extrabold text-slate-900">Riwayat Batch Upload Sebelumnya</h2>
                            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                                <?php if (empty($myBulkLogs)): ?>
                                    <div class="p-8 text-center text-xs text-slate-400">
                                        Belum ada riwayat bulk upload. Tautan massal yang Anda proses akan dicatat di sini.
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs divide-y divide-slate-100">
                                            <thead class="bg-slate-50/80 font-bold text-slate-500 text-[11px] uppercase tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-4">Nama Batch</th>
                                                    <th class="py-3 px-4">Waktu Eksekusi</th>
                                                    <th class="py-3 px-4">Jumlah Tautan</th>
                                                    <th class="py-3 px-4">Tag Bersama</th>
                                                    <th class="py-3 px-4">Contoh Tautan</th>
                                                    <th class="py-3 px-4">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 font-medium">
                                                <?php foreach ($myBulkLogs as $bLog): ?>
                                                    <tr class="hover:bg-slate-50/60 transition">
                                                        <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center gap-2">
                                                            <span class="material-symbols-rounded text-blue-600 text-base">cloud_upload</span>
                                                            <span><?= htmlspecialchars($bLog['batch_name']) ?></span>
                                                        </td>
                                                        <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                                            <?= date('d M Y H:i', strtotime($bLog['created_at'])) ?> WIB
                                                        </td>
                                                        <td class="py-3.5 px-4 font-bold text-slate-800">
                                                            <?= number_format($bLog['total_links']) ?> Link
                                                        </td>
                                                        <td class="py-3.5 px-4">
                                                            <?php if (!empty($bLog['tags'])): ?>
                                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700">
                                                                    #<?= htmlspecialchars($bLog['tags']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-slate-400">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="py-3.5 px-4 text-slate-500 text-[11px] truncate max-w-xs font-mono">
                                                            <?= htmlspecialchars($bLog['sample_links'] ?: '-') ?>
                                                        </td>
                                                        <td class="py-3.5 px-4">
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                                                <span class="material-symbols-rounded text-xs">check</span>
                                                                <span>Sukses</span>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: ACTIVITY LOG (Riwayat Aktivitas Pengguna Ini)
                     ======================================================== -->
                <?php elseif ($tab === 'activity-log'): ?>
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Activity Log</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Audit jejak aktivitas akun Anda, mulai dari pembuatan tautan, grup, hingga kolaborasi.</p>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <?php if (empty($myActivities)): ?>
                                <div class="p-8 text-center text-xs text-slate-400">
                                    Belum ada log aktivitas yang tercatat untuk akun Anda.
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs divide-y divide-slate-100">
                                        <thead class="bg-slate-50/80 font-bold text-slate-500 text-[11px] uppercase tracking-wider">
                                            <tr>
                                                <th class="py-3 px-4">Waktu (WIB)</th>
                                                <th class="py-3 px-4">Tipe Aksi</th>
                                                <th class="py-3 px-4">Deskripsi Aktivitas</th>
                                                <th class="py-3 px-4">IP Address</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            <?php foreach ($myActivities as $act): ?>
                                                <tr class="hover:bg-slate-50/60 transition">
                                                    <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                                        <?= date('d M Y H:i:s', strtotime($act['created_at'])) ?>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 uppercase tracking-wide">
                                                            <?= htmlspecialchars(str_replace('_', ' ', $act['action'])) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-slate-800">
                                                        <?= htmlspecialchars($act['description']) ?>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                                        <?= htmlspecialchars($act['ip_address'] ?: '-') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: DISABLED TABS (SSO, Mobile Deep Links, Webhooks, Billing)
                     ======================================================== -->
                <?php elseif (in_array($tab, $disabledTabs)): ?>
                    <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center space-y-4 max-w-lg mx-auto my-8">
                        <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto">
                            <span class="material-symbols-rounded text-3xl">lock</span>
                        </div>
                        <h2 class="text-lg font-black text-slate-900 capitalize">Fitur <?= htmlspecialchars(str_replace('-', ' ', $tab)) ?> Dinonaktifkan</h2>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Fitur ini sedang dalam status <strong>Disabled / Segera Hadir</strong> untuk pembaruan arsitektur enterprise Labschool selanjutnya.
                        </p>
                        <div class="pt-2">
                            <a href="/settings.php?tab=users" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-extrabold text-xs shadow-md">
                                Kembali ke Manajemen Users
                            </a>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: ACCOUNT DETAILS
                     ======================================================== -->
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

                <!-- ========================================================
                     TAB: PROFILE
                     ======================================================== -->
                <?php elseif ($tab === 'profile'): ?>
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Personal Profile</h1>
                            <p class="text-xs font-semibold text-slate-500 mt-1">Perbarui nama pengguna, kredensial masuk, dan foto profil Anda.</p>
                        </div>

                        <form method="POST" action="/settings.php?tab=profile" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6 max-w-xl">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

                <!-- ========================================================
                     TAB: INTEGRATIONS
                     ======================================================== -->
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
                            </div>
                        </div>
                    </div>

                <!-- ========================================================
                     TAB: API (Access Tokens)
                     ======================================================== -->
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
    </script>
</body>
</html>
