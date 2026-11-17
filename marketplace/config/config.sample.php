<?php
// Salin file ini menjadi config/config.php lalu isi sesuai hosting Anda.
// File config.php sudah masuk .gitignore sehingga tidak terunggah ke GitHub.
return [
    'host' => 'localhost',          // InfinityFree: mis. sqlXXX.infinityfree.com
    'user' => 'db_user',            // InfinityFree: mis. if0_12345678
    'pass' => 'db_password',
    'name' => 'db_name',            // InfinityFree: mis. if0_12345678_marketplace
    'base_url' => '',               // kosong jika di root domain, mis. '/marketplace' jika di subfolder
];
