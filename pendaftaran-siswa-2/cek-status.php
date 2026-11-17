<?php
$pageTitle = 'Cek Status Pendaftaran';
require_once __DIR__ . '/includes/header.php';
$result = null; $error = ''; $searched = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $searched = true;
    $bucket = rate_bucket('cekstatus', client_ip());
    if (rate_exceeded($bucket, 15, 600)) {
        $error = 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.';
    } else {
        rate_hit($bucket);
        $result = siswa_public_status(strtoupper(trim((string)($_POST['no'] ?? ''))), trim((string)($_POST['tgl'] ?? '')));
    }
}
?>
<section class="page-heading"><span class="eyebrow">PUBLIK</span><h1>Cek status pendaftaran</h1><p>Masukkan nomor pendaftaran dan tanggal lahir yang Anda daftarkan.</p></section>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form class="form-card" method="POST"><?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-group"><label for="no">Nomor pendaftaran</label><input id="no" name="no" placeholder="PSB-2026-00001" maxlength="20" required></div>
        <div class="form-group"><label for="tgl">Tanggal lahir</label><input type="date" id="tgl" name="tgl" required></div>
    </div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Cek Status</button></div>
</form>
<?php if ($searched && !$error): ?>
    <div class="panel gap">
    <?php if ($result): ?>
        <h2><?= e($result['no_pendaftaran']) ?></h2>
        <dl class="kv"><dt>Nama</dt><dd><?= e(mask_name($result['nama'])) ?></dd><dt>Jurusan</dt><dd><?= e($result['jurusan_nama'] ?? '-') ?></dd><dt>Status</dt><dd><?= status_badge($result['status']) ?></dd>
        <?php if ($result['catatan_admin']): ?><dt>Catatan</dt><dd><?= e($result['catatan_admin']) ?></dd><?php endif; ?></dl>
    <?php else: ?><p>Data tidak ditemukan. Periksa kembali nomor pendaftaran dan tanggal lahir.</p><?php endif; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
