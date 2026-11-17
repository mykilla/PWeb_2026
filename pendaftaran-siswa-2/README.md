# Aplikasi Pendaftaran Siswa Baru (PSB) v2

Aplikasi web PSB berbasis **PHP Native + MySQL** dengan akun pendaftar, aturan bisnis penerimaan,
pembayaran online (**Midtrans Snap** + mode simulasi), dan pengamanan berlapis.

> Spesifikasi proyek lengkap (Nama Proyek, Deskripsi, Tujuan, Fitur, Rencana Implementasi) tersedia pada dokumen terpisah;
> ringkasannya ada di bawah. Dokumen API: [`docs/API.md`](docs/API.md).

## Ringkasan Spesifikasi

| | |
|---|---|
| **Nama proyek** | Sistem Informasi Pendaftaran Siswa Baru (PSB Online) SMK Coding |
| **Deskripsi** | Website pendaftaran siswa baru: calon siswa membuat akun, mengisi biodata, membayar biaya pendaftaran, dan memantau hasil seleksi; panitia mengelola data, memverifikasi, dan menetapkan penerimaan sesuai kuota. |
| **Tujuan** | Menggantikan pendaftaran manual, mempercepat verifikasi, memastikan pembayaran tercatat otomatis, dan menjaga data pribadi calon siswa tetap aman. |

### Ruang lingkup

| Area | Implementasi |
|---|---|
| **Pengelolaan data** | CRUD pendaftar, jurusan (kuota), gelombang (periode & biaya); pencarian, filter, paginasi; ekspor CSV (aman dari formula injection); unggah dokumen tervalidasi; audit log |
| **Logika bisnis** | Pendaftaran hanya pada gelombang aktif; satu akun satu pendaftaran; NISN unik; batas usia; nomor pendaftaran otomatis `PSB-2026-00001`; alur status terkontrol (`menunggu_pembayaran → menunggu_verifikasi → terverifikasi → diterima/ditolak`); kuota jurusan ditegakkan secara transaksional; biodata terkunci setelah bayar; tagihan pending tidak dobel |
| **Autentikasi & keamanan** | Role `admin`/`pendaftar`; `password_hash`; kebijakan password; wajib ganti password bawaan; pembatasan percobaan login & endpoint publik; CSRF; sesi aman (regenerasi ID, idle timeout, cookie HttpOnly/SameSite); otorisasi per objek (anti-IDOR); header keamanan + CSP; prepared statement; output escaping; berkas unggahan tidak dapat diakses langsung |
| **API / integrasi** | Midtrans Snap (buat transaksi) + webhook ber-signature SHA-512 yang idempoten dan memeriksa jumlah; REST JSON: `jurusan`, `status`, `pembayaran-buat`, `pembayaran-notifikasi` |

### Rencana implementasi

| Fase | Pekerjaan | Hasil |
|---|---|---|
| 1. Analisis & desain | Kebutuhan, ERD, alur status, kontrak API | Dokumen spesifikasi |
| 2. Fondasi | Skema DB, konfigurasi `.env`, autentikasi, keamanan dasar | Login & role berfungsi |
| 3. Fitur inti | Biodata, aturan bisnis, kuota, panel admin | Alur daftar → verifikasi → seleksi |
| 4. Integrasi pembayaran | Midtrans Snap, webhook, simulasi lokal | Pembayaran otomatis |
| 5. Pengujian | Unit test, E2E, uji keamanan, uji sandbox Midtrans | Laporan uji |
| 6. Deploy | HTTPS, `APP_ENV=production`, backup DB, monitoring | Rilis |

## Instalasi (Laragon / XAMPP)

1. Salin folder proyek ke `C:\laragon\www\pendaftaran-siswa`.
2. Jalankan Apache dan MySQL.
3. Impor `database.sql` (membuat database `db_pendaftaran`, tabel, dan data awal).
4. Salin `.env.example` menjadi `.env`, sesuaikan `DB_*` bila perlu (default Laragon: `root`, tanpa password).
5. Pastikan folder `uploads/` dapat ditulis PHP. Persyaratan: PHP ≥ 8.0 dengan ekstensi `mysqli`, `mbstring`, `fileinfo`, `curl`.
6. Buka `http://localhost/pendaftaran-siswa/`.

**Akun admin awal:** `admin@smkcoding.test` / `AdminPSB!2026`. Sistem **memaksa ganti password pada login pertama**.
Untuk produksi, ganti email admin dan jangan memakai kredensial ini.

### Upgrade dari v1
Backup database, jalankan `database.sql` (aman diulang), lalu `migrasi-v1-ke-v2.sql`. Data lama mendapat nomor pendaftaran
otomatis dan status `menunggu_verifikasi`; admin lama dipindah ke tabel `users` (wajib ganti password).

## Pembayaran

- `PAYMENT_DRIVER=mock` (default): halaman simulasi lokal, tanpa akun gateway. Dinonaktifkan otomatis saat `APP_ENV=production`.
- `PAYMENT_DRIVER=midtrans`: isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` dari dashboard Midtrans (mulai dengan sandbox,
  `MIDTRANS_IS_PRODUCTION=false`), isi `APP_URL`, lalu daftarkan **Payment Notification URL** di dashboard Midtrans ke
  `https://domain-anda/api/pembayaran-notifikasi.php`. Webhook harus dapat dijangkau dari internet (gunakan HTTPS).

## Pengujian

```bash
php tests/run.php        # unit test logika murni (tanpa database)
php -S 127.0.0.1:8080 -t . &   # pada database baru dari database.sql, PAYMENT_DRIVER=mock
bash tests/e2e.sh        # uji end-to-end: daftar, bayar, webhook, admin, otorisasi, rate limit
```
CI GitHub Actions (`.github/workflows/ci.yml`) menjalankan lint, unit test, dan E2E pada setiap push.

## Struktur

```
config/      env, koneksi DB, bootstrap (sesi, header keamanan, CSRF, auth)
lib/         pure.php (aturan murni), registration.php, payment.php, api.php
admin/       dashboard, pendaftar, jurusan, gelombang, pembayaran, audit, ekspor
pendaftar/   dashboard, formulir biodata, mulai pembayaran
api/         endpoint JSON (jurusan, status, pembayaran-buat, pembayaran-notifikasi)
tests/       run.php (unit) dan e2e.sh
docs/        API.md
```

## Catatan keamanan & deploy

- Jangan commit `.env` (sudah di `.gitignore`). Set `APP_ENV=production`, gunakan HTTPS, dan buat user database khusus (bukan `root`).
- `uploads/` dilindungi `.htaccess` (Apache). Pada **Nginx** tambahkan `location /uploads/ { deny all; }` dan blokir akses ke `.env`, `config/`, `lib/`, `tests/`.
- Di belakang reverse proxy tepercaya, set `TRUST_PROXY=1` agar IP klien dibaca benar untuk rate limiting.
- Belum ada: verifikasi email, reset password lewat email, dan 2FA admin (lihat rencana pengembangan pada dokumen spesifikasi).

## Publikasi ke GitHub

```bash
git init
git add .
git commit -m "PSB v2: akun, aturan bisnis, payment gateway, keamanan"
git branch -M main
git remote add origin https://github.com/<username>/pendaftaran-siswa.git
git push -u origin main
```
Lisensi: MIT.
