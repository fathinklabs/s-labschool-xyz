<?php
/**
 * Modul Pengiriman Email Enterprise XOAUTH2 (Google Workspace for Education)
 * Akun Pengirim Resmi: admin@labschool.xyz
 * SMP Labschool Jakarta — s.labschool.xyz
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Base64 URL-safe encode
 */
function mail_b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Ambil Access Token XOAUTH2 Google Workspace untuk akun admin@labschool.xyz
 * Menggunakan Service Account Domain-Wide Delegation (DwD) resmi dengan scope https://mail.google.com/
 */
function get_xoauth2_access_token(string $impersonateUser = 'admin@labschool.xyz'): string {
    static $memoryToken = null;
    static $memoryExpiry = 0;

    $now = time();

    // 1. Cek cache in-memory
    if ($memoryToken && $memoryExpiry > ($now + 120)) {
        return $memoryToken;
    }

    // 2. Cek cache Redis jika tersedia
    if (class_exists('Redis')) {
        try {
            $redis = new Redis();
            if (@$redis->connect('127.0.0.1', 6379, 1.0)) {
                $cacheKey = 'labs_xoauth_token:' . md5($impersonateUser);
                $cached = $redis->get($cacheKey);
                if ($cached) {
                    $cData = json_decode($cached, true);
                    if (!empty($cData['access_token']) && ($cData['expires_at'] ?? 0) > ($now + 120)) {
                        $memoryToken = $cData['access_token'];
                        $memoryExpiry = $cData['expires_at'];
                        return $memoryToken;
                    }
                }
            }
        } catch (Throwable $e) {}
    }

    // 3. Cek cache berkas sementara (/tmp)
    $tmpCacheFile = sys_get_temp_dir() . '/labs_xoauth_' . md5($impersonateUser) . '.json';
    if (file_exists($tmpCacheFile)) {
        $fContent = @file_get_contents($tmpCacheFile);
        if ($fContent) {
            $fData = json_decode($fContent, true);
            if (!empty($fData['access_token']) && ($fData['expires_at'] ?? 0) > ($now + 120)) {
                $memoryToken = $fData['access_token'];
                $memoryExpiry = $fData['expires_at'];
                return $memoryToken;
            }
        }
    }

    // 4. Cari berkas kredensial Service Account
    $possiblePaths = [
        __DIR__ . '/google_service_account.json',
        '/var/www/s.labschool.xyz/config/google_service_account.json',
        'C:/GAMConfig/oauth2service.json'
    ];

    $saPath = null;
    foreach ($possiblePaths as $p) {
        if (file_exists($p)) {
            $saPath = $p;
            break;
        }
    }

    if (!$saPath) {
        throw new RuntimeException("Berkas kredensial Google Service Account tidak ditemukan di config/google_service_account.json.");
    }

    $sa = json_decode(file_get_contents($saPath), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) {
        throw new RuntimeException("Format kredensial Google Service Account tidak valid.");
    }

    // 5. Susun JWT Assertion
    $header = ["alg" => "RS256", "typ" => "JWT"];
    $claims = [
        "iss"   => $sa["client_email"],
        "sub"   => $impersonateUser,
        "scope" => "https://mail.google.com/",
        "aud"   => "https://oauth2.googleapis.com/token",
        "exp"   => $now + 3600,
        "iat"   => $now
    ];

    $unsignedJwt = mail_b64url_encode(json_encode($header)) . "." . mail_b64url_encode(json_encode($claims));
    $privateKey = openssl_pkey_get_private($sa["private_key"]);
    if (!$privateKey) {
        throw new RuntimeException("Kunci privat Service Account RSA tidak dapat dibaca oleh OpenSSL.");
    }

    $signature = '';
    if (!openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException("Gagal menandatangani JWT Service Account.");
    }

    $signedJwt = $unsignedJwt . "." . mail_b64url_encode($signature);

    // 6. Request access token ke endpoint Google OAuth2
    $ch = curl_init("https://oauth2.googleapis.com/token");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion"  => $signedJwt
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ["Content-Type: application/x-www-form-urlencoded"]
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        throw new RuntimeException("Gagal otorisasi XOAUTH2 Google: HTTP $httpCode - " . ($curlErr ?: $resp));
    }

    $tokenRes = json_decode($resp, true);
    if (empty($tokenRes['access_token'])) {
        throw new RuntimeException("Respon Google OAuth2 tidak memuat access_token: " . $resp);
    }

    $accessToken = $tokenRes['access_token'];
    $expiresIn = (int)($tokenRes['expires_in'] ?? 3600);
    $expiresAt = $now + $expiresIn;

    $memoryToken = $accessToken;
    $memoryExpiry = $expiresAt;

    // Simpan ke cache
    $cachePayload = json_encode(['access_token' => $accessToken, 'expires_at' => $expiresAt]);
    @file_put_contents($tmpCacheFile, $cachePayload);

    if (isset($redis)) {
        try {
            $redis->setex('labs_xoauth_token:' . md5($impersonateUser), max(60, $expiresIn - 180), $cachePayload);
        } catch (Throwable $e) {}
    }

    return $accessToken;
}

