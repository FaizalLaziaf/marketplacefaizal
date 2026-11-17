<?php
require __DIR__ . '/_bootstrap.php';
ok([
    'nama' => 'Mini Marketplace REST API',
    'versi' => '1.0',
    'endpoint' => [
        ['method' => 'GET',  'path' => 'api/produk.php',              'fungsi' => 'Daftar produk (q, kategori, page, limit)'],
        ['method' => 'GET',  'path' => 'api/produk.php?id={id}',      'fungsi' => 'Detail produk'],
        ['method' => 'GET',  'path' => 'api/kategori.php',            'fungsi' => 'Daftar kategori'],
        ['method' => 'POST', 'path' => 'api/pesanan.php',             'fungsi' => 'Membuat pesanan baru (JSON)'],
        ['method' => 'GET',  'path' => 'api/pesanan.php?id={id}',     'fungsi' => 'Detail pesanan (juga ?kode= atau ?id_pembeli=)'],
    ],
]);
