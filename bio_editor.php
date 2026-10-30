<?php
/**
 * Legacy Redirect: bio_editor.php -> page_builder.php
 * Permanent Redirect (HTTP 301) with Deprecation Headers
 */

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = '/page_builder.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Deprecation: true');
header('Link: </page_builder.php>; rel="successor-version"');
header('Location: ' . $target, true, 301);
exit;