/**
 * Format template email responsif berstandar resmi Labschool
 */
function render_labschool_email_layout(
    string $title,
    string $preheader,
    string $contentHtml,
    ?string $ctaText = null,
    ?string $ctaUrl = null,
    ?string $additionalNote = null
): string {
    $logoUrl = 'https://s.labschool.xyz/assets/img/logo-smp-labschool.png';
    $year = date('Y');

    $ctaSection = '';
    if ($ctaText && $ctaUrl) {
        $ctaSection = '
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 28px 0;">
            <tr>
                <td align="center">
                    <a href="' . htmlspecialchars($ctaUrl) . '" target="_blank" style="display: inline-block; background-color: #2563EB; color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 14px; font-weight: 800; text-decoration: none; padding: 14px 28px; border-radius: 14px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                        ' . htmlspecialchars($ctaText) . ' &rarr;
                    </a>
                </td>
            </tr>
        </table>';
    }

    $noteSection = '';
    if ($additionalNote) {
        $noteSection = '
        <div style="background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 12px; padding: 12px 16px; margin: 20px 0; font-size: 12px; color: #92400E; line-height: 1.5;">
            ' . $additionalNote . '
        </div>';
    }

    return '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; -webkit-font-smoothing: antialiased;">
    <div style="display: none; font-size: 1px; color: #F8FAFC; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        ' . htmlspecialchars($preheader) . '
    </div>
    
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 24px; overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0F172A; padding: 28px 32px; border-bottom: 3px solid #2563EB;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td valign="middle" width="50">
                                        <img src="' . $logoUrl . '" alt="Labschool" width="44" height="44" style="display: block; border-radius: 10px; background: #ffffff; padding: 3px;">
                                    </td>
                                    <td valign="middle" style="padding-left: 14px;">
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 16px; font-weight: 900; color: #ffffff; letter-spacing: -0.2px;">
                                            s.labschool.xyz
                                        </div>
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 11px; font-weight: 600; color: #94A3B8;">
                                            Enterprise URL & Pages &bull; SMP Labschool Jakarta
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 28px 32px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.6;">
                            <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 900; color: #0F172A; letter-spacing: -0.3px;">
                                ' . htmlspecialchars($title) . '
                            </h1>
                            
                            <div style="font-size: 14px; color: #475569;">
                                ' . $contentHtml . '
                            </div>

                            ' . $ctaSection . '
                            ' . $noteSection . '

                            <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 13px; color: #64748B;">
                                Hormat kami,<br>
                                <strong style="color: #0F172A;">Tim IT & Administrator Portal s.labschool.xyz</strong><br>
                                SMP Labschool Jakarta
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F8FAFC; padding: 24px 32px; border-top: 1px solid #E2E8F0; text-align: center; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 11px; color: #94A3B8; line-height: 1.5;">
                            <p style="margin: 0 0 6px 0;">
                                Email otomatis dikirim oleh sistem terverifikasi XOAUTH2 melalui <strong>admin@labschool.xyz</strong>.
                            </p>
                            <p style="margin: 0;">
                                &copy; ' . $year . ' SMP Labschool Jakarta &bull; Jl. Pemuda Kompleks UNJ Rawamangun, Jakarta Timur.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Kirim email via Google Workspace XOAUTH2 (Gmail API & SMTP Fallback)
 * Pengirim resmi default: admin@labschool.xyz
 */
function send_mail_xoauth2(
    string $to,
    string $subject,
    string $htmlBody,
    string $plainText = '',
    string $fromName = 'SMP Labschool Jakarta',
    string $fromEmail = 'admin@labschool.xyz'
): array {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Alamat email tujuan tidak valid: ' . $to];
    }

    try {
        $token = get_xoauth2_access_token($fromEmail);
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Gagal autentikasi XOAUTH2: ' . $e->getMessage()];
    }

    if (empty($plainText)) {
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $htmlBody));
    }

    $boundary = '=_labs_boundary_' . md5(uniqid((string)mt_rand(), true));

    // Susun pesan RFC 2822 MIME
    $rawMime  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
    $rawMime .= "To: <{$to}>\r\n";
    $rawMime .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $rawMime .= "MIME-Version: 1.0\r\n";
    $rawMime .= "Date: " . date('r') . "\r\n";
    $rawMime .= "Message-ID: <" . uniqid("labs_mail_", true) . "@s.labschool.xyz>\r\n";
    $rawMime .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";

    // Bagian Plain Text
    $rawMime .= "--{$boundary}\r\n";
    $rawMime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $rawMime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $rawMime .= chunk_split(base64_encode($plainText)) . "\r\n";

    // Bagian HTML
    $rawMime .= "--{$boundary}\r\n";
    $rawMime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $rawMime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $rawMime .= chunk_split(base64_encode($htmlBody)) . "\r\n";

    $rawMime .= "--{$boundary}--\r\n";

    $encodedMime = mail_b64url_encode($rawMime);

    // Kirim via Google Gmail API
    $apiEndpoint = "https://gmail.googleapis.com/gmail/v1/users/" . urlencode($fromEmail) . "/messages/send";
    $ch = curl_init($apiEndpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(["raw" => $encodedMime]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]
    ]);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        $json = json_decode($resp, true);
        return [
            'success'    => true,
            'message_id' => $json['id'] ?? '',
            'thread_id'  => $json['threadId'] ?? '',
            'message'    => 'Email berhasil dikirim via Gmail XOAUTH2 API.'
        ];
    }

    return [
        'success' => false,
        'message' => "Gmail API Error (HTTP {$httpCode}): " . ($curlErr ?: $resp)
    ];
}

