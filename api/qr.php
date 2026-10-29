<?php
/**
 * Dynamic QR Code Generator: s.labschool.xyz
 * Output: PNG atau SVG dengan kustomisasi warna & logo Labschool
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$code = trim($_GET['code'] ?? '');
$format = strtolower(trim($_GET['format'] ?? 'png'));
$fgColor = trim($_GET['fg'] ?? '#1E40AF');
$bgColor = trim($_GET['bg'] ?? '#FFFFFF');
$frame = trim($_GET['frame'] ?? 'none');
$ctaText = trim($_GET['cta'] ?? 'PINDAI SAYA');
$download = !empty($_GET['download']);
$size = min(max((int)($_GET['size'] ?? 400), 120), 1200);

if (empty($code)) {
    http_response_code(400);
    die('Code parameter is required.');
}

// Cek di database untuk preferensi warna & frame tersimpan jika ada
$stmt = $pdo->prepare("SELECT short_code, custom_slug, qr_fg_color, qr_bg_color, qr_frame, qr_cta_text FROM short_urls WHERE short_code = ? OR custom_slug = ? LIMIT 1");
$stmt->execute([$code, $code]);
$urlRow = $stmt->fetch();
if ($urlRow) {
    if (empty($_GET['fg']) && !empty($urlRow['qr_fg_color'])) {
        $fgColor = $urlRow['qr_fg_color'];
    }
    if (empty($_GET['bg']) && !empty($urlRow['qr_bg_color'])) {
        $bgColor = $urlRow['qr_bg_color'];
    }
    if (empty($_GET['frame']) && !empty($urlRow['qr_frame'])) {
        $frame = $urlRow['qr_frame'];
    }
    if (empty($_GET['cta']) && !empty($urlRow['qr_cta_text'])) {
        $ctaText = $urlRow['qr_cta_text'];
    }
}

$targetUrl = base_url($code);

// Bersihkan hex color
$fgColor = ltrim($fgColor, '#');
$bgColor = ltrim($bgColor, '#');
if (strlen($fgColor) !== 6) $fgColor = '1E40AF';
if (strlen($bgColor) !== 6) $bgColor = 'FFFFFF';

// Buat QR code via GD Image Canvas
// Coba gunakan tcpdf barcode QRcode jika tersedia di sistem
$tcpdfPath = '/var/www/study.elabschool.sch.id/moodle/public/lib/tcpdf/include/barcodes/qrcode.php';
$matrix = null;

if (file_exists($tcpdfPath)) {
    require_once $tcpdfPath;
    $qrcode = new QRcode($targetUrl, 'H'); // Level H (High 30% error correction untuk logo)
    $matrix = $qrcode->getBarcodeArray();
}

if ($matrix && !empty($matrix['bcode'])) {
    $numRows = $matrix['num_rows'];
    $numCols = $matrix['num_cols'];
    $bcode = $matrix['bcode'];

    if ($format === 'svg') {
        header('Content-Type: image/svg+xml');
        if ($download) {
            header("Content-Disposition: attachment; filename=\"qrcode-{$code}.svg\"");
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\" viewBox=\"0 0 {$numCols} {$numRows}\" width=\"{$size}\" height=\"{$size}\">\n";
        echo "  <rect width=\"100%\" height=\"100%\" fill=\"#{$bgColor}\"/>\n";
        echo "  <g fill=\"#{$fgColor}\">\n";

        for ($r = 0; $r < $numRows; $r++) {
            for ($c = 0; $c < $numCols; $c++) {
                if ($bcode[$r][$c] == '1') {
                    echo "    <rect x=\"{$c}\" y=\"{$r}\" width=\"1\" height=\"1\"/>\n";
                }
            }
        }
        echo "  </g>\n";
        echo "</svg>";
        exit;
    }

    // Format PNG menggunakan GD
    $scale = max(2, (int)floor($size / $numCols));
    $imgSize = $numCols * $scale;
    $img = imagecreatetruecolor($imgSize, $imgSize);

    // Warna
    list($bgR, $bgG, $bgB) = sscanf($bgColor, "%02x%02x%02x");
    list($fgR, $fgG, $fgB) = sscanf($fgColor, "%02x%02x%02x");
    $bg = imagecolorallocate($img, $bgR, $bgG, $bgB);
    $fg = imagecolorallocate($img, $fgR, $fgG, $fgB);

    imagefill($img, 0, 0, $bg);

    for ($r = 0; $r < $numRows; $r++) {
        for ($c = 0; $c < $numCols; $c++) {
            if ($bcode[$r][$c] == '1') {
                imagefilledrectangle($img, $c * $scale, $r * $scale, ($c + 1) * $scale - 1, ($r + 1) * $scale - 1, $fg);
            }
        }
    }

    // Tempel Logo Labschool di tengah jika file logo tersedia
    $logoPath = __DIR__ . '/../assets/img/logo-smp-labschool.png';
    if (file_exists($logoPath)) {
        $logo = imagecreatefrompng($logoPath);
        if ($logo) {
            $logoW = imagesx($logo);
            $logoH = imagesy($logo);

            $targetLogoSize = (int)($imgSize * 0.22); // 22% dari ukuran QR
            $logoX = ($imgSize - $targetLogoSize) / 2;
            $logoY = ($imgSize - $targetLogoSize) / 2;

            // Beri background putih bulat di belakang logo agar modul QR tidak mengganggu logo
            $white = imagecolorallocate($img, 255, 255, 255);
            $pad = (int)($scale * 1.5);
            imagefilledrectangle($img, $logoX - $pad, $logoY - $pad, $logoX + $targetLogoSize + $pad, $logoY + $targetLogoSize + $pad, $white);

            imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $targetLogoSize, $targetLogoSize, $logoW, $logoH);
            imagedestroy($logo);
        }
    }

    // Fitur Smart QR Frames: Menambahkan frame dan CTA badge jika diaktifkan
    if ($frame === 'badge-bottom' || $frame === 'card') {
        $framePadding = 24;
        $badgeHeight = 44;
        $framedW = $imgSize + ($framePadding * 2);
        $framedH = $imgSize + ($framePadding * 2) + $badgeHeight;
        
        $framedImg = imagecreatetruecolor($framedW, $framedH);
        $canvasBg = imagecolorallocate($framedImg, 255, 255, 255);
        $borderCol = imagecolorallocate($framedImg, 226, 232, 240); // slate-200
        $badgeBg = imagecolorallocate($framedImg, $fgR, $fgG, $fgB);
        $badgeTxt = imagecolorallocate($framedImg, 255, 255, 255);
        
        imagefill($framedImg, 0, 0, $canvasBg);
        
        // Border card luar
        imagerectangle($framedImg, 0, 0, $framedW - 1, $framedH - 1, $borderCol);
        imagerectangle($framedImg, 1, 1, $framedW - 2, $framedH - 2, $borderCol);
        
        // Salin QR code ke tengah atas
        imagecopy($framedImg, $img, $framePadding, $framePadding, 0, 0, $imgSize, $imgSize);
        imagedestroy($img);
        
        // Tombol / Badge CTA di bawah
        $bX1 = $framePadding;
        $bY1 = $imgSize + $framePadding + 8;
        $bX2 = $framedW - $framePadding;
        $bY2 = $framedH - $framePadding;
        imagefilledrectangle($framedImg, $bX1, $bY1, $bX2, $bY2, $badgeBg);
        
        // Render Teks CTA
        $font = 5; // Built-in font size 5 GD
        $charW = imagefontwidth($font);
        $charH = imagefontheight($font);
        $label = mb_strtoupper($ctaText, 'UTF-8');
        $textLen = strlen($label);
        $textX = $bX1 + (($bX2 - $bX1) - ($textLen * $charW)) / 2;
        $textY = $bY1 + (($bY2 - $bY1) - $charH) / 2;
        imagestring($framedImg, $font, (int)max($bX1 + 4, $textX), (int)$textY, $label, $badgeTxt);
        
        $img = $framedImg;
    }

    header('Content-Type: image/png');
    if ($download) {
        header("Content-Disposition: attachment; filename=\"qrcode-{$code}.png\"");
    }
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Fallback jika class tcpdf belum dimuat: redirect ke API generator eksternal aman
$extUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($targetUrl) . "&color=" . $fgColor . "&bgcolor=" . $bgColor;
header("Location: {$extUrl}");
exit;
