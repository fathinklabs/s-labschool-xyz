<?php
/**
 * Konfigurasi Koneksi Database MariaDB: s.labschool.xyz
 * Platform: s.id Clone Labschool
 */

defined('S_LABSCHOOL') or define('S_LABSCHOOL', true);

// Muat variabel lingkungan dari .env lokal jika tersedia
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 's_labschool_xyz';
$db_user = getenv('DB_USER') ?: 's_labschool_user';
$db_pass = getenv('DB_PASS') ?: '';

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
