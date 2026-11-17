<?php
// Uji logika murni tanpa database:  php tests/run.php
require_once __DIR__ . '/../lib/pure.php';
$pass = 0; $fail = 0;
function check(string $name, bool $cond): void { global $pass, $fail; if ($cond) { $pass++; echo "  ok   $name\n"; } else { $fail++; echo "  FAIL $name\n"; } }

$today = new DateTimeImmutable('2026-11-16');
$ok = ['nama'=>'Budi Santoso','nisn'=>'0012345678','tempat_lahir'=>'Surabaya','tanggal_lahir'=>'2011-05-20','jenis_kelamin'=>'Laki-laki','agama'=>'Islam','asal_sekolah'=>'SMPN 1','alamat'=>'Jl. Mawar 1','no_hp'=>'0812-3456-7890','nama_ortu'=>'Siti','no_hp_ortu'=>'+6281234567890','jurusan_id'=>'1'];
[$c, $e] = validate_biodata($ok, ['today'=>$today]);
check('biodata valid lolos', $e === []);
check('nomor HP dinormalisasi', $c['no_hp'] === '081234567890');
[, $e] = validate_biodata(['nisn'=>'123'] + $ok, ['today'=>$today]); check('NISN salah ditolak', isset($e['nisn']));
[, $e] = validate_biodata(['tanggal_lahir'=>'2020-01-01'] + $ok, ['today'=>$today]); check('usia terlalu muda ditolak', isset($e['tanggal_lahir']));
[, $e] = validate_biodata(['tanggal_lahir'=>'1990-01-01'] + $ok, ['today'=>$today]); check('usia terlalu tua ditolak', isset($e['tanggal_lahir']));
[, $e] = validate_biodata(['tanggal_lahir'=>'2011-02-30'] + $ok, ['today'=>$today]); check('tanggal tidak ada ditolak', isset($e['tanggal_lahir']));
[, $e] = validate_biodata(['jenis_kelamin'=>'X'] + $ok, ['today'=>$today]); check('gender di luar daftar ditolak', isset($e['jenis_kelamin']));
[, $e] = validate_biodata(['no_hp'=>'12345'] + $ok, ['today'=>$today]); check('HP tidak valid ditolak', isset($e['no_hp']));
[, $e] = validate_biodata(['jurusan_id'=>'0'] + $ok, ['today'=>$today]); check('jurusan kosong ditolak', isset($e['jurusan_id']));

check('admin: verifikasi -> terverifikasi', admin_can_transition('menunggu_verifikasi', 'terverifikasi'));
check('admin: tidak boleh lompat ke diterima', !admin_can_transition('menunggu_verifikasi', 'diterima'));
check('admin: tidak boleh melewati pembayaran', !admin_can_transition('menunggu_pembayaran', 'diterima') && !admin_can_transition('menunggu_pembayaran', 'terverifikasi'));
check('admin: batalkan penerimaan', admin_can_transition('diterima', 'terverifikasi'));

check('password pendek ditolak', password_policy_error('abc123') !== null);
check('password tanpa angka ditolak', password_policy_error('abcdefghij') !== null);
check('password tanpa huruf ditolak', password_policy_error('1234567890') !== null);
check('password > 72 byte ditolak', password_policy_error(str_repeat('a1', 40)) !== null);
check('password valid lolos', password_policy_error('Rahasia2026') === null);

$sk = 'SB-Mid-server-xyz';
$sig = midtrans_signature('PSB1-1', '200', '250000.00', $sk);
check('signature sha512 panjang 128', strlen($sig) === 128);
$n = ['order_id'=>'PSB1-1','status_code'=>'200','gross_amount'=>'250000.00','signature_key'=>$sig];
check('signature valid diterima', verify_notification_signature($n, $sk));
check('signature salah kunci ditolak', !verify_notification_signature($n, 'kunci-lain'));
check('signature jumlah dimanipulasi ditolak', !verify_notification_signature(['gross_amount'=>'1.00'] + $n, $sk));
check('signature hilang ditolak', !verify_notification_signature(['order_id'=>'x'], $sk));

check('settlement -> paid', map_gateway_status('settlement') === 'paid');
check('capture accept -> paid', map_gateway_status('capture', 'accept') === 'paid');
check('capture challenge -> pending', map_gateway_status('capture', 'challenge') === 'pending');
check('expire -> expired', map_gateway_status('expire') === 'expired');
check('deny -> failed', map_gateway_status('deny') === 'failed');

check('csv formula dinetralkan', csv_cell('=HYPERLINK("x")') === "'=HYPERLINK(\"x\")" && csv_cell('@a') === "'@a");
check('csv teks biasa tetap', csv_cell('Budi') === 'Budi');
check('format rupiah', format_rupiah(250000) === 'Rp 250.000');
check('order id unik', make_order_id(5) !== make_order_id(5));

echo "\n$pass lulus, $fail gagal\n";
exit($fail ? 1 : 0);
