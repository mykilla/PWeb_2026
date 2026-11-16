<?php require 'lib.php';auth();
$t=q('SELECT t.*,p.nama,p.no_hp,l.nama_layanan,l.harga_per_kg FROM transaksi t JOIN pelanggan p USING(id_pelanggan) JOIN layanan l USING(id_layanan) WHERE id_transaksi=?',[(int)($_GET['id']??0)])->fetch();
if(!$t)die('Transaksi tidak ditemukan.');?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Nota #<?=$t['id_transaksi']?></title><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@800&family=DM+Sans:wght@400;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="nota-page"><div class="nota"><h2>LaundryCrafty</h2><p class="muted">Nota #<?=str_pad($t['id_transaksi'],5,'0',STR_PAD_LEFT)?></p><hr>
<dl><dt>Pelanggan</dt><dd><?=e($t['nama'])?> (<?=e($t['no_hp'])?>)</dd><dt>Layanan</dt><dd><?=e($t['nama_layanan'])?></dd><dt>Berat</dt><dd><?=$t['berat']+0?> kg × <?=rp($t['harga_per_kg'])?></dd><dt>Masuk</dt><dd><?=tgl($t['tanggal_masuk'])?></dd><dt>Estimasi selesai</dt><dd><?=tgl($t['tanggal_selesai'])?></dd><dt>Status</dt><dd><?=e($t['status'])?></dd></dl><hr>
<div class="total">Total <b><?=rp($t['total_harga'])?></b></div><p class="muted c">Terima kasih. Tunjukkan nota ini saat mengambil cucian.</p><button class="btn noprint" onclick="print()">Cetak nota</button></div></body></html>
