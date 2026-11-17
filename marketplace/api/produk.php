<?php
require __DIR__ . '/_bootstrap.php';
allow(['GET']);

function fmt($r) {
    return [
        'id_produk'   => (int)$r['id_produk'],
        'nama_produk' => $r['nama_produk'],
        'deskripsi'   => $r['deskripsi'],
        'harga'       => (float)$r['harga'],
        'stok'        => (int)$r['stok'],
        'gambar'      => gambar_abs($r['gambar']),
        'kategori'    => ['id_kategori' => (int)$r['id_kategori'], 'nama_kategori' => $r['nama_kategori']],
        'penjual'     => ['id_penjual' => (int)$r['id_penjual'], 'nama' => $r['penjual']],
    ];
}
$select = "SELECT p.*, k.nama_kategori, u.nama AS penjual FROM produk p
           JOIN kategori k ON k.id_kategori=p.id_kategori
           JOIN pengguna u ON u.id_pengguna=p.id_penjual WHERE p.status='aktif'";

// GET /api/produk.php?id=1  -> detail
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$id || $id < 1) fail(400, 'Parameter id harus berupa angka positif.');
    $st = $db->prepare($select . ' AND p.id_produk=?');
    $st->bind_param('i', $id); $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if (!$r) fail(404, "Produk dengan id $id tidak ditemukan.");
    ok(fmt($r));
}

// GET /api/produk.php  -> daftar (opsional: q, kategori, page, limit)
$cond = ''; $types = ''; $params = [];
$q = trim($_GET['q'] ?? '');
if ($q !== '') { $cond .= ' AND p.nama_produk LIKE ?'; $types .= 's'; $params[] = "%$q%"; }
if (isset($_GET['kategori'])) {
    $kat = filter_var($_GET['kategori'], FILTER_VALIDATE_INT);
    if (!$kat || $kat < 1) fail(400, 'Parameter kategori harus berupa angka positif.');
    $cond .= ' AND p.id_kategori=?'; $types .= 'i'; $params[] = $kat;
}
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$c = $db->prepare("SELECT COUNT(*) FROM produk p WHERE p.status='aktif'" . $cond);
if ($params) $c->bind_param($types, ...$params);
$c->execute();
$total = (int)$c->get_result()->fetch_row()[0];

$st = $db->prepare($select . $cond . ' ORDER BY p.id_produk LIMIT ? OFFSET ?');
$t2 = $types . 'ii'; $p2 = array_merge($params, [$limit, $offset]);
$st->bind_param($t2, ...$p2); $st->execute();
$data = array_map('fmt', $st->get_result()->fetch_all(MYSQLI_ASSOC));
ok($data, 200, ['meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => (int)ceil($total / $limit)]]);
