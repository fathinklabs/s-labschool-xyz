<?php
/**
 * Public Microsite / Interactive Bio Link Viewer: s.labschool.xyz/p/{slug}
 * Modern Genially-Style Interactive Experience for SMP Labschool Jakarta
 * (Interactive Popovers, Roadmaps, Grid Showcases, Event Countdown & Glassmorphism)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: /');
    exit;
}

$cleanSlug = sanitize_slug($slug);

$stmt = $pdo->prepare("SELECT * FROM microsites WHERE slug = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$cleanSlug]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(404);
    die('Halaman Bio Link tidak ditemukan.');
}

// Ambil blok-blok konten
$stmtBlocks = $pdo->prepare("SELECT * FROM microsite_blocks WHERE microsite_id = ? AND is_active = 1 ORDER BY display_order ASC");
$stmtBlocks->execute([$site['id']]);
$blocks = $stmtBlocks->fetchAll();

$socials = !empty($site['social_links']) ? json_decode($site['social_links'], true) : [];

// Pengaturan Tema Genially
$theme = $site['theme'] ?? 'labs-navy';
$templateStyle = $site['template_style'] ?? 'genially-showcase';

$themeClasses = [
    'labs-navy' => [
        'id'         => 'labs-navy',
        'name'       => 'Labs Navy',
        'bg'         => 'bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 text-white',
        'card'       => 'bg-white/[0.08] hover:bg-white/[0.14] border-white/10 text-white backdrop-blur-xl hover:border-blue-400/40 shadow-xl shadow-blue-950/40',
        'btn_prim'   => 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-600/30',
        'badge'      => 'bg-blue-500/20 text-blue-300 border-blue-400/30',
        'accent'     => 'text-blue-400',
        'glow'       => 'rgba(37, 99, 235, 0.25)',
        'font'       => 'font-sans',
        'title_font' => 'font-black'
    ],
    'genially-cosmic' => [
        'id'         => 'genially-cosmic',
        'name'       => 'Cosmic Neon',
        'bg'         => 'bg-gradient-to-br from-[#0B0F19] via-[#1E1B4B] to-[#0A051E] text-white',
        'card'       => 'bg-white/[0.06] hover:bg-white/[0.12] border-purple-500/20 text-white backdrop-blur-xl hover:border-cyan-400/50 shadow-xl shadow-purple-950/50',
        'btn_prim'   => 'bg-gradient-to-r from-cyan-500 to-purple-600 hover:from-cyan-400 hover:to-purple-500 text-white shadow-lg shadow-purple-500/30',
        'badge'      => 'bg-cyan-500/20 text-cyan-300 border-cyan-400/30',
        'accent'     => 'text-cyan-400',
        'glow'       => 'rgba(6, 182, 212, 0.25)',
        'font'       => 'font-sans',
        'title_font' => 'font-black'
    ],
    'labs-gold' => [
        'id'         => 'labs-gold',
        'name'       => 'Labs Gold',
        'bg'         => 'bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 text-white',
        'card'     => 'bg-amber-950/20 hover:bg-amber-900/30 border-amber-500/20 text-amber-100 backdrop-blur-xl hover:border-amber-400/40 shadow-xl shadow-amber-950/40',
        'btn_prim' => 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold shadow-lg shadow-amber-500/20',
        'badge'    => 'bg-amber-500/20 text-amber-300 border-amber-400/30',
        'accent'   => 'text-amber-400',
        'glow'     => 'rgba(245, 158, 11, 0.25)',
        'font'       => 'font-sans',
        'title_font' => 'font-black'
    ],
    'emerald' => [
        'id'         => 'emerald',
        'name'       => 'Emerald',
        'bg'         => 'bg-gradient-to-br from-slate-950 via-emerald-950 to-slate-900 text-white',
        'card'     => 'bg-emerald-900/20 hover:bg-emerald-900/30 border-emerald-500/20 text-emerald-100 backdrop-blur-xl hover:border-emerald-400/40 shadow-xl shadow-emerald-950/40',
        'btn_prim' => 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/30',
        'badge'    => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
        'accent'   => 'text-emerald-400',
        'glow'     => 'rgba(16, 185, 129, 0.25)',
        'font'       => 'font-sans',
        'title_font' => 'font-black'
    ],
    'slate' => [
        'id'         => 'slate',
        'name'       => 'Clean Slate',
        'bg'         => 'bg-slate-100 text-slate-800',
        'card'     => 'bg-white hover:bg-slate-50 border-slate-200/90 text-slate-800 shadow-md hover:border-blue-500/40',
        'btn_prim' => 'bg-slate-900 hover:bg-slate-800 text-white shadow-md',
        'badge'    => 'bg-slate-200 text-slate-700 border-slate-300',
        'accent'   => 'text-blue-600',
        'glow'     => 'rgba(15, 23, 42, 0.1)',
        'font'       => 'font-sans',
        'title_font' => 'font-black'
    ],

    // ========================================================
    // TEMA EKSKLUSIF HASIL TEMPLATE MICROSITE KHUSUS
    // ========================================================

    // 1. Modern Web Design Studio (Catamaran + Fredoka, Ocean Blueprint Grid & Glassmorphism)
    'web-design-studio' => [
        'id'         => 'web-design-studio',
        'name'       => 'Web Studio (Ocean Blueprint)',
        'bg'         => 'theme-web-studio text-white',
        'card'       => 'bg-white/[0.12] hover:bg-white/[0.22] border border-white/20 text-white backdrop-blur-2xl hover:border-sky-300/70 shadow-2xl shadow-blue-950/60 hover:shadow-sky-500/25 transition-all duration-300',
        'btn_prim'   => 'bg-gradient-to-r from-blue-500 via-indigo-500 to-sky-400 hover:from-blue-600 hover:to-sky-500 text-white font-extrabold shadow-lg shadow-blue-600/35 transition',
        'badge'      => 'bg-sky-400/20 text-sky-200 border-sky-300/40',
        'accent'     => 'text-sky-300',
        'glow'       => 'rgba(56, 189, 248, 0.35)',
        'font'       => 'font-catamaran',
        'title_font' => 'font-fredoka'
    ],

    // 2. Awesome UI/UX & Creative Agency (Nexa/Roboto, High-Tech Studio Canvas, Laptop Mockup)
    'creative-agency' => [
        'id'         => 'creative-agency',
        'name'       => 'Creative Agency (Light Tech)',
        'bg'         => 'theme-creative-agency text-slate-800',
        'card'       => 'bg-white/95 hover:bg-white border border-slate-200/90 text-slate-800 backdrop-blur-xl shadow-xl shadow-indigo-500/10 hover:shadow-2xl hover:border-indigo-400/60 hover:shadow-indigo-500/20 transition-all duration-300',
        'btn_prim'   => 'bg-gradient-to-r from-[#1873fb] to-[#664cf0] hover:from-[#1565e6] hover:to-[#573ed9] text-white font-extrabold shadow-lg shadow-indigo-500/30 transition',
        'badge'      => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'accent'     => 'text-[#664cf0]',
        'glow'       => 'rgba(102, 76, 240, 0.25)',
        'font'       => 'font-roboto',
        'title_font' => 'font-jakarta font-black'
    ],

    // 3. Hand-Drawn Graphic Designer (Gasoek One + Poppins, Neo-Pop Sky Blue & Comic Shadows)
    'artist-doodle' => [
        'id'         => 'artist-doodle',
        'name'       => 'Artist Doodle (Pop Sky Blue)',
        'bg'         => 'theme-artist-doodle text-slate-900',
        'card'       => 'bg-white border-2 border-slate-900 text-slate-900 shadow-[4px_4px_0px_#0f0e0e] hover:shadow-[6px_6px_0px_#0f0e0e] hover:-translate-x-0.5 hover:-translate-y-0.5 transition-all duration-200',
        'btn_prim'   => 'bg-[#fdb849] hover:bg-[#e09e36] border-2 border-slate-900 text-slate-950 font-black shadow-[3px_3px_0px_#0f0e0e] active:translate-x-1 active:translate-y-1 active:shadow-none transition-all',
        'badge'      => 'bg-[#fdb849] text-slate-950 border-2 border-slate-900 shadow-[2px_2px_0px_#0f0e0e] rotate-[-1deg]',
        'accent'     => 'text-amber-300',
        'glow'       => 'rgba(253, 184, 73, 0.35)',
        'font'       => 'font-poppins',
        'title_font' => 'font-gasoek'
    ]
];

$activeTheme = $themeClasses[$theme] ?? $themeClasses['labs-navy'];

$seoTitle = !empty($site['seo_title']) ? $site['seo_title'] : $site['title'] . ' — SMP Labschool Jakarta';
$seoDesc  = !empty($site['seo_description']) ? $site['seo_description'] : ($site['bio'] ?: 'Official Interactive Microsite SMP Labschool Jakarta');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
    <link rel="icon" href="<?= htmlspecialchars($site['avatar_url'] ?: '/assets/img/logo-smp-labschool.png') ?>" type="image/png">
    
    <!-- OpenGraph SEO -->
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta property="og:url" content="<?= base_url('p/' . $cleanSlug) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($site['seo_image'] ?: '/assets/img/logo-smp-labschool.png') ?>">

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
                        fredoka: ['"Fredoka"', 'cursive', 'sans-serif'],
                        catamaran: ['"Catamaran"', 'sans-serif'],
                        gasoek: ['"Gasoek One"', 'cursive', 'sans-serif'],
                        poppins: ['"Poppins"', 'sans-serif'],
                        roboto: ['"Roboto"', 'sans-serif'],
                        jakarta: ['"Plus Jakarta Sans"', 'sans-serif'],
                        symbols: ['"Material Symbols Rounded"', 'sans-serif']
                    },
                    keyframes: {
                        pulseCustom: {
                            '0%, 100%': { transform: 'scale(1)' },
                            '50%': { transform: 'scale(1.02)' }
                        },
                        floatAnimation: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-6px)' }
                        },
                        shimmer: {
                            '100%': { transform: 'translateX(100%)' }
                        }
                    },
                    animation: {
                        'pulse-custom': 'pulseCustom 2.2s infinite ease-in-out',
                        'float-soft': 'floatAnimation 4s ease-in-out infinite'
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
        .genially-glass {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* 1. Theme: Web Design Studio */
        .theme-web-studio {
            background-color: #1a2e56;
            background-image: 
                radial-gradient(circle at 12% 18%, rgba(89, 154, 217, 0.40) 0%, transparent 40%),
                radial-gradient(circle at 88% 82%, rgba(46, 83, 148, 0.50) 0%, transparent 45%),
                linear-gradient(to right, rgba(255, 255, 255, 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.06) 1px, transparent 1px),
                linear-gradient(135deg, #152548 0%, #1e3a75 50%, #2e5495 100%);
            background-size: 100% 100%, 100% 100%, 28px 28px, 28px 28px, 100% 100%;
            background-attachment: fixed;
        }

        /* 2. Theme: Creative Agency */
        .theme-creative-agency {
            background-color: #f1f2f7;
            background-image: 
                radial-gradient(circle at 85% 15%, rgba(102, 76, 240, 0.16) 0%, transparent 40%),
                radial-gradient(circle at 15% 85%, rgba(24, 115, 251, 0.14) 0%, transparent 40%),
                radial-gradient(rgba(102, 76, 240, 0.12) 1.5px, transparent 1.5px),
                linear-gradient(135deg, #f8f9fd 0%, #edf0f9 50%, #e2e6f4 100%);
            background-size: 100% 100%, 100% 100%, 22px 22px, 100% 100%;
            background-attachment: fixed;
        }

        /* 3. Theme: Artist Doodle */
        .theme-artist-doodle {
            background-color: #4aa5fd;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.45) 2px, transparent 2px),
                linear-gradient(to right, rgba(255, 255, 255, 0.20) 1.5px, transparent 1.5px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.20) 1.5px, transparent 1.5px);
            background-size: 24px 24px, 48px 48px, 48px 48px;
            background-attachment: fixed;
        }

        .font-fredoka { font-family: 'Fredoka', cursive, sans-serif; }
        .font-catamaran { font-family: 'Catamaran', sans-serif; }
        .font-roboto { font-family: 'Roboto', sans-serif; }
        .font-poppins { font-family: 'Poppins', sans-serif; }
        .font-gasoek { font-family: 'Gasoek One', cursive, sans-serif; }
        .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="<?= $activeTheme['bg'] ?> <?= $activeTheme['font'] ?? 'font-sans' ?> min-h-screen py-10 px-4 flex flex-col items-center justify-between selection:bg-blue-600 selection:text-white transition-colors duration-500 relative overflow-x-hidden">

    <!-- Ambient Glowing Background Aura (Dinamis Sesuai Tema) -->
    <?php if ($theme === 'web-design-studio'): ?>
        <div class="fixed top-0 left-1/4 -translate-x-1/2 w-[550px] h-[350px] bg-sky-400/25 rounded-full blur-[130px] pointer-events-none -z-10"></div>
        <div class="fixed bottom-0 right-10 w-[450px] h-[350px] bg-pink-400/20 rounded-full blur-[120px] pointer-events-none -z-10"></div>
    <?php elseif ($theme === 'creative-agency'): ?>
        <div class="fixed top-10 left-10 w-[500px] h-[350px] bg-[#664cf0]/18 rounded-full blur-[120px] pointer-events-none -z-10"></div>
        <div class="fixed bottom-10 right-10 w-[500px] h-[350px] bg-[#1873fb]/18 rounded-full blur-[120px] pointer-events-none -z-10"></div>
    <?php elseif ($theme === 'artist-doodle'): ?>
        <div class="fixed top-0 right-1/4 w-[450px] h-[350px] bg-[#fdb849]/30 rounded-full blur-[100px] pointer-events-none -z-10"></div>
        <div class="fixed bottom-0 left-10 w-[450px] h-[300px] bg-[#fdb5d1]/30 rounded-full blur-[100px] pointer-events-none -z-10"></div>
    <?php else: ?>
        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-blue-600/15 rounded-full blur-[120px] pointer-events-none -z-10"></div>
        <div class="fixed bottom-0 right-0 w-[400px] h-[300px] bg-purple-600/10 rounded-full blur-[100px] pointer-events-none -z-10"></div>
    <?php endif; ?>

    <main class="w-full max-w-xl mx-auto flex flex-col items-center z-10">
        
        <!-- Top Verified Bar Sesuai Karakter Tema -->
        <?php if ($theme === 'web-design-studio'): ?>
            <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs text-sky-200 font-extrabold tracking-wide shadow-lg">
                <span class="flex items-center gap-1.5 mr-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ffbd2e]"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#27c93f]"></span>
                </span>
                <span>⚡ WEB DESIGN &amp; DEVELOPMENT STUDIO</span>
            </div>
        <?php elseif ($theme === 'creative-agency'): ?>
            <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-full bg-white border border-slate-200/90 text-xs font-black tracking-wide text-slate-800 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-[#664cf0] animate-pulse"></span>
                <span>NEXTGEN UI/UX &bull; DIGITAL CREATIVE LAB</span>
                <span class="ml-1 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-extrabold text-[10px]">⭐ 99.8% Satisfaction</span>
            </div>
        <?php elseif ($theme === 'artist-doodle'): ?>
            <div class="inline-flex items-center gap-2 mb-4 px-4 py-1.5 rounded-2xl bg-[#fdb849] border-2 border-slate-900 shadow-[3px_3px_0px_#0f0e0e] text-xs font-black tracking-wide text-slate-950 rotate-[-1.5deg]">
                <span>✦ HAND-DRAWN GRAPHIC DESIGNER &amp; ARTIST ✦</span>
            </div>
        <?php else: ?>
            <div class="flex items-center gap-2 mb-6 px-3.5 py-1.5 rounded-full border <?= $activeTheme['badge'] ?> text-[11px] font-extrabold uppercase tracking-widest shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span>Official Labschool Microsite</span>
            </div>
        <?php endif; ?>

        <!-- Avatar Profil with Theme-Specific Framing -->
        <div class="relative mb-5 group animate-float-soft">
            <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-3xl overflow-hidden <?= $theme === 'artist-doodle' ? 'border-3 border-slate-900 shadow-[6px_6px_0px_#0f0e0e] rotate-[-2deg] bg-white p-2' : ($theme === 'creative-agency' ? 'border-2 border-indigo-200 shadow-2xl bg-white p-2.5' : ($theme === 'web-design-studio' ? 'border-2 border-sky-400/50 shadow-2xl shadow-sky-500/20 bg-white/10 backdrop-blur-md p-2' : 'border-2 border-white/20 shadow-2xl bg-white/10 p-1.5')) ?> transition-transform duration-300 group-hover:scale-105">
                <img src="<?= htmlspecialchars(sanitize_safe_url($site['avatar_url'] ?: '/assets/img/logo-smp-labschool.png')) ?>" 
                     alt="<?= htmlspecialchars($site['title']) ?>" 
                     class="w-full h-full object-contain rounded-2xl bg-white/5">
            </div>
            <!-- Verified School Badge -->
            <div class="absolute -bottom-2 -right-2 <?= $theme === 'artist-doodle' ? 'bg-[#fdb849] text-slate-950 border-2 border-slate-900 shadow-[2px_2px_0px_#0f0e0e]' : ($theme === 'creative-agency' ? 'bg-[#1873fb] text-white border-2 border-white shadow-lg' : 'bg-blue-600 text-white border-2 border-slate-900 shadow-xl') ?> rounded-2xl p-2 flex items-center justify-center" title="Terverifikasi SMP Labschool Jakarta">
                <span class="material-symbols-rounded text-base fill">verified</span>
            </div>
        </div>

        <!-- Nama & Gelar -->
        <h1 class="text-2xl sm:text-3xl tracking-tight text-center mb-1.5 px-2 <?= $activeTheme['title_font'] ?? 'font-black' ?> <?= $theme === 'artist-doodle' ? 'text-slate-950 drop-shadow-sm rotate-[-0.5deg]' : ($theme === 'creative-agency' ? 'text-slate-900' : 'text-white') ?>">
            <?= htmlspecialchars($site['title']) ?>
        </h1>
        <div class="inline-flex items-center gap-1.5 text-xs font-bold mb-3 <?= $activeTheme['accent'] ?>">
            <span>SMP Labschool Jakarta</span>
            <span>&bull;</span>
            <span class="opacity-75">s.labschool.xyz/p/<?= htmlspecialchars($cleanSlug) ?></span>
        </div>

        <!-- Bio / Deskripsi -->
        <?php if (!empty($site['bio'])): ?>
            <p class="text-xs sm:text-sm text-center max-w-md mb-4 leading-relaxed px-4 <?= $theme === 'artist-doodle' ? 'text-slate-900 font-semibold' : ($theme === 'creative-agency' ? 'text-slate-600 font-normal' : 'text-white/85') ?>">
                <?= nl2br(htmlspecialchars($site['bio'])) ?>
            </p>
        <?php endif; ?>

        <!-- Feature Chips Sesuai Tema Template -->
        <?php if ($theme === 'web-design-studio'): ?>
            <div class="flex items-center justify-center gap-2 mb-6 flex-wrap">
                <span class="px-3 py-1 rounded-full bg-sky-400/20 border border-sky-300/30 text-sky-200 text-[11px] font-bold">💻 Responsive Web</span>
                <span class="px-3 py-1 rounded-full bg-pink-400/20 border border-pink-300/30 text-pink-200 text-[11px] font-bold">🎨 Modern UI/UX</span>
                <span class="px-3 py-1 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-200 text-[11px] font-bold">⚡ Fast &amp; SEO-Ready</span>
            </div>
        <?php elseif ($theme === 'creative-agency'): ?>
            <div class="flex items-center justify-center gap-2 mb-6 flex-wrap">
                <span class="px-3 py-1 rounded-full bg-white border border-slate-200 text-indigo-700 text-[11px] font-extrabold shadow-sm">🖥️ Interactive Mockups</span>
                <span class="px-3 py-1 rounded-full bg-white border border-slate-200 text-blue-700 text-[11px] font-extrabold shadow-sm">📐 Design Systems</span>
                <span class="px-3 py-1 rounded-full bg-white border border-slate-200 text-purple-700 text-[11px] font-extrabold shadow-sm">🚀 Digital Branding</span>
            </div>
        <?php elseif ($theme === 'artist-doodle'): ?>
            <div class="flex items-center justify-center gap-2 mb-6 flex-wrap">
                <span class="px-3.5 py-1 rounded-xl bg-white border-2 border-slate-900 text-slate-950 shadow-[2px_2px_0px_#0f0e0e] text-[11px] font-extrabold rotate-[1deg]">🎨 Character Design</span>
                <span class="px-3.5 py-1 rounded-xl bg-[#fdb5d1] border-2 border-slate-900 text-slate-950 shadow-[2px_2px_0px_#0f0e0e] text-[11px] font-extrabold rotate-[-1.5deg]">✨ Comic &amp; Doodles</span>
                <span class="px-3.5 py-1 rounded-xl bg-[#fdb849] border-2 border-slate-900 text-slate-950 shadow-[2px_2px_0px_#0f0e0e] text-[11px] font-extrabold rotate-[2deg]">🖌️ Vector Art</span>
            </div>
        <?php endif; ?>

        <!-- Genially Interactive Countdown Timer (Jika Disetel) -->
        <?php if (!empty($site['countdown_date']) && strtotime($site['countdown_date']) > time()): ?>
            <div class="w-full mb-8 p-4 rounded-3xl border <?= $activeTheme['card'] ?> text-center space-y-2">
                <div class="text-[11px] font-extrabold uppercase tracking-widest <?= $activeTheme['accent'] ?>">
                    ⏱️ <?= htmlspecialchars($site['countdown_title'] ?: 'Hitung Mundur Acara') ?>
                </div>
                <div class="grid grid-cols-4 gap-2 max-w-xs mx-auto text-center" id="countdownContainer" data-target="<?= htmlspecialchars($site['countdown_date']) ?>">
                    <div class="p-2 rounded-2xl bg-white/10">
                        <div class="text-xl font-black" id="cdDays">00</div>
                        <div class="text-[9px] font-bold opacity-60 uppercase">Hari</div>
                    </div>
                    <div class="p-2 rounded-2xl bg-white/10">
                        <div class="text-xl font-black" id="cdHours">00</div>
                        <div class="text-[9px] font-bold opacity-60 uppercase">Jam</div>
                    </div>
                    <div class="p-2 rounded-2xl bg-white/10">
                        <div class="text-xl font-black" id="cdMins">00</div>
                        <div class="text-[9px] font-bold opacity-60 uppercase">Menit</div>
                    </div>
                    <div class="p-2 rounded-2xl bg-white/10">
                        <div class="text-xl font-black" id="cdSecs">00</div>
                        <div class="text-[9px] font-bold opacity-60 uppercase">Detik</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Media Links Floating Bar -->
        <?php if (!empty($socials)): ?>
            <div class="flex items-center justify-center gap-2.5 mb-8 flex-wrap">
                <?php if (!empty($socials['whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $socials['whatsapp']) ?>" target="_blank" class="p-3 rounded-2xl <?= $activeTheme['card'] ?> transition transform hover:-translate-y-1 shadow-md" title="WhatsApp">
                        <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['instagram'])): ?>
                    <a href="https://instagram.com/<?= ltrim($socials['instagram'], '@') ?>" target="_blank" class="p-3 rounded-2xl <?= $activeTheme['card'] ?> transition transform hover:-translate-y-1 shadow-md" title="Instagram">
                        <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['youtube'])): ?>
                    <a href="<?= htmlspecialchars($socials['youtube']) ?>" target="_blank" class="p-3 rounded-2xl <?= $activeTheme['card'] ?> transition transform hover:-translate-y-1 shadow-md" title="YouTube">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($socials['email']) ?>" class="p-3 rounded-2xl <?= $activeTheme['card'] ?> transition transform hover:-translate-y-1 shadow-md" title="Email">
                        <span class="material-symbols-rounded text-xl text-amber-400">mail</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TEMPLATE RENDERER -->

        <?php if ($templateStyle === 'genially-roadmap'): ?>
            <!-- TEMPLATE 1: GENIALLY ROADMAP / TIMELINE INTERAKTIF -->
            <div class="w-full space-y-6 relative">
                <!-- Garis Penghubung Roadmap Vertikal -->
                <div class="absolute left-6 top-8 bottom-8 w-0.5 bg-blue-500/30 border-l border-dashed border-blue-400/40"></div>

                <?php $stepIdx = 1; ?>
                <?php foreach ($blocks as $block): ?>
                    <?php
                        $animClass = ($block['animation'] === 'pulse') ? 'animate-pulse-custom' : '';
                        $meta = !empty($block['metadata']) ? json_decode($block['metadata'], true) : [];
                    ?>
                    <div class="relative flex items-start gap-4 group">
                        <!-- Node Lingkaran Interaktif -->
                        <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white font-black text-sm flex items-center justify-center shrink-0 shadow-lg shadow-blue-600/40 border-2 border-white/20 transition-transform duration-300 group-hover:scale-110 z-10">
                            <?= $stepIdx++ ?>
                        </div>

                        <!-- Card Konten -->
                        <div class="flex-1 p-5 rounded-3xl border <?= $activeTheme['card'] ?> space-y-2 <?= $animClass ?>">
                            <div class="flex items-center justify-between">
                                <h3 class="font-extrabold text-base tracking-tight"><?= htmlspecialchars($block['title']) ?></h3>
                                <span class="material-symbols-rounded text-lg opacity-60">arrow_forward</span>
                            </div>
                            <?php if (!empty($block['subtitle'])): ?>
                                <p class="text-xs opacity-75 leading-relaxed"><?= htmlspecialchars($block['subtitle']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($block['target_url'])): ?>
                                <a href="<?= htmlspecialchars(sanitize_safe_url($block['target_url'])) ?>" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold <?= $activeTheme['btn_prim'] ?> transition mt-2">
                                    <span>Buka Tautan</span>
                                    <span class="material-symbols-rounded text-sm">open_in_new</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($templateStyle === 'genially-cards'): ?>
            <!-- TEMPLATE 2: GENIALLY INTERACTIVE 2-COLUMN GRID -->
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($blocks as $block): ?>
                    <?php
                        $animClass = ($block['animation'] === 'pulse') ? 'animate-pulse-custom' : '';
                    ?>
                    <?php if ($block['block_type'] === 'link'): ?>
                        <a href="<?= htmlspecialchars(sanitize_safe_url($block['target_url'])) ?>" target="_blank"
                           class="p-5 rounded-3xl border <?= $activeTheme['card'] ?> flex flex-col justify-between space-y-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl <?= $animClass ?>">
                            <div class="flex items-center justify-between">
                                <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-blue-400">
                                    <span class="material-symbols-rounded text-xl">link</span>
                                </div>
                                <span class="material-symbols-rounded text-lg opacity-40">north_east</span>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm tracking-tight mb-1"><?= htmlspecialchars($block['title']) ?></h3>
                                <?php if (!empty($block['subtitle'])): ?>
                                    <p class="text-[11px] opacity-70 line-clamp-2"><?= htmlspecialchars($block['subtitle']) ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php elseif ($block['block_type'] === 'text'): ?>
                        <div class="col-span-full p-5 rounded-3xl border <?= $activeTheme['card'] ?> space-y-1">
                            <div class="text-[10px] font-extrabold uppercase tracking-widest <?= $activeTheme['accent'] ?>">Pengumuman</div>
                            <h3 class="font-extrabold text-base"><?= htmlspecialchars($block['title']) ?></h3>
                            <p class="text-xs opacity-80 leading-relaxed"><?= nl2br(htmlspecialchars($block['subtitle'] ?: $block['target_url'])) ?></p>
                        </div>
                    <?php elseif ($block['block_type'] === 'youtube'): ?>
                        <?php
                            preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $block['target_url'], $ytMatches);
                            $ytId = $ytMatches[1] ?? '';
                        ?>
                        <?php if ($ytId): ?>
                            <div class="col-span-full p-3 rounded-3xl border <?= $activeTheme['card'] ?> space-y-2">
                                <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-lg">
                                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                                <?php if (!empty($block['title'])): ?>
                                    <div class="text-xs font-bold text-center p-1"><?= htmlspecialchars($block['title']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($block['block_type'] === 'vcard'): ?>
                        <a href="/vcard.php?name=<?= urlencode($site['title']) ?>&org=SMP+Labschool+Jakarta&email=<?= urlencode($socials['email'] ?? '') ?>&phone=<?= urlencode($socials['whatsapp'] ?? '') ?>"
                           class="col-span-full p-4 rounded-3xl border flex items-center justify-center gap-2.5 <?= $activeTheme['btn_prim'] ?> shadow-xl transition transform hover:-translate-y-1 font-extrabold text-sm">
                            <span class="material-symbols-rounded text-xl">contact_phone</span>
                            <span><?= htmlspecialchars($block['title'] ?: 'Simpan Kontak ke HP') ?></span>
                        </a>
                    <?php elseif ($block['block_type'] === 'pdf'): ?>
                        <a href="<?= htmlspecialchars(sanitize_safe_url($block['target_url'])) ?>" target="_blank"
                           class="p-5 rounded-3xl border <?= $activeTheme['card'] ?> flex flex-col justify-between space-y-4 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl">
                            <div class="flex items-center justify-between">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                                    <span class="material-symbols-rounded text-xl">description</span>
                                </div>
                                <span class="material-symbols-rounded text-lg opacity-40">download</span>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-sm tracking-tight mb-1"><?= htmlspecialchars($block['title']) ?></h3>
                                <?php if (!empty($block['subtitle'])): ?>
                                    <p class="text-[11px] opacity-70 line-clamp-2"><?= htmlspecialchars($block['subtitle']) ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php elseif ($block['block_type'] === 'faq'): ?>
                        <details class="col-span-full p-5 rounded-3xl border <?= $activeTheme['card'] ?> group transition-all">
                            <summary class="font-extrabold text-sm cursor-pointer list-none flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-purple-400 text-base">quiz</span>
                                    <span><?= htmlspecialchars($block['title']) ?></span>
                                </span>
                                <span class="material-symbols-rounded text-lg group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="mt-2.5 pt-2.5 border-t border-white/10 text-xs opacity-80 leading-relaxed">
                                <?= nl2br(htmlspecialchars($block['subtitle'] ?: $block['target_url'])) ?>
                            </div>
                        </details>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- TEMPLATE 3: GENIALLY INTERACTIVE SHOWCASE (Default Modern) -->
            <div class="w-full space-y-4">
                <?php foreach ($blocks as $block): ?>
                    <?php
                        $animClass = ($block['animation'] === 'pulse') ? 'animate-pulse-custom' : '';
                        $meta = !empty($block['metadata']) ? json_decode($block['metadata'], true) : [];
                    ?>

                    <?php if ($block['block_type'] === 'link'): ?>
                        <!-- Blok Link Tombol Interaktif dengan Efek Glow Hover -->
                        <a href="<?= htmlspecialchars(sanitize_safe_url($block['target_url'])) ?>" target="_blank"
                           class="w-full p-4 sm:p-5 rounded-3xl border flex items-center justify-between <?= $activeTheme['card'] ?> transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl <?= $animClass ?> group">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center <?= $activeTheme['accent'] ?> shrink-0 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-rounded text-2xl">arrow_outward</span>
                                </div>
                                <div class="text-left truncate">
                                    <div class="font-extrabold text-sm sm:text-base tracking-tight truncate"><?= htmlspecialchars($block['title']) ?></div>
                                    <?php if (!empty($block['subtitle'])): ?>
                                        <div class="text-xs opacity-70 truncate"><?= htmlspecialchars($block['subtitle']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="material-symbols-rounded text-lg opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all">chevron_right</span>
                        </a>

                    <?php elseif ($block['block_type'] === 'youtube'): ?>
                        <!-- Blok YouTube Embed -->
                        <?php
                            preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $block['target_url'], $ytMatches);
                            $ytId = $ytMatches[1] ?? '';
                        ?>
                        <?php if ($ytId): ?>
                            <div class="w-full rounded-3xl overflow-hidden border <?= $activeTheme['card'] ?> p-2.5 shadow-2xl">
                                <div class="aspect-video w-full rounded-2xl overflow-hidden">
                                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                                <?php if (!empty($block['title'])): ?>
                                    <div class="text-xs font-bold p-2.5 text-center"><?= htmlspecialchars($block['title']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($block['block_type'] === 'text'): ?>
                        <!-- Blok Pengumuman Interaktif -->
                        <div class="w-full p-5 rounded-3xl border <?= $activeTheme['card'] ?> space-y-1.5">
                            <div class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-widest <?= $activeTheme['accent'] ?>">
                                <span class="material-symbols-rounded text-sm">campaign</span>
                                <span>Informasi & Pengumuman</span>
                            </div>
                            <h2 class="font-extrabold text-sm sm:text-base"><?= htmlspecialchars($block['title']) ?></h2>
                            <p class="text-xs opacity-85 leading-relaxed"><?= nl2br(htmlspecialchars($block['subtitle'] ?: $block['target_url'])) ?></p>
                        </div>

                    <?php elseif ($block['block_type'] === 'vcard'): ?>
                        <!-- Blok Download Kontak vCard -->
                        <a href="/vcard.php?name=<?= urlencode($site['title']) ?>&org=SMP+Labschool+Jakarta&email=<?= urlencode($socials['email'] ?? '') ?>&phone=<?= urlencode($socials['whatsapp'] ?? '') ?>"
                           class="w-full p-4 rounded-3xl border flex items-center justify-center gap-2.5 <?= $activeTheme['btn_prim'] ?> shadow-xl transition transform hover:-translate-y-1 font-extrabold text-sm">
                            <span class="material-symbols-rounded text-xl">contact_phone</span>
                            <span><?= htmlspecialchars($block['title'] ?: 'Simpan Kontak ke HP') ?></span>
                        </a>

                    <?php elseif ($block['block_type'] === 'pdf'): ?>
                        <!-- Blok Unduhan Berkas / PDF -->
                        <a href="<?= htmlspecialchars(sanitize_safe_url($block['target_url'])) ?>" target="_blank" download
                           class="w-full p-4 sm:p-5 rounded-3xl border flex items-center justify-between <?= $activeTheme['card'] ?> transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl <?= $animClass ?> group">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-rounded text-2xl">description</span>
                                </div>
                                <div class="text-left truncate">
                                    <div class="font-extrabold text-sm sm:text-base tracking-tight truncate"><?= htmlspecialchars($block['title']) ?></div>
                                    <?php if (!empty($block['subtitle'])): ?>
                                        <div class="text-xs opacity-70 truncate"><?= htmlspecialchars($block['subtitle']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 text-xs font-bold shrink-0">
                                <span class="material-symbols-rounded text-base">download</span>
                                <span>Unduh</span>
                            </div>
                        </a>

                    <?php elseif ($block['block_type'] === 'faq'): ?>
                        <!-- Blok FAQ / Pertanyaan Accordion -->
                        <details class="w-full p-5 rounded-3xl border <?= $activeTheme['card'] ?> group transition-all">
                            <summary class="font-extrabold text-sm sm:text-base cursor-pointer list-none flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2.5">
                                    <span class="material-symbols-rounded text-purple-400 text-xl">quiz</span>
                                    <span><?= htmlspecialchars($block['title']) ?></span>
                                </span>
                                <span class="material-symbols-rounded text-lg group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <div class="mt-3 pt-3 border-t border-white/10 text-xs opacity-85 leading-relaxed">
                                <?= nl2br(htmlspecialchars($block['subtitle'] ?: $block['target_url'])) ?>
                            </div>
                        </details>

                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Footer Identitas Sekolah Labschool -->
    <footer class="mt-12 text-center text-xs opacity-70 flex flex-col items-center gap-2 z-10">
        <div class="flex items-center gap-2 font-bold">
            <img src="/assets/img/logo-smp-labschool.png" alt="Logo" class="w-4 h-4 object-contain">
            <span><?= htmlspecialchars($site['custom_footer'] ?: 'SMP Labschool Jakarta') ?></span>
        </div>
        <p class="text-[11px] opacity-60">&copy; <?= date('Y') ?> Unit TI & Sistem Informasi SMP Labschool Jakarta</p>
    </footer>

    <!-- Interactive Countdown Script -->
    <script>
        const cdContainer = document.getElementById('countdownContainer');
        if (cdContainer) {
            const targetStr = cdContainer.getAttribute('data-target');
            const targetDate = new Date(targetStr).getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const diff = targetDate - now;

                if (diff <= 0) {
                    cdContainer.innerHTML = '<div class="col-span-4 p-2 text-xs font-bold text-emerald-400">Acara Sedang Berlangsung!</div>';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);

                document.getElementById('cdDays').innerText = String(days).padStart(2, '0');
                document.getElementById('cdHours').innerText = String(hours).padStart(2, '0');
                document.getElementById('cdMins').innerText = String(mins).padStart(2, '0');
                document.getElementById('cdSecs').innerText = String(secs).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
    </script>
</body>
</html>
