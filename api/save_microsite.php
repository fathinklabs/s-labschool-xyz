<?php
/**
 * Legacy API: Simpan / Perbarui Halaman Microsite (Bio Link)
 * @deprecated Gunakan /api/save_page.php
 */

header('Deprecation: true');
header('Link: </api/save_page.php>; rel="successor-version"');

require __DIR__ . '/save_page.php';
