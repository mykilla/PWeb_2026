<?php
require_once __DIR__ . '/env.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        env('DB_HOST', 'localhost'),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        env('DB_NAME', 'db_pendaftaran'),
        (int)env('DB_PORT', 3306)
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database (.env) dan layanan MySQL.');
}
