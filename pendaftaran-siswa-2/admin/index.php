<?php
$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
require_admin();
$st = array_fill_keys(array_keys(STATUS_LABELS), 0);
foreach ($conn->query('SELECT status, COUNT(*) c FROM calon_siswa GROUP BY status') as $r) $st[$r['status']] = (int)$r['c'];
$total = array_sum($st);
$rev = $conn->query("SELECT COALESCE(SUM(jumlah),0) t, COUNT(*) c FROM pembayaran WHERE status='paid'")->fetch_assoc();
$jur = jurusan_with_quota(false);
$recent = $conn->query('SELECT id, no_pendaftaran, nama, status, created_at FROM calon_siswa ORDER BY id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
?>
<section class="page-heading"><span class="eyebrow">ADMIN</span><h1>Dashboard</h1></section>
<div class="stats-grid four">
    <article class="stat-card"><span>Total pendaftar</span><strong><?= $total ?></strong></article>
    <article class="stat-card"><span>Menunggu verifikasi</span><strong><?= $st['menunggu_verifikasi'] ?></strong></article>
    <article class="stat-card"><span>Diterima</span><strong><?= $st['diterima'] ?></strong></article>
    <article class="stat-card"><span>Pendapatan</span><strong><?= e(format_rupiah($rev['t'])) ?></strong><small><?= (int)$rev['c'] ?> pembayaran lunas</small></article>
</div>
<div class="panel gap"><h2>Status pendaftar</h2><div class="table-scroll"><table><tbody>
<?php foreach ($st as $k => $n): ?><tr><td><?= status_badge($k) ?></td><td><?= $n ?></td><td><a class="text-link" href="<?= e(url('admin/siswa.php?status=' . $k)) ?>">Lihat</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="panel"><h2>Kuota per jurusan</h2><div class="table-scroll"><table><thead><tr><th>Jurusan</th><th>Pendaftar</th><th>Diterima / Kuota</th><th></th></tr></thead><tbody>
<?php foreach ($jur as $j): $pct = $j['kuota'] > 0 ? min(100, round($j['diterima'] / $j['kuota'] * 100)) : 0; ?>
<tr><td><?= e($j['nama']) ?></td><td><?= (int)$j['pendaftar'] ?></td><td><?= (int)$j['diterima'] ?> / <?= (int)$j['kuota'] ?></td><td><div class="meter"><span class="w<?= (int)(round($pct / 5) * 5) ?>"></span></div></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<div class="panel"><h2>Pendaftar terbaru</h2><div class="table-scroll"><table><tbody>
<?php foreach ($recent as $r): ?><tr><td><?= e($r['no_pendaftaran']) ?></td><td><?= e($r['nama']) ?></td><td><?= status_badge($r['status']) ?></td><td><a class="text-link" href="<?= e(url('admin/siswa-edit.php?id=' . (int)$r['id'])) ?>">Detail</a></td></tr><?php endforeach; ?>
<?php if (!$recent): ?><tr><td>Belum ada pendaftar.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
