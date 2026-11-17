<?php
require __DIR__ . '/includes/init.php';
$u = require_login('pembeli'); $uid = $u['id'];

function ambil_keranjang($db, $uid, $lock = false) {
    $sql = "SELECT k.jumlah, p.id_produk, p.nama_produk, p.harga, p.stok, p.status
            FROM keranjang k JOIN produk p ON p.id_produk=k.id_produk WHERE k.id_pengguna=?" . ($lock ? ' FOR UPDATE' : '');
    $st = $db->prepare($sql); $st->bind_param('i', $uid); $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $alamat = trim($_POST['alamat'] ?? '');
    if ($alamat === '') { flash('danger', 'Alamat pengiriman wajib diisi.'); redirect('checkout.php'); }
    try {
        $db->begin_transaction();
        $items = ambil_keranjang($db, $uid, true);
        if (!$items) throw new Exception('Keranjang kosong.');
        $total = 0;
        foreach ($items as $i) {
            if ($i['status'] !== 'aktif' || $i['jumlah'] > $i['stok'])
                throw new Exception('Stok "' . $i['nama_produk'] . '" tidak mencukupi.');
            $total += $i['harga'] * $i['jumlah'];
        }
        $kode = 'INV' . date('YmdHis') . random_int(100, 999);
        $st = $db->prepare('INSERT INTO pesanan (kode_pesanan,id_pembeli,total,alamat_kirim) VALUES (?,?,?,?)');
        $st->bind_param('sids', $kode, $uid, $total, $alamat); $st->execute();
        $idp = $db->insert_id;
        $d = $db->prepare('INSERT INTO detail_pesanan (id_pesanan,id_produk,harga_satuan,jumlah,subtotal) VALUES (?,?,?,?,?)');
        $s = $db->prepare('UPDATE produk SET stok=stok-? WHERE id_produk=?');
        foreach ($items as $i) {
            $sub = $i['harga'] * $i['jumlah'];
            $d->bind_param('iidid', $idp, $i['id_produk'], $i['harga'], $i['jumlah'], $sub); $d->execute();
            $s->bind_param('ii', $i['jumlah'], $i['id_produk']); $s->execute();
        }
        $x = $db->prepare('DELETE FROM keranjang WHERE id_pengguna=?'); $x->bind_param('i', $uid); $x->execute();
        $db->commit();
        flash('success', "Pesanan $kode berhasil dibuat.");
        redirect('pesanan.php?id=' . $idp);
    } catch (Exception $ex) {
        $db->rollback();
        flash('danger', $ex->getMessage()); redirect('keranjang.php');
    }
}

$items = ambil_keranjang($db, $uid);
if (!$items) { flash('warning', 'Keranjang masih kosong.'); redirect('keranjang.php'); }
$total = 0; foreach ($items as $i) $total += $i['harga'] * $i['jumlah'];
$judul = 'Checkout';
include __DIR__ . '/includes/header.php';
?>
<h4 class="mb-3">Checkout</h4>
<div class="row g-4">
 <div class="col-md-7"><div class="card shadow-sm"><div class="card-body">
  <form method="post"><?= csrf_field() ?>
   <label class="form-label">Alamat pengiriman</label>
   <textarea name="alamat" class="form-control mb-3" rows="4" required><?= e($u['alamat'] ?? '') ?></textarea>
   <button class="btn btn-success">Buat Pesanan</button> <a href="keranjang.php" class="btn btn-outline-secondary">Kembali</a>
  </form></div></div></div>
 <div class="col-md-5"><div class="card shadow-sm"><div class="card-body">
  <h6>Ringkasan Pesanan</h6>
  <?php foreach ($items as $i): ?>
    <div class="d-flex justify-content-between small"><span><?= e($i['nama_produk']) ?> x<?= (int)$i['jumlah'] ?></span><span><?= rupiah($i['harga'] * $i['jumlah']) ?></span></div>
  <?php endforeach; ?>
  <hr><div class="d-flex justify-content-between fw-bold"><span>Total</span><span><?= rupiah($total) ?></span></div>
 </div></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
