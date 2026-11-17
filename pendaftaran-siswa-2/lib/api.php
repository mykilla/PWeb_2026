<?php
/** Helper respons JSON untuk endpoint /api. */
function json_out(int $code, array $data): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function api_method(string $m): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $m) { header('Allow: ' . $m); json_out(405, ['error' => 'Metode tidak diizinkan']); }
}
/** Baca body JSON (maks. 64 KB) atau fallback ke form-encoded. */
function api_body(): array
{
    $raw = file_get_contents('php://input', false, null, 0, 65536);
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $d = json_decode($raw ?: '', true);
        return is_array($d) ? $d : [];
    }
    return $_POST;
}
