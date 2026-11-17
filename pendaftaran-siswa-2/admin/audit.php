<?php
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/pagination.php';
require_admin();
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 30;
$total = (int)$conn->query('SELECT COUNT(*) c FROM audit_log')->fetch_assoc()['c']; $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages); $off = ($page - 1) * $per;
$rows = $conn->query("SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT $per OFFSET $off")->fetch_all(MYSQLI_ASSOC);
?>
<section class="page-heading"><span class="eyebrow">KEAMANAN</span><h1>Audit log</h1><p>Jejak login, perubahan status, pembayaran, dan ekspor data.</p></section>
<div class="panel"><div class="table-scroll"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['email'] ?? '-') ?></td><td><?= e($r['aksi']) ?></td><td><?= e(trim(($r['entitas'] ?? '') . ' ' . ($r['entitas_id'] ?? ''))) ?></td><td><?= e($r['detail'] ?? '') ?></td><td><?= e($r['ip']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?= render_pagination($page, $pages) ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
