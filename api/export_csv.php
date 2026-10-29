<?php
/**
 * API: Ekspor Data Analitik Klik ke CSV (Fitur Pro All-Time)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$currentUser = current_user();
if (!$currentUser) {
    $apiKey = trim($_GET['api_key'] ?? '');
    if ($apiKey) {
        $stmtKey = $pdo->prepare("SELECT id, email, full_name, domain, role FROM users WHERE api_key = ? AND is_active = 1");
        $stmtKey->execute([$apiKey]);
        $currentUser = $stmtKey->fetch();
    }
}

if (!$currentUser) {
    http_response_code(401);
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Handler Ekspor Seluruh Pengguna (Hanya Administrator)
if (($_GET['type'] ?? '') === 'users') {
    if (!in_array($currentUser['role'], ['superadmin', 'admin'], true)) {
        http_response_code(403);
        die('Akses ditolak. Ekspor seluruh pengguna hanya diizinkan untuk Administrator.');
    }

    $usersStmt = $pdo->query("
        SELECT u.id, u.email, u.full_name, u.role, u.is_active, u.created_at,
               (SELECT COUNT(*) FROM short_urls WHERE user_id = u.id) as total_links
        FROM users u
        ORDER BY u.created_at ASC
    ");
    $usersData = $usersStmt->fetchAll();

    $filename = "users-export-" . date('Ymd-His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Daftar Pengguna s.labschool.xyz']);
    fputcsv($out, ['Total Pengguna', count($usersData)]);
    fputcsv($out, ['Waktu Ekspor', date('Y-m-d H:i:s')]);
    fputcsv($out, []);
    fputcsv($out, ['ID', 'Nama Lengkap', 'Email', 'Role / Peran', 'Status', 'Total Tautan', 'Tanggal Bergabung']);
    foreach ($usersData as $ud) {
        fputcsv($out, [
            $ud['id'],
            $ud['full_name'],
            $ud['email'],
            $ud['role'],
            $ud['is_active'] ? 'Aktif' : 'Non-aktif',
            $ud['total_links'],
            $ud['created_at']
        ]);
    }
    fclose($out);
    exit;
}

$urlId = (int)($_GET['id'] ?? 0);
if ($urlId <= 0) {
    http_response_code(400);
    die('ID Tautan tidak valid.');
}

// Verifikasi kepemilikan tautan
$stmt = $pdo->prepare("SELECT id, short_code, custom_slug, title, original_url, user_id FROM short_urls WHERE id = ?");
$stmt->execute([$urlId]);
$link = $stmt->fetch();

if (!$link || ($link['user_id'] != $currentUser['id'] && $currentUser['role'] !== 'superadmin')) {
    http_response_code(403);
    die('Anda tidak memiliki izin untuk mengunduh analitik tautan ini.');
}

$slug = $link['custom_slug'] ?: $link['short_code'];

// Ambil semua log klik untuk link ini
$logsStmt = $pdo->prepare("
    SELECT clicked_at, ip_address, country, city, device_type, browser, os, referrer, user_agent
    FROM click_logs
    WHERE url_id = ?
    ORDER BY clicked_at DESC
");
$logsStmt->execute([$urlId]);
$rows = $logsStmt->fetchAll();

// Header unduh CSV
$filename = "analytics-{$slug}-" . date('Ymd-His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$out = fopen('php://output', 'w');

// BOM untuk UTF-8 Excel compatibility
fputs($out, "\xEF\xBB\xBF");

// Baris Judul Laporan
fputcsv($out, ['Laporan Analitik Tautan s.labschool.xyz']);
fputcsv($out, ['Judul Tautan', $link['title']]);
fputcsv($out, ['Slug Pendek', $slug]);
fputcsv($out, ['URL Asli', $link['original_url']]);
fputcsv($out, ['Total Klik', count($rows)]);
fputcsv($out, ['Waktu Ekspor', date('Y-m-d H:i:s')]);
fputcsv($out, []); // Baris kosong

// Kolom Data
fputcsv($out, [
    'Waktu Klik (WIB)',
    'IP Address',
    'Negara',
    'Kota',
    'Tipe Perangkat',
    'Sistem Operasi (OS)',
    'Browser',
    'Sumber Trafik / Referrer',
    'User Agent Lengkap'
]);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['clicked_at'],
        $r['ip_address'],
        $r['country'] ?: 'ID',
        $r['city'] ?: 'Jakarta',
        ucfirst($r['device_type'] ?? 'other'),
        $r['os'] ?? 'Unknown',
        $r['browser'] ?? 'Unknown',
        $r['referrer'] ?? 'Direct',
        $r['user_agent'] ?? ''
    ]);
}

fclose($out);
exit;
