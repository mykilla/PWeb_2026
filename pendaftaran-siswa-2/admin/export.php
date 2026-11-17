<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
[$where, $types, $args] = admin_siswa_filter(trim((string)($_GET['q'] ?? '')), (string)($_GET['status'] ?? ''), (int)($_GET['jurusan'] ?? 0));
$stmt = $conn->prepare("SELECT c.no_pendaftaran, c.nisn, c.nama, c.tempat_lahir, c.tanggal_lahir, c.jenis_kelamin, c.agama, c.asal_sekolah, j.nama jurusan, c.no_hp, c.nama_ortu, c.no_hp_ortu, c.alamat, c.status, c.created_at FROM calon_siswa c LEFT JOIN jurusan j ON j.id = c.jurusan_id $where ORDER BY c.id");
if ($types) $stmt->bind_param($types, ...$args); $stmt->execute();
$res = $stmt->get_result();
audit('export.csv', 'calon_siswa', null, 'baris: ' . $res->num_rows);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pendaftar-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['No Pendaftaran', 'NISN', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Agama', 'Asal Sekolah', 'Jurusan', 'No HP', 'Orang Tua/Wali', 'No HP Ortu', 'Alamat', 'Status', 'Tanggal Daftar']);
while ($r = $res->fetch_row()) fputcsv($out, array_map('csv_cell', $r));
fclose($out);
