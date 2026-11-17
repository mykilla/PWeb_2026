<?php
/** Webhook payment gateway (Midtrans HTTP notification). Didaftarkan di dashboard Midtrans. */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/api.php';
api_method('POST');
$n = api_body();
if (!$n) json_out(400, ['error' => 'Body tidak valid']);
[$code, $msg] = payment_process_notification($n);
json_out($code, ['status' => $code === 200 ? 'ok' : 'error', 'message' => $msg]);
