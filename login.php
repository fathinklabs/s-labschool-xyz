<?php
/**
 * Halaman & Endpoint Login: s.labschool.xyz
 * Terbatas untuk @labschool.xyz dan @elabschool.sch.id
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Jika request JSON API POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    $res = login_user($email, $password);
    if ($res['success']) {
        echo json_encode(['status' => 'success', 'message' => $res['message']]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $res['message']]);
    }
    exit;
}

// Jika GET: Jika sudah login, redirect ke dashboard
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

// Redirect ke beranda dengan membuka modal login
header('Location: /index.php?action=login');
exit;
