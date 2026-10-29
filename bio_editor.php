<?php
/**
 * Legacy Redirect: bio_editor.php -> page_builder.php
 * Backward-compatibility for all existing bookmarks and links.
 */

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = '/page_builder.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $target, true, 302);
exit;
