<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form-daftar.php');
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$asalSekolah = trim($_POST['asal_sekolah'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$jenisKelamin = $_POST['jenis_kelamin'] ?? '';
$agama = $_POST['agama'] ?? '';

$allowedGender = ['Laki-laki', 'Perempuan'];
$allowedReligion = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

if ($nama === '' || mb_strlen($nama) > 100 || $asalSekolah === '' || mb_strlen($asalSekolah) > 100 || $alamat === '' ||
    !in_array($jenisKelamin, $allowedGender, true) ||
    !in_array($agama, $allowedReligion, true)) {
    exit('Data tidak valid. <a href="form-daftar.php">Kembali ke formulir</a>.');
}



$stmt = $conn->prepare(
    'INSERT INTO calon_siswa
    (nama, alamat, jenis_kelamin, agama, asal_sekolah)
    VALUES (?, ?, ?, ?, ?)'
);

$stmt->bind_param(
    'sssss',
    $nama,
    $alamat,
    $jenisKelamin,
    $agama,
    $asalSekolah
);
$stmt->execute();

header('Location: list-siswa.php?status=created');
exit;
