<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = (string)($_POST['lama'] ?? ''); $new = (string)($_POST['baru'] ?? ''); $new2 = (string)($_POST['baru2'] ?? '');
    $uid = (int)$_SESSION['user_id'];
    $s = $conn->prepare('SELECT password_hash FROM users WHERE id=?'); $s->bind_param('i', $uid); $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if (!$row || !password_verify($old, $row['password_hash'])) $error = 'Password lama tidak sesuai.';
    elseif ($err = password_policy_error($new)) $error = $err;
    elseif ($new !== $new2) $error = 'Konfirmasi password baru tidak cocok.';
    elseif ($new === $old) $error = 'Password baru harus berbeda dari password lama.';
    else {
        $h = password_hash($new, PASSWORD_DEFAULT);
        $s = $conn->prepare('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?'); $s->bind_param('si', $h, $uid); $s->execute();
        $_SESSION['must_change'] = 0;
        session_regenerate_id(true);
        audit('password.ganti', 'users', $uid);
        flash('success', 'Password berhasil diganti.');
        redirect(home_for_role());
    }
}
$pageTitle = 'Ganti Password';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
    <form class="form-card auth-card" method="POST">
        <span class="eyebrow">KEAMANAN AKUN</span><h1>Ganti password</h1>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <div class="form-group"><label for="lama">Password lama</label><input type="password" id="lama" name="lama" autocomplete="current-password" required></div>
        <div class="form-group"><label for="baru">Password baru</label><input type="password" id="baru" name="baru" autocomplete="new-password" required><small>Minimal 8 karakter, kombinasi huruf dan angka.</small></div>
        <div class="form-group"><label for="baru2">Ulangi password baru</label><input type="password" id="baru2" name="baru2" autocomplete="new-password" required></div>
        <button class="btn btn-primary btn-full" type="submit">Simpan Password</button>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