/**
 * 1. Kirim Email Verifikasi Pendaftaran Akun Baru (Wajib Verifikasi Sebelum Login)
 */
function send_email_verification(string $toEmail, string $fullName, string $verifyLink): array {
    $title = "Verifikasi Alamat Email Anda";
    $preheader = "Satu langkah lagi untuk mengaktifkan akun portal s.labschool.xyz Anda.";
    
    $content = '
    <p>Halo <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
    <p>Terima kasih telah mendaftar di <strong>s.labschool.xyz</strong> — Layanan Enterprise URL Shortener, Dynamic QR Code, dan Interactive Pages resmi SMP Labschool Jakarta.</p>
    
    <div style="background-color: #EFF6FF; border-radius: 14px; border: 1px solid #BFDBFE; padding: 16px 20px; margin: 20px 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px;">
            <tr>
                <td style="color: #64748B; padding: 4px 0; width: 130px;">Email Terdaftar:</td>
                <td style="color: #0F172A; font-weight: 700; font-family: monospace;">' . htmlspecialchars($toEmail) . '</td>
            </tr>
            <tr>
                <td style="color: #64748B; padding: 4px 0;">Status Akun:</td>
                <td style="color: #D97706; font-weight: 700;">Menunggu Verifikasi Email</td>
            </tr>
            <tr>
                <td style="color: #64748B; padding: 4px 0;">Masa Berlaku:</td>
                <td style="color: #2563EB; font-weight: 700;">24 Jam</td>
            </tr>
        </table>
    </div>

    <p>Demi keamanan sistem civitas akademika dan memastikan keabsahan akun, Anda wajib memverifikasi alamat email ini sebelum dapat masuk (login) ke dashboard platform.</p>
    <p>Silakan klik tombol di bawah ini untuk memverifikasi alamat email dan mengaktifkan akun Anda:</p>';

    $ctaText = "Verifikasi Email Saya";
    $ctaUrl = $verifyLink;
    $note = "Penting: Tautan verifikasi ini berlaku selama 24 jam. Jika tombol di atas tidak dapat diklik, Anda dapat menyalin tautan berikut ke peramban: " . htmlspecialchars($verifyLink);

    $html = render_labschool_email_layout($title, $preheader, $content, $ctaText, $ctaUrl, $note);
    $subject = "Verifikasi Alamat Email Akun s.labschool.xyz";

    return send_mail_xoauth2($toEmail, $subject, $html);
}

