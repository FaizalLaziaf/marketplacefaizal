<?php
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function base() { global $cfg; return rtrim($cfg['base_url'] ?? '', '/'); }
function redirect($path) { header('Location: ' . base() . '/' . ltrim($path, '/')); exit; }
function login_user() { return $_SESSION['user'] ?? null; }
function require_login($role = null) {
    $u = login_user();
    if (!$u) { flash('warning', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
    if ($role && $u['role'] !== $role) { http_response_code(403); exit('Akses ditolak.'); }
    return $u;
}
function flash($type, $msg) { $_SESSION['flash'] = [$type, $msg]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        [$t, $m] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($t) . ' alert-dismissible fade show">' . e($m) .
             '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Token tidak valid. Muat ulang halaman.');
    }
}
function cart_count($db) {
    $u = login_user();
    if (!$u || $u['role'] !== 'pembeli') return 0;
    $st = $db->prepare('SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_pengguna=?');
    $st->bind_param('i', $u['id']); $st->execute();
    return (int)$st->get_result()->fetch_row()[0];
}
function upload_gambar($file, $lama = null) {
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return $lama;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) throw new Exception('Gambar gagal diunggah (maks 2 MB).');
    $info = @getimagesize($file['tmp_name']);
    $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($ok[$info['mime']])) throw new Exception('Format gambar harus JPG, PNG, atau WEBP.');
    $nama = bin2hex(random_bytes(8)) . '.' . $ok[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $nama)) throw new Exception('Gagal menyimpan gambar.');
    if ($lama && is_file(__DIR__ . '/../uploads/' . $lama)) @unlink(__DIR__ . '/../uploads/' . $lama);
    return $nama;
}
function gambar_url($f) {
    return $f ? base() . '/uploads/' . e($f) : 'https://placehold.co/400x300?text=Tanpa+Gambar';
}
