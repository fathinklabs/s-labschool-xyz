<?php
/**
 * API: Bulk Shorten Links (Fitur Bitly Enterprise: Pemendek Tautan Massal)
 * Menerima daftar tautan dalam format JSON array atau teks baris-per-baris
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$currentUser = current_user();
if (!$currentUser) {
    // Cek API Key
    $authHeader = get_auth_header();
    $apiKey = null;
    if (preg_match('/Bearer\s+(labs_[a-zA-Z0-9_]+)/', $authHeader, $matches)) {
        $apiKey = $matches[1];
    }
    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT id, email, full_name, domain, role FROM users WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        $currentUser = $stmt->fetch();
    }
}

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Autentikasi diperlukan.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$urls = [];
if (!empty($data['urls'])) {
    if (is_array($data['urls'])) {
        $urls = $data['urls'];
    } elseif (is_string($data['urls'])) {
        // Baris per baris
        $lines = preg_split('/\r\n|\r|\n/', $data['urls']);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $urls[] = ['url' => $line];
            }
        }
    }
}

if (empty($urls)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Daftar tautan (urls) tidak boleh kosong.']);
    exit;
}

// Batasi maksimal 50 link per batch
if (count($urls) > 50) {
    $urls = array_slice($urls, 0, 50);
}

$defaultTags = trim($data['tags'] ?? '');
$results = [];
$successCount = 0;
$failCount = 0;

$stmtInsert = $pdo->prepare("
    INSERT INTO short_urls (
        user_id, short_code, original_url, title, custom_slug,
        tags, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
");

foreach ($urls as $item) {
    $originalUrl = is_array($item) ? trim($item['url'] ?? '') : trim($item);
    $title = is_array($item) ? trim($item['title'] ?? '') : '';
    $customSlug = is_array($item) ? trim($item['custom_slug'] ?? '') : '';
    $itemTags = is_array($item) && !empty($item['tags']) ? trim($item['tags']) : $defaultTags;

    if (empty($originalUrl)) continue;

    if (!preg_match('/^https?:\/\//i', $originalUrl)) {
        $originalUrl = 'https://' . $originalUrl;
    }

    if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        $results[] = [
            'original_url' => $originalUrl,
            'status'       => 'error',
            'message'      => 'Format URL tidak valid'
        ];
        $failCount++;
        continue;
    }

    $slug = null;
    if (!empty($customSlug)) {
        $cleanSlug = sanitize_slug($customSlug);
        $chk = $pdo->prepare("SELECT id FROM short_urls WHERE custom_slug = ? OR short_code = ?");
        $chk->execute([$cleanSlug, $cleanSlug]);
        if (!$chk->fetch()) {
            $slug = $cleanSlug;
        }
    }

    // Generate short code jika tidak ada custom slug yang valid
    do {
        $shortCode = generate_short_code(5);
        $chk = $pdo->prepare("SELECT id FROM short_urls WHERE short_code = ? OR custom_slug = ?");
        $chk->execute([$shortCode, $shortCode]);
    } while ($chk->fetch());

    if (empty($title)) {
        $title = parse_url($originalUrl, PHP_URL_HOST) ?: 'Tautan Singkat';
    }

    try {
        $stmtInsert->execute([
            $currentUser['id'],
            $shortCode,
            $originalUrl,
            $title,
            $slug,
            $itemTags
        ]);
        $newId = (int)$pdo->lastInsertId();
        $effectiveSlug = $slug ?: $shortCode;

        $results[] = [
            'id'           => $newId,
            'original_url' => $originalUrl,
            'short_url'    => base_url($effectiveSlug),
            'short_code'   => $shortCode,
            'custom_slug'  => $slug,
            'tags'         => $itemTags,
            'status'       => 'success'
        ];
        $successCount++;
    } catch (Exception $e) {
        $results[] = [
            'original_url' => $originalUrl,
            'status'       => 'error',
            'message'      => $e->getMessage()
        ];
        $failCount++;
    }
}

echo json_encode([
    'status'        => 'success',
    'total'         => count($results),
    'success_count' => $successCount,
    'fail_count'    => $failCount,
    'data'          => $results
]);
