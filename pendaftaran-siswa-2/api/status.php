<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/api.php';
api_method('POST');
$b = api_body();
$bucket = rate_bucket('cekstatus', client_ip());
if (rate_exceeded($bucket, 15, 600)) json_out(429, ['error' => 'Terlalu banyak permintaan']);
rate_hit($bucket);
$no = strtoupper(trim((string)($b['no_pendaftaran'] ?? ''))); $tgl = trim((string)($b['tanggal_lahir'] ?? ''));
if ($no === '' || $tgl === '') json_out(422, ['error' => 'no_pendaftaran dan tanggal_lahir wajib diisi']);
$r = siswa_public_status($no, $tgl);
if (!$r) json_out(404, ['error' => 'Data tidak ditemukan']);
json_out(200, ['no_pendaftaran' => $r['no_pendaftaran'], 'nama' => mask_name($r['nama']), 'jurusan' => $r['jurusan_nama'], 'status' => $r['status'], 'status_label' => status_label($r['status']), 'catatan' => $r['catatan_admin']]);
