<?php
require __DIR__ . '/_bootstrap.php';

class ApiError extends Exception {
    public $status;
    public function __construct($status, $msg) { parent::__construct($msg); $this->status = $status; }
}
allow(['GET', 'POST']);

function ambil_pesanan($db, $col, $val, $type) {   // $col hanya diisi konstanta internal
    $st = $db->prepare("SELECT p.id_pesanan,p.kode_pesanan,p.id_pembeli,u.nama AS pembeli,p.tanggal,p.total,p.alamat_kirim,p.status
        FROM pesanan p JOIN pengguna u ON u.id_pengguna=p.id_pembeli WHERE p.$col=?");
    $st->bind_param($type, $val); $st->execute();
    $o = $st->get_result()->fetch_assoc();
    if (!$o) return null;
    $d = $db->prepare("SELECT d.id_produk,pr.nama_produk,d.harga_satuan,d.jumlah,d.subtotal
        FROM detail_pesanan d JOIN produk pr ON pr.id_produk=d.id_produk WHERE d.id_pesanan=?");
    $d->bind_param('i', $o['id_pesanan']); $d->execute();
    $items = [];
    foreach ($d->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $items[] = ['id_produk' => (int)$r['id_produk'], 'nama_produk' => $r['nama_produk'],
                    'harga_satuan' => (float)$r['harga_satuan'], 'jumlah' => (int)$r['jumlah'], 'subtotal' => (float)$r['subtotal']];
    }
    return ['id_pesanan' => (int)$o['id_pesanan'], 'kode_pesanan' => $o['kode_pesanan'], 'id_pembeli' => (int)$o['id_pembeli'],
            'pembeli' => $o['pembeli'], 'tanggal' => $o['tanggal'], 'total' => (float)$o['total'],
            'alamat_kirim' => $o['alamat_kirim'], 'status' => $o['status'], 'items' => $items];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) || isset($_GET['kode'])) {
        if (isset($_GET['id'])) {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id || $id < 1) fail(400, 'Parameter id harus berupa angka positif.');
            $o = ambil_pesanan($db, 'id_pesanan', $id, 'i');
        } else {
            $o = ambil_pesanan($db, 'kode_pesanan', trim($_GET['kode']), 's');
        }
        if (!$o) fail(404, 'Pesanan tidak ditemukan.');
        ok($o);
    }
    if (isset($_GET['id_pembeli'])) {
        $idb = filter_var($_GET['id_pembeli'], FILTER_VALIDATE_INT);
        if (!$idb || $idb < 1) fail(400, 'Parameter id_pembeli harus berupa angka positif.');
        $st = $db->prepare('SELECT id_pesanan,kode_pesanan,tanggal,total,status FROM pesanan WHERE id_pembeli=? ORDER BY id_pesanan DESC');
        $st->bind_param('i', $idb); $st->execute();
        $rows = array_map(fn($r) => ['id_pesanan' => (int)$r['id_pesanan'], 'kode_pesanan' => $r['kode_pesanan'],
            'tanggal' => $r['tanggal'], 'total' => (float)$r['total'], 'status' => $r['status']], $st->get_result()->fetch_all(MYSQLI_ASSOC));
        ok($rows, 200, ['meta' => ['total' => count($rows)]]);
    }
    fail(400, 'Sertakan parameter id, kode, atau id_pembeli.');
}

// ===== POST /api/pesanan.php : buat pesanan baru =====
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) fail(400, 'Body request harus berupa JSON yang valid (Content-Type: application/json).');

$idb = filter_var($in['id_pembeli'] ?? null, FILTER_VALIDATE_INT);
if (!$idb || $idb < 1) fail(422, 'id_pembeli wajib berupa angka positif.');
$items = $in['items'] ?? null;
if (!is_array($items) || !$items) fail(422, 'items wajib berisi minimal 1 produk.');

$qty = [];
foreach ($items as $it) {
    if (!is_array($it)) fail(422, 'Setiap item harus berupa objek {id_produk, jumlah}.');
    $ip = filter_var($it['id_produk'] ?? null, FILTER_VALIDATE_INT);
    $jm = filter_var($it['jumlah'] ?? null, FILTER_VALIDATE_INT);
    if (!$ip || $ip < 1 || !$jm || $jm < 1) fail(422, 'Setiap item wajib punya id_produk dan jumlah berupa angka positif.');
    $qty[$ip] = ($qty[$ip] ?? 0) + $jm;
}

$st = $db->prepare('SELECT role, alamat FROM pengguna WHERE id_pengguna=?');
$st->bind_param('i', $idb); $st->execute();
$u = $st->get_result()->fetch_assoc();
if (!$u) fail(404, "Pengguna dengan id $idb tidak ditemukan.");
if ($u['role'] !== 'pembeli') fail(403, 'Hanya akun pembeli yang dapat membuat pesanan.');
$alamat = trim((string)($in['alamat_kirim'] ?? ''));
if ($alamat === '') $alamat = trim((string)$u['alamat']);
if ($alamat === '') fail(422, 'alamat_kirim wajib diisi.');

try {
    $db->begin_transaction();
    $ids = array_keys($qty);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $db->prepare("SELECT id_produk,nama_produk,harga,stok,status FROM produk WHERE id_produk IN ($ph) FOR UPDATE");
    $st->bind_param(str_repeat('i', count($ids)), ...$ids); $st->execute();
    $prod = [];
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $prod[(int)$r['id_produk']] = $r;

    $total = 0;
    foreach ($qty as $ip => $jm) {
        if (!isset($prod[$ip])) throw new ApiError(404, "Produk dengan id $ip tidak ditemukan.");
        $p = $prod[$ip];
        if ($p['status'] !== 'aktif') throw new ApiError(422, 'Produk "' . $p['nama_produk'] . '" tidak tersedia.');
        if ($jm > (int)$p['stok']) throw new ApiError(409, 'Stok produk "' . $p['nama_produk'] . '" tidak mencukupi (tersisa ' . (int)$p['stok'] . ').');
        $total += $p['harga'] * $jm;
    }
    $kode = 'INV' . date('YmdHis') . random_int(100, 999);
    $st = $db->prepare('INSERT INTO pesanan (kode_pesanan,id_pembeli,total,alamat_kirim) VALUES (?,?,?,?)');
    $st->bind_param('sids', $kode, $idb, $total, $alamat); $st->execute();
    $idp = $db->insert_id;

    $d = $db->prepare('INSERT INTO detail_pesanan (id_pesanan,id_produk,harga_satuan,jumlah,subtotal) VALUES (?,?,?,?,?)');
    $s = $db->prepare('UPDATE produk SET stok=stok-? WHERE id_produk=?');
    foreach ($qty as $ip => $jm) {
        $harga = (float)$prod[$ip]['harga']; $sub = $harga * $jm;
        $d->bind_param('iidid', $idp, $ip, $harga, $jm, $sub); $d->execute();
        $s->bind_param('ii', $jm, $ip); $s->execute();
    }
    $db->commit();
} catch (ApiError $e) {
    $db->rollback();
    fail($e->status, $e->getMessage());
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}
ok(ambil_pesanan($db, 'id_pesanan', $idp, 'i'), 201, ['message' => 'Pesanan berhasil dibuat.']);
