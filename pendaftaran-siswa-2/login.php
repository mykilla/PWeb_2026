<?php
require_once __DIR__ . '/config/bootstrap.php';
if (is_logged_in()) redirect(home_for_role());
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $bUser = rate_bucket('login-user', client_ip() . '|' . $email);
    $bIp = rate_bucket('login-ip', client_ip());
    if (rate_exceeded($bUser, 5, 900) || rate_exceeded($bIp, 30, 900)) {
        $error = 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.';
        audit('login.diblokir', 'users', null, $email);
    } else {
        $u = $email !== '' ? user_find_by_email($email) : null;
        // verifikasi tetap dijalankan saat user tidak ada agar waktu respons seragam
        $hash = $u['password_hash'] ?? '$2y$12$FrNvPwJYh35kL/hMrAh9SOfmkr5ma0E2sXhOQc2oEaBmMulYbiErO';
        $valid = password_verify($password, $hash) && $u && (int)$u['is_active'] === 1;
        if ($valid) {
            rate_clear($bUser);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                $nh = password_hash($password, PASSWORD_DEFAULT);
                $s = $conn->prepare('UPDATE users SET password_hash=? WHERE id=?'); $s->bind_param('si', $nh, $u['id']); $s->execute();
            }
            login_user($u);
            audit('login.sukses', 'users', $u['id']);
            if ($u['must_change_password']) { flash('warning', 'Demi keamanan, ganti password bawaan Anda sekarang.'); redirect('ganti-password.php'); }
            redirect(home_for_role());
        }
        rate_hit($bUser); rate_hit($bIp);
        audit('login.gagal', 'users', null, $email);
        $error = 'Email atau password tidak sesuai.';
    }
}
$pageTitle = 'Masuk';
require_once __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
    <form class="form-card auth-card" method="POST">
        <span class="eyebrow">AKUN</span><h1>Masuk</h1><p>Calon siswa maupun admin masuk melalui halaman ini.</p>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" autocomplete="username" required></div>
        <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary btn-full" type="submit">Masuk</button>
        <p class="muted">Belum punya akun? <a class="text-link" href="<?= e(url('register.php')) ?>">Buat akun</a></p>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
