<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s = $id ? siswa_by_id($id) : null;
if (!$s) { http_response_code(404); exit('Data tidak ditemukan.'); }
$errors = []; $in = $s;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['aksi'] ?? '') === 'status') {
        [$ok, $msg] = admin_change_status($id, (string)($_POST['status'] ?? ''), $_POST['catatan'] ?? null);
        flash($ok ? 'success' : 'error', $msg);
        redirect('admin/siswa-edit.php?id=' . $id);
    }
    $in = $_POST;
    [$clean, $errors] = validate_biodata($_POST, ['min_usia' => (int)env('MIN_USIA', 12), 'max_usia' => (int)env('MAX_USIA', 21)]);
    if (!$errors) $errors = siswa_update($id, $clean);
    if (!$errors) { audit('siswa.ubah', 'calon_siswa', $id, 'oleh admin'); flash('success', 'Data diperbarui.'); redirect('admin/siswa-edit.php?id=' . $id); }
}
$pageTitle = 'Detail Pendaftar';
require_once __DIR__ . '/../includes/header.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
$jurusan = jurusan_with_quota(false);
$next = ADMIN_TRANSITIONS[$s['status']];
$pays = $conn->prepare('SELECT * FROM pembayaran WHERE calon_siswa_id = ? ORDER BY id DESC'); $pays->bind_param('i', $id); $pays->execute(); $pays = $pays->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<section class="page-heading"><span class="eyebrow"><?= e($s['no_pendaftaran']) ?></span><h1><?= e($s['nama']) ?></h1><p><?= status_badge($s['status']) ?></p></section>
<div class="panel"><h2>Ubah status</h2>
<?php if ($next): ?>
<form class="table-form" method="POST"><?= csrf_field() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?= $id ?>">
<select name="status"><?php foreach ($next as $n): ?><option value="<?= e($n) ?>"><?= e(status_label($n)) ?></option><?php endforeach; ?></select>
<input name="catatan" maxlength="255" placeholder="Catatan untuk pendaftar (opsional)" value="<?= e($s['catatan_admin'] ?? '') ?>">
<button class="btn btn-primary" type="submit">Terapkan</button></form>
<?php else: ?><p class="muted">Status menunggu pembayaran berubah otomatis setelah pembayaran lunas.</p><?php endif; ?>
<?php if ($s['dokumen']): ?><p><a class="text-link" target="_blank" rel="noopener" href="<?= e(url('dokumen.php?id=' . $id)) ?>">Lihat dokumen pendukung</a> · <a class="text-link" target="_blank" rel="noopener" href="<?= e(url('bukti.php?id=' . $id)) ?>">Bukti pendaftaran</a></p><?php endif; ?></div>
<div class="panel"><h2>Pembayaran</h2><?php if ($pays): ?><div class="table-scroll"><table><thead><tr><th>Order</th><th>Jumlah</th><th>Status</th><th>Metode</th><th>Dibayar</th></tr></thead><tbody>
<?php foreach ($pays as $p): ?><tr><td><?= e($p['order_id']) ?></td><td><?= e(format_rupiah($p['jumlah'])) ?></td><td><?= status_badge($p['status']) ?></td><td><?= e($p['metode'] ?? '-') ?></td><td><?= e($p['paid_at'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">Belum ada tagihan.</p><?php endif; ?></div>
<form class="form-card wide" method="POST"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><h2>Biodata</h2>
<div class="form-grid">
<div class="form-group"><label>Nama lengkap</label><input name="nama" maxlength="100" value="<?= old($in, 'nama') ?>"><?= $err('nama') ?></div>
<div class="form-group"><label>NISN</label><input name="nisn" maxlength="10" value="<?= old($in, 'nisn') ?>"><?= $err('nisn') ?></div>
<div class="form-group"><label>Tempat lahir</label><input name="tempat_lahir" value="<?= old($in, 'tempat_lahir') ?>"><?= $err('tempat_lahir') ?></div>
<div class="form-group"><label>Tanggal lahir</label><input type="date" name="tanggal_lahir" value="<?= old($in, 'tanggal_lahir') ?>"><?= $err('tanggal_lahir') ?></div>
<div class="form-group"><label>Jenis kelamin</label><select name="jenis_kelamin"><?php foreach (GENDERS as $g): ?><option <?= ($in['jenis_kelamin'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select><?= $err('jenis_kelamin') ?></div>
<div class="form-group"><label>Agama</label><select name="agama"><?php foreach (RELIGIONS as $a): ?><option <?= ($in['agama'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select><?= $err('agama') ?></div>
<div class="form-group"><label>Asal sekolah</label><input name="asal_sekolah" value="<?= old($in, 'asal_sekolah') ?>"><?= $err('asal_sekolah') ?></div>
<div class="form-group"><label>Jurusan</label><select name="jurusan_id"><?php foreach ($jurusan as $j): ?><option value="<?= (int)$j['id'] ?>" <?= (int)($in['jurusan_id'] ?? 0) === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nama']) ?></option><?php endforeach; ?></select><?= $err('jurusan_id') ?></div>
<div class="form-group"><label>No. HP</label><input name="no_hp" value="<?= old($in, 'no_hp') ?>"><?= $err('no_hp') ?></div>
<div class="form-group"><label>Nama orang tua/wali</label><input name="nama_ortu" value="<?= old($in, 'nama_ortu') ?>"><?= $err('nama_ortu') ?></div>
<div class="form-group"><label>No. HP orang tua/wali</label><input name="no_hp_ortu" value="<?= old($in, 'no_hp_ortu') ?>"><?= $err('no_hp_ortu') ?></div></div>
<div class="form-group"><label>Alamat</label><textarea name="alamat" rows="3"><?= old($in, 'alamat') ?></textarea><?= $err('alamat') ?></div>
<div class="form-actions"><a class="btn btn-outline" href="<?= e(url('admin/siswa.php')) ?>">Kembali</a><button class="btn btn-primary" type="submit">Simpan Biodata</button></div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
