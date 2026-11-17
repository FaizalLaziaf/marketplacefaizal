<?php
// Bootstrap bersama untuk semua endpoint REST API.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function out($status, $arr) {
    http_response_code($status);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
function fail($status, $msg) { out($status, ['status' => 'error', 'message' => $msg]); }
function ok($data, $status = 200, $extra = []) { out($status, ['status' => 'success'] + $extra + ['data' => $data]); }
function allow($methods) {
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        fail(405, 'Metode ' . $_SERVER['REQUEST_METHOD'] . ' tidak diizinkan. Gunakan: ' . implode(', ', $methods) . '.');
    }
}
function gambar_abs($f) {
    global $cfg;
    if (!$f) return null;
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($cfg['base_url'] ?? '', '/') . '/uploads/' . $f;
}

set_exception_handler(function ($e) { fail(500, 'Terjadi kesalahan pada server.'); });

$cfgFile = __DIR__ . '/../config/config.php';
if (!is_file($cfgFile)) fail(500, 'Konfigurasi database belum dibuat (config/config.php).');
$cfg = require $cfgFile;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    $db->set_charset('utf8mb4');
} catch (Throwable $e) {
    fail(500, 'Koneksi database gagal.');
}
