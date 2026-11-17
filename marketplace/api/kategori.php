<?php
require __DIR__ . '/_bootstrap.php';
allow(['GET']);

$sql = "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
        FROM kategori k LEFT JOIN produk p ON p.id_kategori=k.id_kategori AND p.status='aktif'";
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$id || $id < 1) fail(400, 'Parameter id harus berupa angka positif.');
    $st = $db->prepare($sql . ' WHERE k.id_kategori=? GROUP BY k.id_kategori, k.nama_kategori');
    $st->bind_param('i', $id); $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if (!$r) fail(404, "Kategori dengan id $id tidak ditemukan.");
    ok(['id_kategori' => (int)$r['id_kategori'], 'nama_kategori' => $r['nama_kategori'], 'jumlah_produk' => (int)$r['jumlah_produk']]);
}
$rows = $db->query($sql . ' GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.nama_kategori')->fetch_all(MYSQLI_ASSOC);
$data = array_map(fn($r) => ['id_kategori' => (int)$r['id_kategori'], 'nama_kategori' => $r['nama_kategori'], 'jumlah_produk' => (int)$r['jumlah_produk']], $rows);
ok($data, 200, ['meta' => ['total' => count($data)]]);
