<?php
require __DIR__ . '/includes/init.php';
$u = require_login('pembeli'); $uid = $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? ''; $idp = (int)($_POST['id_produk'] ?? 0);
    if ($aksi === 'tambah') {
        $jml = max(1, (int)($_POST['jumlah'] ?? 1));
        $s = $db->prepare("SELECT stok FROM produk WHERE id_produk=? AND status='aktif'");
        $s->bind_param('i', $idp); $s->execute(); $p = $s->get_result()->fetch_assoc();
        if (!$p) { flash('danger', 'Produk tidak tersedia.'); redirect('index.php'); }
        $s = $db->prepare('SELECT jumlah FROM keranjang WHERE id_pengguna=? AND id_produk=?');
        $s->bind_param('ii', $uid, $idp); $s->execute();
        $ada = $s->get_result()->fetch_assoc();
        $baru = min($p['stok'], ($ada['jumlah'] ?? 0) + $jml);
        if ($ada) { $s = $db->prepare('UPDATE keranjang SET jumlah=? WHERE id_pengguna=? AND id_produk=?'); $s->bind_param('iii', $baru, $uid, $idp); }
        else { $s = $db->prepare('INSERT INTO keranjang (id_pengguna,id_produk,jumlah) VALUES (?,?,?)'); $s->bind_param('iii', $uid, $idp, $baru); }
        $s->execute();
        flash('success', 'Produk ditambahkan ke keranjang.');
    } elseif ($aksi === 'ubah') {
        $jml = (int)($_POST['jumlah'] ?? 1);
        $s = $db->prepare('SELECT stok FROM produk WHERE id_produk=?'); $s->bind_param('i', $idp); $s->execute();
        $stok = (int)($s->get_result()->fetch_row()[0] ?? 0);
        $jml = max(1, min($jml, $stok));
        $s = $db->prepare('UPDATE keranjang SET jumlah=? WHERE id_pengguna=? AND id_produk=?');
        $s->bind_param('iii', $jml, $uid, $idp); $s->execute();
    } elseif ($aksi === 'hapus') {
        $s = $db->prepare('DELETE FROM keranjang WHERE id_pengguna=? AND id_produk=?');
        $s->bind_param('ii', $uid, $idp); $s->execute();
        flash('info', 'Item dihapus dari keranjang.');
    }
    redirect('keranjang.php');
}

$st = $db->prepare("SELECT k.jumlah, p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar
    FROM keranjang k JOIN produk p ON p.id_produk=k.id_produk WHERE k.id_pengguna=? ORDER BY k.id_keranjang");
$st->bind_param('i', $uid); $st->execute(); $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$total = 0; foreach ($items as $i) $total += $i['harga'] * $i['jumlah'];
$judul = 'Keranjang Belanja';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3">Keranjang Belanja</h4>
<?php if (!$items): ?>
  <div class="alert alert-info">Keranjang masih kosong. <a href="index.php">Belanja sekarang</a></div>
<?php else: ?>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
<thead class="table-light"><tr><th>Produk</th><th>Harga</th><th style="width:170px">Jumlah</th><th>Subtotal</th><th></th></tr></thead><tbody>
<?php foreach ($items as $i): ?>
<tr>
  <td><img src="<?= gambar_url($i['gambar']) ?>" width="50" height="50" style="object-fit:cover" class="me-2 rounded"><?= e($i['nama_produk']) ?></td>
  <td><?= rupiah($i['harga']) ?></td>
  <td><form method="post" class="d-flex gap-1"><?= csrf_field() ?>
    <input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id_produk" value="<?= $i['id_produk'] ?>">
    <input type="number" name="jumlah" value="<?= (int)$i['jumlah'] ?>" min="1" max="<?= (int)$i['stok'] ?>" class="form-control form-control-sm">
    <button class="btn btn-sm btn-outline-secondary">Ubah</button></form></td>
  <td><?= rupiah($i['harga'] * $i['jumlah']) ?></td>
  <td><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_produk" value="<?= $i['id_produk'] ?>">
    <button class="btn btn-sm btn-outline-danger">Hapus</button></form></td>
</tr>
<?php endforeach; ?>
<tr class="table-light"><th colspan="3" class="text-end">Total</th><th colspan="2"><?= rupiah($total) ?></th></tr>
</tbody></table></div>
<div class="text-end"><a href="index.php" class="btn btn-outline-secondary">Lanjut Belanja</a> <a href="checkout.php" class="btn btn-success">Checkout</a></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
