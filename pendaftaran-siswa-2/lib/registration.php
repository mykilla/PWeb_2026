<?php
/** Logika bisnis pendaftaran: akun, periode, kuota, biodata, alur status. */

function user_find_by_email(string $email): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

/** Registrasi akun pendaftar. Mengembalikan [user_id|null, errors]. */
function user_register(string $nama, string $email, string $pw, string $pw2): array
{
    global $conn;
    $e = [];
    $nama = trim($nama); $email = strtolower(trim($email));
    if ($nama === '' || mb_strlen($nama) > 100) $e['nama'] = 'Nama wajib diisi (maks. 100 karakter).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $e['email'] = 'Format email tidak valid.';
    if ($err = password_policy_error($pw)) $e['password'] = $err;
    elseif ($pw !== $pw2) $e['password2'] = 'Konfirmasi password tidak cocok.';
    if ($e) return [null, $e];
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    try {
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?,?,?, 'pendaftar')");
        $stmt->bind_param('sss', $nama, $email, $hash); $stmt->execute();
    } catch (mysqli_sql_exception $ex) {
        if ((int)$ex->getCode() === 1062) return [null, ['email' => 'Email sudah terdaftar. Silakan masuk.']];
        throw $ex;
    }
    return [(int)$conn->insert_id, []];
}

function gelombang_aktif(): ?array
{
    global $conn;
    $r = $conn->query('SELECT * FROM gelombang WHERE aktif = 1 AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY tanggal_mulai LIMIT 1');
    return $r->fetch_assoc() ?: null;
}

/** Daftar jurusan beserta jumlah diterima dan sisa kuota. */
function jurusan_with_quota(bool $onlyActive = true): array
{
    global $conn;
    $sql = "SELECT j.id, j.kode, j.nama, j.kuota, j.aktif,
            (SELECT COUNT(*) FROM calon_siswa c WHERE c.jurusan_id = j.id AND c.status = 'diterima') AS diterima,
            (SELECT COUNT(*) FROM calon_siswa c WHERE c.jurusan_id = j.id) AS pendaftar
            FROM jurusan j" . ($onlyActive ? ' WHERE j.aktif = 1' : '') . ' ORDER BY j.nama';
    $rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) $r['sisa'] = max(0, (int)$r['kuota'] - (int)$r['diterima']);
    return $rows;
}

function siswa_by_user(int $userId): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT c.*, j.nama AS jurusan_nama, j.kode AS jurusan_kode, g.nama AS gelombang_nama, g.tahun_ajaran, g.biaya_pendaftaran
        FROM calon_siswa c LEFT JOIN jurusan j ON j.id = c.jurusan_id LEFT JOIN gelombang g ON g.id = c.gelombang_id WHERE c.user_id = ?');
    $stmt->bind_param('i', $userId); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function siswa_by_id(int $id): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT c.*, j.nama AS jurusan_nama, j.kode AS jurusan_kode, g.nama AS gelombang_nama, g.tahun_ajaran, g.biaya_pendaftaran
        FROM calon_siswa c LEFT JOIN jurusan j ON j.id = c.jurusan_id LEFT JOIN gelombang g ON g.id = c.gelombang_id WHERE c.id = ?');
    $stmt->bind_param('i', $id); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function jurusan_aktif_exists(int $id): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT 1 FROM jurusan WHERE id = ? AND aktif = 1'); $stmt->bind_param('i', $id); $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

/** Simpan unggahan dokumen. Mengembalikan [nama_file|null, error|null]. null+null = tidak ada berkas. */
function handle_upload(?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) return [null, 'Dokumen gagal diunggah atau ukurannya melebihi 2 MB.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) return [null, 'Format dokumen harus PDF, JPG, atau PNG.'];
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return [null, 'Dokumen tidak dapat disimpan.'];
    return [$name, null];
}

function delete_upload(?string $name): void
{
    if (!$name) return;
    $p = __DIR__ . '/../uploads/' . basename($name);
    if (is_file($p)) @unlink($p);
}

/**
 * Buat pendaftaran baru untuk akun pendaftar. Aturan: periode pendaftaran harus aktif,
 * jurusan aktif, satu akun satu pendaftaran, NISN unik.
 * Mengembalikan [siswa_id|null, errors].
 */
function siswa_create(int $userId, array $clean, ?string $dokumen): array
{
    global $conn;
    $gel = gelombang_aktif();
    if (!$gel) return [null, ['_' => 'Pendaftaran sedang ditutup (di luar periode gelombang aktif).']];
    if (!jurusan_aktif_exists($clean['jurusan_id'])) return [null, ['jurusan_id' => 'Jurusan tidak tersedia.']];
    if (siswa_by_user($userId)) return [null, ['_' => 'Anda sudah memiliki data pendaftaran.']];

    $status = ((int)$gel['biaya_pendaftaran'] > 0) ? 'menunggu_pembayaran' : 'menunggu_verifikasi';
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('INSERT INTO calon_siswa (user_id, gelombang_id, jurusan_id, nisn, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, asal_sekolah, alamat, no_hp, nama_ortu, no_hp_ortu, dokumen, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('iiisssssssssssss', $userId, $gel['id'], $clean['jurusan_id'], $clean['nisn'], $clean['nama'], $clean['tempat_lahir'], $clean['tanggal_lahir'],
            $clean['jenis_kelamin'], $clean['agama'], $clean['asal_sekolah'], $clean['alamat'], $clean['no_hp'], $clean['nama_ortu'], $clean['no_hp_ortu'], $dokumen, $status);
        $stmt->execute();
        $id = (int)$conn->insert_id;
        $tahun = substr($gel['tahun_ajaran'], 0, 4);
        $no = sprintf('PSB-%s-%05d', $tahun, $id);
        $stmt = $conn->prepare('UPDATE calon_siswa SET no_pendaftaran = ? WHERE id = ?');
        $stmt->bind_param('si', $no, $id); $stmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        if ((int)$ex->getCode() === 1062) return [null, ['nisn' => 'NISN sudah terdaftar pada sistem.']];
        throw $ex;
    }
    audit('siswa.daftar', 'calon_siswa', $id, $no);
    return [$id, []];
}

