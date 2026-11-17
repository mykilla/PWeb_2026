<?php
require_once __DIR__ . '/../config/bootstrap.php';
$pageTitle = $pageTitle ?? 'Pendaftaran Siswa Baru';
$cur = basename($_SERVER['PHP_SELF']);
$dir = basename(dirname($_SERVER['PHP_SELF']));
$nav = function (string $href, string $label, bool $active) { return '<a class="' . ($active ? 'active' : '') . '" href="' . e(url($href)) . '">' . e($label) . '</a>'; };
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(env('APP_NAME', 'SMK Coding')) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">S</span><span>SMK <strong>CODING</strong></span></a>
        <nav class="main-nav" aria-label="Navigasi utama">
            <?= $nav('index.php', 'Beranda', $cur === 'index.php' && $dir !== 'admin' && $dir !== 'pendaftar') ?>
            <?= $nav('cek-status.php', 'Cek Status', $cur === 'cek-status.php') ?>
            <?php if (is_admin()): ?>
                <?= $nav('admin/index.php', 'Dashboard', $dir === 'admin' && $cur === 'index.php') ?>
                <?= $nav('admin/siswa.php', 'Pendaftar', in_array($cur, ['siswa.php', 'siswa-edit.php'], true)) ?>
                <?= $nav('admin/jurusan.php', 'Jurusan', $cur === 'jurusan.php') ?>
                <?= $nav('admin/gelombang.php', 'Gelombang', $cur === 'gelombang.php') ?>
                <?= $nav('admin/pembayaran.php', 'Pembayaran', $cur === 'pembayaran.php') ?>
                <?= $nav('admin/audit.php', 'Audit', $cur === 'audit.php') ?>
            <?php elseif (is_pendaftar()): ?>
                <?= $nav('pendaftar/index.php', 'Akun Saya', $dir === 'pendaftar') ?>
            <?php else: ?>
                <?= $nav('register.php', 'Buat Akun', $cur === 'register.php') ?>
                <?= $nav('login.php', 'Masuk', $cur === 'login.php') ?>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <?= $nav('ganti-password.php', 'Password', $cur === 'ganti-password.php') ?>
                <form class="inline-form" action="<?= e(url('logout.php')) ?>" method="POST"><?= csrf_field() ?><button class="nav-button" type="submit">Keluar</button></form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
<?= flash_render() ?>
