-- PSB v2 - skema lengkap (instalasi baru). Untuk upgrade dari v1 gunakan migrasi-v1-ke-v2.sql
CREATE DATABASE IF NOT EXISTS db_pendaftaran CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_pendaftaran;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','pendaftar') NOT NULL DEFAULT 'pendaftar',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jurusan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kuota INT UNSIGNED NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS gelombang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(60) NOT NULL,
    tahun_ajaran VARCHAR(9) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    biaya_pendaftaran INT UNSIGNED NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS calon_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_pendaftaran VARCHAR(20) DEFAULT NULL UNIQUE,
    user_id INT UNSIGNED DEFAULT NULL UNIQUE,
    gelombang_id INT UNSIGNED DEFAULT NULL,
    jurusan_id INT UNSIGNED DEFAULT NULL,
    nisn VARCHAR(10) DEFAULT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(60) DEFAULT NULL,
    tanggal_lahir DATE DEFAULT NULL,
    jenis_kelamin VARCHAR(20) NOT NULL,
    agama VARCHAR(30) NOT NULL,
    asal_sekolah VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    nama_ortu VARCHAR(100) DEFAULT NULL,
    no_hp_ortu VARCHAR(20) DEFAULT NULL,
    dokumen VARCHAR(255) DEFAULT NULL,
    status ENUM('menunggu_pembayaran','menunggu_verifikasi','terverifikasi','diterima','ditolak') NOT NULL DEFAULT 'menunggu_pembayaran',
    catatan_admin VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_jurusan (jurusan_id),
    CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_cs_gel FOREIGN KEY (gelombang_id) REFERENCES gelombang(id) ON DELETE SET NULL,
    CONSTRAINT fk_cs_jur FOREIGN KEY (jurusan_id) REFERENCES jurusan(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pembayaran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calon_siswa_id INT UNSIGNED NOT NULL,
    order_id VARCHAR(50) NOT NULL UNIQUE,
    jumlah INT UNSIGNED NOT NULL,
    status ENUM('pending','paid','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
    driver VARCHAR(20) NOT NULL DEFAULT 'mock',
    metode VARCHAR(40) DEFAULT NULL,
    gateway_ref VARCHAR(80) DEFAULT NULL,
    payment_url VARCHAR(500) DEFAULT NULL,
    raw_notification TEXT DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_siswa (calon_siswa_id),
    CONSTRAINT fk_pay_siswa FOREIGN KEY (calon_siswa_id) REFERENCES calon_siswa(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rate_limit_hits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_bucket (bucket, created_at)
);

CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    aksi VARCHAR(60) NOT NULL,
    entitas VARCHAR(40) DEFAULT NULL,
    entitas_id VARCHAR(40) DEFAULT NULL,
    detail VARCHAR(500) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created (created_at)
);

-- Data awal -------------------------------------------------------------
INSERT INTO users (nama, email, password_hash, role, must_change_password)
SELECT 'Administrator', 'admin@smkcoding.test', '$2y$12$FrNvPwJYh35kL/hMrAh9SOfmkr5ma0E2sXhOQc2oEaBmMulYbiErO', 'admin', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@smkcoding.test');

INSERT INTO jurusan (kode, nama, kuota)
SELECT * FROM (SELECT 'RPL' kode, 'Rekayasa Perangkat Lunak' nama, 72 kuota
  UNION ALL SELECT 'TKJ', 'Teknik Komputer dan Jaringan', 72
  UNION ALL SELECT 'DKV', 'Desain Komunikasi Visual', 36) x
WHERE NOT EXISTS (SELECT 1 FROM jurusan);

INSERT INTO gelombang (nama, tahun_ajaran, tanggal_mulai, tanggal_selesai, biaya_pendaftaran, aktif)
SELECT 'Gelombang 1', '2026/2027', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 250000, 1
WHERE NOT EXISTS (SELECT 1 FROM gelombang);
