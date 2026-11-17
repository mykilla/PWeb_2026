<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/api.php';
api_method('POST');
if (!is_pendaftar()) json_out(401, ['error' => 'Autentikasi diperlukan']);
if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) json_out(403, ['error' => 'Token CSRF tidak valid']);
$s = siswa_by_user((int)$_SESSION['user_id']);
if (!$s) json_out(409, ['error' => 'Biodata belum diisi']);
[$url, $err] = payment_create($s);
if ($err) json_out(422, ['error' => $err]);
json_out(201, ['redirect_url' => $url, 'driver' => payment_driver()]);
