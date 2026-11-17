<?php
require_once __DIR__ . '/config/bootstrap.php';
if (is_logged_in()) redirect(home_for_role());
$errors = []; $in = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $in = $_POST;
    $bucket = rate_bucket('register', client_ip());
    if (rate_exceeded($bucket, 10, 3600)) {
        $errors['_'] = 'Terlalu banyak percobaan pendaftaran dari alamat ini. Coba lagi nanti.';
    } else {
        rate_hit($bucket);
        [$uid, $errors] = user_register((string)($_POST['nama'] ?? ''), (string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''), (string)($_POST['password2'] ?? ''));
        if ($uid) {
            $u = user_find_by_email(strtolower(trim($_POST['email'])));
            login_user($u);
            audit('user.register', 'users', $uid);
            flash('success', 'Akun berhasil dibuat. Lengkapi biodata pendaftaran Anda.');
            redirect('pendaftar/form.php');
        }
    }
}
$pageTitle = 'Buat Akun';
require_once __DIR__ . '/includes/header.php';
$err = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<section class="auth-wrap">
    <form class="form-card auth-card" method="POST" novalidate>
        <span class="eyebrow">CALON SISWA</span><h1>Buat akun</h1><p>Akun dipakai untuk mengisi biodata, membayar, dan memantau status.</p>
        <?php if (!empty($errors['_'])): ?><div class="alert error"><?= e($errors['_']) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <div class="form-group"><label for="nama">Nama lengkap</label><input id="nama" name="nama" maxlength="100" value="<?= old($in, 'nama') ?>" required><?= $err('nama') ?></div>
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="120" autocomplete="email" value="<?= old($in, 'email') ?>" required><?= $err('email') ?></div>
        <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="new-password" required><small>Minimal 8 karakter, kombinasi huruf dan angka.</small><?= $err('password') ?></div>
        <div class="form-group"><label for="password2">Ulangi password</label><input type="password" id="password2" name="password2" autocomplete="new-password" required><?= $err('password2') ?></div>
        <button class="btn btn-primary btn-full" type="submit">Buat Akun</button>
        <p class="muted">Sudah punya akun? <a class="text-link" href="<?= e(url('login.php')) ?>">Masuk</a></p>
    </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
