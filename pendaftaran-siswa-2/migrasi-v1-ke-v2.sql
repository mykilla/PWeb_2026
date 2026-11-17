-- Upgrade dari v1 (tabel admins + calon_siswa lama). BACKUP DATABASE TERLEBIH DAHULU.
-- Langkah: 1) jalankan bagian CREATE TABLE di database.sql (IF NOT EXISTS aman diulang),
--          2) jalankan berkas ini satu kali.
USE db_pendaftaran;

ALTER TABLE calon_siswa
    ADD COLUMN no_pendaftaran VARCHAR(20) DEFAULT NULL AFTER id,
    ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER no_pendaftaran,
    ADD COLUMN gelombang_id INT UNSIGNED DEFAULT NULL AFTER user_id,
    ADD COLUMN jurusan_id INT UNSIGNED DEFAULT NULL AFTER gelombang_id,
    ADD COLUMN nisn VARCHAR(10) DEFAULT NULL AFTER jurusan_id,
    ADD COLUMN tempat_lahir VARCHAR(60) DEFAULT NULL AFTER nama,
    ADD COLUMN tanggal_lahir DATE DEFAULT NULL AFTER tempat_lahir,
    ADD COLUMN no_hp VARCHAR(20) DEFAULT NULL AFTER alamat,
    ADD COLUMN nama_ortu VARCHAR(100) DEFAULT NULL AFTER no_hp,
    ADD COLUMN no_hp_ortu VARCHAR(20) DEFAULT NULL AFTER nama_ortu,
    ADD COLUMN status ENUM('menunggu_pembayaran','menunggu_verifikasi','terverifikasi','diterima','ditolak') NOT NULL DEFAULT 'menunggu_verifikasi' AFTER dokumen,
    ADD COLUMN catatan_admin VARCHAR(255) DEFAULT NULL AFTER status,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE calon_siswa SET no_pendaftaran = CONCAT('PSB-', YEAR(created_at), '-', LPAD(id, 5, '0'));
ALTER TABLE calon_siswa
    ADD UNIQUE KEY uq_no (no_pendaftaran),
    ADD UNIQUE KEY uq_user (user_id),
    ADD UNIQUE KEY uq_nisn (nisn),
    ADD KEY idx_status (status),
    ADD KEY idx_jurusan (jurusan_id),
    ADD CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_cs_gel FOREIGN KEY (gelombang_id) REFERENCES gelombang(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_cs_jur FOREIGN KEY (jurusan_id) REFERENCES jurusan(id) ON DELETE SET NULL;

-- Admin lama dipindah ke tabel users; wajib ganti password saat login pertama.
INSERT INTO users (nama, email, password_hash, role, must_change_password)
SELECT username, CONCAT(username, '@admin.local'), password_hash, 'admin', 1
FROM admins a WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.email = CONCAT(a.username, '@admin.local'));
-- Setelah yakin berhasil: DROP TABLE admins;
