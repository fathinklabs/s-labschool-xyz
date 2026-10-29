<?php
/**
 * User Dashboard: s.labschool.xyz
 * Bitly Enterprise Layout & Full Feature Parity
 * (Left Sidebar, Create Dropdown, Sliding Detail Drawer, Smart QR Frames, Deep Linking, Tagging & Bulk Shorten)
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
    <title>Bitly Dashboard — s.labschool.xyz</title>
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
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex font-sans overflow-x-hidden">

    <!-- 1. LEFT SIDEBAR (Bitly Navigation Style) -->
    <aside class="w-64 bg-slate-900 text-white shrink-0 flex flex-col justify-between hidden md:flex min-h-screen sticky top-0 z-30">
        <div class="p-5 space-y-6">
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-3">
                <img src="/assets/img/logo-smp-labschool.png" alt="Labschool" class="h-9 w-auto">
                <div>
                    <div class="text-base font-black tracking-tight text-white leading-tight">s.labschool.xyz</div>
                    <div class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">Enterprise Shortener</div>
                </div>
            </a>

            <!-- + Create New Dropdown Button -->
            <div class="relative">
                <button type="button" onclick="toggleCreateDropdown()" id="btnCreateDropdown"
                    class="w-full py-3 px-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm shadow-lg shadow-blue-600/30 transition flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-rounded text-xl">add</span>
                        <span>Buat Baru</span>
                    </span>
                    <span class="material-symbols-rounded text-lg">arrow_drop_down</span>
                </button>

                <!-- Dropdown Menu -->
                <div id="createDropdownMenu" class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-50 text-slate-800">
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
                    <a href="/bio_editor.php" class="block w-full px-4 py-2.5 text-left text-xs font-bold hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2.5">
                        <span class="material-symbols-rounded text-purple-600 text-lg">view_stream</span>
                        <span>Halaman Bio Link</span>
                    </a>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1.5 text-xs font-bold">
                <button onclick="switchTab('links')" id="navLinkLinks" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl bg-blue-600 text-white transition">
                    <span class="material-symbols-rounded text-xl">link</span>
                    <span>Tautan (Links)</span>
                    <span class="ml-auto bg-blue-700/80 px-2 py-0.5 rounded-full text-[10px]"><?= $totalLinks ?></span>
                </button>
                <button onclick="switchTab('qr')" id="navLinkQr" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">qr_code</span>
                    <span>QR Codes & Frames</span>
                </button>
                <button onclick="switchTab('bios')" id="navLinkBios" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">view_stream</span>
                    <span>Link-in-bio (Pages)</span>
                    <span class="ml-auto bg-slate-800 px-2 py-0.5 rounded-full text-[10px]"><?= $totalBios ?></span>
                </button>
                <button onclick="switchTab('campaigns')" id="navLinkCampaigns" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">label</span>
                    <span>Tag & Kampanye</span>
                    <span class="ml-auto bg-slate-800 px-2 py-0.5 rounded-full text-[10px]"><?= count($allTags) ?></span>
                </button>
                <button onclick="switchTab('api')" id="navLinkApi" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition">
                    <span class="material-symbols-rounded text-xl">data_object</span>
                    <span>Developer REST API</span>
                </button>
                <?php if ($user['role'] === 'superadmin'): ?>
                    <a href="/admin.php" class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-red-400 hover:text-white hover:bg-red-950/40 transition">
                        <span class="material-symbols-rounded text-xl">verified_user</span>
                        <span>Superadmin Panel</span>
                    </a>
                <?php endif; ?>
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
                    <div class="text-[10px] text-slate-400 font-mono truncate"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>
            <a href="/logout.php" class="p-2 text-slate-400 hover:text-red-400 transition" title="Logout">
                <span class="material-symbols-rounded text-lg">logout</span>
            </a>
        </div>
    </aside>

    <!-- 2. MAIN WORKSPACE AREA -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0">
        
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
            <!-- Mobile Menu Toggle & Title -->
            <div class="flex items-center gap-3">
                <button type="button" onclick="openCreateModal()" class="md:hidden p-2 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm">add</span>
                    <span>Buat</span>
                </button>
                <div class="flex items-center gap-2">
                    <h1 class="text-base font-extrabold text-slate-900" id="currentSectionTitle">Tautan Singkat</h1>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600"><?= $totalLinks ?> Tautan</span>
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
                
                <!-- Filter Pills: All, Active, PIN, Expired & Tag Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold scrollbar-none">
                    <button type="button" onclick="setQuickFilter('all', this)" class="quick-filter-btn px-3.5 py-1.5 rounded-xl bg-slate-900 text-white shadow-sm shrink-0">
                        Semua (<?= $totalLinks ?>)
                    </button>
                    <button type="button" onclick="setQuickFilter('active', this)" class="quick-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0">
                        Aktif Saja
                    </button>
                    <button type="button" onclick="setQuickFilter('pin', this)" class="quick-filter-btn px-3.5 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 shrink-0">
                        Terproteksi PIN
                    </button>
                    <?php foreach ($allTags as $t): ?>
                        <button type="button" onclick="setTagFilter('<?= htmlspecialchars($t) ?>', this)" class="quick-tag-btn px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 shrink-0">
                            #<?= htmlspecialchars($t) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Feed Daftar Tautan -->
                <?php if (empty($urls)): ?>
                    <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <span class="material-symbols-rounded text-3xl">link_off</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-800">Belum ada tautan yang dibuat</h3>
                        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">Mulai perpendek tautan panjang Anda dengan fitur enterprise ala Bitly & s.id.</p>
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
                                
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="<?= $uShort ?>" target="_blank" onclick="event.stopPropagation();" class="font-extrabold text-base text-blue-600 hover:underline flex items-center gap-1">
                                            <span>s.labschool.xyz/<?= htmlspecialchars($uSlug) ?></span>
                                            <span class="material-symbols-rounded text-sm opacity-60">open_in_new</span>
                                        </a>

                                        <?php if (!empty($u['password_hash'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                <span class="material-symbols-rounded text-xs">lock</span>
                                                PIN
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($u['ios_url']) || !empty($u['android_url'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800" title="Deep Link Device Routing">
                                                <span class="material-symbols-rounded text-xs">devices</span>
                                                Deep Link
                                            </span>
                                        <?php endif; ?>

                                        <?php if ((int)$u['is_active'] !== 1): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                                Nonaktif
                                            </span>
                                        <?php endif; ?>

                                        <span class="text-xs text-slate-400">&bull; <?= time_ago($u['created_at']) ?></span>
                                    </div>

                                    <div class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($u['title']) ?></div>
                                    <div class="text-xs text-slate-400 truncate max-w-lg"><?= htmlspecialchars($u['original_url']) ?></div>

                                    <!-- Tags list -->
                                    <?php if (!empty($tagsArr)): ?>
                                        <div class="flex items-center gap-1.5 flex-wrap pt-1">
                                            <?php foreach ($tagsArr as $tagItem): ?>
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                                    #<?= htmlspecialchars($tagItem) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions & Analytics -->
                                <div class="flex items-center gap-2 shrink-0 w-full md:w-auto justify-between md:justify-end" onclick="event.stopPropagation();">
                                    <!-- Clicks Counter (Opens Drawer) -->
                                    <button type="button" onclick="openDrawer(<?= $u['id'] ?>)" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition text-xs font-bold">
                                        <span class="material-symbols-rounded text-base">bar_chart</span>
                                        <span><?= number_format($u['clicks_count']) ?> Klik</span>
                                    </button>

                                    <!-- Copy Button -->
                                    <button type="button" onclick="copyLink('<?= $uShort ?>', this)" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Salin Tautan">
                                        <span class="material-symbols-rounded text-lg">content_copy</span>
                                    </button>

                                    <!-- QR Code Button (Opens QR Drawer / Modal) -->
                                    <button type="button" onclick="openDrawerWithQr(<?= $u['id'] ?>)" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Lihat QR Code & Frame">
                                        <span class="material-symbols-rounded text-lg">qr_code</span>
                                    </button>

                                    <!-- Edit Link Button -->
                                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="p-2 rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition" title="Edit Tautan">
                                        <span class="material-symbols-rounded text-lg">edit</span>
                                    </button>

                                    <!-- Delete Link Button -->
                                    <button type="button" onclick="deleteLink(<?= $u['id'] ?>)" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus">
                                        <span class="material-symbols-rounded text-lg">delete</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 2: QR Codes & Frames View -->
            <div id="sectionQr" class="hidden space-y-4">
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Bitly Smart QR Codes & Frames</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Hasilkan kode QR interaktif beresolusi tinggi dengan bingkai Call to Action (CTA) resmi SMP Labschool Jakarta.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <!-- Frame Preview 1: Standar -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50 text-center space-y-3">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">Format Standar</div>
                            <div class="w-36 h-36 mx-auto bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="/api/qr.php?code=default&size=160" alt="QR Preview" class="w-full h-full object-contain">
                            </div>
                            <p class="text-[11px] text-slate-500">QR murni dengan logo resmi Labschool di tengah.</p>
                        </div>

                        <!-- Frame Preview 2: Badge Bawah -->
                        <div class="p-5 rounded-2xl border border-blue-200 bg-blue-50/50 text-center space-y-3">
                            <div class="text-xs font-bold text-blue-900 uppercase tracking-wider">Smart Frame: PINDAI SAYA</div>
                            <div class="w-40 h-48 mx-auto bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="/api/qr.php?code=default&frame=badge-bottom&cta=PINDAI+SAYA&size=180" alt="QR Frame" class="w-full h-full object-contain">
                            </div>
                            <p class="text-[11px] text-blue-700">Dilengkapi tombol ajakan pindai untuk banner cetak / poster.</p>
                        </div>

                        <!-- Frame Preview 3: Custom CTA -->
                        <div class="p-5 rounded-2xl border border-amber-200 bg-amber-50/50 text-center space-y-3">
                            <div class="text-xs font-bold text-amber-900 uppercase tracking-wider">Smart Frame: INFO PPDB</div>
                            <div class="w-40 h-48 mx-auto bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="/api/qr.php?code=default&frame=badge-bottom&cta=INFO+PPDB+2026&size=180" alt="QR Frame" class="w-full h-full object-contain">
                            </div>
                            <p class="text-[11px] text-amber-800">Kustomisasi teks banner sesuai kebutuhan kegiatan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Microsites / Bio Link View -->
            <div id="sectionBios" class="hidden space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Halaman Microsite & Bio Link</h2>
                        <p class="text-xs text-slate-500">Buat landing page mini untuk organisasi, OSIS, MPK, atau ekstrakurikuler Labschool.</p>
                    </div>
                    <a href="/bio_editor.php" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-lg">add</span>
                        <span>Buat Bio Page Baru</span>
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
                                    <p class="text-xs text-slate-500 line-clamp-2 mt-1"><?= htmlspecialchars($m['bio'] ?: 'Tidak ada deskripsi bio.') ?></p>
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <a href="/p/<?= htmlspecialchars($m['slug']) ?>" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">
                                        Buka Halaman Publik &rarr;
                                    </a>
                                    <div class="flex items-center gap-2">
                                        <a href="/bio_editor.php?id=<?= $m['id'] ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition">
                                            Edit Desain
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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

    <!-- 3. RIGHT SLIDING DRAWER (Bitly Detail & Analytics Panel) -->
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

                <!-- Frame Selectors -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-slate-600">Pilih Tipe Frame:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="setDrawerQrFrame('none')" id="qrFrameNone" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-blue-600 text-blue-600">
                            Tanpa Frame
                        </button>
                        <button type="button" onclick="setDrawerQrFrame('badge-bottom')" id="qrFrameBadge" class="px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200">
                            Badge "PINDAI SAYA"
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

    <!-- Client-side Data Array for Lightning Performance -->
    <script>
        const allLinksData = <?= json_encode($urls) ?>;
        let activeLink = null;
        let currentFrame = 'none';

        // 1. Navigation & Dropdowns
        function toggleCreateDropdown() {
            document.getElementById('createDropdownMenu').classList.toggle('hidden');
        }

        function closeCreateDropdown() {
            document.getElementById('createDropdownMenu').classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            const btn = document.getElementById('btnCreateDropdown');
            const menu = document.getElementById('createDropdownMenu');
            if (!btn.contains(e.target) && !menu.contains(e.target)) {
                closeCreateDropdown();
            }
        });

        function switchTab(tab) {
            const sections = ['Links', 'Qr', 'Bios', 'Campaigns', 'Api'];
            sections.forEach(s => {
                document.getElementById('section' + s).classList.add('hidden');
                const nav = document.getElementById('navLink' + s);
                if (nav) {
                    nav.className = 'w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition';
                }
            });

            const activeSection = document.getElementById('section' + tab.charAt(0).toUpperCase() + tab.slice(1));
            const activeNav = document.getElementById('navLink' + tab.charAt(0).toUpperCase() + tab.slice(1));
            
            if (activeSection) activeSection.classList.remove('hidden');
            if (activeNav) activeNav.className = 'w-full flex items-center gap-3 px-3.5 py-3 rounded-xl bg-blue-600 text-white transition';

            const titleMap = {
                links: 'Tautan Singkat',
                qr: 'QR Codes & Smart Frames',
                bios: 'Halaman Microsite (Bio Link)',
                campaigns: 'Tag & Kampanye Sekolah',
                api: 'Developer REST API'
            };
            document.getElementById('currentSectionTitle').innerText = titleMap[tab] || 'Dashboard';
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
            document.getElementById('qrFrameNone').className = frame === 'none' ? 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600' : 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200';
            document.getElementById('qrFrameBadge').className = frame === 'badge-bottom' ? 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-blue-600 bg-blue-50 border-blue-600' : 'px-2.5 py-1.5 rounded-lg border text-xs font-bold text-slate-600 bg-white border-slate-200';
            updateDrawerQrImage();
        }

        function updateDrawerQrImage() {
            if (!activeLink) return;
            const slug = activeLink.custom_slug || activeLink.short_code;
            const cta = encodeURIComponent(activeLink.qr_cta_text || 'PINDAI SAYA');
            const qrUrl = `/api/qr.php?code=${encodeURIComponent(slug)}&frame=${currentFrame}&cta=${cta}&size=320`;
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
    </script>
</body>
</html>
