<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_pendaftar();
$uid = (int)$_SESSION['user_id'];
$s = siswa_by_user($uid);
$editing = (bool)$s;
if ($editing && $s['status'] !== 'menunggu_pembayaran') { flash('warning', 'Biodata terkunci karena pembayaran sudah dilakukan.'); redirect('pendaftar/index.php'); }
if (!$editing && !gelombang_aktif()) { flash('warning', 'Pendaftaran sedang ditutup.'); redirect('pendaftar/index.php'); }

$errors = []; $in = $editing ? $s : ['nama' => $_SESSION['nama']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $in = $_POST;
    [$clean, $errors] = validate_biodata($_POST, ['min_usia' => (int)env('MIN_USIA', 12), 'max_usia' => (int)env('MAX_USIA', 21)]);
    if (!isset($errors['jurusan_id']) && !jurusan_aktif_exists($clean['jurusan_id'])) $errors['jurusan_id'] = 'Jurusan tidak tersedia.';
    [$dok, $upErr] = handle_upload($_FILES['dokumen'] ?? null);
    if ($upErr) $errors['dokumen'] = $upErr;
    if (!$editing && $dok === null && !$errors) $errors['dokumen'] = 'Dokumen pendukung wajib diunggah (PDF/JPG/PNG, maks. 2 MB).';
    if (!$errors) {
        if ($editing) {
            $errors = siswa_update((int)$s['id'], $clean, $dok);
            if (!$errors) { if ($dok) delete_upload($s['dokumen']); audit('siswa.ubah', 'calon_siswa', $s['id']); flash('success', 'Biodata diperbarui.'); redirect('pendaftar/index.php'); }
        } else {
            [$id, $errors] = siswa_create($uid, $clean, $dok);
            if ($id) { flash('success', 'Pendaftaran tersimpan. Lanjutkan dengan pembayaran biaya pendaftaran.'); redirect('pendaftar/index.php'); }
        }
        if ($dok) delete_upload($dok);   // simpan gagal: buang berkas yang baru diunggah
    } elseif ($dok) { delete_upload($dok); }
}
$pageTitle = $editing ? 'Ubah Biodata' : 'Isi Biodata';
require_once __DIR__ . '/../includes/header.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
$cls = fn($k) => isset($errors[$k]) ? ' class="invalid"' : '';
$jurusan = jurusan_with_quota();
?>
<section class="page-heading"><span class="eyebrow">FORMULIR</span><h1><?= $editing ? 'Ubah biodata' : 'Biodata pendaftaran' ?></h1><p>Semua kolom wajib diisi. Data dapat diubah selama pembayaran belum dilakukan.</p></section>
<?php if (!empty($errors['_'])): ?><div class="alert error"><?= e($errors['_']) ?></div><?php endif; ?>
<form class="form-card wide" method="POST" enctype="multipart/form-data" novalidate><?= csrf_field() ?>
<div class="form-grid">
    <div class="form-group"><label for="nama">Nama lengkap</label><input id="nama" name="nama" maxlength="100" value="<?= old($in, 'nama') ?>"<?= $cls('nama') ?>><?= $err('nama') ?></div>
    <div class="form-group"><label for="nisn">NISN</label><input id="nisn" name="nisn" inputmode="numeric" maxlength="10" value="<?= old($in, 'nisn') ?>"<?= $cls('nisn') ?>><?= $err('nisn') ?></div>
    <div class="form-group"><label for="tempat_lahir">Tempat lahir</label><input id="tempat_lahir" name="tempat_lahir" maxlength="60" value="<?= old($in, 'tempat_lahir') ?>"<?= $cls('tempat_lahir') ?>><?= $err('tempat_lahir') ?></div>
    <div class="form-group"><label for="tanggal_lahir">Tanggal lahir</label><input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= old($in, 'tanggal_lahir') ?>"<?= $cls('tanggal_lahir') ?>><?= $err('tanggal_lahir') ?></div>
    <div class="form-group"><label for="jenis_kelamin">Jenis kelamin</label><select id="jenis_kelamin" name="jenis_kelamin"<?= $cls('jenis_kelamin') ?>><option value="">Pilih</option><?php foreach (GENDERS as $g): ?><option <?= ($in['jenis_kelamin'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select><?= $err('jenis_kelamin') ?></div>
    <div class="form-group"><label for="agama">Agama</label><select id="agama" name="agama"<?= $cls('agama') ?>><option value="">Pilih</option><?php foreach (RELIGIONS as $a): ?><option <?= ($in['agama'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select><?= $err('agama') ?></div>
    <div class="form-group"><label for="asal_sekolah">Asal sekolah</label><input id="asal_sekolah" name="asal_sekolah" maxlength="150" value="<?= old($in, 'asal_sekolah') ?>"<?= $cls('asal_sekolah') ?>><?= $err('asal_sekolah') ?></div>
    <div class="form-group"><label for="jurusan_id">Jurusan pilihan</label><select id="jurusan_id" name="jurusan_id"<?= $cls('jurusan_id') ?>><option value="">Pilih</option><?php foreach ($jurusan as $j): ?><option value="<?= (int)$j['id'] ?>" <?= (int)($in['jurusan_id'] ?? 0) === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nama']) ?> (sisa <?= (int)$j['sisa'] ?>)</option><?php endforeach; ?></select><?= $err('jurusan_id') ?></div>
    <div class="form-group"><label for="no_hp">No. HP calon siswa</label><input id="no_hp" name="no_hp" inputmode="tel" value="<?= old($in, 'no_hp') ?>"<?= $cls('no_hp') ?>><?= $err('no_hp') ?></div>
    <div class="form-group"><label for="nama_ortu">Nama orang tua/wali</label><input id="nama_ortu" name="nama_ortu" maxlength="100" value="<?= old($in, 'nama_ortu') ?>"<?= $cls('nama_ortu') ?>><?= $err('nama_ortu') ?></div>
    <div class="form-group"><label for="no_hp_ortu">No. HP orang tua/wali</label><input id="no_hp_ortu" name="no_hp_ortu" inputmode="tel" value="<?= old($in, 'no_hp_ortu') ?>"<?= $cls('no_hp_ortu') ?>><?= $err('no_hp_ortu') ?></div>
</div>
<div class="form-group"><label for="alamat">Alamat lengkap</label><textarea id="alamat" name="alamat" rows="3" maxlength="1000"<?= $cls('alamat') ?>><?= old($in, 'alamat') ?></textarea><?= $err('alamat') ?></div>
<div class="form-group"><label for="dokumen">Dokumen pendukung (rapor/ijazah)</label><input type="file" id="dokumen" name="dokumen" accept=".pdf,.jpg,.jpeg,.png"><small>PDF/JPG/PNG, maks. 2 MB.<?= $editing && $s['dokumen'] ? ' Kosongkan jika tidak ingin mengganti.' : '' ?></small><?= $err('dokumen') ?></div>
<div class="form-actions"><a class="btn btn-outline" href="<?= e(url('pendaftar/index.php')) ?>">Batal</a><button class="btn btn-primary" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Kirim Pendaftaran' ?></button></div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
