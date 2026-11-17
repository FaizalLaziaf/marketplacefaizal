# Mini Marketplace (PHP Native + MySQLi + Bootstrap 5)

Aplikasi marketplace sederhana: penjual mengelola produk (CRUD), pembeli memilih produk,
memasukkan ke keranjang, dan membuat pesanan yang tersimpan di database.

## Instalasi
1. Buat database MySQL, lalu impor `database/marketplace.sql` lewat phpMyAdmin.
2. Salin `config/config.sample.php` menjadi `config/config.php`, isi host/user/password/nama database.
3. Unggah semua file ke folder `htdocs` hosting (InfinityFree). Pastikan folder `uploads/` dapat ditulis.
4. REST API

`config/config.php` ada di `.gitignore` agar kredensial tidak masuk repository.
