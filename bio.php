<?php
/**
 * Public Microsite / Bio Link Viewer: s.labschool.xyz/p/{slug}
 * Paritas s.id Pro Bio Link dengan Desain SMP Labschool Jakarta
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

// Pengaturan Tema
$theme = $site['theme'] ?? 'labs-navy';
$themeClasses = [
    'labs-navy' => [
        'bg'       => 'bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white',
        'card'     => 'bg-white/10 hover:bg-white/15 border-white/10 text-white backdrop-blur-md',
        'btn_prim' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'badge'    => 'bg-blue-500/20 text-blue-300 border-blue-400/30'
    ],
    'labs-gold' => [
        'bg'       => 'bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950 text-white',
        'card'     => 'bg-amber-950/20 hover:bg-amber-900/30 border-amber-500/20 text-amber-100 backdrop-blur-md',
        'btn_prim' => 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold',
        'badge'    => 'bg-amber-500/20 text-amber-300 border-amber-400/30'
    ],
    'emerald' => [
        'bg'       => 'bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 text-white',
        'card'     => 'bg-emerald-900/20 hover:bg-emerald-900/30 border-emerald-500/20 text-emerald-100 backdrop-blur-md',
        'btn_prim' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
        'badge'    => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30'
    ],
    'slate' => [
        'bg'       => 'bg-slate-100 text-slate-800',
        'card'     => 'bg-white hover:bg-slate-50 border-slate-200/80 text-slate-800 shadow-sm',
        'btn_prim' => 'bg-slate-900 hover:bg-slate-800 text-white',
        'badge'    => 'bg-slate-200 text-slate-700 border-slate-300'
    ]
];

$activeTheme = $themeClasses[$theme] ?? $themeClasses['labs-navy'];

$seoTitle = !empty($site['seo_title']) ? $site['seo_title'] : $site['title'] . ' — SMP Labschool Jakarta';
$seoDesc  = !empty($site['seo_description']) ? $site['seo_description'] : ($site['bio'] ?: 'Official Bio Link SMP Labschool Jakarta');
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Nunito Sans"', 'sans-serif'] },
                    keyframes: {
                        pulseCustom: {
                            '0%, 100%': { transform: 'scale(1)' },
                            '50%': { transform: 'scale(1.02)' }
                        }
                    },
                    animation: {
                        'pulse-custom': 'pulseCustom 2s infinite ease-in-out'
                    }
                }
            }
        };
    </script>
</head>
<body class="<?= $activeTheme['bg'] ?> min-h-screen py-10 px-4 flex flex-col items-center justify-between font-sans transition-colors duration-300">

    <main class="w-full max-w-lg mx-auto flex flex-col items-center">
        <!-- Avatar Profil -->
        <div class="relative mb-4 group">
            <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-white/20 shadow-2xl bg-white/10 p-1">
                <img src="<?= htmlspecialchars($site['avatar_url'] ?: '/assets/img/logo-smp-labschool.png') ?>" 
                     alt="<?= htmlspecialchars($site['title']) ?>" 
                     class="w-full h-full object-contain rounded-full">
            </div>
            <!-- Verified School Badge -->
            <div class="absolute bottom-1 right-1 bg-blue-600 text-white rounded-full p-1.5 shadow-lg border-2 border-slate-900" title="Terverifikasi SMP Labschool Jakarta">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            </div>
        </div>

        <!-- Nama & Gelar -->
        <h1 class="text-2xl font-extrabold tracking-tight text-center mb-1"><?= htmlspecialchars($site['title']) ?></h1>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold mb-3 border <?= $activeTheme['badge'] ?>">
            <span>SMP Labschool Jakarta</span>
        </div>

        <!-- Bio / Deskripsi -->
        <?php if (!empty($site['bio'])): ?>
            <p class="text-sm opacity-80 text-center max-w-sm mb-6 leading-relaxed">
                <?= nl2br(htmlspecialchars($site['bio'])) ?>
            </p>
        <?php endif; ?>

        <!-- Social Media Links Bar -->
        <?php if (!empty($socials)): ?>
            <div class="flex items-center justify-center gap-3 mb-8 flex-wrap">
                <?php if (!empty($socials['whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $socials['whatsapp']) ?>" target="_blank" class="p-3 rounded-full <?= $activeTheme['card'] ?> transition transform hover:scale-110 shadow-sm" title="WhatsApp">
                        <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['instagram'])): ?>
                    <a href="https://instagram.com/<?= ltrim($socials['instagram'], '@') ?>" target="_blank" class="p-3 rounded-full <?= $activeTheme['card'] ?> transition transform hover:scale-110 shadow-sm" title="Instagram">
                        <svg class="w-5 h-5 text-pink-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['youtube'])): ?>
                    <a href="<?= htmlspecialchars($socials['youtube']) ?>" target="_blank" class="p-3 rounded-full <?= $activeTheme['card'] ?> transition transform hover:scale-110 shadow-sm" title="YouTube">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($socials['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($socials['email']) ?>" class="p-3 rounded-full <?= $activeTheme['card'] ?> transition transform hover:scale-110 shadow-sm" title="Email">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Daftar Blok Komponen (Pro Parity) -->
        <div class="w-full space-y-4">
            <?php foreach ($blocks as $block): ?>
                <?php
                    $animClass = ($block['animation'] === 'pulse') ? 'animate-pulse-custom' : '';
                    $meta = !empty($block['metadata']) ? json_decode($block['metadata'], true) : [];
                ?>

                <?php if ($block['block_type'] === 'link'): ?>
                    <!-- Blok Link Tombol -->
                    <a href="<?= htmlspecialchars($block['target_url']) ?>" target="_blank"
                       class="w-full p-4 rounded-2xl border flex items-center justify-between <?= $activeTheme['card'] ?> transition-all duration-200 transform hover:-translate-y-0.5 <?= $animClass ?>">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-white/10 text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </div>
                            <div class="text-left">
                                <div class="font-bold text-base tracking-tight"><?= htmlspecialchars($block['title']) ?></div>
                                <?php if (!empty($block['subtitle'])): ?>
                                    <div class="text-xs opacity-70"><?= htmlspecialchars($block['subtitle']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>

                <?php elseif ($block['block_type'] === 'youtube'): ?>
                    <!-- Blok YouTube Embed -->
                    <?php
                        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $block['target_url'], $ytMatches);
                        $ytId = $ytMatches[1] ?? '';
                    ?>
                    <?php if ($ytId): ?>
                        <div class="w-full rounded-2xl overflow-hidden border <?= $activeTheme['card'] ?> p-2 shadow-lg">
                            <div class="aspect-video w-full rounded-xl overflow-hidden">
                                <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <?php if (!empty($block['title'])): ?>
                                <div class="text-xs font-bold p-2 text-center"><?= htmlspecialchars($block['title']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($block['block_type'] === 'text'): ?>
                    <!-- Blok Pengumuman / Teks -->
                    <div class="w-full p-4 rounded-2xl border <?= $activeTheme['card'] ?>">
                        <h2 class="font-extrabold text-sm mb-1 uppercase tracking-wider text-blue-400"><?= htmlspecialchars($block['title']) ?></h2>
                        <p class="text-sm opacity-90 leading-relaxed"><?= nl2br(htmlspecialchars($block['subtitle'] ?: $block['target_url'])) ?></p>
                    </div>

                <?php elseif ($block['block_type'] === 'vcard'): ?>
                    <!-- Blok Download Kontak vCard -->
                    <a href="/vcard.php?name=<?= urlencode($site['title']) ?>&org=SMP+Labschool+Jakarta&email=<?= urlencode($socials['email'] ?? '') ?>&phone=<?= urlencode($socials['whatsapp'] ?? '') ?>"
                       class="w-full p-4 rounded-2xl border flex items-center justify-center gap-3 <?= $activeTheme['btn_prim'] ?> shadow-lg shadow-blue-500/20 transition transform hover:-translate-y-0.5 font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                        <span><?= htmlspecialchars($block['title'] ?: 'Simpan Kontak ke HP') ?></span>
                    </a>

                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Footer Identitas Sekolah -->
    <footer class="mt-12 text-center text-xs opacity-60 flex flex-col items-center gap-2">
        <div class="flex items-center gap-1.5 font-bold">
            <img src="/assets/img/logo-smp-labschool.png" alt="Logo" class="w-4 h-4 object-contain">
            <span><?= htmlspecialchars($site['custom_footer'] ?: 'SMP Labschool Jakarta') ?></span>
        </div>
        <p>&copy; <?= date('Y') ?> Unit TI & Sistem Informasi SMP Labschool Jakarta</p>
    </footer>

</body>
</html>
