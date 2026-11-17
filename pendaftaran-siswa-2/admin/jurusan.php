<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
$errors = []; $edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $s = $conn->prepare('SELECT COUNT(*) c FROM calon_siswa WHERE jurusan_id = ?'); $s->bind_param('i', $id); $s->execute();
        if ((int)$s->get_result()->fetch_assoc()['c'] > 0) flash('error', 'Jurusan sudah dipakai pendaftar. Nonaktifkan saja, jangan dihapus.');
        else { $s = $conn->prepare('DELETE FROM jurusan WHERE id = ?'); $s->bind_param('i', $id); $s->execute(); audit('jurusan.hapus', 'jurusan', $id); flash('success', 'Jurusan dihapus.'); }
        redirect('admin/jurusan.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $kode = strtoupper(trim((string)($_POST['kode'] ?? ''))); $nama = trim((string)($_POST['nama'] ?? ''));
    $kuota = filter_var($_POST['kuota'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    if (!preg_match('/^[A-Z0-9]{2,10}$/', $kode)) $errors['kode'] = 'Kode 2-10 huruf/angka.';
    if ($nama === '' || mb_strlen($nama) > 100) $errors['nama'] = 'Nama wajib diisi (maks. 100 karakter).';
    if ($kuota === false) $errors['kuota'] = 'Kuota harus angka 0 - 100000.';
    if (!$errors) {
        try {
            if ($id) { $s = $conn->prepare('UPDATE jurusan SET kode=?, nama=?, kuota=?, aktif=? WHERE id=?'); $s->bind_param('ssiii', $kode, $nama, $kuota, $aktif, $id); }
            else { $s = $conn->prepare('INSERT INTO jurusan (kode, nama, kuota, aktif) VALUES (?,?,?,?)'); $s->bind_param('ssii', $kode, $nama, $kuota, $aktif); }
            $s->execute(); audit($id ? 'jurusan.ubah' : 'jurusan.tambah', 'jurusan', $id ?: $conn->insert_id, $kode);
            flash('success', 'Jurusan disimpan.'); redirect('admin/jurusan.php');
        } catch (mysqli_sql_exception $ex) { if ((int)$ex->getCode() === 1062) $errors['kode'] = 'Kode sudah dipakai.'; else throw $ex; }
    }
    $edit = ['id' => $id, 'kode' => $kode, 'nama' => $nama, 'kuota' => $_POST['kuota'] ?? '', 'aktif' => $aktif];
} elseif (isset($_GET['edit'])) {
    $s = $conn->prepare('SELECT * FROM jurusan WHERE id = ?'); $eid = (int)$_GET['edit']; $s->bind_param('i', $eid); $s->execute(); $edit = $s->get_result()->fetch_assoc() ?: null;
}
$pageTitle = 'Kelola Jurusan';
require_once __DIR__ . '/../includes/header.php';
$rows = jurusan_with_quota(false);
$err = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<section class="page-heading"><span class="eyebrow">DATA MASTER</span><h1>Jurusan &amp; kuota</h1></section>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kuota</th><th>Diterima</th><th>Pendaftar</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $j): ?><tr><td><?= e($j['kode']) ?></td><td><?= e($j['nama']) ?></td><td><?= (int)$j['kuota'] ?></td><td><?= (int)$j['diterima'] ?></td><td><?= (int)$j['pendaftar'] ?></td><td><?= $j['aktif'] ? 'Ya' : 'Tidak' ?></td>
<td class="actions"><a class="text-link" href="?edit=<?= (int)$j['id'] ?>">Ubah</a>
<form class="inline-form" method="POST" data-confirm="Hapus jurusan <?= e($j['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><button class="nav-button" type="submit">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<form class="form-card wide" method="POST"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><h2><?= $edit ? 'Ubah jurusan' : 'Tambah jurusan' ?></h2>
<div class="form-grid"><div class="form-group"><label>Kode</label><input name="kode" maxlength="10" value="<?= e($edit['kode'] ?? '') ?>"><?= $err('kode') ?></div>
<div class="form-group"><label>Nama jurusan</label><input name="nama" maxlength="100" value="<?= e($edit['nama'] ?? '') ?>"><?= $err('nama') ?></div>
<div class="form-group"><label>Kuota</label><input type="number" min="0" name="kuota" value="<?= e($edit['kuota'] ?? '36') ?>"><?= $err('kuota') ?></div>
<div class="form-group"><label><input type="checkbox" name="aktif" value="1" <?= ($edit['aktif'] ?? 1) ? 'checked' : '' ?>> Aktif (dapat dipilih pendaftar)</label></div></div>
<div class="form-actions"><?php if ($edit): ?><a class="btn btn-outline" href="<?= e(url('admin/jurusan.php')) ?>">Batal</a><?php endif; ?><button class="btn btn-primary" type="submit">Simpan</button></div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
