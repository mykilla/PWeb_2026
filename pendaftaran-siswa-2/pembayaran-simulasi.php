<?php
/** Halaman simulasi payment gateway. Hanya aktif pada driver mock dan bukan production. */
require_once __DIR__ . '/config/bootstrap.php';
if (payment_driver() !== 'mock' || is_production()) { http_response_code(404); exit('Tidak ditemukan.'); }
require_pendaftar();
$orderId = (string)($_GET['order'] ?? $_POST['order'] ?? '');
$stmt = $conn->prepare('SELECT p.*, c.user_id, c.no_pendaftaran FROM pembayaran p JOIN calon_siswa c ON c.id = p.calon_siswa_id WHERE p.order_id = ?');
$stmt->bind_param('s', $orderId); $stmt->execute();
$pay = $stmt->get_result()->fetch_assoc();
if (!$pay || (int)$pay['user_id'] !== (int)$_SESSION['user_id']) { http_response_code(404); exit('Tagihan tidak ditemukan.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ts = ($_POST['hasil'] ?? '') === 'berhasil' ? 'settlement' : 'deny';
    $amount = number_format((float)$pay['jumlah'], 2, '.', '');
    $n = ['order_id' => $orderId, 'status_code' => '200', 'gross_amount' => $amount, 'transaction_status' => $ts,
          'payment_type' => 'simulasi', 'transaction_id' => 'MOCK-' . bin2hex(random_bytes(6))];
    $n['signature_key'] = midtrans_signature($orderId, '200', $amount, payment_server_key());
    [$code, $msg] = payment_process_notification($n);   // jalur yang sama dengan webhook
    flash($code === 200 && $ts === 'settlement' ? 'success' : 'error', $code === 200 && $ts === 'settlement' ? 'Pembayaran simulasi berhasil.' : 'Pembayaran simulasi gagal (' . $msg . ').');
    redirect('pendaftar/index.php');
}
$pageTitle = 'Simulasi Pembayaran';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><form class="form-card auth-card" method="POST"><?= csrf_field() ?><input type="hidden" name="order" value="<?= e($orderId) ?>">
    <span class="eyebrow">MODE SIMULASI</span><h1>Payment Gateway (Mock)</h1>
    <div class="alert info">Halaman ini hanya untuk pengembangan. Ganti <code>PAYMENT_DRIVER=midtrans</code> pada .env untuk pembayaran sungguhan.</div>
    <dl class="kv"><dt>Order</dt><dd><?= e($orderId) ?></dd><dt>Pendaftaran</dt><dd><?= e($pay['no_pendaftaran']) ?></dd><dt>Jumlah</dt><dd><strong><?= e(format_rupiah($pay['jumlah'])) ?></strong></dd><dt>Status</dt><dd><?= status_badge($pay['status']) ?></dd></dl>
    <?php if ($pay['status'] === 'pending'): ?>
    <div class="form-actions"><button class="btn btn-outline" name="hasil" value="gagal">Simulasikan Gagal</button><button class="btn btn-primary" name="hasil" value="berhasil">Bayar Sekarang</button></div>
    <?php else: ?><p>Tagihan sudah diproses.</p><a class="btn btn-outline" href="<?= e(url('pendaftar/index.php')) ?>">Kembali</a><?php endif; ?>
</form></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
