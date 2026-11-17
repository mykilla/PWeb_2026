<?php
/**
 * Fungsi murni (tanpa akses database/sesi) agar mudah diuji: aturan validasi,
 * alur status, tanda tangan webhook, dan utilitas format.
 */

const GENDERS = ['Laki-laki', 'Perempuan'];
const RELIGIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

const STATUS_LABELS = [
    'menunggu_pembayaran' => 'Menunggu Pembayaran',
    'menunggu_verifikasi' => 'Menunggu Verifikasi',
    'terverifikasi'       => 'Terverifikasi',
    'diterima'            => 'Diterima',
    'ditolak'             => 'Ditolak',
];

/** Transisi status yang boleh dilakukan ADMIN. Transisi pembayaran ditangani sistem. */
const ADMIN_TRANSITIONS = [
    'menunggu_pembayaran' => [],
    'menunggu_verifikasi' => ['terverifikasi', 'ditolak'],
    'terverifikasi'       => ['diterima', 'ditolak', 'menunggu_verifikasi'],
    'diterima'            => ['terverifikasi'],
    'ditolak'             => ['terverifikasi'],
];

function admin_can_transition(string $from, string $to): bool
{
    return in_array($to, ADMIN_TRANSITIONS[$from] ?? [], true);
}

function status_label(string $status): string
{
    return STATUS_LABELS[$status] ?? $status;
}

function format_rupiah($n): string
{
    return 'Rp ' . number_format((int)$n, 0, ',', '.');
}

function normalize_phone(string $p): string
{
    return preg_replace('/[\s\-().]/', '', $p);
}

function is_valid_phone(string $p): bool
{
    return (bool)preg_match('/^(\+62|62|0)8[0-9]{8,12}$/', normalize_phone($p));
}

/** Kebijakan password: 8-72 byte, mengandung huruf dan angka. Mengembalikan pesan error atau null. */
function password_policy_error(string $pw): ?string
{
    if (strlen($pw) < 8) return 'Password minimal 8 karakter.';
    if (strlen($pw) > 72) return 'Password maksimal 72 karakter.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Password harus mengandung huruf dan angka.';
    return null;
}

function calc_age(string $birthDate, ?DateTimeInterface $today = null): ?int
{
    $d = DateTime::createFromFormat('!Y-m-d', $birthDate);
    if (!$d || $d->format('Y-m-d') !== $birthDate) return null;
    $today = $today ?? new DateTimeImmutable('today');
    return (int)$d->diff($today)->y;
}

/**
 * Validasi biodata. Mengembalikan [bersih, errors].
 * $opts: min_usia, max_usia, today (DateTimeInterface)
 */
function validate_biodata(array $in, array $opts = []): array
{
    $minAge = $opts['min_usia'] ?? 12;
    $maxAge = $opts['max_usia'] ?? 21;
    $f = fn($k) => trim((string)($in[$k] ?? ''));
    $c = [
        'nama' => $f('nama'), 'nisn' => $f('nisn'), 'tempat_lahir' => $f('tempat_lahir'),
        'tanggal_lahir' => $f('tanggal_lahir'), 'jenis_kelamin' => $f('jenis_kelamin'),
        'agama' => $f('agama'), 'asal_sekolah' => $f('asal_sekolah'), 'alamat' => $f('alamat'),
        'no_hp' => normalize_phone($f('no_hp')), 'nama_ortu' => $f('nama_ortu'),
        'no_hp_ortu' => normalize_phone($f('no_hp_ortu')), 'jurusan_id' => (int)($in['jurusan_id'] ?? 0),
    ];
    $e = [];
    if ($c['nama'] === '' || mb_strlen($c['nama']) > 100) $e['nama'] = 'Nama wajib diisi (maks. 100 karakter).';
    if (!preg_match('/^\d{10}$/', $c['nisn'])) $e['nisn'] = 'NISN harus 10 digit angka.';
    if ($c['tempat_lahir'] === '' || mb_strlen($c['tempat_lahir']) > 60) $e['tempat_lahir'] = 'Tempat lahir wajib diisi (maks. 60 karakter).';
    $age = calc_age($c['tanggal_lahir'], $opts['today'] ?? null);
    if ($age === null) $e['tanggal_lahir'] = 'Tanggal lahir tidak valid.';
    elseif ($age < $minAge || $age > $maxAge) $e['tanggal_lahir'] = "Usia pendaftar harus antara $minAge dan $maxAge tahun.";
    if (!in_array($c['jenis_kelamin'], GENDERS, true)) $e['jenis_kelamin'] = 'Pilih jenis kelamin.';
    if (!in_array($c['agama'], RELIGIONS, true)) $e['agama'] = 'Pilih agama.';
    if ($c['asal_sekolah'] === '' || mb_strlen($c['asal_sekolah']) > 150) $e['asal_sekolah'] = 'Asal sekolah wajib diisi (maks. 150 karakter).';
    if ($c['alamat'] === '' || mb_strlen($c['alamat']) > 1000) $e['alamat'] = 'Alamat wajib diisi (maks. 1000 karakter).';
    if (!is_valid_phone($c['no_hp'])) $e['no_hp'] = 'Nomor HP tidak valid (contoh: 081234567890).';
    if ($c['nama_ortu'] === '' || mb_strlen($c['nama_ortu']) > 100) $e['nama_ortu'] = 'Nama orang tua/wali wajib diisi.';
    if (!is_valid_phone($c['no_hp_ortu'])) $e['no_hp_ortu'] = 'Nomor HP orang tua/wali tidak valid.';
    if ($c['jurusan_id'] <= 0) $e['jurusan_id'] = 'Pilih jurusan.';
    return [$c, $e];
}

/** Tanda tangan notifikasi Midtrans: SHA512(order_id + status_code + gross_amount + server_key). */
function midtrans_signature(string $orderId, string $statusCode, string $grossAmount, string $serverKey): string
{
    return hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
}

function verify_notification_signature(array $n, string $serverKey): bool
{
    foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $k) {
        if (!isset($n[$k]) || !is_scalar($n[$k])) return false;
    }
    $expected = midtrans_signature((string)$n['order_id'], (string)$n['status_code'], (string)$n['gross_amount'], $serverKey);
    return hash_equals($expected, strtolower((string)$n['signature_key']));
}

/** Petakan status transaksi Midtrans ke status internal: paid | pending | failed | expired | cancelled. */
function map_gateway_status(string $transactionStatus, ?string $fraudStatus = null): string
{
    switch ($transactionStatus) {
        case 'settlement': return 'paid';
        case 'capture':    return ($fraudStatus === null || $fraudStatus === 'accept') ? 'paid' : 'pending';
        case 'pending':    return 'pending';
        case 'expire':     return 'expired';
        case 'cancel':     return 'cancelled';
        case 'deny': case 'failure': return 'failed';
        default:           return 'pending';
    }
}

/** Cegah CSV/Formula injection saat ekspor. */
function csv_cell($v): string
{
    $s = (string)$v;
    if ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) return "'" . $s;
    return $s;
}

function make_order_id(int $siswaId, ?int $ts = null): string
{
    return 'PSB' . $siswaId . '-' . ($ts ?? time()) . '-' . strtoupper(bin2hex(random_bytes(2)));
}
