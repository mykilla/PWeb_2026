<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_pendaftar();
require_post();
$s = siswa_by_user((int)$_SESSION['user_id']);
if (!$s) { flash('error', 'Isi biodata terlebih dahulu.'); redirect('pendaftar/form.php'); }
[$url, $err] = payment_create($s);
if ($err) { flash('error', $err); redirect('pendaftar/index.php'); }
redirect($url);
