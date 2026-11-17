<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('ID tidak valid.'); }
$s = siswa_by_id($id);
// Otorisasi: admin boleh semua; pendaftar hanya miliknya sendiri (mencegah IDOR).
if (!$s || (!is_admin() && (int)$s['user_id'] !== (int)$_SESSION['user_id'])) { http_response_code(404); exit('Dokumen tidak ditemukan.'); }
if (!$s['dokumen']) { http_response_code(404); exit('Dokumen tidak ditemukan.'); }
$file = __DIR__ . '/uploads/' . basename($s['dokumen']);
if (!is_file($file)) { http_response_code(404); exit('File tidak ditemukan.'); }
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="dokumen-' . $id . '.' . $ext . '"');
header('Content-Length: ' . filesize($file));
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
readfile($file);