/**
 * 1b. Kirim Email Konfirmasi Pendaftaran Akun Baru (Legacy / Info)
 */
function send_registration_confirmation_email(string $toEmail, string $fullName): array {
    $title = "Selamat Datang di s.labschool.xyz";
    $preheader = "Akun resmi portal pemendek tautan, QR code, dan Pages Anda telah siap digunakan.";
    
    $content = '
    <p>Halo <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
    <p>Selamat bergabung di platform <strong>s.labschool.xyz</strong> — Layanan Enterprise URL Shortener, Dynamic QR Code, dan Interactive Pages resmi SMP Labschool Jakarta.</p>
    
    <div style="background-color: #F8FAFC; border-radius: 14px; border: 1px solid #E2E8F0; padding: 16px 20px; margin: 20px 0;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px;">
            <tr>
                <td style="color: #64748B; padding: 4px 0; width: 120px;">Email Terdaftar:</td>
                <td style="color: #0F172A; font-weight: 700; font-family: monospace;">' . htmlspecialchars($toEmail) . '</td>
            </tr>
            <tr>
                <td style="color: #64748B; padding: 4px 0;">Status Akun:</td>
                <td style="color: #059669; font-weight: 700;">Aktif &bull; Civitas Labschool</td>
            </tr>
            <tr>
                <td style="color: #64748B; padding: 4px 0;">Akses Domain:</td>
                <td style="color: #2563EB; font-weight: 700;">s.labschool.xyz</td>
            </tr>
        </table>
    </div>

    <p>Dengan akun ini, Anda dapat memanfaatkan berbagai fitur unggulan sekolah:</p>
    <ul style="padding-left: 20px; margin: 12px 0; color: #475569; font-size: 13px;">
        <li style="margin-bottom: 6px;"><strong>Pemendek Tautan Kustom</strong>: Buat tautan singkat resmi (contoh: <code>s.labschool.xyz/latihan-mtk</code>).</li>
        <li style="margin-bottom: 6px;"><strong>Kode QR Dinamis</strong>: Unduh format vektor SVG dan PNG beresolusi tinggi dengan logo resmi sekolah.</li>
        <li style="margin-bottom: 6px;"><strong>Labs Pages Builder</strong>: Rancang microsite interaktif, kartu nama digital guru, dan portofolio kegiatan siswa.</li>
    </ul>';

    $ctaText = "Buka Dashboard Sekarang";
    $ctaUrl = "https://s.labschool.xyz/dashboard.php";
    $note = "Catatan Keamanan: Jangan pernah membagikan kata sandi akun Anda kepada siapa pun. Jika Anda tidak merasa mendaftar di layanan ini, harap abaikan email ini atau hubungi admin sekolah.";

    $html = render_labschool_email_layout($title, $preheader, $content, $ctaText, $ctaUrl, $note);
    $subject = "Konfirmasi Pendaftaran Akun Portal s.labschool.xyz";

    return send_mail_xoauth2($toEmail, $subject, $html);
}

