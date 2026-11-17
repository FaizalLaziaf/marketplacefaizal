<?php
require __DIR__ . '/includes/init.php';
$u = require_login(); $uid = $u['id']; $judul = 'Pesanan';
$id = (int)($_GET['id'] ?? 0);

if ($u['role'] === 'pembeli' && $id) {            // detail pesanan pembeli
    $st = $db->prepare('SELECT * FROM pesanan WHERE id_pesanan=? AND id_pembeli=?');
    $st->bind_param('ii', $id, $uid); $st->execute(); $o = $st->get_result()->fetch_assoc();
    if (!$o) { http_response_code(404); exit('Pesanan tidak ditemukan.'); }
    $st = $db->prepare('SELECT d.*, p.nama_produk FROM detail_pesanan d JOIN produk p ON p.id_produk=d.id_produk WHERE d.id_pesanan=?');
    $st->bind_param('i', $id); $st->execute(); $det = $st->get_result();
    include __DIR__ . '/includes/header.php'; ?>
    <h4>Pesanan <?= e($o['kode_pesanan']) ?></h4>
    <p class="text-muted"><?= e($o['tanggal']) ?> &middot; Status: <span class="badge text-bg-warning"><?= e($o['status']) ?></span><br>Alamat: <?= nl2br(e($o['alamat_kirim'])) ?></p>
    <table class="table table-bordered bg-white"><thead class="table-light"><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead><tbody>
    <?php while ($d = $det->fetch_assoc()): ?>
      <tr><td><?= e($d['nama_produk']) ?></td><td><?= rupiah($d['harga_satuan']) ?></td><td><?= (int)$d['jumlah'] ?></td><td><?= rupiah($d['subtotal']) ?></td></tr>
    <?php endwhile; ?>
    <tr class="table-light"><th colspan="3" class="text-end">Total</th><th><?= rupiah($o['total']) ?></th></tr></tbody></table>
    <a href="pesanan.php" class="btn btn-outline-secondary">Kembali</a>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

if ($u['role'] === 'pembeli') {                   // daftar pesanan pembeli
    $st = $db->prepare('SELECT * FROM pesanan WHERE id_pembeli=? ORDER BY id_pesanan DESC');
    $st->bind_param('i', $uid); $st->execute(); $list = $st->get_result();
    include __DIR__ . '/includes/header.php'; ?>
    <h4 class="mb-3">Pesanan Saya</h4>
    <table class="table table-bordered bg-white"><thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$list->num_rows): ?><tr><td colspan="5" class="text-center text-muted">Belum ada pesanan.</td></tr><?php endif;
    while ($o = $list->fetch_assoc()): ?>
      <tr><td><?= e($o['kode_pesanan']) ?></td><td><?= e($o['tanggal']) ?></td><td><?= rupiah($o['total']) ?></td><td><?= e($o['status']) ?></td>
      <td><a class="btn btn-sm btn-outline-primary" href="pesanan.php?id=<?= $o['id_pesanan'] ?>">Detail</a></td></tr>
    <?php endwhile; ?></tbody></table>
    <?php include __DIR__ . '/includes/footer.php'; exit;
}

// penjual: pesanan masuk untuk produknya
$st = $db->prepare("SELECT o.kode_pesanan, o.tanggal, o.status, b.nama AS pembeli, p.nama_produk, d.jumlah, d.subtotal
    FROM detail_pesanan d JOIN produk p ON p.id_produk=d.id_produk JOIN pesanan o ON o.id_pesanan=d.id_pesanan
    JOIN pengguna b ON b.id_pengguna=o.id_pembeli WHERE p.id_penjual=? ORDER BY o.id_pesanan DESC");
$st->bind_param('i', $uid); $st->execute(); $list = $st->get_result();
include __DIR__ . '/includes/header.php'; ?>
<h4 class="mb-3">Pesanan Masuk</h4>
<table class="table table-bordered bg-white"><thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Pembeli</th><th>Produk</th><th>Jml</th><th>Subtotal</th><th>Status</th></tr></thead><tbody>
<?php if (!$list->num_rows): ?><tr><td colspan="7" class="text-center text-muted">Belum ada pesanan masuk.</td></tr><?php endif;
while ($r = $list->fetch_assoc()): ?>
<tr><td><?= e($r['kode_pesanan']) ?></td><td><?= e($r['tanggal']) ?></td><td><?= e($r['pembeli']) ?></td><td><?= e($r['nama_produk']) ?></td><td><?= (int)$r['jumlah'] ?></td><td><?= rupiah($r['subtotal']) ?></td><td><?= e($r['status']) ?></td></tr>
<?php endwhile; ?></tbody></table>
<?php include __DIR__ . '/includes/footer.php'; ?>
