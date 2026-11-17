<?php
require __DIR__ . '/../includes/init.php';
$u = require_login('penjual'); $judul = 'Kelola Produk';
$st = $db->prepare('SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON k.id_kategori=p.id_kategori WHERE p.id_penjual=? ORDER BY p.id_produk DESC');
$st->bind_param('i', $u['id']); $st->execute(); $list = $st->get_result();
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between mb-3"><h4>Kelola Produk</h4><a class="btn btn-primary" href="produk_form.php">+ Tambah Produk</a></div>
<div class="table-responsive"><table class="table table-bordered bg-white align-middle">
<thead class="table-light"><tr><th>#</th><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if (!$list->num_rows): ?><tr><td colspan="8" class="text-center text-muted">Belum ada produk.</td></tr><?php endif;
$no = 1; while ($p = $list->fetch_assoc()): ?>
<tr><td><?= $no++ ?></td><td><img src="<?= gambar_url($p['gambar']) ?>" width="50" height="50" style="object-fit:cover" class="rounded" alt=""></td>
<td><?= e($p['nama_produk']) ?></td><td><?= e($p['nama_kategori']) ?></td><td><?= rupiah($p['harga']) ?></td><td><?= (int)$p['stok'] ?></td>
<td><span class="badge text-bg-<?= $p['status'] === 'aktif' ? 'success' : 'secondary' ?>"><?= e($p['status']) ?></span></td>
<td class="text-nowrap"><a class="btn btn-sm btn-warning" href="produk_form.php?id=<?= $p['id_produk'] ?>">Edit</a>
<form method="post" action="produk_hapus.php" class="d-inline" onsubmit="return confirm('Hapus produk ini?')"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $p['id_produk'] ?>"><button class="btn btn-sm btn-danger">Hapus</button></form></td></tr>
<?php endwhile; ?></tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
