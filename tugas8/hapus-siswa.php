<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list-siswa.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('ID siswa tidak valid.');
}

$stmt = $conn->prepare('DELETE FROM calon_siswa WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: list-siswa.php?status=deleted');
exit;