/** Perbarui biodata oleh pemilik (hanya saat menunggu pembayaran) atau admin. */
function siswa_update(int $id, array $c, ?string $dokumenBaru = null): array
{
    global $conn;
    try {
        $sql = 'UPDATE calon_siswa SET jurusan_id=?, nisn=?, nama=?, tempat_lahir=?, tanggal_lahir=?, jenis_kelamin=?, agama=?, asal_sekolah=?, alamat=?, no_hp=?, nama_ortu=?, no_hp_ortu=?';
        $types = 'isssssssssss'; $args = [$c['jurusan_id'], $c['nisn'], $c['nama'], $c['tempat_lahir'], $c['tanggal_lahir'], $c['jenis_kelamin'], $c['agama'], $c['asal_sekolah'], $c['alamat'], $c['no_hp'], $c['nama_ortu'], $c['no_hp_ortu']];
        if ($dokumenBaru !== null) { $sql .= ', dokumen=?'; $types .= 's'; $args[] = $dokumenBaru; }
        $sql .= ' WHERE id=?'; $types .= 'i'; $args[] = $id;
        $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$args); $stmt->execute();
    } catch (mysqli_sql_exception $ex) {
        if ((int)$ex->getCode() === 1062) return ['nisn' => 'NISN sudah dipakai pendaftar lain.'];
        throw $ex;
    }
    return [];
}

/**
 * Ubah status oleh admin dengan aturan alur dan kuota (transaksional).
 * Mengembalikan [ok, pesan].
 */
function admin_change_status(int $id, string $to, ?string $catatan): array
{
    global $conn;
    if (!isset(STATUS_LABELS[$to])) return [false, 'Status tidak dikenal.'];
    $catatan = $catatan !== null ? mb_substr(trim($catatan), 0, 255) : null;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT status, jurusan_id FROM calon_siswa WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) { $conn->rollback(); return [false, 'Data tidak ditemukan.']; }
        $from = $row['status'];
        if (!admin_can_transition($from, $to)) { $conn->rollback(); return [false, 'Perubahan status ' . status_label($from) . ' → ' . status_label($to) . ' tidak diizinkan.']; }
        if ($to === 'diterima') {
            if (!$row['jurusan_id']) { $conn->rollback(); return [false, 'Pendaftar belum memiliki jurusan.']; }
            $s = $conn->prepare('SELECT kuota FROM jurusan WHERE id = ? FOR UPDATE'); $s->bind_param('i', $row['jurusan_id']); $s->execute();
            $kuota = (int)$s->get_result()->fetch_assoc()['kuota'];
            $s = $conn->prepare("SELECT COUNT(*) c FROM calon_siswa WHERE jurusan_id = ? AND status = 'diterima'"); $s->bind_param('i', $row['jurusan_id']); $s->execute();
            if ((int)$s->get_result()->fetch_assoc()['c'] >= $kuota) { $conn->rollback(); return [false, 'Kuota jurusan sudah penuh.']; }
        }
        $stmt = $conn->prepare('UPDATE calon_siswa SET status = ?, catatan_admin = ? WHERE id = ?');
        $stmt->bind_param('ssi', $to, $catatan, $id); $stmt->execute();
        $conn->commit();
    } catch (Throwable $t) { $conn->rollback(); throw $t; }
    audit('siswa.status', 'calon_siswa', $id, "$from -> $to");
    return [true, 'Status diperbarui menjadi ' . status_label($to) . '.'];
}

/** Cek status publik: nomor pendaftaran + tanggal lahir. */
function siswa_public_status(string $no, string $tgl): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT c.no_pendaftaran, c.nama, c.status, c.catatan_admin, j.nama AS jurusan_nama FROM calon_siswa c LEFT JOIN jurusan j ON j.id = c.jurusan_id WHERE c.no_pendaftaran = ? AND c.tanggal_lahir = ?');
    $stmt->bind_param('ss', $no, $tgl); $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function mask_name(string $n): string
{
    $parts = preg_split('/\s+/', trim($n));
    return implode(' ', array_map(fn($p) => mb_substr($p, 0, 1) . str_repeat('*', max(0, min(5, mb_strlen($p) - 1))), $parts));
}

/** Susun klausa WHERE terparameter untuk daftar/ekspor pendaftar. Mengembalikan [where, types, args]. */
function admin_siswa_filter(string $q, string $status, int $jurusan): array
{
    $w = []; $types = ''; $args = [];
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $w[] = '(c.nama LIKE ? OR c.nisn LIKE ? OR c.no_pendaftaran LIKE ?)'; $types .= 'sss'; array_push($args, $like, $like, $like);
    }
    if (isset(STATUS_LABELS[$status])) { $w[] = 'c.status = ?'; $types .= 's'; $args[] = $status; }
    if ($jurusan > 0) { $w[] = 'c.jurusan_id = ?'; $types .= 'i'; $args[] = $jurusan; }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $types, $args];
}
