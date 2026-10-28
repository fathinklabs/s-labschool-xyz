<?php
/**
 * Detailed Link Analytics: s.labschool.xyz
 * Paritas s.id Pro Analytics (All-Time Retention, Devices, Geolocation, CSV Export)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

require_login();
$user = current_user();

$linkId = (int)($_GET['id'] ?? 0);
if ($linkId <= 0) {
    header('Location: /dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM short_urls WHERE id = ?");
$stmt->execute([$linkId]);
$link = $stmt->fetch();

if (!$link || ($link['user_id'] != $user['id'] && $user['role'] !== 'superadmin')) {
    header('Location: /dashboard.php');
    exit;
}

$slug = $link['custom_slug'] ?: $link['short_code'];
$shortUrl = base_url($slug);

// 1. Data Timeline 14 Hari Terakhir
$timelineStmt = $pdo->prepare("
    SELECT DATE(clicked_at) as cdate, COUNT(*) as total
    FROM click_logs
    WHERE url_id = ?
    GROUP BY DATE(clicked_at)
    ORDER BY cdate ASC
    LIMIT 30
");
$timelineStmt->execute([$linkId]);
$timelineRows = $timelineStmt->fetchAll();

$dates = [];
$counts = [];
foreach ($timelineRows as $r) {
    $dates[] = date('d M', strtotime($r['cdate']));
    $counts[] = (int)$r['total'];
}

// 2. Breakdown Perangkat (Mobile, Desktop, Tablet)
$devStmt = $pdo->prepare("SELECT device_type, COUNT(*) as total FROM click_logs WHERE url_id = ? GROUP BY device_type");
$devStmt->execute([$linkId]);
$devRows = $devStmt->fetchAll();

// 3. Breakdown Sumber Referrer (WhatsApp, IG, Direct)
$refStmt = $pdo->prepare("SELECT referrer, COUNT(*) as total FROM click_logs WHERE url_id = ? GROUP BY referrer ORDER BY total DESC LIMIT 6");
$refStmt->execute([$linkId]);
$refRows = $refStmt->fetchAll();

// 4. Log Terakhir (20 Klik Terbaru)
$recentStmt = $pdo->prepare("SELECT clicked_at, ip_address, country, city, device_type, browser, os, referrer FROM click_logs WHERE url_id = ? ORDER BY clicked_at DESC LIMIT 20");
$recentStmt->execute([$linkId]);
$recentRows = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik <?= htmlspecialchars($slug) ?> — s.labschool.xyz</title>
    <link rel="icon" href="/assets/img/logo-smp-labschool.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
                        <h1 class="text-sm font-extrabold text-slate-900 leading-tight">Analitik Tautan: <?= htmlspecialchars($slug) ?></h1>
                        <p class="text-xs text-slate-500">Official Link Performance & Tracking</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="/api/export_csv.php?id=<?= $linkId ?>" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Ekspor CSV (Pro)</span>
                </a>
                <a href="<?= $shortUrl ?>" target="_blank" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                    Buka Link
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8 w-full space-y-6 flex-1">
        <!-- Banner Info Tautan -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 mb-2">
                    <span>s.labschool.xyz/<?= htmlspecialchars($slug) ?></span>
                </div>
                <h2 class="text-xl font-black text-slate-900"><?= htmlspecialchars($link['title']) ?></h2>
                <p class="text-xs text-slate-500 break-all mt-1"><?= htmlspecialchars($link['original_url']) ?></p>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="text-3xl font-black text-blue-600"><?= number_format($link['clicks_count']) ?></div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Klik</div>
                </div>
                <a href="/api/qr.php?code=<?= urlencode($slug) ?>&download=1" class="p-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Unduh QR Code">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </a>
            </div>
        </div>

        <!-- Grafik Garis Tren Klik -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                <span>Tren Frekuensi Klik (Real-time Timeline)</span>
            </h3>
            <div class="h-64 w-full">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>

        <!-- Grid 2 Kolom: Perangkat & Sumber Referrer -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Perangkat -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900 mb-4">Klasifikasi Perangkat</h3>
                <div class="space-y-3">
                    <?php if (empty($devRows)): ?>
                        <p class="text-xs text-slate-400 py-6 text-center">Belum ada data klik yang tercatat.</p>
                    <?php else: ?>
                        <?php foreach ($devRows as $d): ?>
                            <?php $pct = $link['clicks_count'] > 0 ? round(($d['total'] / $link['clicks_count']) * 100) : 0; ?>
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="capitalize"><?= htmlspecialchars($d['device_type']) ?></span>
                                    <span><?= $d['total'] ?> klik (<?= $pct ?>%)</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-blue-600 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sumber Trafik / Referrer -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                <h3 class="text-sm font-extrabold text-slate-900 mb-4">Sumber Trafik (Referrer)</h3>
                <div class="space-y-3">
                    <?php if (empty($refRows)): ?>
                        <p class="text-xs text-slate-400 py-6 text-center">Belum ada data referrer.</p>
                    <?php else: ?>
                        <?php foreach ($refRows as $r): ?>
                            <?php $pct = $link['clicks_count'] > 0 ? round(($r['total'] / $link['clicks_count']) * 100) : 0; ?>
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-1">
                                    <span class="truncate max-w-[200px]"><?= htmlspecialchars($r['referrer']) ?></span>
                                    <span><?= $r['total'] ?> klik (<?= $pct ?>%)</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-amber-500 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabel Log 20 Klik Terbaru -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm overflow-hidden">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4">Log Pengunjung Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider">
                            <th class="py-2.5 font-bold">Waktu (WIB)</th>
                            <th class="py-2.5 font-bold">Lokasi</th>
                            <th class="py-2.5 font-bold">Perangkat / OS</th>
                            <th class="py-2.5 font-bold">Browser</th>
                            <th class="py-2.5 font-bold">Referrer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        <?php if (empty($recentRows)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada aktivitas klik.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentRows as $rc): ?>
                                <tr>
                                    <td class="py-3 font-semibold text-slate-800"><?= date('d/m H:i:s', strtotime($rc['clicked_at'])) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($rc['city'] ?: 'Jakarta') ?>, <?= htmlspecialchars($rc['country'] ?: 'ID') ?></td>
                                    <td class="py-3"><span class="capitalize font-bold text-slate-700"><?= htmlspecialchars($rc['device_type']) ?></span> (<?= htmlspecialchars($rc['os'] ?: 'OS') ?>)</td>
                                    <td class="py-3"><?= htmlspecialchars($rc['browser'] ?: 'Browser') ?></td>
                                    <td class="py-3 truncate max-w-xs"><?= htmlspecialchars($rc['referrer'] ?: 'Direct') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('timelineChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: 'Jumlah Klik',
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
