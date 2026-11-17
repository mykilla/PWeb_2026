<?php
$pageTitle = 'Akun Saya';
require_once __DIR__ . '/../includes/header.php';
require_pendaftar();
$s = siswa_by_user((int)$_SESSION['user_id']);
$gel = gelombang_aktif();
$pay = $s ? payment_latest((int)$s['id']) : null;
$order = ['menunggu_pembayaran' => 1, 'menunggu_verifikasi' => 2, 'terverifikasi' => 3, 'diterima' => 4, 'ditolak' => 4];
$pos = $s ? $order[$s['status']] : 0;
?>
<section class="page-heading"><span class="eyebrow">PENDAFTAR</span><h1>Halo, <?= e($_SESSION['nama']) ?></h1></section>
<ol class="steps">
    <li class="<?= $pos === 0 ? 'now' : 'done' ?>"><strong>1. Isi biodata</strong>Lengkapi data diri dan dokumen</li>
    <li class="<?= $pos === 1 ? 'now' : ($pos > 1 ? 'done' : '') ?>"><strong>2. Bayar</strong>Biaya pendaftaran via gateway</li>
    <li class="<?= in_array($pos, [2, 3], true) ? 'now' : ($pos > 3 ? 'done' : '') ?>"><strong>3. Verifikasi</strong>Dicek oleh panitia</li>
    <li class="<?= $pos === 4 ? 'now' : '' ?>"><strong>4. Hasil seleksi</strong>Diterima / ditolak</li>
</ol>

<?php if (!$s): ?>
    <div class="panel"><h2>Anda belum mengisi biodata</h2>
    <?php if ($gel): ?><p>Pendaftaran <strong><?= e($gel['nama']) ?></strong> dibuka sampai <?= e(date('d-m-Y', strtotime($gel['tanggal_selesai']))) ?>.</p><a class="btn btn-primary" href="<?= e(url('pendaftar/form.php')) ?>">Isi Biodata Sekarang</a>
    <?php else: ?><div class="alert warning">Pendaftaran sedang ditutup.</div><?php endif; ?></div>
<?php else: ?>
    <div class="panel"><h2>Pendaftaran <?= e($s['no_pendaftaran']) ?></h2>
    <dl class="kv"><dt>Status</dt><dd><?= status_badge($s['status']) ?></dd><dt>Nama</dt><dd><?= e($s['nama']) ?></dd><dt>Jurusan</dt><dd><?= e($s['jurusan_nama'] ?? '-') ?></dd><dt>Gelombang</dt><dd><?= e(($s['gelombang_nama'] ?? '-') . ' ' . ($s['tahun_ajaran'] ?? '')) ?></dd>
    <?php if ($s['catatan_admin']): ?><dt>Catatan panitia</dt><dd><?= e($s['catatan_admin']) ?></dd><?php endif; ?></dl>
    <div class="form-actions">
        <?php if ($s['status'] === 'menunggu_pembayaran'): ?>
            <a class="btn btn-outline" href="<?= e(url('pendaftar/form.php')) ?>">Ubah Biodata</a>
            <form method="POST" action="<?= e(url('pendaftar/bayar.php')) ?>"><?= csrf_field() ?><button class="btn btn-primary" type="submit">Bayar <?= e(format_rupiah($s['biaya_pendaftaran'])) ?></button></form>
        <?php else: ?>
            <a class="btn btn-outline" target="_blank" rel="noopener" href="<?= e(url('bukti.php?id=' . (int)$s['id'])) ?>">Cetak Bukti</a>
        <?php endif; ?>
        <?php if ($s['dokumen']): ?><a class="btn btn-outline" target="_blank" rel="noopener" href="<?= e(url('dokumen.php?id=' . (int)$s['id'])) ?>">Lihat Dokumen</a><?php endif; ?>
    </div></div>
    <?php if ($pay): ?>
    <div class="panel"><h2>Pembayaran terakhir</h2><dl class="kv"><dt>Order</dt><dd><?= e($pay['order_id']) ?></dd><dt>Jumlah</dt><dd><?= e(format_rupiah($pay['jumlah'])) ?></dd><dt>Status</dt><dd><?= status_badge($pay['status']) ?></dd><?php if ($pay['metode']): ?><dt>Metode</dt><dd><?= e($pay['metode']) ?></dd><?php endif; ?></dl></div>
    <?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
