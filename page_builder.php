<?php
/**
 * Labs Page Builder: s.labschool.xyz
 * Interactive Experience & Modern Template Engine
 * SMP Labschool Jakarta
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();

$siteId = (int)($_GET['id'] ?? 0);
$selectedTemplateKey = trim($_GET['template'] ?? 'link-in-bio');

$site = null;
$blocks = [];

// Load centralized template presets (19 templates across 4 Bitly categories + Custom)
require_once __DIR__ . '/config/templates.php';



if ($siteId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM microsites WHERE id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();
    if (!$site || ($site['user_id'] != $user['id'] && $user['role'] !== 'superadmin')) {
        header('Location: /dashboard.php?tab=bios');
        exit;
    }

    $stmtB = $pdo->prepare("SELECT * FROM microsite_blocks WHERE microsite_id = ? ORDER BY display_order ASC");
    $stmtB->execute([$siteId]);
    $blocks = $stmtB->fetchAll();
} else {
    // New Page: initialize from selected template preset
    $preset = $templatePresets[$selectedTemplateKey] ?? $templatePresets['link-in-bio'];
    $site = [
        'id'              => 0,
        'slug'            => '',
        'title'           => $preset['title'],
        'bio'             => $preset['bio'],
        'avatar_url'      => '',
        'template_style'  => $preset['template_style'],
        'theme'           => $preset['theme'],
        'countdown_title' => $preset['countdown_title'],
        'countdown_date'  => $preset['countdown_date'],
        'social_links'    => json_encode([
            'whatsapp'  => '081200000000',
            'instagram' => '@smplabschooljakarta',
            'youtube'   => 'https://youtube.com/@smplabschooljakarta',
            'email'     => 'kontak@labschool.xyz'
        ])
    ];
    $blocks = $preset['blocks'];
}

$socials = !empty($site['social_links']) ? json_decode($site['social_links'], true) : [];
$activeTemplate = $site['template_style'] ?? 'genially-showcase';
$activeTheme = $site['theme'] ?? 'labs-navy';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteId ? 'Edit Page' : 'Buat Page Baru' ?> — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@400;600;700;800;900&family=Fredoka:wght@500;600;700&family=Gasoek+One&family=Nunito+Sans:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Poppins:wght@400;600;700;800;900&family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0..1,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 
                        sans: ['"Plus Jakarta Sans"', '"Nunito Sans"', 'sans-serif'],
                        symbols: ['"Material Symbols Rounded"', 'sans-serif']
                    },
                    colors: {
                        labs: { blue: '#2563EB', dark: '#1E40AF', amber: '#F59E0B' }
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
        /* Drawer Transition classes */
        .drawer-backdrop {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .drawer-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
        .drawer-panel {
            transform: translateX(100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .drawer-panel.active {
            transform: translateX(0);
        }
        @keyframes pulseRing {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        .highlight-new-block {
            animation: pulseRing 1.2s ease-out 2;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">

    <!-- Top Sticky Navbar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/dashboard.php?tab=bios" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition" title="Kembali ke Daftar Pages">
                    <span class="material-symbols-rounded text-xl">arrow_back</span>
                </a>
                <div class="flex items-center gap-2.5">
                    <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-9 w-auto">
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm font-extrabold text-slate-900 leading-tight"><?= $siteId ? 'Edit Page' : 'Labs Page Builder' ?></h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 uppercase tracking-wide">
                                <?= htmlspecialchars($templatePresets[$selectedTemplateKey]['category'] ?? 'Interactive') ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 hidden sm:block">Modern Interactive Page Experience &bull; SMP Labschool Jakarta</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <?php if ($siteId > 0 && !empty($site['slug'])): ?>
                    <a href="/p/<?= htmlspecialchars($site['slug']) ?>" target="_blank" class="hidden sm:inline-flex px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition items-center gap-1.5">
                        <span class="material-symbols-rounded text-sm">open_in_new</span>
                        <span>Lihat Publik</span>
                    </a>
                <?php endif; ?>

                <button type="button" onclick="openTemplatePickerModal()" class="px-3.5 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 font-extrabold text-xs transition flex items-center gap-1.5 border border-purple-200">
                    <span class="material-symbols-rounded text-sm">auto_fix_high</span>
                    <span class="hidden sm:inline">Ganti Template</span>
                </button>

                <button type="button" onclick="saveMicrosite()" id="btnSave" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-base">save</span>
                    <span>Simpan & Terbitkan</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Builder Grid -->
    <div class="max-w-7xl mx-auto px-4 py-8 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 flex-1">
        
        <!-- Kolom Form Editor (7 Kolom) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- 1. Identitas Page -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-blue-50 text-blue-600">
                        <span class="material-symbols-rounded text-lg">badge</span>
                    </span>
                    <span>1. Profil & Alamat URL Page</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Custom Slug (Alamat URL) *</label>
                        <div class="flex items-center rounded-xl border border-slate-300 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100 overflow-hidden bg-slate-50">
                            <span class="px-3 text-xs text-slate-400 font-semibold select-none">s.labschool.xyz/p/</span>
                            <input type="text" id="siteSlug" value="<?= htmlspecialchars($site['slug'] ?? '') ?>" placeholder="contoh: osis-2026" required
                                class="w-full py-2.5 pr-3 text-sm font-bold bg-transparent outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Judul / Nama Page *</label>
                        <input type="text" id="siteTitle" value="<?= htmlspecialchars($site['title'] ?? '') ?>" placeholder="contoh: OSIS SMP Labschool Jakarta" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Deskripsi Page</label>
                    <textarea id="siteBio" rows="2" placeholder="Tuliskan deskripsi profil atau agenda kegiatan..."
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"><?= htmlspecialchars($site['bio'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">URL Avatar / Logo Profil</label>
                        <input type="url" id="siteAvatar" value="<?= htmlspecialchars($site['avatar_url'] ?? '') ?>" placeholder="https://... (kosongkan untuk logo Labschool)"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">URL Gambar Sampul / Cover Banner (Fitur s.id)</label>
                        <input type="url" id="siteHeaderBg" value="<?= htmlspecialchars($site['header_bg_url'] ?? '') ?>" placeholder="https://... (banner horizontal 3:1 di atas profil)"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1 flex-wrap text-xs text-slate-600 border-t border-slate-100">
                    <label class="flex items-center gap-1.5 cursor-pointer font-bold select-none">
                        <input type="checkbox" id="siteTitleBold" <?= $titleIsBold ? 'checked' : '' ?> class="rounded text-blue-600">
                        <span>Tebalkan Judul (Bold)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer font-bold select-none">
                        <input type="checkbox" id="siteTitleItalic" <?= $titleIsItalic ? 'checked' : '' ?> class="rounded text-blue-600">
                        <span>Miringkan Judul (Italic)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer font-bold select-none">
                        <input type="checkbox" id="siteBioItalic" <?= $bioIsItalic ? 'checked' : '' ?> class="rounded text-blue-600">
                        <span>Miringkan Slogan (Italic)</span>
                    </label>
                    <span class="text-[11px] text-slate-400 italic">Mendukung format Markdown **tebal** dan *miring* di deskripsi dan judul tombol.</span>
                </div>
            </div>

            <!-- 2. Pilihan Layout Template Interaktif -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-purple-50 text-purple-600">
                        <span class="material-symbols-rounded text-lg">dashboard_customize</span>
                    </span>
                    <span>2. Template Layout Interaktif</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" id="templateSelector">
                    <!-- Template 1: Genially Showcase -->
                    <label class="cursor-pointer">
                        <input type="radio" name="template_style" value="genially-showcase" <?= $activeTemplate === 'genially-showcase' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3.5 rounded-2xl border-2 peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:bg-slate-50 transition text-left space-y-1.5">
                            <span class="material-symbols-rounded text-blue-600 text-2xl">auto_awesome</span>
                            <div class="text-xs font-black text-slate-900">Showcase Hero</div>
                            <p class="text-[10px] text-slate-500 leading-tight">Hero banner, tombol link interaktif dengan chevron & efek shimmer berkilau.</p>
                        </div>
                    </label>

                    <!-- Template 2: Genially Roadmap -->
                    <label class="cursor-pointer">
                        <input type="radio" name="template_style" value="genially-roadmap" <?= $activeTemplate === 'genially-roadmap' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3.5 rounded-2xl border-2 peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:bg-slate-50 transition text-left space-y-1.5">
                            <span class="material-symbols-rounded text-purple-600 text-2xl">route</span>
                            <div class="text-xs font-black text-slate-900">Step Roadmap</div>
                            <p class="text-[10px] text-slate-500 leading-tight">Alur linier bertahap dengan nomor urut untuk PPDB atau panduan kursus.</p>
                        </div>
                    </label>

                    <!-- Template 3: Genially Cards Grid -->
                    <label class="cursor-pointer">
                        <input type="radio" name="template_style" value="genially-cards" <?= $activeTemplate === 'genially-cards' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3.5 rounded-2xl border-2 peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:bg-slate-50 transition text-left space-y-1.5">
                            <span class="material-symbols-rounded text-emerald-600 text-2xl">grid_view</span>
                            <div class="text-xs font-black text-slate-900">Cards Grid 2-Col</div>
                            <p class="text-[10px] text-slate-500 leading-tight">Tata letak kartu modern 2 kolom berdampingan dengan efek 3D tilt hover.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 3. Hitung Mundur Acara (Event Countdown) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-amber-50 text-amber-600">
                        <span class="material-symbols-rounded text-lg">timer</span>
                    </span>
                    <span>3. Hitung Mundur Acara (Event Countdown)</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Judul Countdown (Opsional)</label>
                        <input type="text" id="cdTitle" value="<?= htmlspecialchars($site['countdown_title'] ?? '') ?>" placeholder="Contoh: Menuju Open House Labschool 2026"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Waktu Target Acara (WIB)</label>
                        <input type="datetime-local" id="cdDate" value="<?= !empty($site['countdown_date']) ? date('Y-m-d\TH:i', strtotime($site['countdown_date'])) : '' ?>"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>
            </div>

            <!-- 4. Pengaturan Tema Visual -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-blue-50 text-blue-600">
                        <span class="material-symbols-rounded text-lg">palette</span>
                    </span>
                    <span>4. Tema Tampilan Visual</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5" id="themeSelector">
                    <!-- Tema Standar Labs & Genially -->
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="labs-navy" <?= $activeTheme === 'labs-navy' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-100 bg-slate-900 text-white text-center transition">
                            <div class="w-5 h-5 rounded-full bg-blue-600 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Labs Navy</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="genially-cosmic" <?= $activeTheme === 'genially-cosmic' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-cyan-400 peer-checked:ring-2 peer-checked:ring-cyan-100 bg-[#0B0F19] text-cyan-300 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-r from-cyan-400 to-purple-500 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Cosmic Neon</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="labs-gold" <?= $activeTheme === 'labs-gold' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-100 bg-slate-950 text-amber-400 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-amber-500 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Labs Gold</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="emerald" <?= $activeTheme === 'emerald' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-emerald-600 peer-checked:ring-2 peer-checked:ring-emerald-100 bg-slate-900 text-emerald-400 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-emerald-600 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Emerald</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="slate" <?= $activeTheme === 'slate' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-slate-800 peer-checked:ring-2 peer-checked:ring-slate-200 bg-slate-100 text-slate-800 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-slate-800 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Clean Slate</span>
                        </div>
                    </label>

                    <!-- Tema Eksklusif Berbasis Template Desain -->
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="web-design-studio" <?= $activeTheme === 'web-design-studio' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-sky-400 peer-checked:ring-2 peer-checked:ring-sky-100 bg-[#1e3a75] text-sky-200 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-r from-blue-500 to-sky-400 mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Web Studio</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="creative-agency" <?= $activeTheme === 'creative-agency' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-indigo-600 peer-checked:ring-2 peer-checked:ring-indigo-100 bg-[#f1f2f7] text-slate-800 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-r from-[#1873fb] to-[#664cf0] mx-auto mb-1"></div>
                            <span class="text-[10px] font-bold block truncate">Agency UI</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="artist-doodle" <?= $activeTheme === 'artist-doodle' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-2.5 rounded-2xl border-2 peer-checked:border-slate-900 peer-checked:ring-2 peer-checked:ring-amber-200 bg-[#4aa5fd] text-slate-900 text-center transition">
                            <div class="w-5 h-5 rounded-full bg-[#fdb849] border border-slate-900 mx-auto mb-1"></div>
                            <span class="text-[10px] font-black block truncate">Artist Pop</span>
                        </div>
                    </label>
                </div>

                <!-- Kustom Wallpaper & Latar Belakang (Fitur Lengkap s.id) -->
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-xs font-bold text-slate-700">Atau Gunakan Wallpaper & Latar Belakang Kustom</label>
                        <span class="text-[11px] text-slate-400">Timpa tema dengan gambar atau warna sendiri</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs mb-3">
                        <div>
                            <label class="block font-semibold text-slate-500 mb-1">Tipe Latar</label>
                            <select id="customBgType" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none bg-white font-bold text-xs">
                                <option value="theme" <?= ($site['custom_bg_type'] ?? 'theme') === 'theme' ? 'selected' : '' ?>>Gunakan Tema Preset Di Atas</option>
                                <option value="image" <?= ($site['custom_bg_type'] ?? '') === 'image' ? 'selected' : '' ?>>Gambar Wallpaper (URL)</option>
                                <option value="color" <?= ($site['custom_bg_type'] ?? '') === 'color' ? 'selected' : '' ?>>Warna Solid (Hex)</option>
                                <option value="gradient" <?= ($site['custom_bg_type'] ?? '') === 'gradient' ? 'selected' : '' ?>>Gradien CSS</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block font-semibold text-slate-500 mb-1">URL Wallpaper / Nilai Warna (Hex / CSS)</label>
                            <input type="text" id="customBgValue" value="<?= htmlspecialchars($site['custom_bg_value'] ?? '') ?>" placeholder="https://... atau #D9D9D9" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none text-xs">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-2 border-t border-slate-100">
                        <div>
                            <div class="flex justify-between font-semibold text-slate-600 mb-1">
                                <span>Efek Blur Wallpaper</span>
                                <span id="bgBlurVal"><?= (int)($site['bg_blur'] ?? 0) ?>px</span>
                            </div>
                            <input type="range" id="bgBlur" min="0" max="25" value="<?= (int)($site['bg_blur'] ?? 0) ?>" oninput="document.getElementById('bgBlurVal').innerText = this.value + 'px'" class="w-full accent-blue-600">
                        </div>
                        <div>
                            <div class="flex justify-between font-semibold text-slate-600 mb-1">
                                <span>Kegelapan Overlay (Dimming)</span>
                                <span id="bgDarkVal"><?= (int)($site['bg_dark'] ?? 0) ?>%</span>
                            </div>
                            <input type="range" id="bgDark" min="0" max="90" value="<?= (int)($site['bg_dark'] ?? 0) ?>" oninput="document.getElementById('bgDarkVal').innerText = this.value + '%'" class="w-full accent-blue-600">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Media Sosial & Kontak -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-pink-50 text-pink-600">
                        <span class="material-symbols-rounded text-lg">share</span>
                    </span>
                    <span>5. Ikon Media Sosial & Kontak</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">WhatsApp (Nomor HP / Tautan)</label>
                        <input type="text" id="socWa" value="<?= htmlspecialchars($socials['whatsapp'] ?? '') ?>" placeholder="0812xxxx" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Instagram (@username)</label>
                        <input type="text" id="socIg" value="<?= htmlspecialchars($socials['instagram'] ?? '') ?>" placeholder="@smplabschooljakarta" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">YouTube URL</label>
                        <input type="url" id="socYt" value="<?= htmlspecialchars($socials['youtube'] ?? '') ?>" placeholder="https://youtube.com/..." class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">Email Sekolah / Kontak</label>
                        <input type="email" id="socEmail" value="<?= htmlspecialchars($socials['email'] ?? '') ?>" placeholder="kontak@labschool.xyz" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>
            </div>

                        <!-- 7. Pengaturan Lanjutan (SEO, Google Analytics & Footer) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-xl bg-cyan-50 text-cyan-600">
                        <span class="material-symbols-rounded text-lg">tune</span>
                    </span>
                    <span>7. Pengaturan Lanjutan (SEO, Google Analytics &amp; Footer)</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Judul SEO (Meta Title)</label>
                        <input type="text" id="seoTitle" value="<?= htmlspecialchars($site['seo_title'] ?? '') ?>" placeholder="Judul saat dibagikan ke WhatsApp / Google..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Gambar Social Share (OG Image)</label>
                        <input type="url" id="seoImage" value="<?= htmlspecialchars($site['seo_image'] ?? '') ?>" placeholder="https://... (thumbnail link preview WhatsApp)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Google Analytics / GTM Tracking ID</label>
                        <input type="text" id="gtagId" value="<?= htmlspecialchars($site['gtag_id'] ?? '') ?>" placeholder="contoh: G-XXXXXXXXXX atau GTM-XXXXXXX" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">Lacak statistik pengunjung dan performa klik langsung di dashboard Google Analytics Anda.</p>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Teks Footer Kustom</label>
                        <input type="text" id="customFooter" value="<?= htmlspecialchars($site['custom_footer'] ?? 'SMP Labschool Jakarta') ?>" placeholder="SMP Labschool Jakarta" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-slate-600 mb-1">Deskripsi SEO (Meta Description)</label>
                    <textarea id="seoDescription" rows="2" placeholder="Deskripsi ringkas yang tampil di pencarian Google atau kartu tautan media sosial..." class="w-full px-3.5 py-2 rounded-xl border border-slate-300 outline-none"><?= htmlspecialchars($site['seo_description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- 6. Daftar Blok Komponen (Dengan Drawer Interaktif) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-xl bg-emerald-50 text-emerald-600">
                                <span class="material-symbols-rounded text-lg">view_stream</span>
                            </span>
                            <span>6. Daftar Blok Komponen</span>
                            <span id="blockCountBadge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">0 Blok</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Kelola tombol tautan, video, banner pengumuman, vCard, PDF, dan FAQ.</p>
                    </div>

                    <!-- Trigger Tombol Drawer Interaktif -->
                    <button type="button" onclick="openBlockDrawer()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-rounded text-base">add</span>
                        <span>Tambah Blok</span>
                    </button>
                </div>

                <div id="blocksContainer" class="space-y-3">
                    <!-- Dynamic Blocks Rendered via JS -->
                </div>
            </div>
        </div>

        <!-- Kolom Live Mockup Preview (5 Kolom) -->
        <div class="lg:col-span-5 hidden lg:block sticky top-24 self-start">
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Simulasi Pratinjau Seluler</span>
                    <button type="button" onclick="refreshPreview()" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                        <span class="material-symbols-rounded text-sm">refresh</span>
                        <span>Muat Ulang</span>
                    </button>
                </div>

                <!-- Mockup Smartphone Frame -->
                <div class="w-[320px] h-[580px] mx-auto rounded-[40px] border-[10px] border-slate-900 bg-slate-950 shadow-2xl overflow-hidden relative">
                    <div class="absolute top-2 left-1/2 -translate-x-1/2 w-24 h-4 bg-slate-800 rounded-full z-20"></div>
                    <iframe id="previewIframe" src="<?= $siteId ? '/p/' . htmlspecialchars($site['slug']) : 'about:blank' ?>" class="w-full h-full border-0 bg-slate-950"></iframe>
                </div>
                <p class="text-xs text-slate-400 mt-4">Pratinjau otomatis sinkron setelah Anda menekan tombol <strong>Simpan & Terbitkan</strong>.</p>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- INTERACTIVE SLIDE-OVER DRAWER: TAMBAH BLOK KOMPONEN     -->
    <!-- ======================================================== -->
    <div id="drawerBackdrop" onclick="handleDrawerBackdropClick(event)" class="drawer-backdrop fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex justify-end">
        <div id="drawerPanel" class="drawer-panel w-full max-w-md bg-white h-full shadow-2xl flex flex-col justify-between overflow-y-auto">
            
            <!-- Drawer Header -->
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50 sticky top-0 z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                        <span class="material-symbols-rounded text-xl">add_box</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Tambah Blok Komponen</h3>
                        <p class="text-xs text-slate-500">Pilih komponen interaktif untuk disematkan</p>
                    </div>
                </div>
                <button type="button" onclick="closeBlockDrawer()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition">
                    <span class="material-symbols-rounded text-xl">close</span>
                </button>
            </div>

            <!-- Drawer Cards Body -->
            <div class="p-5 space-y-3 flex-1 overflow-y-auto">
                <div class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">Pilihan Elemen Interaktif</div>

                <!-- 1. Tombol Tautan -->
                <button type="button" onclick="selectBlockFromDrawer('link')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">link</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-blue-600 transition-colors flex items-center gap-1.5">
                            <span>Tombol Tautan (Link Button)</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700">Populer</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Tautan interaktif ke portal sekolah, formulir Google Drive, LMS, atau website dengan opsi animasi pulse.</p>
                    </div>
                </button>

                <!-- 2. Video YouTube -->
                <button type="button" onclick="selectBlockFromDrawer('youtube')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-red-500 hover:bg-red-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">play_circle</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-red-600 transition-colors flex items-center gap-1.5">
                            <span>Video YouTube Responsif</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700">Media</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Sematkan video perkenalan, dokumentasi sekolah, tutorial, atau materi belajar langsung di halaman.</p>
                    </div>
                </button>

                <!-- 3. Pengumuman / Teks Banner -->
                <button type="button" onclick="selectBlockFromDrawer('text')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-amber-500 hover:bg-amber-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">campaign</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-amber-600 transition-colors flex items-center gap-1.5">
                            <span>Pengumuman / Teks Banner</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Info</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Teks pesan penting berbingkai dengan badge penyorot untuk jadwal, edaran, atau informasi mendesak.</p>
                    </div>
                </button>

                <!-- 4. Kontak & Kartu Nama vCard -->
                <button type="button" onclick="selectBlockFromDrawer('vcard')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">contact_phone</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-emerald-600 transition-colors flex items-center gap-1.5">
                            <span>Simpan Kontak (vCard Digital)</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700">Digital Card</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Tombol one-click unduh berkas kontak (.vcf) otomatis ke buku telepon smartphone pengguna.</p>
                    </div>
                </button>

                <!-- 5. Unduhan Dokumen / PDF -->
                <button type="button" onclick="selectBlockFromDrawer('pdf')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">description</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-indigo-600 transition-colors flex items-center gap-1.5">
                            <span>Dokumen / Unduhan PDF</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700">Berkas</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Tautan download formulir pendaftaran, e-brosur, silabus kurikulum, atau panduan materi sekolah.</p>
                    </div>
                </button>

                <!-- 6. FAQ Akordion -->
                <button type="button" onclick="selectBlockFromDrawer('faq')" class="w-full p-4 rounded-2xl border border-slate-200 hover:border-purple-500 hover:bg-purple-50/40 transition text-left flex items-start gap-3.5 group">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-rounded text-xl">quiz</span>
                    </div>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-purple-600 transition-colors flex items-center gap-1.5">
                            <span>FAQ / Tanya Jawab (Akordion)</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-700">Interaktif</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 leading-snug">Daftar pertanyaan dan jawaban yang rapi serta dapat diklik buka-tutup dengan lancar.</p>
                    </div>
                </button>
            </div>

            <!-- Drawer Footer -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-400">Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[10px]">Esc</kbd> untuk menutup</span>
                <button type="button" onclick="closeBlockDrawer()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: GANTI TEMPLATE CEPAT                             -->
    <!-- ======================================================== -->
    <div id="modalTemplatePicker" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Pilih Template Halaman</h3>
                    <p class="text-xs text-slate-500">Pilih template awal untuk mempercepat perancangan Page Anda.</p>
                </div>
                <button type="button" onclick="closeTemplatePickerModal()" class="p-1 rounded-xl text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-rounded text-xl">close</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <?php foreach ($templatePresets as $key => $tp): ?>
                    <div class="p-4 rounded-2xl border <?= $key === $selectedTemplateKey ? 'border-blue-600 bg-blue-50/30 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300' ?> flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase"><?= htmlspecialchars($tp['category']) ?></span>
                                <?php if ($key === $selectedTemplateKey): ?>
                                    <span class="text-[10px] font-bold text-blue-600 flex items-center gap-0.5"><span class="material-symbols-rounded text-xs">check_circle</span> Aktif</span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-xs font-extrabold text-slate-900"><?= htmlspecialchars($tp['name']) ?></h4>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($tp['bio'] ?: 'Template kanvas bersih tanpa konten awal.') ?></p>
                        </div>
                        <a href="/page_builder.php?template=<?= $key ?>" onclick="return confirm('Menerapkan template ini akan memuat ulang preset awal. Lanjutkan?')" class="w-full text-center py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs transition">
                            Gunakan Template Ini
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Script Block Management, Drawer, and Save -->
    <script>
        const initialBlocks = <?= json_encode($blocks) ?>;
        const siteId = <?= (int)$siteId ?>;

        let blocksData = initialBlocks.length ? initialBlocks.map(b => {
            let meta = {};
            try {
                meta = b.metadata ? (typeof b.metadata === 'string' ? JSON.parse(b.metadata) : b.metadata) : {};
            } catch(e) {}
            return {
                block_type: b.block_type || 'link',
                title: b.title || '',
                subtitle: b.subtitle || '',
                target_url: b.target_url || '',
                icon: b.icon || 'link',
                animation: b.animation || 'none',
                image_icon: meta.image_icon || b.image_icon || '',
                button_color: meta.button_color || b.button_color || '',
                button_text_color: meta.button_text_color || b.button_text_color || '',
                button_border_color: meta.button_border_color || b.button_border_color || '',
                button_type: meta.button_type || b.button_type || 'default',
                text_position: meta.text_position || b.text_position || 'left',
                password_active: meta.password_active || b.password_active || false,
                password: meta.password || b.password || '',
                keep_in_page: meta.keep_in_page || b.keep_in_page || false
            };
        }) : [
            { block_type: 'link', title: 'Portal Resmi SMP Labschool Jakarta', subtitle: 'smplabschooljakarta.sch.id', target_url: 'https://smplabschooljakarta.sch.id', animation: 'none', image_icon: '', button_color: '', button_text_color: '', button_type: 'default', text_position: 'left', password_active: false, password: '', keep_in_page: false }
        ];

        // Drawer Controls
        function openBlockDrawer() {
            const backdrop = document.getElementById('drawerBackdrop');
            const panel = document.getElementById('drawerPanel');
            backdrop.classList.add('active');
            panel.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeBlockDrawer() {
            const backdrop = document.getElementById('drawerBackdrop');
            const panel = document.getElementById('drawerPanel');
            backdrop.classList.remove('active');
            panel.classList.remove('active');
            document.body.style.overflow = '';
        }

        function handleDrawerBackdropClick(e) {
            if (e.target.id === 'drawerBackdrop') {
                closeBlockDrawer();
            }
        }

        // Close drawer on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeBlockDrawer();
                closeTemplatePickerModal();
            }
        });

        // Template Picker Modal Controls
        function openTemplatePickerModal() {
            const backdrop = document.getElementById('templateModalBackdrop');
            const panel = document.getElementById('templateModalPanel');
            backdrop.classList.add('active');
            panel.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeTemplatePickerModal() {
            const backdrop = document.getElementById('templateModalBackdrop');
            const panel = document.getElementById('templateModalPanel');
            backdrop.classList.remove('active');
            panel.classList.remove('active');
            document.body.style.overflow = '';
        }

        function selectBlockFromDrawer(type) {
            addBlock(type);
            closeBlockDrawer();

            // Smooth scroll to the newest block
            setTimeout(() => {
                const container = document.getElementById('blocksContainer');
                if (container && container.lastElementChild) {
                    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        function renderBlocks() {
            const container = document.getElementById('blocksContainer');
            container.innerHTML = '';

            const badge = document.getElementById('blockCountBadge');
            if (badge) badge.innerText = `${blocksData.length} Blok`;

            if (blocksData.length === 0) {
                container.innerHTML = `
                    <div class="py-8 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                        <span class="material-symbols-rounded text-3xl text-slate-400">widgets</span>
                        <p class="text-xs text-slate-500 font-bold mt-1">Belum ada blok komponen</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Buka drawer komponen untuk menambahkan tombol, video, atau banner promosi.</p>
                        <button type="button" onclick="openBlockDrawer()" class="mt-3 px-3.5 py-1.5 rounded-xl bg-blue-600 text-white font-extrabold text-xs inline-flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-rounded text-sm">add</span>
                            <span>Buka Drawer Komponen</span>
                        </button>
                    </div>
                `;
                return;
            }

            blocksData.forEach((b, idx) => {
                const el = document.createElement('div');
                el.className = 'p-4 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-3 relative group transition hover:border-slate-300';
                
                let badgeClass = 'bg-blue-100 text-blue-700';
                let iconSymbol = 'link';
                let labelTitle = 'Judul Tombol Tautan';
                let labelTarget = 'Alamat URL Target';

                if (b.block_type === 'youtube') {
                    badgeClass = 'bg-red-100 text-red-700';
                    iconSymbol = 'play_circle';
                    labelTitle = 'Judul Video';
                    labelTarget = 'URL Video YouTube';
                } else if (b.block_type === 'text') {
                    badgeClass = 'bg-amber-100 text-amber-800';
                    iconSymbol = 'campaign';
                    labelTitle = 'Judul Pengumuman';
                    labelTarget = 'Rincian / Teks Pengumuman';
                } else if (b.block_type === 'vcard') {
                    badgeClass = 'bg-emerald-100 text-emerald-800';
                    iconSymbol = 'contact_phone';
                    labelTitle = 'Label Tombol Kontak';
                    labelTarget = 'Deskripsi Singkat';
                } else if (b.block_type === 'pdf') {
                    badgeClass = 'bg-indigo-100 text-indigo-800';
                    iconSymbol = 'description';
                    labelTitle = 'Nama Dokumen / Berkas';
                    labelTarget = 'URL Unduhan File (PDF)';
                } else if (b.block_type === 'faq') {
                    badgeClass = 'bg-purple-100 text-purple-800';
                    iconSymbol = 'quiz';
                    labelTitle = 'Pertanyaan (FAQ)';
                    labelTarget = 'Jawaban FAQ';
                } else if (b.block_type === 'image') {
                    badgeClass = 'bg-rose-100 text-rose-800';
                    iconSymbol = 'image';
                    labelTitle = 'Keterangan Banner (Opsional)';
                    labelTarget = 'URL Target Klik (Opsional)';
                }

                el.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-black ${badgeClass}">
                            <span class="material-symbols-rounded text-sm">${iconSymbol}</span>
                            <span>${b.block_type.toUpperCase()}</span>
                        </span>
                        <div class="flex items-center gap-1 text-slate-400">
                            <button type="button" onclick="moveBlock(${idx}, -1)" class="p-1 rounded hover:bg-slate-200 hover:text-slate-800 transition" title="Geser ke Atas">
                                <span class="material-symbols-rounded text-base">arrow_upward</span>
                            </button>
                            <button type="button" onclick="moveBlock(${idx}, 1)" class="p-1 rounded hover:bg-slate-200 hover:text-slate-800 transition" title="Geser ke Bawah">
                                <span class="material-symbols-rounded text-base">arrow_downward</span>
                            </button>
                            <button type="button" onclick="deleteBlock(${idx})" class="p-1 rounded text-red-400 hover:bg-red-50 hover:text-red-600 ml-1 transition" title="Hapus Blok">
                                <span class="material-symbols-rounded text-base">delete</span>
                            </button>
                        </div>
                    </div>

                    ${b.block_type === 'image' ? `
                    <div class="space-y-2.5 text-xs">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">URL Gambar Banner *</label>
                            <div class="flex items-center gap-2">
                                <input type="url" value="${escapeHtml(b.subtitle || '')}" oninput="updateBlockData(${idx}, 'subtitle', this.value); updateBlockData(${idx}, 'target_url', b.target_url || this.value);" placeholder="https://... (URL file gambar poster/banner)" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-rose-500 bg-white">
                                ${b.subtitle ? `<img src="${escapeHtml(b.subtitle)}" class="w-10 h-10 object-cover rounded-xl border border-slate-200 shrink-0">` : ''}
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block font-semibold text-slate-500 mb-1">Judul / Caption (Opsional)</label>
                                <input type="text" value="${escapeHtml(b.title || '')}" oninput="updateBlockData(${idx}, 'title', this.value)" placeholder="Keterangan singkat di bawah banner..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-500 mb-1">Alamat URL Target Saat Diklik (Opsional)</label>
                                <input type="url" value="${escapeHtml(b.target_url && b.target_url !== b.subtitle ? b.target_url : '')}" oninput="updateBlockData(${idx}, 'target_url', this.value)" placeholder="https://..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 outline-none bg-white">
                            </div>
                        </div>
                    </div>
                    ` : `
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">${labelTitle} *</label>
                            <input type="text" value="${escapeHtml(b.title)}" oninput="updateBlockData(${idx}, 'title', this.value)" placeholder="Ketik judul..." class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-blue-600 bg-white">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">${labelTarget}</label>
                            ${b.block_type === 'text' || b.block_type === 'faq' ? `
                                <textarea rows="1" oninput="updateBlockData(${idx}, 'subtitle', this.value)" placeholder="Isi rincian lengkap..." class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-blue-600 bg-white">${escapeHtml(b.subtitle || b.target_url)}</textarea>
                            ` : `
                                <input type="text" value="${escapeHtml(b.target_url)}" oninput="updateBlockData(${idx}, 'target_url', this.value)" placeholder="https://..." class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none focus:border-blue-600 bg-white">
                            `}
                        </div>
                    </div>
                    `}

                    ${(b.block_type === 'link' || b.block_type === 'pdf') ? `
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs pt-1 border-t border-slate-200/60">
                        <div>
                            <label class="block font-semibold text-slate-500 mb-1">Subjudul / Keterangan Tambahan</label>
                            <input type="text" value="${escapeHtml(b.subtitle)}" oninput="updateBlockData(${idx}, 'subtitle', this.value)" placeholder="Contoh: portal.labschool.xyz" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 outline-none focus:border-blue-600 bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-500 mb-1">Efek Animasi Tombol</label>
                            <select onchange="updateBlockData(${idx}, 'animation', this.value)" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 outline-none bg-white">
                                <option value="none" ${b.animation === 'none' ? 'selected' : ''}>Tanpa Animasi</option>
                                <option value="pulse" ${b.animation === 'pulse' ? 'selected' : ''}>Pulse Highlight (Denyut Berkilau)</option>
                            </select>
                        </div>
                    </div>` : ''}

                    ${b.block_type === 'link' ? `
                    <!-- Accordion Kustomisasi Lanjutan S.id -->
                    <details class="text-xs pt-1 border-t border-slate-200/60 group/opt">
                        <summary class="cursor-pointer font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 select-none py-1">
                            <span class="material-symbols-rounded text-sm">tune</span>
                            <span>Kustomisasi Desain &amp; Keamanan Lanjutan (Fitur Lengkap s.id)</span>
                        </summary>
                        <div class="mt-2.5 p-3 rounded-xl bg-white border border-slate-200/80 space-y-3">
                            <!-- Logo Thumbnail Image -->
                            <div>
                                <label class="block font-bold text-slate-600 mb-1">URL Gambar Logo / Thumbnail Tombol (Opsional)</label>
                                <div class="flex items-center gap-2">
                                    <input type="url" value="${escapeHtml(b.image_icon || '')}" oninput="updateBlockData(${idx}, 'image_icon', this.value)" placeholder="https://... (contoh: logo Dapodik, E-Rapor, SIMUTU)" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 outline-none focus:border-blue-600 text-xs">
                                    ${b.image_icon ? `<img src="${escapeHtml(b.image_icon)}" class="w-7 h-7 object-contain rounded-lg border border-slate-200 shrink-0">` : ''}
                                </div>
                                <p class="text-[10px] text-slate-400 mt-0.5">Mendukung format PNG / WebP transparan untuk logo instansi atau sistem sekolah.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <!-- Warna Latar Tombol -->
                                <div>
                                    <label class="block font-semibold text-slate-500 mb-1">Warna Tombol (Hex)</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="color" value="${b.button_color || '#c8d3d8'}" onchange="updateBlockData(${idx}, 'button_color', this.value)" class="w-7 h-7 rounded cursor-pointer border border-slate-300 p-0.5">
                                        <input type="text" value="${escapeHtml(b.button_color || '')}" oninput="updateBlockData(${idx}, 'button_color', this.value)" placeholder="#c8d3d8" class="w-full px-2 py-1 rounded-lg border border-slate-300 text-xs font-mono">
                                    </div>
                                </div>
                                <!-- Gaya Tombol -->
                                <div>
                                    <label class="block font-semibold text-slate-500 mb-1">Gaya Tombol</label>
                                    <select onchange="updateBlockData(${idx}, 'button_type', this.value)" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                        <option value="default" ${b.button_type === 'default' ? 'selected' : ''}>Bawaan Tema</option>
                                        <option value="solid" ${b.button_type === 'solid' ? 'selected' : ''}>Solid Terisi</option>
                                        <option value="outline" ${b.button_type === 'outline' ? 'selected' : ''}>Outline Transparan</option>
                                    </select>
                                </div>
                                <!-- Posisi Teks -->
                                <div>
                                    <label class="block font-semibold text-slate-500 mb-1">Perataan Teks</label>
                                    <select onchange="updateBlockData(${idx}, 'text_position', this.value)" class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                                        <option value="left" ${b.text_position === 'left' ? 'selected' : ''}>Rata Kiri</option>
                                        <option value="center" ${b.text_position === 'center' ? 'selected' : ''}>Rata Tengah (Center)</option>
                                        <option value="right" ${b.text_position === 'right' ? 'selected' : ''}>Rata Kanan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Proteksi Password / PIN -->
                                <div class="p-2.5 rounded-xl border border-amber-200/70 bg-amber-50/50 space-y-1.5">
                                    <label class="flex items-center gap-1.5 cursor-pointer font-bold text-amber-900 select-none">
                                        <input type="checkbox" ${b.password_active ? 'checked' : ''} onchange="updateBlockData(${idx}, 'password_active', this.checked); renderBlocks();" class="rounded text-amber-600 focus:ring-amber-500">
                                        <span>🔒 Kunci dengan Sandi / PIN</span>
                                    </label>
                                    ${b.password_active ? `
                                        <input type="text" value="${escapeHtml(b.password || '')}" oninput="updateBlockData(${idx}, 'password', this.value)" placeholder="Ketik PIN / Sandi..." class="w-full px-2.5 py-1.5 rounded-lg border border-amber-300 bg-white text-xs font-bold outline-none">
                                        <p class="text-[10px] text-amber-700">Pengunjung harus memasukkan PIN ini untuk membuka tautan.</p>
                                    ` : `
                                        <p class="text-[10px] text-amber-600/80">Cocok untuk berkas soal ujian atau sistem internal guru.</p>
                                    `}
                                </div>

                                <!-- Keep in Page (Modal Iframe) -->
                                <div class="p-2.5 rounded-xl border border-blue-200/70 bg-blue-50/50 space-y-1.5">
                                    <label class="flex items-center gap-1.5 cursor-pointer font-bold text-blue-900 select-none">
                                        <input type="checkbox" ${b.keep_in_page ? 'checked' : ''} onchange="updateBlockData(${idx}, 'keep_in_page', this.checked)" class="rounded text-blue-600 focus:ring-blue-500">
                                        <span>📱 Buka di Halaman (In-Page Modal)</span>
                                    </label>
                                    <p class="text-[10px] text-blue-600/80">Tautan akan dibuka dalam pop-up web view di dalam halaman tanpa berpindah tab.</p>
                                </div>
                            </div>
                        </div>
                    </details>` : ''}
                `;
                container.appendChild(el);
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function addBlock(type) {
            if (type === 'youtube') {
                blocksData.push({ block_type: 'youtube', title: 'Video Dokumentasi Labschool', target_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', subtitle: '', animation: 'none' });
            } else if (type === 'text') {
                blocksData.push({ block_type: 'text', title: 'Pengumuman Penting', subtitle: 'Informasi kegiatan dan agenda sekolah minggu ini.', target_url: '', animation: 'none' });
            } else if (type === 'vcard') {
                blocksData.push({ block_type: 'vcard', title: 'Simpan Kontak ke HP (vCard)', subtitle: 'Simpan nomor resmi ke kontak ponsel', target_url: '/vcard.php', animation: 'pulse' });
            } else if (type === 'pdf') {
                blocksData.push({ block_type: 'pdf', title: 'Edaran / Buku Panduan (PDF)', subtitle: 'Unduh dokumen resmi sekolah', target_url: 'https://smplabschooljakarta.sch.id/panduan.pdf', animation: 'none' });
            } else if (type === 'faq') {
                blocksData.push({ block_type: 'faq', title: 'Berapa kuota penerimaan jalur PSB?', subtitle: 'Informasi kuota dan syarat pendaftaran dapat diakses melalui portal resmi Labschool.', target_url: '', animation: 'none' });
            } else if (type === 'image') {
                blocksData.push({ block_type: 'image', title: 'Banner Promosi Kegiatan', subtitle: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&q=80', target_url: 'https://smplabschooljakarta.sch.id', animation: 'none' });
            } else {
                blocksData.push({ block_type: 'link', title: 'Tautan Baru', subtitle: '', target_url: 'https://', animation: 'none', image_icon: '', button_color: '', button_text_color: '', button_type: 'default', text_position: 'left', password_active: false, password: '', keep_in_page: false });
            }
            renderBlocks();
        }

        function deleteBlock(index) {
            if (confirm('Hapus blok komponen ini?')) {
                blocksData.splice(index, 1);
                renderBlocks();
            }
        }

        function moveBlock(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= blocksData.length) return;
            const temp = blocksData[index];
            blocksData[index] = blocksData[newIndex];
            blocksData[newIndex] = temp;
            renderBlocks();
        }

        function updateBlockData(index, field, value) {
            if (blocksData[index]) {
                blocksData[index][field] = value;
            }
        }

        function refreshPreview() {
            const frame = document.getElementById('previewIframe');
            if (frame && frame.src && frame.src !== 'about:blank') {
                const url = new URL(frame.src);
                url.searchParams.set('t', Date.now());
                frame.src = url.toString();
            }
        }

        async function saveMicrosite() {
            const btn = document.getElementById('btnSave');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-rounded text-base animate-spin">refresh</span><span>Menyimpan...</span>';

            const payload = {
                id: siteId,
                slug: document.getElementById('siteSlug').value.trim(),
                title: document.getElementById('siteTitle').value.trim(),
                bio: document.getElementById('siteBio').value.trim(),
                avatar_url: document.getElementById('siteAvatar').value.trim(),
                header_bg_url: document.getElementById('siteHeaderBg').value.trim(),
                template_style: document.querySelector('input[name="template_style"]:checked')?.value || 'genially-showcase',
                countdown_title: document.getElementById('cdTitle').value.trim(),
                countdown_date: document.getElementById('cdDate').value,
                theme: document.querySelector('input[name="theme"]:checked')?.value || 'labs-navy',
                custom_bg_type: document.getElementById('customBgType').value,
                custom_bg_value: document.getElementById('customBgValue').value.trim(),
                bg_blur: parseInt(document.getElementById('bgBlur').value) || 0,
                bg_dark: parseInt(document.getElementById('bgDark').value) || 0,
                custom_settings: {
                    title_bold: document.getElementById('siteTitleBold').checked,
                    title_italic: document.getElementById('siteTitleItalic').checked,
                    bio_italic: document.getElementById('siteBioItalic').checked
                },
                seo_title: document.getElementById('seoTitle').value.trim(),
                seo_description: document.getElementById('seoDescription').value.trim(),
                seo_image: document.getElementById('seoImage').value.trim(),
                gtag_id: document.getElementById('gtagId').value.trim(),
                custom_footer: document.getElementById('customFooter').value.trim(),
                social_links: {
                    whatsapp: document.getElementById('socWa').value.trim(),
                    instagram: document.getElementById('socIg').value.trim(),
                    youtube: document.getElementById('socYt').value.trim(),
                    email: document.getElementById('socEmail').value.trim()
                },
                blocks: blocksData
            };

            if (!payload.slug) {
                alert('Silakan tentukan slug alamat URL (contoh: osis, openhouse).');
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-rounded text-base">save</span><span>Simpan & Terbitkan</span>';
                document.getElementById('siteSlug').focus();
                return;
            }

            if (!payload.title) {
                alert('Judul Page wajib diisi.');
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-rounded text-base">save</span><span>Simpan & Terbitkan</span>';
                document.getElementById('siteTitle').focus();
                return;
            }

            try {
                const res = await fetch('/api/save_page.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('Page berhasil disimpan dan diterbitkan!');
                    if (json.data && json.data.id && !siteId) {
                        window.location.href = '/page_builder.php?id=' + json.data.id;
                    } else {
                        document.getElementById('previewIframe').src = json.data.bio_url + '?t=' + Date.now();
                    }
                } else {
                    alert('Gagal menyimpan: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi atau jaringan saat menyimpan.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-rounded text-base">save</span><span>Simpan & Terbitkan</span>';
            }
        }

        renderBlocks();</script>
</body>
</html>
