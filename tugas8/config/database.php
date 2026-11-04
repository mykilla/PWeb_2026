<?php
// Sesuaikan pengaturan ini dengan konfigurasi MySQL Laragon kamu.
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_pendaftaran';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa MySQL Laragon dan pengaturan di config/database.php.');
}
