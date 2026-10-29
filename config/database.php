<?php
/**
 * Konfigurasi Koneksi Database MariaDB: s.labschool.xyz
 * Platform: s.id Clone Labschool
 */

defined('S_LABSCHOOL') or define('S_LABSCHOOL', true);

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 's_labschool_xyz';
$db_user = getenv('DB_USER') ?: 's_labschool_user';
$db_pass = getenv('DB_PASS') ?: 'Labschool_Shortener_2026!#';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Koneksi database MariaDB gagal: " . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'status'  => 'error',
        'message' => 'Layanan basis data sedang mengalami gangguan teknis. Silakan coba beberapa saat lagi.'
    ]));
}
