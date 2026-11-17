<?php
require __DIR__ . '/includes/init.php';
$judul = 'Masuk'; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $st = $db->prepare('SELECT * FROM pengguna WHERE email=?'); $st->bind_param('s', $email); $st->execute();
    $u = $st->get_result()->fetch_assoc();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id_pengguna'], 'nama' => $u['nama'], 'role' => $u['role'], 'alamat' => $u['alamat']];
        redirect($u['role'] === 'penjual' ? 'penjual/produk.php' : 'index.php');
    }
    $err = 'Email atau password salah.';
}
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5"><div class="card shadow-sm"><div class="card-body">
<h4>Masuk</h4>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
  <button class="btn btn-primary w-100">Masuk</button>
</form>
<p class="small text-muted mt-3 mb-0">Akun contoh: pembeli@contoh.test / penjual@contoh.test (password: password123)</p>
</div></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
