<?php
$pageTitle = $pageTitle ?? 'Pendaftaran Siswa Baru';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | SMK Coding</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="index.php"><span class="brand-mark">S</span><span>SMK <strong>CODING</strong></span></a>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">Beranda</a>
            <a class="<?= in_array($currentPage, ['form-daftar.php', 'proses-pendaftaran.php'], true) ? 'active' : '' ?>" href="form-daftar.php">Pendaftaran</a>
            <a class="<?= in_array($currentPage, ['list-siswa.php', 'edit-siswa.php'], true) ? 'active' : '' ?>" href="list-siswa.php">Data Siswa</a>
        </nav>
    </div>
</header>
<main class="container main-content">
