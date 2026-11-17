<?php
require __DIR__ . '/includes/init.php';
$judul = 'Daftar Produk';
$q = trim($_GET['q'] ?? ''); $kat = (int)($_GET['kategori'] ?? 0);
$sql = "SELECT p.*, k.nama_kategori, u.nama AS penjual FROM produk p
        JOIN kategori k ON k.id_kategori=p.id_kategori
        JOIN pengguna u ON u.id_pengguna=p.id_penjual WHERE p.status='aktif'";
$types = ''; $params = [];
if ($q !== '') { $sql .= ' AND p.nama_produk LIKE ?'; $types .= 's'; $params[] = "%$q%"; }
if ($kat) { $sql .= ' AND p.id_kategori=?'; $types .= 'i'; $params[] = $kat; }
$sql .= ' ORDER BY p.created_at DESC, p.id_produk DESC';
$st = $db->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute(); $produk = $st->get_result();
$kategori = $db->query('SELECT * FROM kategori ORDER BY nama_kategori');
include __DIR__ . '/includes/header.php';
?>
<form class="row g-2 mb-4" method="get">
  <div class="col-md-6"><input class="form-control" name="q" placeholder="Cari produk..." value="<?= e($q) ?>"></div>
  <div class="col-md-4">
    <select class="form-select" name="kategori">
      <option value="0">Semua kategori</option>
      <?php while ($k = $kategori->fetch_assoc()): ?>
        <option value="<?= $k['id_kategori'] ?>" <?= $kat == $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid"><button class="btn btn-primary">Cari</button></div>
</form>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
<?php if ($produk->num_rows === 0): ?>
  <div class="col-12"><div class="alert alert-info">Produk tidak ditemukan.</div></div>
<?php endif; while ($p = $produk->fetch_assoc()): ?>
  <div class="col"><div class="card h-100 shadow-sm">
    <img src="<?= gambar_url($p['gambar']) ?>" class="card-img-top" style="height:180px;object-fit:cover" alt="">
    <div class="card-body">
      <span class="badge text-bg-secondary mb-2"><?= e($p['nama_kategori']) ?></span>
      <h6 class="card-title"><?= e($p['nama_produk']) ?></h6>
      <div class="fw-bold text-primary"><?= rupiah($p['harga']) ?></div>
      <small class="text-muted">Penjual: <?= e($p['penjual']) ?> &middot; Stok: <?= (int)$p['stok'] ?></small>
    </div>
    <div class="card-footer bg-white border-0"><a class="btn btn-outline-primary w-100" href="produk.php?id=<?= $p['id_produk'] ?>">Lihat Detail</a></div>
  </div></div>
<?php endwhile; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
