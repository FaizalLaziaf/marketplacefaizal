<?php
require __DIR__ . '/includes/init.php';
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT p.*, k.nama_kategori, u.nama AS penjual FROM produk p
    JOIN kategori k ON k.id_kategori=p.id_kategori JOIN pengguna u ON u.id_pengguna=p.id_penjual
    WHERE p.id_produk=? AND p.status='aktif'");
$st->bind_param('i', $id); $st->execute();
$p = $st->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); exit('Produk tidak ditemukan.'); }
$judul = $p['nama_produk'];
include __DIR__ . '/includes/header.php';
?>
<nav class="mb-3"><a href="index.php">&larr; Kembali ke daftar produk</a></nav>
<div class="card shadow-sm"><div class="card-body"><div class="row g-4">
  <div class="col-md-5"><img src="<?= gambar_url($p['gambar']) ?>" class="img-fluid rounded" alt=""></div>
  <div class="col-md-7">
    <span class="badge text-bg-secondary"><?= e($p['nama_kategori']) ?></span>
    <h3 class="mt-2"><?= e($p['nama_produk']) ?></h3>
    <h4 class="text-primary"><?= rupiah($p['harga']) ?></h4>
    <p class="text-muted mb-1">Penjual: <?= e($p['penjual']) ?></p>
    <p class="mb-3">Stok: <strong><?= (int)$p['stok'] ?></strong></p>
    <p><?= nl2br(e($p['deskripsi'])) ?></p>
    <?php $u = login_user(); if ($p['stok'] < 1): ?>
      <div class="alert alert-warning">Stok habis.</div>
    <?php elseif ($u && $u['role'] === 'penjual'): ?>
      <div class="alert alert-info">Login sebagai pembeli untuk memesan.</div>
    <?php else: ?>
      <form method="post" action="keranjang.php" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="tambah">
        <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
        <div class="col-auto"><label class="form-label">Jumlah</label>
          <input type="number" name="jumlah" class="form-control" value="1" min="1" max="<?= (int)$p['stok'] ?>" style="width:100px"></div>
        <div class="col-auto"><button class="btn btn-primary">+ Tambah ke Keranjang</button></div>
      </form>
    <?php endif; ?>
  </div>
</div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
