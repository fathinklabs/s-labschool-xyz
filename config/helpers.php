<?php
/**
 * Helper Functions: s.labschool.xyz
 * Platform: s.id Clone Labschool
 */

require_once __DIR__ . '/database.php';

function base_url(string $path = ''): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 's.labschool.xyz';
    $path = ltrim($path, '/');
    return "{$protocol}://{$host}/{$path}";
}

/**
 * Sanitasi string slug kustom
 */
function sanitize_slug(string $slug): string {
    $slug = trim($slug);
    $slug = strtolower($slug);
    // Hapus karakter non-alphanumeric selain strip dan underscore
    $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);
    return substr($slug, 0, 64);
}

/**
 * Generate acak Base62 short code (5-6 karakter)
 */
function generate_short_code(int $length = 5): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxIndex = strlen($characters) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $maxIndex)];
    }
    return $code;
}

/**
 * Ambil IP Pengunjung yang sebenarnya (Mendukung Cloudflare CF-Connecting-IP)
 */
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Ambil Negara Pengunjung dari Cloudflare Header
 */
function get_client_country(): string {
    return $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'ID';
}

/**
 * Ambil Kota Pengunjung dari Cloudflare Header
 */
function get_client_city(): string {
    return $_SERVER['HTTP_CF_IPCITY'] ?? 'Jakarta';
}

/**
 * Parser User-Agent sederhana untuk menentukan Perangkat, Browser, dan OS
 */
function parse_user_agent(?string $ua = null): array {
    $ua = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    
    // Perangkat
    $device = 'desktop';
    if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
        $device = 'tablet';
    } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile|mobile)/i', $ua)) {
        $device = 'mobile';
    } elseif (preg_match('/(bot|crawl|slurp|spider|mediapartners)/i', $ua)) {
        $device = 'bot';
    }

    // OS
    $os = 'Unknown OS';
    if (preg_match('/windows nt 10/i', $ua))     $os = 'Windows 10/11';
    elseif (preg_match('/windows/i', $ua))       $os = 'Windows';
    elseif (preg_match('/iphone/i', $ua))        $os = 'iOS (iPhone)';
    elseif (preg_match('/ipad/i', $ua))          $os = 'iOS (iPad)';
    elseif (preg_match('/android/i', $ua))       $os = 'Android';
    elseif (preg_match('/macintosh|mac os x/i', $ua)) $os = 'macOS';
    elseif (preg_match('/linux/i', $ua))         $os = 'Linux';

    // Browser
    $browser = 'Unknown Browser';
    if (preg_match('/chrome/i', $ua) && !preg_match('/edg|opr/i', $ua)) $browser = 'Google Chrome';
    elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) $browser = 'Safari';
    elseif (preg_match('/firefox/i', $ua)) $browser = 'Mozilla Firefox';
    elseif (preg_match('/edg/i', $ua))     $browser = 'Microsoft Edge';
    elseif (preg_match('/opr|opera/i', $ua)) $browser = 'Opera';

    return [
        'device'  => $device,
        'os'      => $os,
        'browser' => $browser
    ];
}

/**
 * Catat event klik ke database (Asinkron / Direct)
 */
function record_click(int $url_id): void {
    global $pdo;
    try {
        $ip = get_client_ip();
        $country = get_client_country();
        $city = get_client_city();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $parsedUa = parse_user_agent($ua);
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
        if (strlen($referrer) > 255) {
            $referrer = substr($referrer, 0, 255);
        }

        // 1. Catat ke click_logs
        $stmt = $pdo->prepare("
            INSERT INTO click_logs (url_id, ip_address, country, city, user_agent, device_type, browser, os, referrer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $url_id, $ip, $country, $city, substr($ua, 0, 500),
            $parsedUa['device'], $parsedUa['browser'], $parsedUa['os'], $referrer
        ]);

        // 2. Increment clicks_count di tabel short_urls
        $up = $pdo->prepare("UPDATE short_urls SET clicks_count = clicks_count + 1 WHERE id = ?");
        $up->execute([$url_id]);
    } catch (Exception $e) {
        // Jangan hentikan redirect jika log analitik gagal
        error_log("Gagal mencatat klik url_id={$url_id}: " . $e->getMessage());
    }
}

/**
 * Helper format waktu relatif (misal: 5 menit lalu)
 */
function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', $time);
}
