<?php
$pageTitle = 'Formulir Pendaftaran';
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <span class="eyebrow">FORMULIR ONLINE</span>
    <h1>Pendaftaran siswa baru</h1>
    <p>Lengkapi informasi berikut dengan benar.</p>
</section>
<form class="form-card" action="proses-pendaftaran.php" method="POST">
    <div class="form-grid">
        <div class="form-group full">
            <label for="nama">Nama lengkap</label>
            <input type="text" id="nama" name="nama" maxlength="100" placeholder="Masukkan nama lengkap" required>
        </div>
        <div class="form-group full">
            <label for="asal_sekolah">Asal Sekolah</label>
            <input
                type="text"
                id="asal_sekolah"
                name="asal_sekolah"
                maxlength="100"
                placeholder="Contoh: SMP Negeri 1 Surabaya"
                required>
        </div>
        <div class="form-group full">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap" required></textarea>
        </div>
        <fieldset class="form-group full fieldset">
            <legend>Jenis kelamin</legend>
            <label class="radio-label"><input type="radio" name="jenis_kelamin" value="Laki-laki" required> Laki-laki</label>
            <label class="radio-label"><input type="radio" name="jenis_kelamin" value="Perempuan"> Perempuan</label>
        </fieldset>
        <div class="form-group full">
            <label for="agama">Agama</label>
            <select id="agama" name="agama" required>
                <option value="">Pilih agama</option>
                <option>Islam</option><option>Kristen</option><option>Katolik</option>
                <option>Hindu</option><option>Buddha</option><option>Konghucu</option>
                <option>Lainnya</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn btn-outline" href="index.php">Kembali</a>
        <button class="btn btn-primary" type="submit">Kirim Pendaftaran</button>
    </div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
