<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_admin();
$errors = []; $edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['aksi'] ?? '') === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $s = $conn->prepare('SELECT COUNT(*) c FROM calon_siswa WHERE gelombang_id = ?'); $s->bind_param('i', $id); $s->execute();
        if ((int)$s->get_result()->fetch_assoc()['c'] > 0) flash('error', 'Gelombang sudah dipakai pendaftar. Nonaktifkan saja.');
        else { $s = $conn->prepare('DELETE FROM gelombang WHERE id = ?'); $s->bind_param('i', $id); $s->execute(); audit('gelombang.hapus', 'gelombang', $id); flash('success', 'Gelombang dihapus.'); }
        redirect('admin/gelombang.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim((string)($_POST['nama'] ?? '')); $ta = trim((string)($_POST['tahun_ajaran'] ?? ''));
    $mulai = (string)($_POST['tanggal_mulai'] ?? ''); $selesai = (string)($_POST['tanggal_selesai'] ?? '');
    $biaya = filter_var($_POST['biaya'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000000]]);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $okDate = fn($d) => ($x = DateTime::createFromFormat('!Y-m-d', $d)) && $x->format('Y-m-d') === $d;
    if ($nama === '' || mb_strlen($nama) > 60) $errors['nama'] = 'Nama wajib diisi.';
    if (!preg_match('#^\d{4}/\d{4}$#', $ta)) $errors['tahun_ajaran'] = 'Format 2026/2027.';
    if (!$okDate($mulai)) $errors['tanggal_mulai'] = 'Tanggal tidak valid.';
    if (!$okDate($selesai)) $errors['tanggal_selesai'] = 'Tanggal tidak valid.';
    elseif ($okDate($mulai) && $selesai < $mulai) $errors['tanggal_selesai'] = 'Tanggal selesai harus setelah tanggal mulai.';
    if ($biaya === false) $errors['biaya'] = 'Biaya harus angka >= 0.';
    if (!$errors && $aktif) {   // gelombang aktif tidak boleh tumpang tindih
        $s = $conn->prepare('SELECT nama FROM gelombang WHERE aktif = 1 AND id <> ? AND tanggal_mulai <= ? AND tanggal_selesai >= ? LIMIT 1');
        $s->bind_param('iss', $id, $selesai, $mulai); $s->execute();
        if ($o = $s->get_result()->fetch_assoc()) $errors['tanggal_mulai'] = 'Periode tumpang tindih dengan gelombang aktif "' . $o['nama'] . '".';
    }
    if (!$errors) {
        if ($id) { $s = $conn->prepare('UPDATE gelombang SET nama=?, tahun_ajaran=?, tanggal_mulai=?, tanggal_selesai=?, biaya_pendaftaran=?, aktif=? WHERE id=?'); $s->bind_param('ssssiii', $nama, $ta, $mulai, $selesai, $biaya, $aktif, $id); }
        else { $s = $conn->prepare('INSERT INTO gelombang (nama, tahun_ajaran, tanggal_mulai, tanggal_selesai, biaya_pendaftaran, aktif) VALUES (?,?,?,?,?,?)'); $s->bind_param('ssssii', $nama, $ta, $mulai, $selesai, $biaya, $aktif); }
        $s->execute(); audit($id ? 'gelombang.ubah' : 'gelombang.tambah', 'gelombang', $id ?: $conn->insert_id, $nama);
        flash('success', 'Gelombang disimpan.'); redirect('admin/gelombang.php');
    }
    $edit = ['id' => $id, 'nama' => $nama, 'tahun_ajaran' => $ta, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai, 'biaya_pendaftaran' => $_POST['biaya'] ?? '', 'aktif' => $aktif];
} elseif (isset($_GET['edit'])) {
    $s = $conn->prepare('SELECT * FROM gelombang WHERE id = ?'); $eid = (int)$_GET['edit']; $s->bind_param('i', $eid); $s->execute(); $edit = $s->get_result()->fetch_assoc() ?: null;
}
$pageTitle = 'Kelola Gelombang';
require_once __DIR__ . '/../includes/header.php';
$rows = $conn->query('SELECT g.*, (SELECT COUNT(*) FROM calon_siswa c WHERE c.gelombang_id = g.id) pendaftar FROM gelombang g ORDER BY tanggal_mulai DESC')->fetch_all(MYSQLI_ASSOC);
$err = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<section class="page-heading"><span class="eyebrow">DATA MASTER</span><h1>Gelombang pendaftaran</h1><p>Pendaftaran hanya dibuka pada gelombang aktif yang periodenya mencakup hari ini.</p></section>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>Nama</th><th>Tahun ajaran</th><th>Periode</th><th>Biaya</th><th>Pendaftar</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $g): ?><tr><td><?= e($g['nama']) ?></td><td><?= e($g['tahun_ajaran']) ?></td><td><?= e($g['tanggal_mulai']) ?> s/d <?= e($g['tanggal_selesai']) ?></td><td><?= e(format_rupiah($g['biaya_pendaftaran'])) ?></td><td><?= (int)$g['pendaftar'] ?></td><td><?= $g['aktif'] ? 'Ya' : 'Tidak' ?></td>
<td class="actions"><a class="text-link" href="?edit=<?= (int)$g['id'] ?>">Ubah</a>
<form class="inline-form" method="POST" data-confirm="Hapus <?= e($g['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><button class="nav-button" type="submit">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<form class="form-card wide" method="POST"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><h2><?= $edit ? 'Ubah gelombang' : 'Tambah gelombang' ?></h2>
<div class="form-grid"><div class="form-group"><label>Nama</label><input name="nama" maxlength="60" value="<?= e($edit['nama'] ?? '') ?>"><?= $err('nama') ?></div>
<div class="form-group"><label>Tahun ajaran</label><input name="tahun_ajaran" placeholder="2026/2027" value="<?= e($edit['tahun_ajaran'] ?? '') ?>"><?= $err('tahun_ajaran') ?></div>
<div class="form-group"><label>Tanggal mulai</label><input type="date" name="tanggal_mulai" value="<?= e($edit['tanggal_mulai'] ?? '') ?>"><?= $err('tanggal_mulai') ?></div>
<div class="form-group"><label>Tanggal selesai</label><input type="date" name="tanggal_selesai" value="<?= e($edit['tanggal_selesai'] ?? '') ?>"><?= $err('tanggal_selesai') ?></div>
<div class="form-group"><label>Biaya pendaftaran (Rp)</label><input type="number" min="0" name="biaya" value="<?= e($edit['biaya_pendaftaran'] ?? '250000') ?>"><?= $err('biaya') ?></div>
<div class="form-group"><label><input type="checkbox" name="aktif" value="1" <?= ($edit['aktif'] ?? 1) ? 'checked' : '' ?>> Aktif</label></div></div>
<div class="form-actions"><?php if ($edit): ?><a class="btn btn-outline" href="<?= e(url('admin/gelombang.php')) ?>">Batal</a><?php endif; ?><button class="btn btn-primary" type="submit">Simpan</button></div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
