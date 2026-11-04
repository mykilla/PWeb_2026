<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">PENERIMAAN SISWA BARU</span>
        <h1>Mulai langkah hebatmu bersama <span>SMK Coding.</span></h1>
        <p>Daftarkan dirimu dengan mudah melalui sistem pendaftaran online kami.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="form-daftar.php">Daftar Sekarang <span aria-hidden="true">→</span></a>
            <a class="btn btn-outline" href="list-siswa.php">Lihat Pendaftar</a>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="art-card"><span class="art-icon">✦</span><strong>Belajar.</strong><strong>Berkarya.</strong><strong>Bertumbuh.</strong><small>YOUR FUTURE STARTS HERE</small></div>
        <div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div>
    </div>
</section>
<section class="feature-grid">
    <article class="feature-card"><span class="feature-number">01</span><h2>Praktis</h2><p>Isi formulir pendaftaran secara online tanpa proses yang berbelit.</p></article>
    <article class="feature-card"><span class="feature-number">02</span><h2>Terorganisir</h2><p>Data pendaftar tersimpan rapi dalam basis data sekolah.</p></article>
    <article class="feature-card"><span class="feature-number">03</span><h2>Mudah dikelola</h2><p>Petugas dapat melihat, memperbarui, dan menghapus data pendaftar.</p></article>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