/**
 * 2. Kirim Email Reset Password (Lupa Kata Sandi)
 */
function send_password_reset_email(string $toEmail, string $fullName, string $resetLink, string $ipAddress): array {
    $title = "Permintaan Atur Ulang Kata Sandi";
    $preheader = "Tautan pengaturan ulang kata sandi akun s.labschool.xyz Anda berlaku selama 30 menit.";
    
    $content = '
    <p>Halo <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
    <p>Kami menerima permintaan untuk mengatur ulang kata sandi akun portal <strong>s.labschool.xyz</strong> yang tertaut dengan alamat email <strong>' . htmlspecialchars($toEmail) . '</strong>.</p>
    
    <div style="background-color: #F8FAFC; border-radius: 14px; border: 1px solid #E2E8F0; padding: 14px 18px; margin: 18px 0; font-size: 12px; color: #64748B;">
        <div style="margin-bottom: 4px;"><strong>Waktu Permintaan:</strong> ' . date('d F Y, H:i:s') . ' WIB</div>
        <div><strong>Alamat IP Pemohon:</strong> <code style="color: #0F172A; font-weight: bold;">' . htmlspecialchars($ipAddress) . '</code></div>
    </div>

    <p>Silakan klik tombol di bawah ini untuk membuat kata sandi baru. Tautan ini hanya berlaku selama <strong>30 menit</strong> demi keamanan akun Anda:</p>';

    $ctaText = "Atur Ulang Kata Sandi";
    $ctaUrl = $resetLink;
    $note = "Penting: Jika Anda tidak meminta reset kata sandi, abaikan email ini. Kata sandi lama Anda tetap aman dan tidak akan berubah tanpa tindakan dari tautan di atas.";

    $html = render_labschool_email_layout($title, $preheader, $content, $ctaText, $ctaUrl, $note);
    $subject = "Reset Kata Sandi Akun s.labschool.xyz";

    return send_mail_xoauth2($toEmail, $subject, $html);
}

/**
 * 3. Uji Coba Pengiriman Email XOAUTH2
 */
function test_xoauth2_connection(string $testRecipient = 'admin@labschool.xyz'): array {
    $title = "Uji Coba Pengiriman Email XOAUTH2";
    $preheader = "Verifikasi koneksi Google Workspace XOAUTH2 dari admin@labschool.xyz berhasil.";
    $time = date('d F Y, H:i:s') . ' WIB';

    $content = '
    <p>Halo Administrator,</p>
    <p>Email ini adalah pengujian otomatis untuk memverifikasi bahwa konfigurasi <strong>Google Workspace XOAUTH2</strong> pada web <strong>s.labschool.xyz</strong> berfungsi dengan sempurna.</p>
    <div style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 14px 18px; margin: 16px 0; font-size: 13px; color: #065F46;">
        &check; <strong>Status Otorisasi:</strong> Berhasil Terhubung (200 OK)<br>
        &check; <strong>Akun Pengirim:</strong> admin@labschool.xyz<br>
        &check; <strong>Waktu Verifikasi:</strong> ' . $time . '
    </div>';

    $html = render_labschool_email_layout($title, $preheader, $content, "Buka Admin Panel", "https://s.labschool.xyz/admin.php?tab=config");
    $subject = "Tes Koneksi Email XOAUTH2 s.labschool.xyz - " . date('d/m/Y H:i');

    return send_mail_xoauth2($testRecipient, $subject, $html);
}
