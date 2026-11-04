<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list-siswa.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nama = trim($_POST['nama'] ?? '');
$asalSekolah = trim($_POST['asal_sekolah'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$jenisKelamin = $_POST['jenis_kelamin'] ?? '';
$agama = $_POST['agama'] ?? '';

$allowedGender = ['Laki-laki', 'Perempuan'];
$allowedReligion = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

if (!$id || $nama === '' || mb_strlen($nama) > 100 || $asalSekolah === '' || mb_strlen($asalSekolah) > 100 || $alamat === '' ||
    !in_array($jenisKelamin, $allowedGender, true) ||
    !in_array($agama, $allowedReligion, true)) {
    exit('Data tidak valid. <a href="list-siswa.php">Kembali</a>.');
}



$stmt = $conn->prepare(
    'UPDATE calon_siswa
    SET nama = ?, alamat = ?, jenis_kelamin = ?,
    agama = ?, asal_sekolah = ?
    WHERE id = ?'
);

$stmt->bind_param(
    'sssssi',
    $nama,
    $alamat,
    $jenisKelamin,
    $agama,
    $asalSekolah,
    $id
);

$stmt->execute();

header('Location: list-siswa.php?status=updated');
exit;
