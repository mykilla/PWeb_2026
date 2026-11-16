CREATE DATABASE IF NOT EXISTS laundrycrafty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laundrycrafty;
DROP TABLE IF EXISTS transaksi, layanan, pelanggan, user;
CREATE TABLE pelanggan(id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(100) NOT NULL,alamat TEXT,no_hp VARCHAR(16) NOT NULL,INDEX(nama));
CREATE TABLE layanan(id_layanan INT AUTO_INCREMENT PRIMARY KEY,nama_layanan VARCHAR(50) NOT NULL,harga_per_kg INT NOT NULL);
CREATE TABLE transaksi(id_transaksi INT AUTO_INCREMENT PRIMARY KEY,id_pelanggan INT NOT NULL,id_layanan INT NOT NULL,tanggal_masuk DATE NOT NULL,tanggal_selesai DATE NOT NULL,berat DECIMAL(5,1) NOT NULL,total_harga INT NOT NULL,status ENUM('Proses','Selesai','Sudah Diambil') NOT NULL DEFAULT 'Proses',INDEX(tanggal_masuk),
 FOREIGN KEY(id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE RESTRICT,FOREIGN KEY(id_layanan) REFERENCES layanan(id_layanan) ON DELETE RESTRICT);
CREATE TABLE user(id_user INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,role ENUM('admin','kasir') NOT NULL);
INSERT INTO layanan(nama_layanan,harga_per_kg) VALUES('Cuci Kering',5000),('Cuci Setrika',7000),('Express',12000);
INSERT INTO pelanggan(nama,alamat,no_hp) VALUES('Budi Santoso','Jl. Keputih No. 12, Surabaya','081234567890'),('Siti Aminah','Jl. Gebang Lor 7, Surabaya','081298765432'),('Rina Wijaya','Jl. Mulyorejo 21, Surabaya','085611223344');
INSERT INTO transaksi(id_pelanggan,id_layanan,tanggal_masuk,tanggal_selesai,berat,total_harga,status) VALUES
(1,2,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 DAY),3.5,24500,'Proses'),(2,3,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 DAY),2.0,24000,'Proses'),
(3,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),CURDATE(),5.0,25000,'Selesai'),(1,2,DATE_SUB(CURDATE(),INTERVAL 2 DAY),CURDATE(),4.0,28000,'Sudah Diambil'),
(2,2,DATE_SUB(CURDATE(),INTERVAL 3 DAY),DATE_SUB(CURDATE(),INTERVAL 1 DAY),6.0,42000,'Sudah Diambil'),(3,3,DATE_SUB(CURDATE(),INTERVAL 5 DAY),DATE_SUB(CURDATE(),INTERVAL 4 DAY),2.5,30000,'Sudah Diambil');
INSERT INTO user(username,password,role) VALUES('admin','$2y$10$xkanNjd.9mrdirvvMsBD1eBQQaoBjFEr8TGu3tWsX5RY5dF9/s3/C','admin'),('kasir','$2y$10$lf/KrJmVFAzbEuky2cq4b.17tuwoE1/AX31DHtvlCMzcvLEJso2a6','kasir');
