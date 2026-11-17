<?php $u = login_user(); $judul = $judul ?? 'Marketplace'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - Mini Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
 <div class="container">
  <a class="navbar-brand fw-bold" href="<?= base() ?>/index.php">Mini Marketplace</a>
  <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="nv">
   <ul class="navbar-nav ms-auto align-items-lg-center">
    <li class="nav-item"><a class="nav-link" href="<?= base() ?>/index.php">Produk</a></li>
    <?php if ($u && $u['role'] === 'pembeli'): ?>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/keranjang.php">Keranjang <span class="badge bg-warning text-dark"><?= cart_count($db) ?></span></a></li>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/pesanan.php">Pesanan Saya</a></li>
    <?php elseif ($u && $u['role'] === 'penjual'): ?>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/penjual/produk.php">Kelola Produk</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/pesanan.php">Pesanan Masuk</a></li>
    <?php endif; ?>
    <?php if ($u): ?>
      <li class="nav-item"><span class="nav-link text-white-50"><?= e($u['nama']) ?> (<?= e($u['role']) ?>)</span></li>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/logout.php">Keluar</a></li>
    <?php else: ?>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/login.php">Masuk</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= base() ?>/register.php">Daftar</a></li>
    <?php endif; ?>
   </ul>
  </div>
 </div>
</nav>
<main class="container">
<?php show_flash(); ?>
