<?php
/**
 * Modul Autentikasi & Restriksi Domain: s.labschool.xyz
 * Domain yang diizinkan pendaftaran: @labschool.xyz dan @elabschool.sch.id
 */

if (session_status() === PHP_SESSION_NONE) {
    // Redis Session Save Handler (dengan fallback otomatis ke file jika Redis tidak tersedia)
    if (extension_loaded('redis')) {
        try {
            $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
            $redisPort = (int)(getenv('REDIS_PORT') ?: 6379);
            $redisTimeout = 1.0;
            
            $testRedis = new \Redis();
            if (@$testRedis->connect($redisHost, $redisPort, $redisTimeout)) {
                $testRedis->close();
                ini_set('session.save_handler', 'redis');
                ini_set('session.save_path', "tcp://{$redisHost}:{$redisPort}?prefix=labs_sess:&timeout=2.0");
            }
        } catch (\Throwable $e) {
            // Fallback transparan ke file default PHP session handler
        }
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';

const ALLOWED_DOMAINS = ['labschool.xyz', 'elabschool.sch.id'];

/**
 * Validasi apakah domain email termasuk yang diizinkan
 */
function is_allowed_domain(string $email): bool {
    $email = strtolower(trim($email));
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }
    $domain = trim($parts[1]);
    $rawWhitelist = function_exists('get_global_setting') ? get_global_setting('allowed_email_domains', 'labschool.xyz, elabschool.sch.id') : 'labschool.xyz, elabschool.sch.id';
    $whitelist = array_filter(array_map('trim', explode(',', strtolower($rawWhitelist))));
    if (empty($whitelist)) {
        $whitelist = ['labschool.xyz', 'elabschool.sch.id'];
    }
    return in_array($domain, $whitelist, true);
}

/**
 * Mendapatkan domain dari email
 */
function extract_domain(string $email): ?string {
    $email = strtolower(trim($email));
    $parts = explode('@', $email);
    if (count($parts) === 2 && in_array(trim($parts[1]), ALLOWED_DOMAINS, true)) {
        return trim($parts[1]);
    }
    return null;
}

/**
 * Cek apakah user sedang login
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login saat ini
 */
function current_user(): ?array {
    global $pdo;
    if (!is_logged_in()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, email, full_name, domain, avatar_url, role, api_key, api_key_created_at, created_at FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['cached_user'] = $user;
        return $user;
    }
    
    // User tidak ditemukan atau dinonaktifkan
    unset($_SESSION['user_id'], $_SESSION['cached_user']);
    return null;
}

/**
 * Wajibkan login untuk mengakses halaman
 */
function require_login(string $redirect_to = '/login.php'): void {
    if (!is_logged_in()) {
        header("Location: {$redirect_to}");
        exit;
    }
}

/**
 * Alias require_auth() untuk kompatibilitas
 */
function require_auth(string $redirect_to = '/login.php'): void {
    require_login($redirect_to);
}

/**
 * Wajibkan peran Superadmin untuk mengakses halaman
 */
function require_admin(string $redirect_to = '/dashboard.php'): void {
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== 'superadmin') {
        http_response_code(403);
        die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Akses Ditolak</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 text-center font-sans"><div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl max-w-md"><h1 class="text-xl font-bold text-red-600 mb-2">Akses Ditolak</h1><p class="text-sm text-slate-500 mb-4">Halaman Admin Panel hanya dapat diakses oleh Administrator Sistem SMP Labschool Jakarta.</p><a href="/dashboard.php" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm">Kembali ke Dashboard</a></div></body></html>');
    }
}

/**
 * Proses registrasi user baru
 */
function register_user(string $email, string $password, string $full_name): array {
    global $pdo;
    $email = strtolower(trim($email));
    $full_name = strip_tags(trim($full_name));
    $password = str_replace(["\0", "\x00"], '', (string)$password);

    // Cek kebijakan pendaftaran mandiri dari Konfigurasi Global
    if (function_exists('get_global_setting')) {
        $allowReg = get_global_setting('allow_public_registration', '0');
        if ($allowReg !== '1') {
            return [
                'success' => false,
                'message' => 'Pendaftaran akun mandiri sedang dinonaktifkan oleh Administrator. Silakan hubungi admin sekolah.'
            ];
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format alamat email tidak valid.'];
    }

    if (!is_allowed_domain($email)) {
        return [
            'success' => false,
            'message' => 'Pendaftaran DIBATASI! Hanya pengguna dengan alamat email resmi @labschool.xyz atau @elabschool.sch.id yang diizinkan mendaftar.'
        ];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password minimal harus terdiri dari 6 karakter.'];
    }

    if (empty($full_name)) {
        return ['success' => false, 'message' => 'Nama lengkap tidak boleh kosong.'];
    }

    // Cek apakah email sudah terdaftar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Alamat email ini sudah terdaftar. Silakan langsung login.'];
    }

    $domain = extract_domain($email);
    try {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Gagal memproses kata sandi: ' . $e->getMessage()];
    }

    try {
        $insert = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, domain, role, api_key) VALUES (?, ?, ?, ?, 'teacher', NULL)");
        $insert->execute([$email, $password_hash, $full_name, $domain]);
        $userId = (int)$pdo->lastInsertId();

        // Auto login
        $_SESSION['user_id'] = $userId;
        $_SESSION['cached_user'] = [
            'id'        => $userId,
            'email'     => $email,
            'full_name' => $full_name,
            'domain'    => $domain,
            'role'      => 'teacher',
            'api_key'   => null
        ];

        return ['success' => true, 'message' => 'Pendaftaran berhasil! Selamat datang di s.labschool.xyz.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Gagal menyimpan akun: ' . $e->getMessage()];
    }
}

/**
 * Proses login user
 */
function login_user(string $email, string $password): array {
    global $pdo;
    $email = strtolower(trim($email));
    $password = str_replace(["\0", "\x00"], '', (string)$password);

    if (!is_allowed_domain($email)) {
        return [
            'success' => false,
            'message' => 'Akses terbatas untuk civitas akademika Labschool (@labschool.xyz dan @elabschool.sch.id).'
        ];
    }

    $stmt = $pdo->prepare("SELECT id, email, password_hash, full_name, domain, avatar_url, role, api_key, api_key_created_at, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email atau kata sandi tidak cocok.'];
    }

    if ((int)$user['is_active'] !== 1) {
        return ['success' => false, 'message' => 'Akun Anda dinonaktifkan oleh administrator.'];
    }

    // Cegah serangan Session Fixation dengan regenerasi ID sesi
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    unset($user['password_hash']);
    $_SESSION['cached_user'] = $user;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return ['success' => true, 'message' => 'Login berhasil!'];
}

/**
 * Helper cetak input hidden token CSRF untuk formulir HTML
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Dapatkan atau buat token CSRF untuk sesi saat ini
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi token CSRF yang dikirimkan klien
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Validasi CSRF wajib untuk aksi POST. Menolak jika tidak valid.
 */
function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan refresh halaman.']);
        } else {
            die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 - CSRF Invalid</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 text-center font-sans"><div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl max-w-md"><h1 class="text-xl font-bold text-red-600 mb-2">Validasi Keamanan Gagal (CSRF)</h1><p class="text-sm text-slate-500 mb-4">Sesi permintaan Anda tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman dan coba kembali.</p><a href="javascript:location.reload()" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-sm">Muat Ulang Halaman</a></div></body></html>');
        }
        exit;
    }
}

/**
 * Logout
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
