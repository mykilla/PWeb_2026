<?php
$pageTitle = 'Data Pendaftar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/pagination.php';
require_admin();
$q = trim((string)($_GET['q'] ?? '')); $status = (string)($_GET['status'] ?? ''); $jur = (int)($_GET['jurusan'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 15;
[$where, $types, $args] = admin_siswa_filter($q, $status, $jur);
$stmt = $conn->prepare("SELECT COUNT(*) c FROM calon_siswa c $where"); if ($types) $stmt->bind_param($types, ...$args); $stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c']; $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages); $off = ($page - 1) * $per;
$stmt = $conn->prepare("SELECT c.id, c.no_pendaftaran, c.nama, c.nisn, c.status, c.created_at, j.kode FROM calon_siswa c LEFT JOIN jurusan j ON j.id = c.jurusan_id $where ORDER BY c.id DESC LIMIT $per OFFSET $off");
if ($types) $stmt->bind_param($types, ...$args); $stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$jurusan = jurusan_with_quota(false);
$query = array_filter(['q' => $q, 'status' => $status, 'jurusan' => $jur ?: null]);
?>
<section class="page-heading"><span class="eyebrow">ADMIN</span><h1>Data pendaftar</h1><p><?= $total ?> data ditemukan.</p></section>
<form class="table-form" method="GET"><input name="q" placeholder="Cari nama / NISN / no. daftar" value="<?= e($q) ?>">
<select name="status"><option value="">Semua status</option><?php foreach (STATUS_LABELS as $k => $l): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
<select name="jurusan"><option value="">Semua jurusan</option><?php foreach ($jurusan as $j): ?><option value="<?= (int)$j['id'] ?>" <?= $jur === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['kode']) ?></option><?php endforeach; ?></select>
<button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-outline" href="<?= e(url('admin/export.php?' . http_build_query($query))) ?>">Ekspor CSV</a></form>
<div class="panel gap"><div class="table-scroll"><table><thead><tr><th>No. Daftar</th><th>Nama</th><th>NISN</th><th>Jurusan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['no_pendaftaran']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['nisn'] ?? '-') ?></td><td><?= e($r['kode'] ?? '-') ?></td><td><?= status_badge($r['status']) ?></td>
<td class="actions"><a class="text-link" href="<?= e(url('admin/siswa-edit.php?id=' . (int)$r['id'])) ?>">Detail</a>
<form class="inline-form" method="POST" action="<?= e(url('admin/siswa-hapus.php')) ?>" data-confirm="Hapus data <?= e($r['nama']) ?> secara permanen?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="nav-button" type="submit">Hapus</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6">Tidak ada data.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?= render_pagination($page, $pages, $query) ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
