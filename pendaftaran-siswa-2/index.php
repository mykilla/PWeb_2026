<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
$gel = gelombang_aktif();
$jurusan = jurusan_with_quota();
?>
<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">PENERIMAAN SISWA BARU</span>
        <h1>Mulai langkah hebatmu bersama <span>SMK Coding.</span></h1>
        <p>Daftar online, bayar biaya pendaftaran lewat payment gateway, dan pantau status seleksi kapan saja.</p>
        <div class="hero-actions">
            <?php if (is_pendaftar()): ?><a class="btn btn-primary" href="<?= e(url('pendaftar/index.php')) ?>">Lanjutkan Pendaftaran →</a>
            <?php elseif (is_admin()): ?><a class="btn btn-primary" href="<?= e(url('admin/index.php')) ?>">Dashboard Admin</a>
            <?php else: ?><a class="btn btn-primary" href="<?= e(url('register.php')) ?>">Daftar Sekarang →</a><a class="btn btn-outline" href="<?= e(url('login.php')) ?>">Masuk</a><?php endif; ?>
        </div>
        <?php if ($gel): ?>
            <p class="muted"><strong><?= e($gel['nama']) ?></strong> (<?= e($gel['tahun_ajaran']) ?>) dibuka sampai <?= e(date('d-m-Y', strtotime($gel['tanggal_selesai']))) ?> · Biaya <?= e(format_rupiah($gel['biaya_pendaftaran'])) ?></p>
        <?php else: ?>
            <div class="alert warning">Pendaftaran sedang ditutup. Silakan kembali saat gelombang berikutnya dibuka.</div>
        <?php endif; ?>
    </div>
    <div class="hero-art" aria-hidden="true"><div class="art-card"><span class="art-icon">✦</span><strong>Belajar.</strong><strong>Berkarya.</strong><strong>Bertumbuh.</strong><small>YOUR FUTURE STARTS HERE</small></div><div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div></div>
</section>

<section class="feature-grid">
    <article class="feature-card"><span class="feature-number">01</span><h2>Buat akun &amp; isi biodata</h2><p>Satu akun untuk satu pendaftaran, lengkap dengan dokumen pendukung.</p></article>
    <article class="feature-card"><span class="feature-number">02</span><h2>Bayar online</h2><p>Biaya pendaftaran dibayar lewat payment gateway dan terverifikasi otomatis.</p></article>
    <article class="feature-card"><span class="feature-number">03</span><h2>Pantau hasil seleksi</h2><p>Status verifikasi dan penerimaan dapat dicek kapan saja.</p></article>
</section>

<section class="panel gap">
    <h2>Jurusan &amp; kuota</h2>
    <div class="table-scroll"><table><thead><tr><th>Kode</th><th>Jurusan</th><th>Kuota</th><th>Sisa kursi</th></tr></thead><tbody>
    <?php foreach ($jurusan as $j): ?><tr><td><?= e($j['kode']) ?></td><td><?= e($j['nama']) ?></td><td><?= (int)$j['kuota'] ?></td><td><?= (int)$j['sisa'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
