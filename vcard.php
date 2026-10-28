<?php
/**
 * Generator File Kontak vCard (.vcf)
 */

$name = trim($_GET['name'] ?? 'Civitas Labschool');
$org  = trim($_GET['org'] ?? 'SMP Labschool Jakarta');
$title = trim($_GET['title'] ?? 'Staff / Guru');
$phone = trim($_GET['phone'] ?? '');
$email = trim($_GET['email'] ?? '');
$url   = trim($_GET['url'] ?? 'https://smplabschooljakarta.sch.id');

$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) . '.vcf';

header('Content-Type: text/vcard; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

echo "BEGIN:VCARD\r\n";
echo "VERSION:3.0\r\n";
echo "FN;CHARSET=UTF-8:{$name}\r\n";
echo "N;CHARSET=UTF-8:{$name};;;;\r\n";
echo "ORG:{$org}\r\n";
if (!empty($title)) echo "TITLE:{$title}\r\n";
if (!empty($phone)) echo "TEL;TYPE=CELL:{$phone}\r\n";
if (!empty($email)) echo "EMAIL;TYPE=INTERNET:{$email}\r\n";
if (!empty($url))   echo "URL:{$url}\r\n";
echo "END:VCARD\r\n";
exit;
