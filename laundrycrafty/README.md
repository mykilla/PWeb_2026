# LaundryCrafty
Sistem manajemen laundry (PHP 8 + MySQL + JS + CSS).

## Cara pakai (XAMPP / Laragon)
1. Salin folder `laundrycrafty` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, tab **Import**, pilih `database.sql`.
3. Sesuaikan koneksi di `lib.php` (baris `const DBC`) bila user/password MySQL Anda berbeda.
4. Buka http://localhost/laundrycrafty/

Akun contoh: **admin / admin123** (semua fitur) dan **kasir / kasir123** (transaksi, pelanggan, status, nota).
Ganti password sebelum dipakai sungguhan. Hash baru: `php -r "echo password_hash('passwordbaru',PASSWORD_DEFAULT);"`

## API (perlu login lebih dulu, sesi cookie)
GET /api/pelanggan · POST /api/transaksi · GET /api/transaksi/{id} · PUT /api/transaksi/{id} · GET /api/laporan?periode=hari|minggu|bulan
Body POST/PUT berupa JSON dengan header `Content-Type: application/json`.
Contoh: `{"id_pelanggan":1,"id_layanan":2,"berat":3.5}` dan `{"status":"Selesai"}`.

## Halaman
dashboard · transaksi (cari, ubah status, nota) · pelanggan · layanan (admin) · laporan (admin) · cek.php (status untuk pelanggan, tanpa login staf)
