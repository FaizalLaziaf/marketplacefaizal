<?php
require __DIR__ . '/../includes/init.php';
$u = require_login('penjual');
$id = (int)($_GET['id'] ?? 0); $p = null;
if ($id) {
    $st = $db->prepare('SELECT * FROM produk WHERE id_produk=? AND id_penjual=?');
    $st->bind_param('ii', $id, $u['id']); $st->execute(); $p = $st->get_result()->fetch_assoc();
    if (!$p) { http_response_code(404); exit('Produk tidak ditemukan.'); }
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim($_POST['nama_produk'] ?? ''); $kat = (int)($_POST['id_kategori'] ?? 0);
    $desk = trim($_POST['deskripsi'] ?? ''); $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0); $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
    if ($nama === '' || !$kat || $harga <= 0 || $stok < 0) { $err = 'Nama, kategori, harga (> 0), dan stok (>= 0) wajib valid.'; }
    else {
        try {
            $gambar = upload_gambar($_FILES['gambar'] ?? ['name' => ''], $p['gambar'] ?? null);
            if ($p) {
                $st = $db->prepare('UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, harga=?, stok=?, gambar=?, status=? WHERE id_produk=? AND id_penjual=?');
                $st->bind_param('issdissii', $kat, $nama, $desk, $harga, $stok, $gambar, $status, $id, $u['id']);
                $st->execute(); flash('success', 'Produk diperbarui.');
            } else {
                $st = $db->prepare('INSERT INTO produk (id_penjual,id_kategori,nama_produk,deskripsi,harga,stok,gambar,status) VALUES (?,?,?,?,?,?,?,?)');
                $st->bind_param('iissdiss', $u['id'], $kat, $nama, $desk, $harga, $stok, $gambar, $status);
                $st->execute(); flash('success', 'Produk ditambahkan.');
            }
            redirect('penjual/produk.php');
        } catch (Exception $ex) { $err = $ex->getMessage(); }
    }
    $p = ['nama_produk' => $nama, 'id_kategori' => $kat, 'deskripsi' => $desk, 'harga' => $harga, 'stok' => $stok, 'status' => $status, 'gambar' => $p['gambar'] ?? null];
}
$kategori = $db->query('SELECT * FROM kategori ORDER BY nama_kategori');
$judul = $id ? 'Edit Produk' : 'Tambah Produk';
include __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><?= e($judul) ?></h4>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card card-body shadow-sm"><?= csrf_field() ?>
 <div class="row g-3">
  <div class="col-md-8"><label class="form-label">Nama produk</label><input name="nama_produk" class="form-control" required value="<?= e($p['nama_produk'] ?? '') ?>"></div>
  <div class="col-md-4"><label class="form-label">Kategori</label><select name="id_kategori" class="form-select" required>
    <option value="">- pilih -</option>
    <?php while ($k = $kategori->fetch_assoc()): ?><option value="<?= $k['id_kategori'] ?>" <?= ($p['id_kategori'] ?? 0) == $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endwhile; ?>
  </select></div>
  <div class="col-md-4"><label class="form-label">Harga (Rp)</label><input type="number" step="1" min="1" name="harga" class="form-control" required value="<?= isset($p['harga']) ? e((int)$p['harga']) : '' ?>"></div>
  <div class="col-md-4"><label class="form-label">Stok</label><input type="number" min="0" name="stok" class="form-control" required value="<?= e($p['stok'] ?? 0) ?>"></div>
  <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">
    <option value="aktif" <?= ($p['status'] ?? 'aktif') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
    <option value="nonaktif" <?= ($p['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div>
  <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" rows="4" class="form-control"><?= e($p['deskripsi'] ?? '') ?></textarea></div>
  <div class="col-12"><label class="form-label">Gambar (JPG/PNG/WEBP, maks 2 MB)</label><input type="file" name="gambar" accept="image/*" class="form-control">
    <?php if (!empty($p['gambar'])): ?><img src="<?= gambar_url($p['gambar']) ?>" width="100" class="mt-2 rounded" alt=""><?php endif; ?></div>
 </div>
 <div class="mt-3"><button class="btn btn-primary">Simpan</button> <a href="produk.php" class="btn btn-outline-secondary">Batal</a></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
