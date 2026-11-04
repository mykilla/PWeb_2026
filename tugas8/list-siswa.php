<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Data Pendaftar';
$result = $conn->query(
    'SELECT id, nama, alamat, jenis_kelamin, agama,
    asal_sekolah, created_at
    FROM calon_siswa
    ORDER BY id DESC'
);
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-heading heading-row">
    <div><span class="eyebrow">ADMINISTRASI</span><h1>Data pendaftar</h1><p>Daftar siswa yang sudah mengisi formulir.</p></div>
    <a class="btn btn-primary" href="form-daftar.php">+ Tambah Siswa</a>
</section>
<?php if (($_GET['status'] ?? '') === 'created'): ?><div class="alert success">Pendaftaran berhasil disimpan.</div><?php endif; ?>
<?php if (($_GET['status'] ?? '') === 'updated'): ?><div class="alert success">Data siswa berhasil diperbarui.</div><?php endif; ?>
<?php if (($_GET['status'] ?? '') === 'deleted'): ?><div class="alert success">Data siswa berhasil dihapus.</div><?php endif; ?>
<div class="table-card">
    <?php if ($result->num_rows === 0): ?>
        <div class="empty-state"><span>◎</span><h2>Belum ada pendaftar</h2><p>Data siswa akan muncul di sini setelah formulir dikirim.</p><a class="btn btn-primary" href="form-daftar.php">Daftarkan siswa</a></div>
    <?php else: ?>
    <div class="table-scroll"><table>
        <thead><tr><th>No.</th><th>Nama</th><th>Asal Sekolah</th><th>Alamat</th><th>Jenis Kelamin</th><th>Agama</th><th>Tindakan</th></tr></thead>
        <tbody>
        <?php $no = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                <td><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['alamat'])) ?></td>
                <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                <td><?= htmlspecialchars($row['agama']) ?></td>
                <td class="actions">
                    <a class="text-link" href="edit-siswa.php?id=<?= (int)$row['id'] ?>">Edit</a>
                    <form action="hapus-siswa.php" method="POST" class="inline-form" data-confirm="Yakin ingin menghapus data siswa ini?">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="text-link danger-link" type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
