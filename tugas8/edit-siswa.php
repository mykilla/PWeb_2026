<?php
require_once __DIR__ . '/config/database.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('ID siswa tidak valid.'); }

$stmt = $conn->prepare('SELECT nama, asal_sekolah, alamat, jenis_kelamin, agama FROM calon_siswa WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
if (!$siswa) { http_response_code(404); exit('Data siswa tidak ditemukan.'); }

$pageTitle = 'Edit Data Siswa';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><span class="eyebrow">PEMBARUAN DATA</span><h1>Edit data siswa</h1><p>Periksa kembali informasi sebelum menyimpan perubahan.</p></section>
<form class="form-card" action="proses-edit.php" method="POST">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-grid">
        <div class="form-group full"><label for="nama">Nama lengkap</label><input id="nama" name="nama" maxlength="100" value="<?= htmlspecialchars($siswa['nama']) ?>" required></div>
        <div class="form-group full"><label for="alamat">Alamat</label><textarea id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($siswa['alamat']) ?></textarea></div>
        <fieldset class="form-group full fieldset"><legend>Jenis kelamin</legend>
            <?php foreach (['Laki-laki', 'Perempuan'] as $gender): ?>
                <label class="radio-label"><input type="radio" name="jenis_kelamin" value="<?= $gender ?>" <?= $siswa['jenis_kelamin'] === $gender ? 'checked' : '' ?> required> <?= $gender ?></label>
            <?php endforeach; ?>
        </fieldset>
        <div class="form-group full">
        <label for="asal_sekolah">Asal Sekolah</label>
        <input
        type="text"
        id="asal_sekolah"
        name="asal_sekolah"
        maxlength="100"
        value="<?= htmlspecialchars($siswa['asal_sekolah']) ?>"
        required>
        </div>
    </div>
    <div class="form-actions"><a class="btn btn-outline" href="list-siswa.php">Batal</a><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
