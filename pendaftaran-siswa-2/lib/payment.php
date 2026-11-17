<?php
/**
 * Integrasi Payment Gateway. Driver:
 *  - midtrans : Snap API (sandbox/production) + webhook ber-signature
 *  - mock     : simulasi lokal untuk pengembangan/demo (tidak memanggil jaringan)
 * Keduanya memakai satu jalur pemrosesan notifikasi: payment_process_notification().
 */

function payment_driver(): string { return env('PAYMENT_DRIVER', 'mock') === 'midtrans' ? 'midtrans' : 'mock'; }

function payment_server_key(): string
{
    return payment_driver() === 'midtrans' ? (string)env('MIDTRANS_SERVER_KEY', '') : 'mock-server-key';
}

function app_url(): string
{
    $u = (string)env('APP_URL', '');
    if ($u !== '') return rtrim($u, '/');
    if (is_production()) throw new RuntimeException('APP_URL wajib diisi pada mode production.');
    return (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
}

function payment_latest(int $siswaId): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM pembayaran WHERE calon_siswa_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $siswaId); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/** Panggil Midtrans Snap. Mengembalikan [redirect_url|null, error|null]. */
function midtrans_create_snap(array $siswa, string $orderId, int $amount): array
{
    $key = payment_server_key();
    if ($key === '') return [null, 'MIDTRANS_SERVER_KEY belum dikonfigurasi.'];
    $isProd = env('MIDTRANS_IS_PRODUCTION', 'false') === 'true';
    $endpoint = $isProd ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    $payload = [
        'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $amount],
        'item_details' => [['id' => 'BIAYA-PSB', 'price' => $amount, 'quantity' => 1, 'name' => 'Biaya pendaftaran ' . $siswa['no_pendaftaran']]],
        'customer_details' => ['first_name' => mb_substr($siswa['nama'], 0, 50), 'phone' => $siswa['no_hp']],
        'callbacks' => ['finish' => app_url() . '/pendaftar/index.php'],
    ];
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Basic ' . base64_encode($key . ':')],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($body === false) { error_log('Midtrans curl: ' . $err); return [null, 'Tidak dapat terhubung ke payment gateway.']; }
    $res = json_decode($body, true);
    if ($code >= 200 && $code < 300 && !empty($res['redirect_url'])) return [$res['redirect_url'], null];
    error_log('Midtrans error ' . $code . ': ' . $body);
    return [null, 'Payment gateway menolak permintaan. Coba lagi nanti.'];
}

/**
 * Buat (atau gunakan ulang) tagihan untuk pendaftar. Aturan: hanya saat status menunggu_pembayaran;
 * tagihan pending < 24 jam dipakai ulang agar tidak ada tagihan ganda.
 * Mengembalikan [payment_url|null, error|null].
 */
function payment_create(array $siswa): array
{
    global $conn;
    if ($siswa['status'] !== 'menunggu_pembayaran') return [null, 'Pendaftaran ini tidak memerlukan pembayaran.'];
    $amount = (int)$siswa['biaya_pendaftaran'];
    if ($amount <= 0) return [null, 'Biaya pendaftaran tidak valid.'];

    $last = payment_latest((int)$siswa['id']);
    if ($last && $last['status'] === 'pending' && $last['payment_url'] && strtotime($last['created_at']) > time() - 86400 && (int)$last['jumlah'] === $amount) {
        return [$last['payment_url'], null];
    }
    $driver = payment_driver();
    $orderId = make_order_id((int)$siswa['id']);
    if ($driver === 'midtrans') {
        [$url, $err] = midtrans_create_snap($siswa, $orderId, $amount);
        if ($err) return [null, $err];
    } else {
        $url = url('pembayaran-simulasi.php?order=' . urlencode($orderId));
    }
    $sid = (int)$siswa['id'];
    $stmt = $conn->prepare("INSERT INTO pembayaran (calon_siswa_id, order_id, jumlah, status, driver, payment_url) VALUES (?,?,?, 'pending', ?, ?)");
    $stmt->bind_param('isiss', $sid, $orderId, $amount, $driver, $url); $stmt->execute();
    audit('pembayaran.buat', 'pembayaran', $orderId, "$driver $amount");
    return [$url, null];
}

/**
 * Proses notifikasi gateway (webhook). Aman diulang (idempoten).
 * Mengembalikan [http_status, pesan].
 */
function payment_process_notification(array $n): array
{
    global $conn;
    if (!verify_notification_signature($n, payment_server_key())) {
        audit('pembayaran.signature_invalid', 'pembayaran', (string)($n['order_id'] ?? '?'));
        return [403, 'Signature tidak valid'];
    }
    $orderId = (string)$n['order_id'];
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT * FROM pembayaran WHERE order_id = ? FOR UPDATE');
        $stmt->bind_param('s', $orderId); $stmt->execute();
        $pay = $stmt->get_result()->fetch_assoc();
        if (!$pay) { $conn->rollback(); return [404, 'Order tidak ditemukan']; }
        if ((int)round((float)$n['gross_amount']) !== (int)$pay['jumlah']) {
            $conn->rollback(); audit('pembayaran.jumlah_tidak_cocok', 'pembayaran', $orderId); return [400, 'Jumlah tidak cocok'];
        }
        if ($pay['status'] === 'paid') { $conn->rollback(); return [200, 'Sudah diproses']; }   // idempoten

        $new = map_gateway_status((string)($n['transaction_status'] ?? ''), $n['fraud_status'] ?? null);
        $metode = isset($n['payment_type']) ? mb_substr((string)$n['payment_type'], 0, 40) : null;
        $ref = isset($n['transaction_id']) ? mb_substr((string)$n['transaction_id'], 0, 80) : null;
        $raw = mb_substr(json_encode($n, JSON_UNESCAPED_UNICODE), 0, 5000);
        $paidAt = $new === 'paid' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare('UPDATE pembayaran SET status=?, metode=?, gateway_ref=?, raw_notification=?, paid_at=? WHERE id=?');
        $stmt->bind_param('sssssi', $new, $metode, $ref, $raw, $paidAt, $pay['id']); $stmt->execute();
        if ($new === 'paid') {
            $stmt = $conn->prepare("UPDATE calon_siswa SET status='menunggu_verifikasi' WHERE id=? AND status='menunggu_pembayaran'");
            $stmt->bind_param('i', $pay['calon_siswa_id']); $stmt->execute();
        }
        $conn->commit();
    } catch (Throwable $t) { $conn->rollback(); error_log('payment notify: ' . $t->getMessage()); return [500, 'Kesalahan server']; }
    audit('pembayaran.' . $new, 'pembayaran', $orderId);
    return [200, 'OK'];
}
