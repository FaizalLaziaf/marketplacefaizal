<?php
require __DIR__ . '/../includes/init.php';
$u = require_login('penjual');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('penjual/produk.php');
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$st = $db->prepare('SELECT gambar FROM produk WHERE id_produk=? AND id_penjual=?');
$st->bind_param('ii', $id, $u['id']); $st->execute(); $p = $st->get_result()->fetch_assoc();
if (!$p) { flash('danger', 'Produk tidak ditemukan.'); redirect('penjual/produk.php'); }
$c = $db->prepare('SELECT 1 FROM detail_pesanan WHERE id_produk=? LIMIT 1');
$c->bind_param('i', $id); $c->execute();
if ($c->get_result()->num_rows) {   // sudah pernah dipesan -> nonaktifkan agar riwayat pesanan utuh
    $s = $db->prepare("UPDATE produk SET status='nonaktif' WHERE id_produk=?"); $s->bind_param('i', $id); $s->execute();
    flash('info', 'Produk sudah pernah dipesan, sehingga dinonaktifkan (bukan dihapus).');
} else {
    $s = $db->prepare('DELETE FROM produk WHERE id_produk=? AND id_penjual=?'); $s->bind_param('ii', $id, $u['id']); $s->execute();
    if ($p['gambar'] && is_file(__DIR__ . '/../uploads/' . $p['gambar'])) @unlink(__DIR__ . '/../uploads/' . $p['gambar']);
    flash('success', 'Produk dihapus.');
}
redirect('penjual/produk.php');
