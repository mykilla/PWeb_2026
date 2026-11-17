<?php
/** Render navigasi halaman. $query = parameter GET lain yang dipertahankan. */
function render_pagination(int $page, int $pages, array $query = []): string
{
    if ($pages <= 1) return '';
    $out = '<nav class="pagination" aria-label="Halaman">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i !== 1 && $i !== $pages && abs($i - $page) > 2) { if (abs($i - $page) === 3) $out .= '<span>…</span>'; continue; }
        $qs = http_build_query(array_merge($query, ['page' => $i]));
        $out .= '<a class="' . ($i === $page ? 'current' : '') . '" href="?' . e($qs) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}
