<?php
$pageTitle = 'Pembayaran';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/pagination.php';
require_admin();
$status = (string)($_GET['status'] ?? ''); $valid = ['pending', 'paid', 'failed', 'expired', 'cancelled'];
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 20;
$where = in_array($status, $valid, true) ? 'WHERE p.status = ?' : '';
$s = $conn->prepare("SELECT COUNT(*) c FROM pembayaran p $where"); if ($where) $s->bind_param('s', $status); $s->execute();
$total = (int)$s->get_result()->fetch_assoc()['c']; $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages); $off = ($page - 1) * $per;
$s = $conn->prepare("SELECT p.*, c.nama, c.no_pendaftaran, c.id sid FROM pembayaran p JOIN calon_siswa c ON c.id = p.calon_siswa_id $where ORDER BY p.id DESC LIMIT $per OFFSET $off"); if ($where) $s->bind_param('s', $status); $s->execute();
$rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<section class="page-heading"><span class="eyebrow">KEUANGAN</span><h1>Riwayat pembayaran</h1><p>Driver aktif: <strong><?= e(payment_driver()) ?></strong> · <?= $total ?> transaksi.</p></section>
<form class="table-form" method="GET"><select name="status"><option value="">Semua status</option><?php foreach ($valid as $v): ?><option <?= $status === $v ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Filter</button></form>
<div class="panel gap"><div class="table-scroll"><table><thead><tr><th>Order</th><th>Pendaftar</th><th>Jumlah</th><th>Status</th><th>Metode</th><th>Dibuat</th><th>Dibayar</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['order_id']) ?></td><td><a class="text-link" href="<?= e(url('admin/siswa-edit.php?id=' . (int)$r['sid'])) ?>"><?= e($r['nama']) ?></a></td><td><?= e(format_rupiah($r['jumlah'])) ?></td><td><?= status_badge($r['status']) ?></td><td><?= e($r['metode'] ?? '-') ?></td><td><?= e($r['created_at']) ?></td><td><?= e($r['paid_at'] ?? '-') ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table></div></div>
<?= render_pagination($page, $pages, array_filter(['status' => $status])) ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
