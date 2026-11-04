CREATE DATABASE IF NOT EXISTS db_pendaftaran
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_pendaftaran;

CREATE TABLE IF NOT EXISTS calon_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    asal_sekolah TEXT NOT NULL,
    alamat TEXT NOT NULL,
    jenis_kelamin VARCHAR(20) NOT NULL,
    agama VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
