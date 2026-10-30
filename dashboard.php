<?php
/**
 * User Dashboard: s.labschool.xyz
 * Enterprise Layout & Full Feature Platform
 * (Left Sidebar, Create Dropdown, Sliding Detail Drawer, Smart QR Frames, Deep Linking, Tagging & Bulk Shorten)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/templates.php';

require_login();
$user = current_user();

// 1. Ambil daftar Short URLs milik user
$urlStmt = $pdo->prepare("SELECT s.*, 'Pribadi' AS workspace_scope, NULL AS owner_name FROM short_urls s WHERE s.user_id = ? ORDER BY s.created_at DESC");
$urlStmt->execute([$user['id']]);
$urls = $urlStmt->fetchAll();

// 1b. Ambil Short URLs dari organisasi kolaborasi di mana user di-assign ke group tertentu
$collabGroupsStmt = $pdo->prepare("
    SELECT g.id AS group_id, g.owner_id, g.name AS group_name, g.tags AS group_tags, u.full_name AS owner_name
    FROM user_group_members ugm
    JOIN user_groups g ON ugm.group_id = g.id
    JOIN organization_collaborators oc ON oc.owner_id = g.owner_id AND oc.collaborator_id = ?
    JOIN users u ON g.owner_id = u.id
    WHERE oc.status = 'active'
");
$collabGroupsStmt->execute([$user['id']]);
$collabGroups = $collabGroupsStmt->fetchAll();

if (!empty($collabGroups)) {
    $ownerTagsMap = [];
    foreach ($collabGroups as $cg) {
        $oid = $cg['owner_id'];
        if (!isset($ownerTagsMap[$oid])) {
            $ownerTagsMap[$oid] = [
                'owner_name' => $cg['owner_name'],
                'all_tags'   => []
            ];
        }
        $rawTags = array_map('trim', explode(',', strtolower($cg['group_tags'] ?? '')));
        foreach ($rawTags as $rt) {
            if ($rt !== '' && !in_array($rt, $ownerTagsMap[$oid]['all_tags'], true)) {
                $ownerTagsMap[$oid]['all_tags'][] = $rt;
            }
        }
    }

    foreach ($ownerTagsMap as $oid => $info) {
        if (empty($info['all_tags'])) continue;
        $ownerUrlsStmt = $pdo->prepare("SELECT * FROM short_urls WHERE user_id = ? ORDER BY created_at DESC");
        $ownerUrlsStmt->execute([$oid]);
        $ownerUrls = $ownerUrlsStmt->fetchAll();
        foreach ($ownerUrls as $ou) {
            $linkTags = array_map('trim', explode(',', strtolower($ou['tags'] ?? '')));
            $intersect = array_intersect($linkTags, $info['all_tags']);
            if (!empty($intersect)) {
                $ou['workspace_scope'] = 'Organisasi: ' . $info['owner_name'];
                $ou['owner_name'] = $info['owner_name'];
                $ou['is_collaborated'] = true;
                $urls[] = $ou;
            }
        }
    }
}

// 2. Ambil daftar Microsites milik user
$bioStmt = $pdo->prepare("SELECT * FROM microsites WHERE user_id = ? ORDER BY created_at DESC");
$bioStmt->execute([$user['id']]);
$microsites = $bioStmt->fetchAll();

// 3. Ringkasan Statistik Global
$totalClicks = array_sum(array_column($urls, 'clicks_count'));
$totalLinks  = count($urls);
$totalBios   = count($microsites);

// Kumpulkan semua tags unik
$allTags = [];
foreach ($urls as $u) {
    if (!empty($u['tags'])) {
        $tagParts = explode(',', $u['tags']);
        foreach ($tagParts as $tp) {
            $t = trim($tp);
            if (!empty($t) && !in_array($t, $allTags, true)) {
                $allTags[] = $t;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0..1,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 
                        sans: ['"Nunito Sans"', 'sans-serif'],
                        symbols: ['"Material Symbols Rounded"', 'sans-serif']
                    },
                    colors: {
                        labs: { 
                            blue: '#2563EB', 
                            dark: '#1E40AF', 
                            navy: '#0F172A',
                            amber: '#F59E0B' 
                        }
                    }
                }
            }
        };
    </script>
    <style>
        .material-symbols-rounded {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .material-symbols-rounded.fill {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        /* Dynamic Left Sidebar Drawer & Mini-Rail Styles */
        #leftSidebar {
            transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
        }

        /* Mobile drawer behavior (< 768px) */
        @media (max-width: 767px) {
            #leftSidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                width: 18rem; /* 288px */
                transform: translateX(-100%);
                z-index: 50;
            }
            #leftSidebar.drawer-open {
                transform: translateX(0);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            }
        }

        /* Desktop collapsible mini-rail behavior (>= 768px) */
        @media (min-width: 768px) {
            #leftSidebar {
                position: sticky;
                top: 0;
                height: 100vh;
                width: 16rem; /* 256px expanded */
                transform: translateX(0);
                z-index: 30;
            }
            #leftSidebar.desktop-collapsed {
                width: 4.75rem; /* 76px compact icon rail */
            }
            #leftSidebar.desktop-collapsed .sidebar-text {
                display: none !important;
            }
            #leftSidebar.desktop-collapsed .sidebar-expand-only {
                display: none !important;
            }
            #leftSidebar.desktop-collapsed .sidebar-collapsed-only {
                display: flex !important;
            }
            #leftSidebar.desktop-collapsed .sidebar-box-pad {
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
            }
            #leftSidebar.desktop-collapsed .sidebar-nav-btn {
                justify-content: center !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                gap: 0 !important;
            }
            #leftSidebar.desktop-collapsed .sidebar-brand-wrapper {
                justify-content: center !important;
            }
        }

        /* Default hidden for collapsed-only elements */
        .sidebar-collapsed-only {
            display: none !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex font-sans overflow-x-hidden">

    <!-- 0. LEFT SIDEBAR BACKDROP (For Mobile / Drawer Overlay) -->
    <div id="sidebarBackdrop" onclick="closeSidebarDrawer()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden transition-opacity duration-300"></div>

    <!-- 1. LEFT SIDEBAR (Enterprise Navigation - Dynamic Drawer & Mini-Rail) -->
    <aside id="leftSidebar" class="bg-slate-900 text-white shrink-0 flex flex-col justify-between overflow-y-auto">
        <div class="p-5 space-y-6 sidebar-box-pad">
            <!-- Brand Logo & Collapse / Close Button -->
            <div class="sidebar-brand-wrapper flex items-center justify-between">
                <a href="/" class="flex items-center gap-3" title="s.labschool.xyz Enterprise Shortener">
                    <img src="/assets/img/logo-smp-labschool.png" alt="Labschool" class="h-9 w-auto shrink-0">
                    <div class="sidebar-text">
                        <div class="text-base font-black tracking-tight text-white leading-tight">s.labschool.xyz</div>
                        <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">Enterprise Shortener</div>
                    </div>
                </a>
                <button type="button" onclick="closeSidebarDrawer()" class="sidebar-expand-only p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition" title="Ciutkan Menu ke Pita Baris Ikon">
                    <span class="material-symbols-rounded text-xl">menu_open</span>
                </button>
            </div>

            <!-- + Create New Dropdown Button -->
            <div class="relative">
                <!-- Expanded Button -->
                <button type="button" onclick="toggleCreateDropdown()" id="btnCreateDropdown"
                    class="sidebar-expand-only w-full py-3 px-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-lg shadow-blue-600/30 transition flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-xl">add</span>
                        <span>Buat Baru</span>
                    </span>
                    <span class="material-symbols-rounded text-lg">arrow_drop_down</span>
                </button>

                <!-- Collapsed Icon-only Button -->
                <button type="button" onclick="toggleCreateDropdown()" id="btnCreateDropdownMini"
                    class="sidebar-collapsed-only w-11 h-11 mx-auto rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xl shadow-lg shadow-blue-600/30 transition items-center justify-center" title="Buat Baru (Tautan / QR / Page)">
                    <span class="material-symbols-rounded text-2xl">add</span>
                </button>

                <!-- Dropdown Menu -->
                <div id="createDropdownMenu" class="hidden absolute top-full left-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50 text-slate-800">
                    <button type="button" onclick="openCreateModal(); closeCreateDropdown();" class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-blue-600 text-lg">link</span>
                        <span>Tautan Singkat</span>
                    </button>
                    <button type="button" onclick="openBulkModal(); closeCreateDropdown();" class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-blue-600 text-lg">auto_awesome</span>
                        <span>Tautan Massal (Bulk)</span>
                    </button>
                    <button type="button" onclick="openQrModal(); closeCreateDropdown();" class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-blue-600 text-lg">qr_code</span>
                        <span>QR Code Kustom & Frame</span>
                    </button>
                    <button type="button" onclick="openSelectTemplateModal(); closeCreateDropdown();" class="w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-purple-600 text-lg">view_stream</span>
                        <span>Create Page</span>
                    </button>
                </div>
            </div>

            <!-- Navigation Links (With Icons & Tooltips) -->
            <nav class="space-y-1.5 text-xs font-bold">
                <button onclick="switchTab('links')" id="navLinkLinks" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl bg-blue-600 text-white transition" title="Tautan (Links)">
                    <span class="material-symbols-rounded text-xl shrink-0">link</span>
                    <span class="sidebar-text truncate">Tautan (Links)</span>
                    <span class="sidebar-text ml-auto bg-blue-700/80 px-2 py-0.5 rounded-full text-[10px]"><?= $totalLinks ?></span>
                </button>
                <button onclick="switchTab('qr')" id="navLinkQr" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="QR Codes">
                    <span class="material-symbols-rounded text-xl shrink-0">qr_code</span>
                    <span class="sidebar-text truncate">QR Codes</span>
                </button>
                <button onclick="switchTab('bios')" id="navLinkBios" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Pages">
                    <span class="material-symbols-rounded text-xl shrink-0">view_stream</span>
                    <span class="sidebar-text truncate">Pages</span>
                    <span class="sidebar-text ml-auto bg-slate-800 px-2 py-0.5 rounded-full text-[10px]"><?= $totalBios ?></span>
                </button>
                <a href="/analytics.php" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Analitik">
                    <span class="material-symbols-rounded text-xl shrink-0">bar_chart</span>
                    <span class="sidebar-text truncate">Analitik</span>
                </a>
                <button onclick="switchTab('campaigns')" id="navLinkCampaigns" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Tag & Kampanye">
                    <span class="material-symbols-rounded text-xl shrink-0">label</span>
                    <span class="sidebar-text truncate">Tag & Kampanye</span>
                    <span class="sidebar-text ml-auto bg-slate-800 px-2 py-0.5 rounded-full text-[10px]"><?= count($allTags) ?></span>
                </button>
                <a href="/settings.php?tab=integrations" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Integrasi">
                    <span class="material-symbols-rounded text-xl shrink-0">widgets</span>
                    <span class="sidebar-text truncate">Integrasi</span>
                </a>
                <a href="/settings.php?tab=account-details" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Pengaturan">
                    <span class="material-symbols-rounded text-xl shrink-0">settings</span>
                    <span class="sidebar-text truncate">Pengaturan</span>
                </a>
                <button onclick="switchTab('api')" id="navLinkApi" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition" title="Developer REST API">
                    <span class="material-symbols-rounded text-xl shrink-0">data_object</span>
                    <span class="sidebar-text truncate">Developer REST API</span>
                </button>
                <?php if ($user['role'] === 'superadmin'): ?>
                    <a href="/admin.php" class="sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-red-400 hover:text-white hover:bg-red-950/40 transition" title="Superadmin Panel">
                        <span class="material-symbols-rounded text-xl shrink-0">verified_user</span>
                        <span class="sidebar-text truncate">Superadmin Panel</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- User Profile Card -->
        <div class="p-3 border-t border-slate-800 flex items-center justify-between sidebar-box-pad">
            <div class="flex items-center gap-2.5 truncate mx-auto md:mx-0">
                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-black text-xs text-white uppercase shrink-0" title="<?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)">
                    <?= substr($user['full_name'], 0, 2) ?>
                </div>
                <div class="truncate sidebar-text">
                    <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="text-[10px] text-slate-400 font-mono truncate"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>
            <a href="/logout.php" class="p-2 text-slate-400 hover:text-red-400 transition sidebar-expand-only" title="Logout">
                <span class="material-symbols-rounded text-lg">logout</span>
            </a>
        </div>
    </aside>

    <!-- 2. MAIN WORKSPACE AREA -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
            <!-- Sidebar Drawer Toggle & Section Title -->
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="toggleSidebarDrawer()" class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center justify-center shrink-0" title="Buka / Sembunyikan Menu Navigasi (Drawer)">
                    <span class="material-symbols-rounded text-2xl" id="sidebarToggleIcon">menu_open</span>
                </button>
                <button type="button" onclick="openCreateModal()" class="md:hidden p-2 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center gap-1 shrink-0">
                    <span class="material-symbols-rounded text-sm">add</span>
                    <span>Buat</span>
                </button>
                <div class="flex items-center gap-2 min-w-0">
                    <h1 class="text-base font-extrabold text-slate-900 truncate" id="currentSectionTitle">Tautan Singkat</h1>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0"><?= $totalLinks ?> Tautan</span>
                </div>
            </div>

            <!-- Search Bar (Live Filter) -->
            <div class="flex items-center gap-3 max-w-md w-full ml-auto">
                <div class="relative w-full">
                    <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                    <input type="text" id="liveSearchInput" oninput="filterLinks()" placeholder="Cari tautan, slug, tujuan, atau #tag..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50/70 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs transition">
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 flex-1">
            
            <!-- Quick Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Tautan Aktif</div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($totalLinks) ?></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-blue-50 text-blue-600">
                        <span class="material-symbols-rounded text-2xl">link</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Klik Pengunjung</div>
                        <div class="text-2xl font-black text-blue-600"><?= number_format($totalClicks) ?></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600">
                        <span class="material-symbols-rounded text-2xl">ads_click</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Halaman Microsite</div>
                        <div class="text-2xl font-black text-purple-600"><?= number_format($totalBios) ?></div>
                    </div>
                    <div class="p-3 rounded-2xl bg-purple-50 text-purple-600">
                        <span class="material-symbols-rounded text-2xl">view_stream</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: Tautan (Links View) -->
            <div id="sectionLinks" class="space-y-4">
                <!-- Header Toolbar: Labs Links & Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                            <span class="material-symbols-rounded text-2xl">link</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-black text-slate-900 leading-tight">Labs Links</h1>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600"><?= $totalLinks ?> Tautan</span>
                            </div>
                            <p class="text-xs text-slate-500">Kelola pemendek tautan, deep link aplikasi, proteksi PIN, dan analisis klik SMP Labschool Jakarta.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 shrink-0">
                            <span class="material-symbols-rounded text-base">add</span>
                            <span>Create link</span>
                        </button>
                        <button type="button" onclick="window.location.href='/api/export_csv.php'" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-extrabold text-xs shadow-sm transition flex items-center gap-1.5 shrink-0" title="Export CSV">
                            <span class="material-symbols-rounded text-base text-amber-500">crown</span>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 text-xs">
                    <!-- Live Search -->
                    <div class="relative flex-1">
                        <span class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                        <input type="text" id="linkSearchInput" oninput="filterLinkCards()" placeholder="Search links, title, or destination..."
                            class="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs transition">
                        <button type="button" onclick="document.getElementById('linkSearchInput').value=''; filterLinkCards();" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm">
                            <span class="material-symbols-rounded text-base">close</span>
                        </button>
                    </div>

                    <!-- Filter Options -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0 scrollbar-none font-bold">
                        <button type="button" onclick="setQuickFilter('all', this)" class="quick-filter-btn px-3 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0 flex items-center gap-1.5">
                            <span>Show: All</span>
                        </button>
                        <button type="button" onclick="setQuickFilter('active', this)" class="quick-filter-btn px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                            <span>Active</span>
                        </button>
                        <button type="button" onclick="setQuickFilter('pin', this)" class="quick-filter-btn px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0 flex items-center gap-1">
                            <span class="material-symbols-rounded text-xs">lock</span>
                            <span>PIN</span>
                        </button>
                        <?php foreach ($allTags as $t): ?>
                            <button type="button" onclick="setTagFilter('<?= htmlspecialchars($t) ?>', this)" class="quick-tag-btn px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 shrink-0">
                                #<?= htmlspecialchars($t) ?>
                            </button>
                        <?php endforeach; ?>
                        <div class="h-4 w-px bg-slate-200 hidden md:block"></div>
                        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl shrink-0">
                            <button type="button" class="p-1 rounded-lg bg-white shadow-xs text-slate-800" title="List View"><span class="material-symbols-rounded text-base">format_list_bulleted</span></button>
                            <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-slate-700" title="Agenda View"><span class="material-symbols-rounded text-base">view_agenda</span></button>
                            <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-slate-700" title="Grid View"><span class="material-symbols-rounded text-base">grid_view</span></button>
                        </div>
                    </div>
                </div>

                <!-- Feed Daftar Tautan -->
                <?php if (empty($urls)): ?>
                    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">link_off</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-800">Belum ada tautan yang dibuat</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">Mulai perpendek tautan panjang Anda dengan fitur enterprise Labschool.</p>
                        <button type="button" onclick="openCreateModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-extrabold text-xs shadow-md shadow-blue-500/20">
                            + Buat Tautan Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden divide-y divide-slate-100" id="linkCardsContainer">
                        <?php foreach ($urls as $u): ?>
                            <?php
                                $uSlug = $u['custom_slug'] ?: $u['short_code'];
                                $uShort = base_url($uSlug);
                                $tagsArr = array_filter(array_map('trim', explode(',', $u['tags'] ?? '')));
                            ?>
                            <div class="link-item p-4 sm:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 hover:bg-slate-50/80 transition cursor-pointer"
                                 id="link-row-<?= $u['id'] ?>"
                                 data-id="<?= $u['id'] ?>"
                                 data-slug="<?= htmlspecialchars($uSlug) ?>"
                                 data-title="<?= htmlspecialchars($u['title']) ?>"
                                 data-orig="<?= htmlspecialchars($u['original_url']) ?>"
                                 data-tags="<?= htmlspecialchars($u['tags'] ?? '') ?>"
                                 data-haspass="<?= !empty($u['password_hash']) ? '1' : '0' ?>"
                                 data-active="<?= (int)$u['is_active'] ?>"
                                 data-clicks="<?= (int)$u['clicks_count'] ?>"
                                 onclick="handleLinkRowClick(event, <?= $u['id'] ?>)">
                                
                                <!-- Left Info: Checkbox + Short Link + Destination -->
                                <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                    <input type="checkbox" onclick="event.stopPropagation();" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <!-- Title -->
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="text-sm font-extrabold text-slate-900 truncate hover:text-blue-600 transition"><?= htmlspecialchars($u['title']) ?></h3>
                                            <?php if (!empty($u['is_collaborated'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                    <span class="symbols text-[12px]">group</span> <?= htmlspecialchars($u['workspace_scope']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Short URL with copy button -->
                                        <div class="flex items-center gap-2">
                                            <a href="<?= $uShort ?>" target="_blank" onclick="event.stopPropagation();" class="font-extrabold text-xs text-blue-600 hover:underline flex items-center gap-1">
                                                <span>s.labschool.xyz/<?= htmlspecialchars($uSlug) ?></span>
                                            </a>
                                            <button type="button" onclick="event.stopPropagation(); copyLink('<?= $uShort ?>', this)" class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100" title="Salin Tautan">
                                                <span class="material-symbols-rounded text-sm">content_copy</span>
                                            </button>
                                        </div>

                                        <!-- Destination URL with subdirectory_arrow_right -->
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500 truncate max-w-xl">
                                            <span class="material-symbols-rounded text-sm text-slate-400 shrink-0">subdirectory_arrow_right</span>
                                            <span class="truncate"><?= htmlspecialchars($u['original_url']) ?></span>
                                        </div>

                                        <!-- Bottom Info Metadata: Date, Tags, Badges -->
                                        <div class="flex items-center gap-3 pt-1 text-[11px] text-slate-400 flex-wrap">
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-rounded text-xs">calendar_today</span>
                                                <span><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                                            </span>
                                            <?php if (!empty($tagsArr)): ?>
                                                <span class="flex items-center gap-1">
                                                    <span class="material-symbols-rounded text-xs">sell</span>
                                                    <span><?= htmlspecialchars(implode(', ', $tagsArr)) ?></span>
                                                </span>
                                            <?php else: ?>
                                                <span class="flex items-center gap-1 text-slate-400">
                                                    <span class="material-symbols-rounded text-xs">sell</span>
                                                    <span>No tags</span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($u['password_hash'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    <span class="material-symbols-rounded text-xs">lock</span>
                                                    PIN
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($u['ios_url']) || !empty($u['android_url'])): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                                    <span class="material-symbols-rounded text-xs">devices</span>
                                                    Deep Link
                                                </span>
                                            <?php endif; ?>
                                            <?php if ((int)$u['is_active'] === 1): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Engagement Box + Quick Action Icons -->
                                <div class="flex items-center gap-5 shrink-0 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0 border-slate-100" onclick="event.stopPropagation();">
                                    <!-- Engagements Box -->
                                    <div class="text-right">
                                        <div class="flex items-center md:justify-end gap-1.5 text-xs font-black text-slate-900">
                                            <span class="material-symbols-rounded text-base text-blue-600">ads_click</span>
                                            <span><?= number_format($u['clicks_count']) ?> engagements</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 hidden sm:block">Includes Labs Link clicks, QR Code scans, and Labs Page clicks</div>
                                    </div>

                                    <!-- Action Icons -->
                                    <div class="flex items-center gap-1">
                                        <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit Link">
                                            <span class="material-symbols-rounded text-lg">edit</span>
                                        </button>
                                        <button type="button" onclick="shareLink('<?= $uShort ?>', '<?= htmlspecialchars(addslashes($u['title'])) ?>')" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Share">
                                            <span class="material-symbols-rounded text-lg">share</span>
                                        </button>
                                        <a href="/analytics.php?id=<?= $u['id'] ?>" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Laporan Analitik">
                                            <span class="material-symbols-rounded text-lg">bar_chart</span>
                                        </a>
                                        <button type="button" onclick="openDrawer(<?= $u['id'] ?>)" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="More details">
                                            <span class="material-symbols-rounded text-lg">more_horiz</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 2: QR Codes & Frames View -->
            <div id="sectionQr" class="hidden space-y-4">
                
                <!-- Header & Action Toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                            <span class="material-symbols-rounded text-2xl">qr_code</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-slate-900 leading-tight">Kode QR</h2>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600"><?= $totalLinks ?> Kode</span>
                            </div>
                            <p class="text-xs text-slate-500">Kelola, kustomisasi bingkai (frame), dan unduh kode QR interaktif SMP Labschool Jakarta.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 shrink-0">
                            <span class="material-symbols-rounded text-base">add</span>
                            <span>Buat kode</span>
                        </button>
                    </div>
                </div>

                <!-- Live Search & Filter Bar -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative w-full sm:max-w-md">
                        <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                        <input type="text" id="qrSearchInput" oninput="filterQrCards()" placeholder="Cari kode QR, judul, atau link tujuan..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs transition">
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto text-xs font-bold pb-1 sm:pb-0">
                        <button type="button" onclick="setQrFilter('all', this)" class="qr-filter-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shrink-0 shadow-sm">
                            Semua (<?= $totalLinks ?>)
                        </button>
                        <button type="button" onclick="setQrFilter('frame', this)" class="qr-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0">
                            Dengan Frame CTA
                        </button>
                        <button type="button" onclick="setQrFilter('standard', this)" class="qr-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0">
                            Format Standar
                        </button>
                    </div>
                </div>

                <!-- Feed Daftar Kode QR (Card Container List) -->
                <?php if (empty($urls)): ?>
                    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">qr_code_2</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-800">Belum ada kode QR</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">Setiap tautan pendek yang dibuat otomatis dilengkapi kode QR dinamis beresolusi tinggi.</p>
                        <button type="button" onclick="openCreateModal()" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-extrabold text-xs shadow-md shadow-blue-500/20">
                            + Buat Kode QR Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="space-y-3" id="qrCardsContainer">
                        <?php foreach ($urls as $u): ?>
                            <?php
                                $uSlug = $u['custom_slug'] ?: $u['short_code'];
                                $uShort = base_url($uSlug);
                                $tagsArr = array_filter(array_map('trim', explode(',', $u['tags'] ?? '')));
                                $frameType = $u['qr_frame'] ?? 'none';
                                $ctaText = $u['qr_cta_text'] ?? 'PINDAI SAYA';
                                $qrPreviewUrl = "/api/qr.php?code=" . urlencode($uSlug) . "&frame=" . urlencode($frameType) . "&cta=" . urlencode($ctaText) . "&size=160";
                            ?>
                            <div class="qr-card-item bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition p-4 sm:p-5 flex flex-col md:flex-row items-start md:items-center gap-4 sm:gap-5"
                                 id="qr-card-<?= $u['id'] ?>"
                                 data-id="<?= $u['id'] ?>"
                                 data-slug="<?= htmlspecialchars($uSlug) ?>"
                                 data-title="<?= htmlspecialchars($u['title']) ?>"
                                 data-orig="<?= htmlspecialchars($u['original_url']) ?>"
                                 data-tags="<?= htmlspecialchars($u['tags'] ?? '') ?>"
                                 data-frame="<?= htmlspecialchars($frameType) ?>">
                                
                                <!-- Kolom Kiri: Checkbox & Gambar QR Code -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-slate-50 border border-slate-200 p-1.5 flex items-center justify-center shrink-0 relative group shadow-xs cursor-pointer"
                                         onclick="openDrawerWithQr(<?= $u['id'] ?>)" title="Klik untuk pratinjau besar & frame">
                                        <img src="<?= $qrPreviewUrl ?>" alt="Kode QR untuk <?= htmlspecialchars($uSlug) ?>" class="w-full h-full object-contain rounded-lg">
                                        <!-- Hover Overlay Quick Action -->
                                        <div class="absolute inset-0 bg-slate-900/40 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="p-1.5 rounded-lg bg-white/90 text-slate-800 shadow">
                                                <span class="material-symbols-rounded text-sm">zoom_in</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Tengah: Konten Detail -->
                                <div class="flex-1 min-w-0 space-y-1.5">
                                    <!-- Badge Kategori Tipe (Website / Portal Sekolah) -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700">
                                            <span class="material-symbols-rounded text-xs text-blue-600">public</span>
                                            <span>Website</span>
                                        </span>

                                        <?php if ($frameType !== 'none'): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <span class="material-symbols-rounded text-xs">crop_portrait</span>
                                                <span>Frame: <?= htmlspecialchars($ctaText) ?></span>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($u['password_hash'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">
                                                <span class="material-symbols-rounded text-xs">lock</span>
                                                PIN
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Judul Kode QR (Klik membuka Drawer) -->
                                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 hover:text-blue-600 transition cursor-pointer truncate"
                                        onclick="openDrawer(<?= $u['id'] ?>)">
                                        <?= htmlspecialchars($u['title']) ?>
                                    </h3>

                                    <!-- URL Tujuan Asli (subdirectory_arrow_right) -->
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500 truncate">
                                        <span class="material-symbols-rounded text-sm text-slate-400 shrink-0">subdirectory_arrow_right</span>
                                        <a href="<?= htmlspecialchars($u['original_url']) ?>" target="_blank" class="hover:underline text-slate-600 truncate max-w-md">
                                            <?= htmlspecialchars($u['original_url']) ?>
                                        </a>
                                    </div>

                                    <!-- Metadata Bawah: Scan Count, Tanggal, Short URL & Tags -->
                                    <div class="flex items-center gap-4 flex-wrap pt-1 text-[11px] text-slate-500 font-medium">
                                        <!-- Scans Count -->
                                        <a href="/analytics.php?id=<?= $u['id'] ?>" class="flex items-center gap-1 font-bold text-slate-700 hover:text-blue-600 transition" title="Lihat Analitik Scan">
                                            <span class="material-symbols-rounded text-sm text-blue-600">bar_chart</span>
                                            <span><strong class="font-extrabold"><?= number_format($u['clicks_count']) ?></strong> scan</span>
                                        </a>

                                        <span class="text-slate-300">&bull;</span>

                                        <!-- Tanggal Dibuat -->
                                        <div class="flex items-center gap-1 text-slate-400">
                                            <span class="material-symbols-rounded text-xs">calendar_today</span>
                                            <span><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                                        </div>

                                        <span class="text-slate-300">&bull;</span>

                                        <!-- Tautan Pendek -->
                                        <div class="flex items-center gap-1.5">
                                            <a href="<?= $uShort ?>" target="_blank" class="font-bold text-blue-600 hover:underline">
                                                s.labschool.xyz/<?= htmlspecialchars($uSlug) ?>
                                            </a>
                                            <button type="button" onclick="copyLink('<?= $uShort ?>', this)" class="p-1 rounded text-slate-400 hover:text-slate-700 transition" title="Salin Link">
                                                <span class="material-symbols-rounded text-xs">content_copy</span>
                                            </button>
                                        </div>

                                        <!-- Tags -->
                                        <?php if (!empty($tagsArr)): ?>
                                            <span class="text-slate-300">&bull;</span>
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <span class="material-symbols-rounded text-xs text-slate-400">label</span>
                                                <?php foreach ($tagsArr as $tagItem): ?>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                                        #<?= htmlspecialchars($tagItem) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Tombol Aksi -->
                                <div class="flex items-center gap-1.5 shrink-0 w-full md:w-auto justify-end border-t md:border-t-0 pt-2 md:pt-0">
                                    <!-- Edit Konten / Kustomisasi QR -->
                                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                        class="p-2 rounded-xl text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" title="Edit Konten & Frame">
                                        <span class="material-symbols-rounded text-lg">edit</span>
                                    </button>

                                    <!-- Unduh Kode QR (PNG / SVG Dropdown) -->
                                    <div class="relative group/dl">
                                        <a href="/api/qr.php?code=<?= urlencode($uSlug) ?>&frame=<?= urlencode($frameType) ?>&cta=<?= urlencode($ctaText) ?>&download=1"
                                            class="p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition flex items-center" title="Unduh Kode QR (PNG)">
                                            <span class="material-symbols-rounded text-lg">file_download</span>
                                        </a>
                                    </div>

                                    <!-- Detail / Analitik Scan -->
                                    <a href="/analytics.php?id=<?= $u['id'] ?>"
                                        class="p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" title="Laporan Analitik Scan">
                                        <span class="material-symbols-rounded text-lg">bar_chart</span>
                                    </a>

                                    <!-- Opsi Lainnya (More) -->
                                    <button type="button" onclick="openDrawer(<?= $u['id'] ?>)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Lihat Detail Lengkap">
                                        <span class="material-symbols-rounded text-lg">more_horiz</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 3: Microsites / Bio Link View -->
            <div id="sectionBios" class="hidden space-y-4">
                <!-- Header Toolbar: Labs Pages -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-purple-50 text-purple-600">
                            <span class="material-symbols-rounded text-2xl">view_stream</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-black text-slate-900 leading-tight">Pages</h1>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600"><?= $totalBios ?> Pages</span>
                            </div>
                            <p class="text-xs text-slate-500">Kelola halaman profil interaktif, showcase kegiatan sekolah, and kartu nama digital.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openSelectTemplateModal()" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs shadow-md shadow-purple-500/20 transition flex items-center gap-1.5 shrink-0">
                            <span class="material-symbols-rounded text-base">add</span>
                            <span>Create Page</span>
                        </button>
                    </div>
                </div>

                <!-- Template Selector Card Catalog (Enterprise Template Catalog) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Select a template</h2>
                        <p class="text-xs text-slate-500 mt-0.5">You'll be able to change the content, colors, and layout later.</p>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold scrollbar-none" id="bioCategoryTabs">
                        <button type="button" onclick="filterBioTemplates('all', this)" class="bio-cat-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0">
                            All templates
                        </button>
                        <button type="button" onclick="filterBioTemplates('link-in-bio', this)" class="bio-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                            Link-in-bio
                        </button>
                        <button type="button" onclick="filterBioTemplates('products', this)" class="bio-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                            Products and services
                        </button>
                        <button type="button" onclick="filterBioTemplates('card', this)" class="bio-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                            Digital business card
                        </button>
                        <button type="button" onclick="filterBioTemplates('promotions', this)" class="bio-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                            Promotions
                        </button>
                        <a href="/page_builder.php?template=blank" class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 shrink-0 flex items-center gap-1 font-extrabold">
                            <span class="material-symbols-rounded text-sm">add</span>
                            <span>Start from scratch</span>
                        </a>
                    </div>

                    <!-- Template Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="bioTemplateGrid">
                        
                        <!-- Template 1: Link-in-Bio Official Labschool -->
                        <div class="bio-tmpl-item border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col justify-between" data-category="link-in-bio">
                            <div class="p-4 bg-gradient-to-b from-blue-900 to-blue-950 text-white space-y-3">
                                <div class="w-12 h-12 rounded-xl bg-white p-1 shadow-md mx-auto">
                                    <img src="/assets/img/logo-smp-labschool.png" alt="Logo" class="w-full h-full object-contain">
                                </div>
                                <div class="text-center">
                                    <h3 class="font-extrabold text-sm">SMP Labschool Jakarta</h3>
                                    <p class="text-[10px] text-blue-200">Berdikari, Berprestasi, Berkarakter</p>
                                </div>
                                <div class="flex items-center justify-center gap-3 text-xs text-blue-200 pt-1">
                                    <span>Instagram</span> &bull; <span>YouTube</span> &bull; <span>Website</span>
                                </div>
                                <div class="space-y-1.5 pt-2">
                                    <div class="w-full py-1.5 rounded-lg bg-white/10 text-[10px] font-bold text-center">Portal Pembelajaran LMS</div>
                                    <div class="w-full py-1.5 rounded-lg bg-white/10 text-[10px] font-bold text-center">Penerimaan Siswa Baru (PSB)</div>
                                </div>
                            </div>
                            <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">Labs Official Bio</span>
                                <a href="/page_builder.php?template=link-in-bio" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition">
                                    Use template
                                </a>
                            </div>
                        </div>

                        <!-- Template 2: Digital Business Card Guru & Pegawai -->
                        <div class="bio-tmpl-item border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col justify-between" data-category="card">
                            <div class="p-4 bg-gradient-to-b from-slate-900 to-slate-800 text-white space-y-3">
                                <div class="w-12 h-12 rounded-full bg-blue-600 text-white font-black text-sm flex items-center justify-center mx-auto shadow-md">
                                    AF
                                </div>
                                <div class="text-center">
                                    <h3 class="font-extrabold text-sm">Abdul Fathir Faruqi</h3>
                                    <p class="text-[10px] text-slate-300">Guru Matematika & IT Labschool</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-2.5 space-y-1 text-[10px]">
                                    <div class="flex items-center gap-1.5 text-slate-300"><span class="material-symbols-rounded text-xs text-blue-400">call</span> <span>(021) 4786-0038</span></div>
                                    <div class="flex items-center gap-1.5 text-slate-300"><span class="material-symbols-rounded text-xs text-blue-400">mail</span> <span>fathink@gmail.com</span></div>
                                    <div class="flex items-center gap-1.5 text-slate-300"><span class="material-symbols-rounded text-xs text-blue-400">language</span> <span>smplabschooljakarta.sch.id</span></div>
                                </div>
                                <div class="w-full py-1.5 rounded-lg bg-blue-600 text-[10px] font-bold text-center flex items-center justify-center gap-1">
                                    <span class="material-symbols-rounded text-xs">contact_page</span>
                                    <span>Simpan Kontak (vCard)</span>
                                </div>
                            </div>
                            <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">Digital Business Card</span>
                                <a href="/page_builder.php?template=digital-card" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition">
                                    Use template
                                </a>
                            </div>
                        </div>

                        <!-- Template 3: Event & Promotion (Cosmic Countdown) -->
                        <div class="bio-tmpl-item border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col justify-between" data-category="promotions">
                            <div class="p-4 bg-gradient-to-b from-indigo-950 via-purple-950 to-slate-950 text-white space-y-3">
                                <div class="text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-400/20 text-amber-300 border border-amber-400/30">OPEN HOUSE 2027</span>
                                    <h3 class="font-extrabold text-sm mt-1">PSB Labschool Jakarta</h3>
                                </div>
                                <div class="bg-white/10 rounded-xl p-2.5 text-center">
                                    <div class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Hitung Mundur Acara</div>
                                    <div class="font-mono font-black text-sm text-amber-300 mt-0.5">14 : 08 : 32 : 10</div>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="w-full py-1.5 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 text-[10px] font-black text-slate-950 text-center shadow-md">Daftar Online Sekarang</div>
                                    <div class="w-full py-1.5 rounded-lg bg-white/10 text-[10px] font-bold text-center">Unduh Brosur Informasi</div>
                                </div>
                            </div>
                            <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">Event Countdown & Promo</span>
                                <a href="/page_builder.php?template=school-event" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition">
                                    Use template
                                </a>
                            </div>
                        </div>

                        <!-- Template 4: Products & Services (Layanan Akademik) -->
                        <div class="bio-tmpl-item border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition flex flex-col justify-between" data-category="products">
                            <div class="p-4 bg-gradient-to-b from-emerald-900 to-slate-900 text-white space-y-3">
                                <div class="text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">Layanan Siswa</span>
                                    <h3 class="font-extrabold text-sm mt-1">Pusat Layanan Akademik</h3>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="w-full py-1.5 rounded-lg bg-white/10 text-[10px] font-bold text-center flex items-center justify-center gap-1">
                                        <span class="material-symbols-rounded text-xs">download</span>
                                        <span>Modul & Bank Soal Ujian</span>
                                    </div>
                                    <div class="w-full py-1.5 rounded-lg bg-white/10 text-[10px] font-bold text-center flex items-center justify-center gap-1">
                                        <span class="material-symbols-rounded text-xs">calendar_month</span>
                                        <span>Kalender Pendidikan 2026/2027</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-800">Products & Services</span>
                                <a href="/page_builder.php?template=portfolio-showcase" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition">
                                    Use template
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Active Pages List -->
                <div class="space-y-3 pt-2">
                    <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Halaman Pages Aktif Anda (<?= $totalBios ?>)</h2>
                    <?php if (empty($microsites)): ?>
                        <div class="bg-white rounded-3xl p-8 text-center border border-slate-200">
                            <p class="text-xs text-slate-400 font-semibold mb-2">Belum ada halaman Pages yang dipublikasikan.</p>
                            <button type="button" onclick="openSelectTemplateModal()" class="inline-block px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition">Buat Page Pertama</button>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($microsites as $m): ?>
                                <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700">
                                                s.labschool.xyz/p/<?= htmlspecialchars($m['slug']) ?>
                                            </span>
                                            <span class="text-xs text-slate-400 font-bold"><?= number_format($m['clicks_count']) ?> Views</span>
                                        </div>
                                        <h3 class="text-base font-extrabold text-slate-900"><?= htmlspecialchars($m['title']) ?></h3>
                                        <p class="text-xs text-slate-500 line-clamp-2 mt-1"><?= htmlspecialchars($m['bio'] ?: 'Tidak ada deskripsi bio.') ?></p>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                        <a href="/p/<?= htmlspecialchars($m['slug']) ?>" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">
                                            Buka Halaman Publik &rarr;
                                        </a>
                                        <div class="flex items-center gap-2">
                                            <a href="/page_builder.php?id=<?= $m['id'] ?>" class="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 text-xs font-bold transition flex items-center gap-1">
                                                <span class="material-symbols-rounded text-sm">edit</span>
                                                <span>Edit Page</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 4: Campaigns & Tags View -->
            <div id="sectionCampaigns" class="hidden space-y-4">
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                    <h2 class="text-base font-extrabold text-slate-900">Tag & Agregasi Kampanye Sekolah</h2>
                    <p class="text-xs text-slate-500">Kumpulkan dan analisis performa gabungan berbagai tautan yang memiliki tag atau kampanye sama (misal: #PPDB2026, #CBT, #OSIS).</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2">
                        <?php if (empty($allTags)): ?>
                            <div class="col-span-full py-8 text-center text-xs text-slate-400">
                                Belum ada tag yang disematkan ke tautan. Tambahkan tag saat membuat atau mengedit tautan.
                            </div>
                        <?php else: ?>
                            <?php foreach ($allTags as $tag): ?>
                                <?php
                                    $tagUrls = array_filter($urls, function($item) use ($tag) {
                                        return strpos($item['tags'] ?? '', $tag) !== false;
                                    });
                                    $tagClicks = array_sum(array_column($tagUrls, 'clicks_count'));
                                ?>
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-md transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-extrabold text-blue-600">#<?= htmlspecialchars($tag) ?></span>
                                        <span class="text-xs font-bold text-slate-500"><?= count($tagUrls) ?> Tautan</span>
                                    </div>
                                    <div class="text-xl font-black text-slate-900"><?= number_format($tagClicks) ?> <span class="text-xs font-medium text-slate-400">Total Klik</span></div>
                                    <button type="button" onclick="filterByTag('<?= htmlspecialchars($tag) ?>')" class="mt-3 text-xs font-bold text-blue-600 hover:underline">
                                        Tampilkan Semua Tautan &rarr;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: Developer REST API -->
            <div id="sectionApi" class="hidden space-y-6">
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
                                        <span class="material-symbols-rounded text-sm">refresh</span>
                                        <span>Reset & Buat Kunci Baru</span>
                                    </button>
                                    <button type="button" onclick="revokeApiKey()" class="px-3.5 py-2.5 rounded-xl bg-slate-200 hover:bg-rose-50 hover:text-rose-600 text-slate-600 font-bold text-xs transition" title="Cabut Kunci">
                                        Cabut
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="requestApiKey('generate')" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                                        <span class="material-symbols-rounded text-sm">key</span>
                                        <span>Terbitkan API Key</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-100 flex items-start gap-2.5 text-xs text-blue-900">
                            <span class="material-symbols-rounded text-blue-600 text-lg shrink-0 mt-0.5">info</span>
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
  -d '{"original_url": "https://smplabschooljakarta.sch.id/pengumuman", "custom_slug": "agenda-baru", "tags": "PPDB,Pengumuman"}'</pre>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 3. RIGHT SLIDING DRAWER (Detail & Analytics Panel) -->
    <div id="drawerOverlay" onclick="closeDrawer()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 hidden transition-opacity"></div>
    <aside id="detailDrawer" class="fixed top-0 right-0 w-full sm:w-[460px] h-full bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-200">
        <!-- Drawer Header -->
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900" id="drawerTitle">Detail Tautan</h3>
                <div class="text-[11px] text-blue-600 font-bold" id="drawerShortUrl">s.labschool.xyz/slug</div>
            </div>
            <button type="button" onclick="closeDrawer()" class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition">
                <span class="material-symbols-rounded text-xl">close</span>
            </button>
        </div>

        <!-- Drawer Body (Scrollable) -->
        <div class="p-5 space-y-6 flex-1 overflow-y-auto text-xs">
            
            <!-- Quick Action Bar -->
            <div class="flex items-center gap-2">
                <button type="button" id="drawerCopyBtn" onclick="copyCurrentDrawerLink()" class="flex-1 py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                    <span class="material-symbols-rounded text-sm">content_copy</span>
                    <span>Salin Link</span>
                </button>
                <a id="drawerFullAnalyticsBtn" href="#" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">query_stats</span>
                    <span>Laporan Analitik</span>
                </a>
            </div>

            <!-- Clicks Overview Card -->
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-between">
                <div>
                    <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Performa Interaksi</div>
                    <div class="text-2xl font-black text-blue-900" id="drawerClicksCount">0 Klik</div>
                </div>
                <span class="material-symbols-rounded text-3xl text-blue-600">monitoring</span>
            </div>

            <!-- Destination URL & Device Routing -->
            <div class="space-y-2">
                <div class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Tautan Tujuan (Original URL)</div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 break-all font-mono text-[11px] text-slate-600" id="drawerOriginalUrl">
                    https://smplabschooljakarta.sch.id/...
                </div>

                <!-- Deep Links -->
                <div id="drawerDeepLinks" class="space-y-1 pt-1 hidden">
                    <div class="font-bold text-indigo-700 text-[10px]">📱 Device-based Smart Routing (Deep Links)</div>
                    <div class="p-2 rounded-lg bg-indigo-50/70 border border-indigo-100 text-[10px] space-y-0.5">
                        <div id="drawerIosUrl" class="truncate text-indigo-900"><strong>iOS:</strong> -</div>
                        <div id="drawerAndroidUrl" class="truncate text-indigo-900"><strong>Android:</strong> -</div>
                    </div>
                </div>
            </div>

            <!-- QR Code Customization & Preview -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Smart QR Code & Frames</div>
                    <span class="text-[10px] font-bold text-amber-600">Labschool Logo Embedded</span>
                </div>

                <!-- QR Image Preview -->
                <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex flex-col items-center justify-center">
                    <img id="drawerQrImage" src="" alt="QR Code" class="max-w-[200px] h-auto object-contain rounded-lg shadow-xs">
                </div>

                <!-- Frame Selectors & Logo Presets -->
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-slate-600">Pilih Gaya Frame QR:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="setDrawerQrFrame('none')" id="qrFrame_none" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600">
                            Tanpa Frame
                        </button>
                        <button type="button" onclick="setDrawerQrFrame('badge-bottom')" id="qrFrame_badge_bottom" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50">
                            Badge Bawah
                        </button>
                        <button type="button" onclick="setDrawerQrFrame('badge-top')" id="qrFrame_badge_top" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50">
                            Badge Atas
                        </button>
                        <button type="button" onclick="setDrawerQrFrame('card')" id="qrFrame_card" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50">
                            Card Border
                        </button>
                    </div>

                    <label class="block text-[11px] font-bold text-slate-600 pt-1">Logo Tengah:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="setDrawerQrLogo('smp')" id="qrLogo_smp" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600">
                            Logo SMP Labschool
                        </button>
                        <button type="button" onclick="setDrawerQrLogo('none')" id="qrLogo_none" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50">
                            Tanpa Logo (Polos)
                        </button>
                    </div>
                </div>

                <!-- Download Actions -->
                <div class="flex items-center gap-2 pt-1">
                    <a id="drawerQrDownloadPng" href="#" download class="flex-1 py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-center transition">
                        Unduh PNG
                    </a>
                    <a id="drawerQrDownloadSvg" href="#" download class="flex-1 py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-center transition">
                        Unduh SVG
                    </a>
                </div>
            </div>

            <!-- Tags Manager -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Label & Kampanye (Tags)</div>
                <div id="drawerTagsContainer" class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-slate-400">Tidak ada tag</span>
                </div>
            </div>

        </div>

        <!-- Drawer Footer (Edit & Delete Buttons) -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <button type="button" id="drawerEditBtn" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition flex items-center gap-1.5">
                <span class="material-symbols-rounded text-sm">edit</span>
                <span>Edit Tautan</span>
            </button>
            <button type="button" id="drawerDeleteBtn" class="px-4 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-bold text-xs transition">
                Hapus Tautan
            </button>
        </div>
    </aside>

    <!-- 4. MODALS (Create Single, Bulk Shorten, Edit, API Key) -->

    <!-- Modal 4A: Buat Tautan Baru (Single) -->
    <div id="createModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-2xl">add_link</span>
                    <h3 class="text-base font-extrabold text-slate-900">Buat Tautan Pendek Baru</h3>
                </div>
                <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form id="formCreateModal" onsubmit="handleModalShorten(event)" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tautan Tujuan Asli *</label>
                    <input type="url" id="modalUrl" placeholder="https://contoh-link.com/dokumen..." required
                        class="w-full px-3.5 py-3 rounded-xl border border-slate-300 focus:border-blue-600 outline-none text-sm">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Judul Tautan (Opsional)</label>
                    <input type="text" id="modalTitle" placeholder="Contoh: Brosur PPDB Labschool 2026"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-blue-600 outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Custom Alias / Slug</label>
                        <div class="flex items-center rounded-xl border border-slate-300 overflow-hidden bg-slate-50">
                            <span class="px-2 text-slate-400 font-mono text-[10px]">s.labschool.xyz/</span>
                            <input type="text" id="modalSlug" placeholder="ppdb26" class="w-full py-2 pr-2 font-bold bg-transparent outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Label / Tags (Pisahkan koma)</label>
                        <input type="text" id="modalTags" placeholder="PPDB, Siswa, OSIS" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>

                <!-- Advanced Options Toggle -->
                <div class="pt-2">
                    <button type="button" onclick="toggleModalAdvanced()" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                        <span class="material-symbols-rounded text-sm">tune</span>
                        <span>Opsi Lanjutan (Proteksi PIN, Jadwal, Deep Link)</span>
                        <span id="modalAdvArrow">&darr;</span>
                    </button>

                    <div id="modalAdvancedSection" class="hidden mt-3 pt-3 border-t border-slate-100 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Proteksi Kata Sandi / PIN</label>
                                <input type="password" id="modalPass" placeholder="Kosongkan jika publik" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Waktu Kadaluarsa</label>
                                <input type="datetime-local" id="modalExp" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                            </div>
                        </div>

                        <!-- Deep Linking -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                            <div class="font-bold text-slate-700 text-[11px]">Device-based Routing (Deep Link)</div>
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Tujuan khusus iOS (iPhone / iPad):</label>
                                <input type="url" id="modalIosUrl" placeholder="https://apps.apple.com/..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white outline-none text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Tujuan khusus Android:</label>
                                <input type="url" id="modalAndroidUrl" placeholder="https://play.google.com/..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white outline-none text-xs">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2.5 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                    <button type="submit" id="btnSubmitModalCreate" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold shadow-md shadow-blue-500/20">
                        Simpan & Terbitkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4B: Pemendek Tautan Massal (Bulk Shorten) -->
    <div id="bulkModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-2xl">auto_awesome</span>
                    <h3 class="text-base font-extrabold text-slate-900">Pemendek Tautan Massal (Bulk Shorten)</h3>
                </div>
                <button type="button" onclick="closeBulkModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form id="formBulk" onsubmit="handleBulkSubmit(event)" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tempel Daftar URL Panjang (Satu URL per baris, maks 50 tautan) *</label>
                    <textarea id="bulkUrls" rows="6" required placeholder="https://link-pertama.com&#10;https://link-kedua.com&#10;https://link-ketiga.com"
                        class="w-full p-3 rounded-xl border border-slate-300 font-mono text-xs outline-none focus:border-blue-600"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tag / Label Bersama (Opsional)</label>
                    <input type="text" id="bulkTags" placeholder="Contoh: CBT2026, Ujian, SMP" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeBulkModal()" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                    <button type="submit" id="btnSubmitBulk" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold shadow-md shadow-blue-500/20">
                        Proses Massal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4C: Edit Tautan (Pro Features) -->
    <div id="editModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Tautan & Deep Link</h3>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>

            <form id="formEdit" onsubmit="handleEditSubmit(event)" class="space-y-4 text-xs">
                <input type="hidden" id="editId">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">URL Tujuan Asli *</label>
                    <input type="url" id="editOriginalUrl" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none text-sm">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Judul Tautan *</label>
                    <input type="text" id="editTitle" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Custom Slug</label>
                        <input type="text" id="editSlug" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tags (Pisahkan Koma)</label>
                        <input type="text" id="editTags" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>

                <!-- Deep Linking Edit -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="font-bold text-slate-700 text-[11px]">📱 Deep Link Routing</div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Tujuan khusus iOS:</label>
                        <input type="url" id="editIosUrl" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white outline-none text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Tujuan khusus Android:</label>
                        <input type="url" id="editAndroidUrl" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white outline-none text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ubah Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" id="editPassword" placeholder="PIN / Password baru..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    <label class="mt-1.5 flex items-center gap-1.5 text-slate-500 font-semibold">
                        <input type="checkbox" id="editRemovePassword"> Hapus proteksi kata sandi
                    </label>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-slate-600 font-bold hover:bg-slate-100">Batal</button>
                    <button type="submit" id="btnSaveEdit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md shadow-blue-500/20">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4D: API Key Reveal (Tampilkan Sekali) -->
    <div id="apiKeyModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center gap-3 text-emerald-600">
                <div class="p-2.5 rounded-2xl bg-emerald-50">
                    <span class="material-symbols-rounded text-2xl">verified_user</span>
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

    <!-- ======================================================== -->
    <!-- MODAL: SELECT A TEMPLATE (Bitly-Style Flow)              -->
    <!-- ======================================================== -->
    <div id="modalSelectTemplate" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6 max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-2xl">dashboard_customize</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-black text-slate-900">Select a template</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 uppercase">Interactive</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih template awal atau mulai dari kanvas kosong untuk memulai merancang Page Anda. Anda dapat mengubah konten, warna, dan blok nanti.</p>
                    </div>
                </div>
                <button type="button" onclick="closeSelectTemplateModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                    <span class="material-symbols-rounded text-xl">close</span>
                </button>
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold scrollbar-none" id="modalCategoryTabs">
                <button type="button" onclick="filterModalTemplates('all', this)" class="modal-cat-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0">
                    All templates
                </button>
                <button type="button" onclick="filterModalTemplates('Link-in-bio', this)" class="modal-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                    Link-in-bio
                </button>
                <button type="button" onclick="filterModalTemplates('Product and services', this)" class="modal-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                    Product and services
                </button>
                <button type="button" onclick="filterModalTemplates('Digital business card', this)" class="modal-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                    Digital business card
                </button>
                <button type="button" onclick="filterModalTemplates('Promotions', this)" class="modal-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">
                    Promotions
                </button>
            </div>

            <!-- Templates Grid (Dynamic Master Collection: 20 Templates) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="modalTemplateGrid">
                <?php foreach ($templatePresets as $tKey => $tp): ?>
                    <?php if ($tKey === 'blank') continue; ?>
                    <div class="modal-tmpl-item border border-slate-200 rounded-2xl overflow-hidden hover:shadow-xl transition flex flex-col justify-between bg-white group" data-category="<?= htmlspecialchars($tp['category']) ?>">
                        <?php if (!empty($tp['thumbnail'])): ?>
                            <div class="h-40 overflow-hidden bg-slate-100 relative">
                                <img src="<?= htmlspecialchars($tp['thumbnail']) ?>" alt="<?= htmlspecialchars($tp['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex items-end p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-blue-600/90 text-white backdrop-blur-sm uppercase">
                                        <?= htmlspecialchars($tp['category']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: 
                            $grad = 'from-blue-900 to-slate-900';
                            $catIcon = 'auto_awesome';
                            if ($tp['category'] === 'Product and services') {
                                $grad = 'from-emerald-900 via-teal-950 to-slate-900';
                                $catIcon = 'storefront';
                            } elseif ($tp['category'] === 'Digital business card') {
                                $grad = 'from-slate-900 via-indigo-950 to-slate-900';
                                $catIcon = 'badge';
                            } elseif ($tp['category'] === 'Promotions') {
                                $grad = 'from-purple-950 via-slate-950 to-amber-950';
                                $catIcon = 'local_offer';
                            }
                        ?>
                            <div class="p-4 bg-gradient-to-b <?= $grad ?> text-white space-y-2.5 min-h-[140px] flex flex-col justify-between">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-white/20 text-white border border-white/30 uppercase"><?= htmlspecialchars($tp['category']) ?></span>
                                    <span class="material-symbols-rounded text-white/80 text-lg"><?= $catIcon ?></span>
                                </div>
                                <div class="text-center pt-1">
                                    <h4 class="font-extrabold text-xs line-clamp-1"><?= htmlspecialchars($tp['title'] ?: $tp['name']) ?></h4>
                                    <p class="text-[10px] text-slate-300 line-clamp-2 mt-0.5"><?= htmlspecialchars($tp['bio'] ?: $tp['name']) ?></p>
                                </div>
                                <div class="space-y-1">
                                    <div class="w-full py-1 rounded-lg bg-white/10 text-[9px] font-bold text-center text-white/90 truncate px-2">
                                        <?= !empty($tp['blocks'][0]['title']) ? htmlspecialchars($tp['blocks'][0]['title']) : 'Lihat Tautan Utama' ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <div class="truncate mr-2">
                                <span class="text-xs font-extrabold text-slate-800 block truncate"><?= htmlspecialchars($tp['name']) ?></span>
                                <span class="text-[10px] text-slate-400 block"><?= count($tp['blocks'] ?? []) ?> blocks</span>
                            </div>
                            <a href="/page_builder.php?template=<?= urlencode($tKey) ?>" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition shrink-0">
                                Use template
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Start from Scratch Card -->
                <div class="modal-tmpl-item border-2 border-dashed border-slate-300 rounded-2xl p-5 hover:border-blue-500 hover:bg-blue-50/30 transition flex flex-col justify-between text-center space-y-3 bg-white" data-category="all">
                    <div class="space-y-2 pt-3">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center mx-auto">
                            <span class="material-symbols-rounded text-2xl">add_box</span>
                        </div>
                        <h4 class="font-extrabold text-sm text-slate-900">Mulai dari Kanvas Kosong</h4>
                        <p class="text-[11px] text-slate-500">Rancang tata letak, tombol, tema warna, dan susunan blok sendiri dari awal tanpa preset.</p>
                    </div>
                    <a href="/page_builder.php?template=blank" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs shadow-sm transition">
                        Start from Scratch
                    </a>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="/page_builder.php?template=blank" class="text-xs font-extrabold text-blue-600 hover:underline flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">edit_note</span>
                    <span>Atau lewati pilihan dan mulai kanvas kosong &rarr;</span>
                </a>
                <button type="button" onclick="closeSelectTemplateModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

        <!-- ======================================================== -->
    <!-- MODAL: SHARE LINK (Social & Direct Share)                -->
    <!-- ======================================================== -->
    <div id="shareModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5">
            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-xl">share</span>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Bagikan Tautan</h3>
                        <p class="text-xs text-slate-500" id="shareModalTitle">Sebarkan tautan resmi Labschool</p>
                    </div>
                </div>
                <button type="button" onclick="closeShareModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                    <span class="material-symbols-rounded text-lg">close</span>
                </button>
            </div>

            <!-- Short URL Box -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-2">
                <input type="text" id="shareModalUrlInput" readonly class="bg-transparent text-xs font-mono font-bold text-blue-600 w-full focus:outline-none select-all truncate">
                <button type="button" id="btnShareModalCopy" onclick="copyShareModalLink()" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm transition shrink-0 flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm" id="shareModalCopyIcon">content_copy</span>
                    <span id="shareModalCopyText">Salin</span>
                </button>
            </div>

            <!-- Social Share Grid -->
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2">Bagikan Langsung ke Platform:</label>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <a id="shareBtnWa" href="#" target="_blank" class="p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-[11px] flex flex-col items-center gap-1 transition">
                        <span class="material-symbols-rounded text-xl">chat</span>
                        <span>WhatsApp</span>
                    </a>
                    <a id="shareBtnTelegram" href="#" target="_blank" class="p-3 rounded-2xl bg-sky-50 hover:bg-sky-100 text-sky-600 font-extrabold text-[11px] flex flex-col items-center gap-1 transition">
                        <span class="material-symbols-rounded text-xl">send</span>
                        <span>Telegram</span>
                    </a>
                    <a id="shareBtnTwitter" href="#" target="_blank" class="p-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-extrabold text-[11px] flex flex-col items-center gap-1 transition">
                        <span class="material-symbols-rounded text-xl">tag</span>
                        <span>X / Twitter</span>
                    </a>
                    <a id="shareBtnFb" href="#" target="_blank" class="p-3 rounded-2xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-extrabold text-[11px] flex flex-col items-center gap-1 transition">
                        <span class="material-symbols-rounded text-xl">public</span>
                        <span>Facebook</span>
                    </a>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" onclick="closeShareModal()" class="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    <!-- Client-side Data Array for Lightning Performance -->
    <script>
        const CSRF_TOKEN = '<?= csrf_token() ?>';
        // Auto-attach X-CSRF-TOKEN to all internal fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options = options || {};
            options.headers = options.headers || {};
            if (typeof options.headers.set === 'function') {
                options.headers.set('X-CSRF-TOKEN', CSRF_TOKEN);
            } else {
                options.headers['X-CSRF-TOKEN'] = CSRF_TOKEN;
            }
            return originalFetch(url, options);
        };

        <?php
        // Sanitasi daftar tautan untuk frontend: buang hash password agar tidak terekspos ke klien
        $clientUrls = array_map(function($item) {
            $item['has_password'] = !empty($item['password_hash']) ? 1 : 0;
            unset($item['password_hash']);
            return $item;
        }, $urls);
        ?>
        const allLinksData = <?= json_encode($clientUrls) ?>;
        let activeLink = null;
        let currentFrame = 'none';
        let currentQrLogo = 'smp';

        // 1. Navigation & Dropdowns
        function toggleCreateDropdown() {
            document.getElementById('createDropdownMenu').classList.toggle('hidden');
        }

        function closeCreateDropdown() {
            document.getElementById('createDropdownMenu').classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            const btn = document.getElementById('btnCreateDropdown');
            const btnMini = document.getElementById('btnCreateDropdownMini');
            const menu = document.getElementById('createDropdownMenu');
            if (menu && !menu.contains(e.target)) {
                if ((!btn || !btn.contains(e.target)) && (!btnMini || !btnMini.contains(e.target))) {
                    closeCreateDropdown();
                }
            }
        });

        function switchTab(tab) {
            closeSidebarOnMobile();
            const sections = ['Links', 'Qr', 'Bios', 'Campaigns', 'Api'];
            sections.forEach(s => {
                const sec = document.getElementById('section' + s);
                if (sec) sec.classList.add('hidden');
                const nav = document.getElementById('navLink' + s);
                if (nav) {
                    nav.className = 'sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition';
                }
            });

            const activeSection = document.getElementById('section' + tab.charAt(0).toUpperCase() + tab.slice(1));
            const activeNav = document.getElementById('navLink' + tab.charAt(0).toUpperCase() + tab.slice(1));
            
            if (activeSection) activeSection.classList.remove('hidden');
            if (activeNav) activeNav.className = 'sidebar-nav-btn w-full flex items-center gap-3 px-3.5 py-3 rounded-xl bg-blue-600 text-white transition';

            const titleMap = {
                links: 'Tautan Singkat',
                qr: 'Kode QR',
                bios: 'Pages',
                campaigns: 'Tag & Kampanye Sekolah',
                api: 'Developer REST API'
            };
            const titleEl = document.getElementById('currentSectionTitle');
            if (titleEl) titleEl.innerText = titleMap[tab] || 'Dashboard';
        }

        // 2. Sliding Drawer (Detail Panel)
        function handleLinkRowClick(e, linkId) {
            openDrawer(linkId);
        }

        function openDrawer(linkId) {
            activeLink = allLinksData.find(l => l.id == linkId);
            if (!activeLink) return;

            const slug = activeLink.custom_slug || activeLink.short_code;
            const shortUrl = window.location.origin + '/' + slug;

            document.getElementById('drawerTitle').innerText = activeLink.title || 'Tautan Singkat';
            document.getElementById('drawerShortUrl').innerText = 's.labschool.xyz/' + slug;
            document.getElementById('drawerClicksCount').innerText = Number(activeLink.clicks_count).toLocaleString() + ' Klik';
            document.getElementById('drawerOriginalUrl').innerText = activeLink.original_url;
            document.getElementById('drawerFullAnalyticsBtn').href = '/analytics.php?id=' + activeLink.id;

            // Deep Links
            const dlSec = document.getElementById('drawerDeepLinks');
            if (activeLink.ios_url || activeLink.android_url) {
                dlSec.classList.remove('hidden');
                document.getElementById('drawerIosUrl').innerHTML = '<strong>iOS:</strong> ' + (activeLink.ios_url || 'Default');
                document.getElementById('drawerAndroidUrl').innerHTML = '<strong>Android:</strong> ' + (activeLink.android_url || 'Default');
            } else {
                dlSec.classList.add('hidden');
            }

            // Tags
            const tagsCont = document.getElementById('drawerTagsContainer');
            if (activeLink.tags && activeLink.tags.trim()) {
                const parts = activeLink.tags.split(',');
                tagsCont.innerHTML = parts.map(p => `<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700">#${p.trim()}</span>`).join('');
            } else {
                tagsCont.innerHTML = '<span class="text-slate-400">Belum ada label/tag</span>';
            }

            // QR Frame & Image
            currentFrame = activeLink.qr_frame || 'none';
            updateDrawerQrImage();

            // Edit & Delete handlers
            document.getElementById('drawerEditBtn').onclick = () => { closeDrawer(); openEditModal(activeLink); };
            document.getElementById('drawerDeleteBtn').onclick = () => { closeDrawer(); deleteLink(activeLink.id); };

            // Show drawer
            document.getElementById('drawerOverlay').classList.remove('hidden');
            document.getElementById('detailDrawer').classList.remove('translate-x-full');
        }

        function openDrawerWithQr(linkId) {
            openDrawer(linkId);
        }

        function setDrawerQrFrame(frame) {
            currentFrame = frame;
            ['none', 'badge-bottom', 'badge-top', 'card'].forEach(f => {
                const btn = document.getElementById('qrFrame_' + f.replace('-', '_'));
                if (btn) {
                    btn.className = f === frame 
                        ? 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600'
                        : 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50';
                }
            });
            updateDrawerQrImage();
        }

        function setDrawerQrLogo(logo) {
            currentQrLogo = logo;
            ['smp', 'none'].forEach(l => {
                const btn = document.getElementById('qrLogo_' + l);
                if (btn) {
                    btn.className = l === logo 
                        ? 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600'
                        : 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200 hover:bg-slate-50';
                }
            });
            updateDrawerQrImage();
        }

        function updateDrawerQrImage() {
            if (!activeLink) return;
            const slug = activeLink.custom_slug || activeLink.short_code;
            const cta = encodeURIComponent(activeLink.qr_cta_text || 'PINDAI SAYA');
            const qrUrl = `/api/qr.php?code=${encodeURIComponent(slug)}&frame=${currentFrame}&logo=${currentQrLogo}&cta=${cta}&size=360`;
            document.getElementById('drawerQrImage').src = qrUrl;
            document.getElementById('drawerQrDownloadPng').href = qrUrl + '&download=1';
            document.getElementById('drawerQrDownloadSvg').href = `/api/qr.php?code=${encodeURIComponent(slug)}&format=svg&download=1`;
        }

        function closeDrawer() {
            document.getElementById('detailDrawer').classList.add('translate-x-full');
            document.getElementById('drawerOverlay').classList.add('hidden');
            activeLink = null;
        }

        function copyCurrentDrawerLink() {
            if (!activeLink) return;
            const slug = activeLink.custom_slug || activeLink.short_code;
            copyLink(window.location.origin + '/' + slug);
        }

        // 3. Modals Controls
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function toggleModalAdvanced() {
            const sec = document.getElementById('modalAdvancedSection');
            sec.classList.toggle('hidden');
            document.getElementById('modalAdvArrow').innerHTML = sec.classList.contains('hidden') ? '&darr;' : '&uarr;';
        }

        function openBulkModal() {
            document.getElementById('bulkModal').classList.remove('hidden');
        }

        function closeBulkModal() {
            document.getElementById('bulkModal').classList.add('hidden');
        }

        function openQrModal() {
            switchTab('qr');
        }

        // 4. API Calls & Forms
        async function handleModalShorten(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitModalCreate');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                original_url: document.getElementById('modalUrl').value,
                title: document.getElementById('modalTitle').value,
                custom_slug: document.getElementById('modalSlug').value,
                tags: document.getElementById('modalTags').value,
                password: document.getElementById('modalPass').value,
                expires_at: document.getElementById('modalExp').value,
                ios_url: document.getElementById('modalIosUrl').value,
                android_url: document.getElementById('modalAndroidUrl').value
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
                alert('Terjadi gangguan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan & Terbitkan';
            }
        }

        async function handleBulkSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitBulk');
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            const payload = {
                urls: document.getElementById('bulkUrls').value,
                tags: document.getElementById('bulkTags').value
            };

            try {
                const res = await fetch('/api/bulk_shorten.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert(`Selesai! Berhasil membuat ${json.success_count} tautan (Gagal: ${json.fail_count}).`);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Proses Massal';
            }
        }

        function copyLink(text, btnElement) {
            navigator.clipboard.writeText(text);
            if (btnElement) {
                const orig = btnElement.innerHTML;
                btnElement.innerHTML = '<span class="material-symbols-rounded text-lg text-emerald-600">check</span>';
                setTimeout(() => { btnElement.innerHTML = orig; }, 1500);
            } else {
                alert('Tautan disalin ke clipboard: ' + text);
            }
        }

        function openEditModal(link) {
            document.getElementById('editId').value = link.id;
            document.getElementById('editOriginalUrl').value = link.original_url;
            document.getElementById('editTitle').value = link.title;
            document.getElementById('editSlug').value = link.custom_slug || '';
            document.getElementById('editTags').value = link.tags || '';
            document.getElementById('editIosUrl').value = link.ios_url || '';
            document.getElementById('editAndroidUrl').value = link.android_url || '';
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
                tags: document.getElementById('editTags').value,
                ios_url: document.getElementById('editIosUrl').value,
                android_url: document.getElementById('editAndroidUrl').value,
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
                alert('Terjadi gangguan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan Perubahan';
            }
        }

        async function deleteLink(id) {
            if (!confirm('Yakin ingin menghapus tautan ini? Tindakan ini tidak dapat dibatalkan.')) return;
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
                    alert('Gagal menghapus: ' + json.message);
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        // 5. Search & Filters
        function filterLinks() {
            const query = document.getElementById('liveSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.link-item');
            rows.forEach(r => {
                const text = (r.getAttribute('data-slug') + ' ' + r.getAttribute('data-title') + ' ' + r.getAttribute('data-orig') + ' ' + r.getAttribute('data-tags')).toLowerCase();
                r.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function setQuickFilter(type, btn) {
            document.querySelectorAll('.quick-filter-btn').forEach(b => {
                b.className = 'quick-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0';
            });
            btn.className = 'quick-filter-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0';

            const rows = document.querySelectorAll('.link-item');
            rows.forEach(r => {
                if (type === 'all') {
                    r.style.display = '';
                } else if (type === 'active') {
                    r.style.display = r.getAttribute('data-active') === '1' ? '' : 'none';
                } else if (type === 'pin') {
                    r.style.display = r.getAttribute('data-haspass') === '1' ? '' : 'none';
                }
            });
        }

        function setTagFilter(tag, btn) {
            document.getElementById('liveSearchInput').value = tag;
            filterLinks();
        }

        function filterByTag(tag) {
            switchTab('links');
            document.getElementById('liveSearchInput').value = tag;
            filterLinks();
        }

        // QR Code Filters
        function filterQrCards() {
            const query = (document.getElementById('qrSearchInput')?.value || '').toLowerCase();
            const cards = document.querySelectorAll('.qr-card-item');
            cards.forEach(c => {
                const text = (c.getAttribute('data-slug') + ' ' + c.getAttribute('data-title') + ' ' + c.getAttribute('data-orig') + ' ' + c.getAttribute('data-tags')).toLowerCase();
                c.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function setQrFilter(type, btn) {
            document.querySelectorAll('.qr-filter-btn').forEach(b => {
                b.className = 'qr-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0';
            });
            btn.className = 'qr-filter-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shrink-0 shadow-sm';

            const cards = document.querySelectorAll('.qr-card-item');
            cards.forEach(c => {
                if (type === 'all') {
                    c.style.display = '';
                } else if (type === 'frame') {
                    c.style.display = (c.getAttribute('data-frame') !== 'none') ? '' : 'none';
                } else if (type === 'standard') {
                    c.style.display = (c.getAttribute('data-frame') === 'none') ? '' : 'none';
                }
            });
        }

        // 6. API Key Handlers
        async function requestApiKey(action) {
            const msg = action === 'reset' 
                ? 'Apakah Anda yakin ingin mereset API Key? Kunci lama akan otomatis tidak berlaku.'
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
            if (!confirm('Apakah Anda yakin ingin mencabut / menonaktifkan API Key Anda?')) return;
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
    
        
        // ========================================================
        // DYNAMIC LEFT SIDEBAR DRAWER CONTROLS
        // ========================================================
        function isMobileView() {
            return window.innerWidth < 768;
        }

        function updateSidebarToggleIcon() {
            const sidebar = document.getElementById('leftSidebar');
            const icon = document.getElementById('sidebarToggleIcon');
            if (!icon || !sidebar) return;

            if (isMobileView()) {
                const isOpen = sidebar.classList.contains('drawer-open');
                icon.innerText = isOpen ? 'close' : 'menu';
            } else {
                const isCollapsed = sidebar.classList.contains('desktop-collapsed');
                icon.innerText = isCollapsed ? 'menu' : 'menu_open';
            }
        }

        function openSidebarDrawer() {
            const sidebar = document.getElementById('leftSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar) return;

            if (isMobileView()) {
                sidebar.classList.add('drawer-open');
                if (backdrop) backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                sidebar.classList.remove('desktop-collapsed');
                localStorage.setItem('sidebar_collapsed_desktop', '0');
            }
            updateSidebarToggleIcon();
        }

        function closeSidebarDrawer() {
            const sidebar = document.getElementById('leftSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar) return;

            if (isMobileView()) {
                sidebar.classList.remove('drawer-open');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                sidebar.classList.add('desktop-collapsed');
                localStorage.setItem('sidebar_collapsed_desktop', '1');
            }
            updateSidebarToggleIcon();
        }

        function toggleSidebarDrawer() {
            const sidebar = document.getElementById('leftSidebar');
            if (!sidebar) return;

            if (isMobileView()) {
                if (sidebar.classList.contains('drawer-open')) {
                    closeSidebarDrawer();
                } else {
                    openSidebarDrawer();
                }
            } else {
                if (sidebar.classList.contains('desktop-collapsed')) {
                    openSidebarDrawer();
                } else {
                    closeSidebarDrawer();
                }
            }
        }

        function closeSidebarOnMobile() {
            if (isMobileView()) {
                closeSidebarDrawer();
            }
        }

        function initSidebarState() {
            const sidebar = document.getElementById('leftSidebar');
            if (sidebar && !isMobileView()) {
                const isCollapsed = localStorage.getItem('sidebar_collapsed_desktop') === '1';
                if (isCollapsed) {
                    sidebar.classList.add('desktop-collapsed');
                }
            }
            updateSidebarToggleIcon();
        }

        // Initialize immediately & on events
        initSidebarState();
        window.addEventListener('resize', function() {
            updateSidebarToggleIcon();
            if (!isMobileView()) {
                const backdrop = document.getElementById('sidebarBackdrop');
                if (backdrop) backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        // Bitly-Style Page Creation & Template Filter Controls
        function openSelectTemplateModal() {
            const modal = document.getElementById('modalSelectTemplate');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeSelectTemplateModal() {
            const modal = document.getElementById('modalSelectTemplate');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Handle Escape key to close modal
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSelectTemplateModal();
            }
        });

        function filterBioTemplates(cat, btn) {
            document.querySelectorAll('.bio-cat-btn').forEach(b => {
                b.className = 'bio-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0';
            });
            if (btn) {
                btn.className = 'bio-cat-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0';
            }
            document.querySelectorAll('#bioTemplateGrid .bio-tmpl-item').forEach(el => {
                const itemCat = el.getAttribute('data-category');
                if (cat === 'all' || itemCat === cat) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        }

        // Share Link Functionality
        let currentShareUrl = '';
        function shareLink(url, title) {
            currentShareUrl = url;
            const fullTitle = title || 'Tautan Resmi Labschool';
            
            // On mobile devices with native Web Share API
            if (navigator.share && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                navigator.share({
                    title: fullTitle,
                    text: fullTitle + ' - SMP Labschool Jakarta',
                    url: url
                }).catch(() => {});
                return;
            }

            // Open custom Share Modal
            const modal = document.getElementById('shareModal');
            if (modal) {
                document.getElementById('shareModalTitle').innerText = fullTitle;
                document.getElementById('shareModalUrlInput').value = url;
                
                const encUrl = encodeURIComponent(url);
                const encText = encodeURIComponent(fullTitle + ' - ' + url);
                
                document.getElementById('shareBtnWa').href = 'https://api.whatsapp.com/send?text=' + encText;
                document.getElementById('shareBtnTelegram').href = 'https://t.me/share/url?url=' + encUrl + '&text=' + encodeURIComponent(fullTitle);
                document.getElementById('shareBtnTwitter').href = 'https://twitter.com/intent/tweet?text=' + encText;
                document.getElementById('shareBtnFb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encUrl;
                
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Tautan disalin ke clipboard:\n' + url);
                });
            }
        }

        function closeShareModal() {
            const modal = document.getElementById('shareModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function copyShareModalLink() {
            const input = document.getElementById('shareModalUrlInput');
            if (input && input.value) {
                navigator.clipboard.writeText(input.value).then(() => {
                    const icon = document.getElementById('shareModalCopyIcon');
                    const text = document.getElementById('shareModalCopyText');
                    if (icon) icon.innerText = 'check';
                    if (text) text.innerText = 'Tersalin!';
                    setTimeout(() => {
                        if (icon) icon.innerText = 'content_copy';
                        if (text) text.innerText = 'Salin';
                    }, 2000);
                });
            }
        }

        function filterModalTemplates(cat, btn) {
            document.querySelectorAll('.modal-cat-btn').forEach(b => {
                b.className = 'modal-cat-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0';
            });
            if (btn) {
                btn.className = 'modal-cat-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0';
            }
            document.querySelectorAll('#modalTemplateGrid .modal-tmpl-item').forEach(el => {
                const itemCat = el.getAttribute('data-category') || '';
                if (cat === 'all' || itemCat.toLowerCase() === cat.toLowerCase() || itemCat === 'all') {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        }


    </script>

    <!-- Labs Assist AI Side Drawer (AI Side Drawer) -->
    <div id="assistOverlay" onclick="toggleLabsAssist()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 hidden transition-opacity"></div>
    <aside id="labsAssistDrawer" class="fixed top-0 right-0 w-full sm:w-[460px] h-full bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-200">
        <!-- Header -->
        <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-blue-900 to-indigo-950 text-white">
            <div class="flex items-center gap-2.5">
                <div class="p-1.5 rounded-xl bg-white/10 text-amber-300">
                    <span class="material-symbols-rounded text-2xl">auto_awesome</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold leading-tight">Labs Assist</h3>
                    <p class="text-[11px] text-blue-200">AI Assistant s.labschool.xyz</p>
                </div>
            </div>
            <button type="button" onclick="toggleLabsAssist()" class="p-1.5 rounded-xl hover:bg-white/10 text-slate-300 hover:text-white transition">
                <span class="material-symbols-rounded text-xl">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6 flex-1 overflow-y-auto text-xs text-slate-600">
            <div>
                <h4 class="text-sm font-extrabold text-slate-900">Tingkatkan Produktivitas Anda</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Bawa kecerdasan buatan ke dalam alur kerja Anda dengan Labs Assist — mulai dari membuat tautan hingga menemukan tren performa tautan sekolah.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-100 space-y-2.5 text-xs text-blue-950 font-bold">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-base">check_circle</span>
                    <span>Get instant analytics and performance insights</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-base">check_circle</span>
                    <span>Create and optimize links with AI assistance</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-base">check_circle</span>
                    <span>Generate QR Codes and branded content</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-blue-600 text-base">check_circle</span>
                    <span>Discover trends and patterns automatically</span>
                </div>
            </div>

            <!-- Quick Suggestion Prompts -->
            <div class="space-y-2">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Saran Perintah Cepat:</label>
                <div class="flex flex-col gap-2 font-bold text-xs">
                    <button type="button" onclick="setAssistPrompt('Analisis performa klik tautan minggu ini dan berikan ringkasan.')" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-left hover:border-blue-500 hover:bg-blue-50/50 transition flex items-center justify-between">
                        <span>Rangkum performa klik minggu ini</span>
                        <span class="material-symbols-rounded text-slate-400 text-sm">arrow_forward</span>
                    </button>
                    <button type="button" onclick="setAssistPrompt('Buat rekomendasi vanity slug untuk pengumuman ujian matematika.')" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-left hover:border-blue-500 hover:bg-blue-50/50 transition flex items-center justify-between">
                        <span>Rekomendasikan vanity slug tautan</span>
                        <span class="material-symbols-rounded text-slate-400 text-sm">arrow_forward</span>
                    </button>
                    <button type="button" onclick="setAssistPrompt('Bandingkan jumlah scan QR Code vs klik tautan langsung.')" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-left hover:border-blue-500 hover:bg-blue-50/50 transition flex items-center justify-between">
                        <span>Bandingkan scan QR vs klik langsung</span>
                        <span class="material-symbols-rounded text-slate-400 text-sm">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer Input -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/60 space-y-2">
            <div class="relative flex items-center">
                <input type="text" id="assistPromptInput" placeholder="Tanyakan ke Labs Assist..." class="w-full pl-4 pr-12 py-3 rounded-2xl bg-white border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none text-xs font-medium">
                <button type="button" onclick="sendAssistQuery()" class="absolute right-2 p-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">
                    <span class="material-symbols-rounded text-base">arrow_forward</span>
                </button>
            </div>
            <p class="text-[10px] text-slate-400 text-center">Labs Assist can make mistakes. Always verify your links, codes, and data.</p>
        </div>
    </aside>
    
</body>
</html>
