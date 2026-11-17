<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('ID tidak valid.'); }
$s = siswa_by_id($id);
if (!$s || (!is_admin() && (int)$s['user_id'] !== (int)$_SESSION['user_id'])) { http_response_code(404); exit('Data tidak ditemukan.'); }
if ($s['status'] === 'menunggu_pembayaran') { http_response_code(409); exit('Bukti pendaftaran tersedia setelah pembayaran berhasil.'); }
$pay = payment_latest((int)$s['id']);
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bukti Pendaftaran</title><link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"></head><body>
<main class="container print-page"><div class="print-actions"><button class="btn btn-primary" data-print>Cetak Bukti</button><button class="btn btn-outline" data-close>Tutup</button></div>
<section class="receipt"><div class="receipt-head"><div class="brand-mark">S</div><div><h1><?= e(strtoupper(env('APP_NAME', 'SMK Coding'))) ?></h1><p>BUKTI PENDAFTARAN SISWA BARU</p></div></div><hr>
<table class="receipt-table">
<tr><th>Nomor Pendaftaran</th><td><?= e($s['no_pendaftaran']) ?></td></tr>
<tr><th>Nama</th><td><?= e($s['nama']) ?></td></tr>
<tr><th>NISN</th><td><?= e($s['nisn'] ?? '-') ?></td></tr>
<tr><th>Tempat, Tanggal Lahir</th><td><?= e($s['tempat_lahir'] ?? '-') ?>, <?= $s['tanggal_lahir'] ? e(date('d-m-Y', strtotime($s['tanggal_lahir']))) : '-' ?></td></tr>
<tr><th>Asal Sekolah</th><td><?= e($s['asal_sekolah']) ?></td></tr>
<tr><th>Jurusan Pilihan</th><td><?= e($s['jurusan_nama'] ?? '-') ?></td></tr>
<tr><th>Gelombang</th><td><?= e(($s['gelombang_nama'] ?? '-') . ' ' . ($s['tahun_ajaran'] ?? '')) ?></td></tr>
<tr><th>Tanggal Pendaftaran</th><td><?= e(date('d-m-Y H:i', strtotime($s['created_at']))) ?></td></tr>
<tr><th>Pembayaran</th><td><?= $pay && $pay['status'] === 'paid' ? 'LUNAS · ' . e(format_rupiah($pay['jumlah'])) . ' · ' . e(date('d-m-Y H:i', strtotime($pay['paid_at']))) : 'Tanpa biaya' ?></td></tr>
<tr><th>Status</th><td><?= e(status_label($s['status'])) ?></td></tr>
</table><p class="receipt-note">Bukti ini menyatakan data pendaftaran telah tercatat. Hasil seleksi diumumkan melalui halaman Cek Status.</p></section></main><script src="<?= e(url('assets/js/script.js')) ?>"></script></body></html>
