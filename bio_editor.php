<?php
/**
 * Visual Microsite Editor: s.labschool.xyz
 * Paritas s.id Pro Bio Link Builder
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();

$siteId = (int)($_GET['id'] ?? 0);
$site = null;
$blocks = [];

if ($siteId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM microsites WHERE id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();
    if (!$site || ($site['user_id'] != $user['id'] && $user['role'] !== 'superadmin')) {
        header('Location: /dashboard.php');
        exit;
    }

    $stmtB = $pdo->prepare("SELECT * FROM microsite_blocks WHERE microsite_id = ? ORDER BY display_order ASC");
    $stmtB->execute([$siteId]);
    $blocks = $stmtB->fetchAll();
}

$socials = !empty($site['social_links']) ? json_decode($site['social_links'], true) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siteId ? 'Edit Microsite' : 'Buat Microsite Baru' ?> — s.labschool.xyz</title>
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

    <!-- Top Navbar -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/dashboard.php" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition" title="Kembali ke Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="flex items-center gap-2">
                    <img src="/assets/img/logo-smp-labschool.png" alt="Logo Labschool" class="h-9 w-auto">
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 leading-tight"><?= $siteId ? 'Edit Halaman Microsite' : 'Buat Halaman Microsite Baru' ?></h1>
                        <p class="text-xs text-slate-500">Official Bio Link SMP Labschool Jakarta</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="saveMicrosite()" id="btnSave" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 flex-1">
        <!-- Kolom Form Editor (7 Kolom) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- 1. Identitas Halaman -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                    <span>1. Profil & URL Halaman</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Custom Slug (Alamat URL) *</label>
                        <div class="flex items-center rounded-xl border border-slate-300 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100 overflow-hidden bg-slate-50">
                            <span class="px-3 text-xs text-slate-400 font-semibold select-none">s.labschool.xyz/p/</span>
                            <input type="text" id="siteSlug" value="<?= htmlspecialchars($site['slug'] ?? '') ?>" placeholder="contoh: osis" required
                                class="w-full py-2.5 pr-3 text-sm font-bold bg-transparent outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Judul / Nama Profil *</label>
                        <input type="text" id="siteTitle" value="<?= htmlspecialchars($site['title'] ?? '') ?>" placeholder="contoh: OSIS SMP Labschool Jakarta" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Deskripsi / Bio</label>
                    <textarea id="siteBio" rows="2" placeholder="Tuliskan bio atau deskripsi profil..."
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none"><?= htmlspecialchars($site['bio'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">URL Avatar / Logo Profil (Opsional)</label>
                    <input type="url" id="siteAvatar" value="<?= htmlspecialchars($site['avatar_url'] ?? '') ?>" placeholder="https://... (kosongkan untuk menggunakan logo Labschool default)"
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
            </div>

            <!-- 2. Pengaturan Tema (s.id Pro) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg></span>
                    <span>2. Tema Tampilan (Labschool Styles)</span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="themeSelector">
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="labs-navy" <?= ($site['theme'] ?? 'labs-navy') === 'labs-navy' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3 rounded-2xl border-2 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-100 bg-slate-900 text-white text-center transition">
                            <div class="w-6 h-6 rounded-full bg-blue-600 mx-auto mb-1"></div>
                            <span class="text-xs font-bold block">Labs Navy</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="labs-gold" <?= ($site['theme'] ?? '') === 'labs-gold' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3 rounded-2xl border-2 peer-checked:border-amber-500 peer-checked:ring-2 peer-checked:ring-amber-100 bg-slate-950 text-amber-400 text-center transition">
                            <div class="w-6 h-6 rounded-full bg-amber-500 mx-auto mb-1"></div>
                            <span class="text-xs font-bold block">Labs Gold</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="emerald" <?= ($site['theme'] ?? '') === 'emerald' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3 rounded-2xl border-2 peer-checked:border-emerald-600 peer-checked:ring-2 peer-checked:ring-emerald-100 bg-slate-900 text-emerald-400 text-center transition">
                            <div class="w-6 h-6 rounded-full bg-emerald-600 mx-auto mb-1"></div>
                            <span class="text-xs font-bold block">Emerald</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="slate" <?= ($site['theme'] ?? '') === 'slate' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="p-3 rounded-2xl border-2 peer-checked:border-slate-800 peer-checked:ring-2 peer-checked:ring-slate-200 bg-slate-100 text-slate-800 text-center transition">
                            <div class="w-6 h-6 rounded-full bg-slate-800 mx-auto mb-1"></div>
                            <span class="text-xs font-bold block">Clean Slate</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 3. Media Sosial -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-lg bg-pink-50 text-pink-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></span>
                    <span>3. Ikon Media Sosial</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-600 block mb-1">WhatsApp (No HP / Link)</label>
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
                        <label class="font-bold text-slate-600 block mb-1">Email Sekolah</label>
                        <input type="email" id="socEmail" value="<?= htmlspecialchars($socials['email'] ?? '') ?>" placeholder="kontak@labschool.xyz" class="w-full px-3 py-2 rounded-xl border border-slate-300 outline-none">
                    </div>
                </div>
            </div>

            <!-- 4. Blok Komponen (Link, Video, Text, vCard) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg></span>
                        <span>4. Daftar Blok Komponen</span>
                    </h2>
                    <div class="flex gap-2">
                        <button type="button" onclick="addBlock('link')" class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-bold transition">
                            + Tombol Link
                        </button>
                        <button type="button" onclick="addBlock('youtube')" class="px-3 py-1.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 text-xs font-bold transition">
                            + YouTube
                        </button>
                        <button type="button" onclick="addBlock('text')" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold transition">
                            + Pengumuman
                        </button>
                    </div>
                </div>

                <div id="blocksContainer" class="space-y-3">
                    <!-- Dynamic Blocks Rendered via JS -->
                </div>
            </div>
        </div>

        <!-- Kolom Live Mockup Preview (5 Kolom) -->
        <div class="lg:col-span-5 hidden lg:block sticky top-24 self-start">
            <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4">Simulasi Pratinjau Seluler</h3>
                
                <!-- Mockup Smartphone Frame -->
                <div class="w-[320px] h-[580px] mx-auto rounded-[40px] border-[10px] border-slate-800 bg-slate-900 shadow-2xl overflow-hidden relative">
                    <div class="absolute top-2 left-1/2 -translate-x-1/2 w-24 h-4 bg-slate-800 rounded-full z-20"></div>
                    <iframe id="previewIframe" src="<?= $siteId ? '/p/' . htmlspecialchars($site['slug']) : 'about:blank' ?>" class="w-full h-full border-0 bg-slate-900"></iframe>
                </div>
                <p class="text-xs text-slate-400 mt-4">Pratinjau otomatis diperbarui saat Anda menekan tombol Simpan.</p>
            </div>
        </div>
    </div>

    <!-- Script Block Management & Save -->
    <script>
        const initialBlocks = <?= json_encode($blocks) ?>;
        const siteId = <?= $siteId ?>;

        let blocksData = initialBlocks.length ? initialBlocks.map(b => ({
            block_type: b.block_type,
            title: b.title,
            subtitle: b.subtitle || '',
            target_url: b.target_url || '',
            animation: b.animation || 'none'
        })) : [
            { block_type: 'link', title: 'Portal Resmi SMP Labschool Jakarta', subtitle: 'smplabschooljakarta.sch.id', target_url: 'https://smplabschooljakarta.sch.id', animation: 'none' },
            { block_type: 'link', title: 'Jadwal Pelajaran Sekolah', subtitle: 'jadwal.labschool.xyz', target_url: 'https://jadwal.labschool.xyz', animation: 'pulse' }
        ];

        function renderBlocks() {
            const container = document.getElementById('blocksContainer');
            container.innerHTML = '';

            blocksData.forEach((b, idx) => {
                const el = document.createElement('div');
                el.className = 'p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2 relative group';
                el.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold ${b.block_type === 'youtube' ? 'bg-red-100 text-red-600' : (b.block_type === 'text' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600')}">
                            ${b.block_type.toUpperCase()}
                        </span>
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="moveBlock(${idx}, -1)" class="p-1 text-slate-400 hover:text-slate-700" title="Geser ke Atas">&uarr;</button>
                            <button type="button" onclick="moveBlock(${idx}, 1)" class="p-1 text-slate-400 hover:text-slate-700" title="Geser ke Bawah">&darr;</button>
                            <button type="button" onclick="deleteBlock(${idx})" class="p-1 text-red-400 hover:text-red-600 ml-2" title="Hapus Blok">&times;</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5">${b.block_type === 'text' ? 'Judul Pengumuman' : 'Judul Tombol'}</label>
                            <input type="text" value="${escapeHtml(b.title)}" onchange="updateBlockData(${idx}, 'title', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5">${b.block_type === 'text' ? 'Isi Teks Lengkap' : (b.block_type === 'youtube' ? 'URL Video YouTube' : 'URL Target')}</label>
                            <input type="text" value="${escapeHtml(b.block_type === 'text' ? b.subtitle : b.target_url)}" onchange="updateBlockData(${idx}, '${b.block_type === 'text' ? 'subtitle' : 'target_url'}', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none">
                        </div>
                    </div>

                    ${b.block_type === 'link' ? `
                    <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5">Subjudul / Keterangan Kecil</label>
                            <input type="text" value="${escapeHtml(b.subtitle)}" onchange="updateBlockData(${idx}, 'subtitle', this.value)" placeholder="Opsional..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5">Animasi Tombol (Pro)</label>
                            <select onchange="updateBlockData(${idx}, 'animation', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none bg-white">
                                <option value="none" ${b.animation === 'none' ? 'selected' : ''}>Tanpa Animasi</option>
                                <option value="pulse" ${b.animation === 'pulse' ? 'selected' : ''}>Pulse Highlight (Denyut)</option>
                            </select>
                        </div>
                    </div>` : ''}
                `;
                container.appendChild(el);
            });
        }

        function escapeHtml(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function addBlock(type) {
            if (type === 'youtube') {
                blocksData.push({ block_type: 'youtube', title: 'Video Dokumentasi Labschool', target_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' });
            } else if (type === 'text') {
                blocksData.push({ block_type: 'text', title: 'Pengumuman Penting', subtitle: 'Informasi kegiatan sekolah minggu ini.' });
            } else {
                blocksData.push({ block_type: 'link', title: 'Tautan Baru', subtitle: '', target_url: 'https://', animation: 'none' });
            }
            renderBlocks();
        }

        function deleteBlock(idx) {
            blocksData.splice(idx, 1);
            renderBlocks();
        }

        function moveBlock(idx, dir) {
            const target = idx + dir;
            if (target < 0 || target >= blocksData.length) return;
            const temp = blocksData[idx];
            blocksData[idx] = blocksData[target];
            blocksData[target] = temp;
            renderBlocks();
        }

        function updateBlockData(idx, key, val) {
            blocksData[idx][key] = val;
        }

        async function saveMicrosite() {
            const btn = document.getElementById('btnSave');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                id: siteId,
                slug: document.getElementById('siteSlug').value,
                title: document.getElementById('siteTitle').value,
                bio: document.getElementById('siteBio').value,
                avatar_url: document.getElementById('siteAvatar').value,
                theme: document.querySelector('input[name="theme"]:checked')?.value || 'labs-navy',
                social_links: {
                    whatsapp: document.getElementById('socWa').value,
                    instagram: document.getElementById('socIg').value,
                    youtube: document.getElementById('socYt').value,
                    email: document.getElementById('socEmail').value
                },
                blocks: blocksData
            };

            try {
                const res = await fetch('/api/save_microsite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.status === 'success') {
                    alert('Halaman Microsite berhasil disimpan!');
                    if (json.data && json.data.id && !siteId) {
                        window.location.href = '/bio_editor.php?id=' + json.data.id;
                    } else {
                        document.getElementById('previewIframe').src = json.data.bio_url + '?t=' + Date.now();
                    }
                } else {
                    alert('Error: ' + json.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan Perubahan';
            }
        }

        renderBlocks();
    </script>
</body>
</html>
