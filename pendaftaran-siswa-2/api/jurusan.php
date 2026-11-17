<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/api.php';
api_method('GET');
$gel = gelombang_aktif();
json_out(200, [
    'gelombang_aktif' => $gel ? ['nama' => $gel['nama'], 'tahun_ajaran' => $gel['tahun_ajaran'], 'tanggal_selesai' => $gel['tanggal_selesai'], 'biaya_pendaftaran' => (int)$gel['biaya_pendaftaran']] : null,
    'data' => array_map(fn($j) => ['id' => (int)$j['id'], 'kode' => $j['kode'], 'nama' => $j['nama'], 'kuota' => (int)$j['kuota'], 'sisa' => (int)$j['sisa']], jurusan_with_quota()),
]);
