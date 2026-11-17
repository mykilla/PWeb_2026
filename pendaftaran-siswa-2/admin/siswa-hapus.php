<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
require_post();
$id = (int)($_POST['id'] ?? 0);
$s = $id ? siswa_by_id($id) : null;
if (!$s) { flash('error', 'Data tidak ditemukan.'); redirect('admin/siswa.php'); }
$stmt = $conn->prepare('DELETE FROM calon_siswa WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute();
delete_upload($s['dokumen']);
audit('siswa.hapus', 'calon_siswa', $id, $s['no_pendaftaran'] . ' ' . $s['nama']);
flash('success', 'Data pendaftar dihapus.');
redirect('admin/siswa.php');
