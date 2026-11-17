<?php
/**
 * Pemuat konfigurasi sederhana: membaca file .env (jika ada), lalu environment server.
 */
function env(string $key, $default = null)
{
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $file = __DIR__ . '/../.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $v = trim($v);
                if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
                    $q = $v[0];
                    $end = strpos($v, $q, 1);
                    $v = $end === false ? substr($v, 1) : substr($v, 1, $end - 1);
                } else {
                    $v = trim(preg_replace('/\s+#.*$/', '', $v));
                }
                $vars[trim($k)] = $v;
            }
        }
    }
    if (array_key_exists($key, $vars)) return $vars[$key];
    $sys = getenv($key);
    return $sys !== false ? $sys : $default;
}

function is_production(): bool
{
    return env('APP_ENV', 'local') === 'production';
}
