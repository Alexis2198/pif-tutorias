<?php
/**
 * Utilidades compartidas por todas las vistas.
 */

const PER_PAGE = 10;

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

/** Lee un parámetro POST con valor por defecto. */
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/** Lee un parámetro GET con valor por defecto. */
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/** Número de página saneado a partir del query string. */
function current_page(): int
{
    return max(1, (int) get('page', 1));
}

/** Etiqueta desambiguada para estudiantes homónimos. */
function student_label(array $s): string
{
    return $s['nombre'] . ' (' . $s['carrera'] . ', semestre ' . $s['semestre'] . ')';
}

/**
 * Barra de paginación. Reserva el número de página bajo la clave 'page'
 * y conserva el resto del query string actual para no perder filtros.
 */
const PAGER_WINDOW = 5;

function render_pager(int $total, int $page, string $path): void
{
    $pages = (int) max(1, ceil($total / PER_PAGE));
    if ($pages <= 1) {
        return;
    }

    // Ventana deslizante centrada en la página actual, de ancho fijo.
    $half = intdiv(PAGER_WINDOW, 2);
    $start = max(1, $page - $half);
    $end = min($pages, $start + PAGER_WINDOW - 1);
    $start = max(1, $end - PAGER_WINDOW + 1); // recentra al llegar al final

    $base = $_GET;
    $link = function (int $p, string $label, bool $current = false, bool $disabled = false) use ($base, $path): string {
        if ($disabled) {
            return '<span class="pager-item pager-disabled">' . $label . '</span>';
        }
        if ($current) {
            return '<span class="pager-item pager-current">' . $label . '</span>';
        }
        $base['page'] = $p;
        return '<a class="pager-item" href="' . e($path . '?' . http_build_query($base)) . '">' . $label . '</a>';
    };

    echo '<nav class="pager">';
    echo $link(1, '«', false, $page <= 1);
    echo $link($page - 1, '‹', false, $page <= 1);
    if ($start > 1) {
        echo '<span class="pager-item pager-gap">…</span>';
    }
    for ($p = $start; $p <= $end; $p++) {
        echo $link($p, (string) $p, $p === $page);
    }
    if ($end < $pages) {
        echo '<span class="pager-item pager-gap">…</span>';
    }
    echo $link($page + 1, '›', false, $page >= $pages);
    echo $link($pages, '»', false, $page >= $pages);
    echo '</nav>';
}

function layout_header(string $title): void
{
    $active = basename($_SERVER['PHP_SELF']);
    $link = function (string $file, string $label) use ($active): string {
        $cls = ($active === $file) ? ' class="active"' : '';
        return "<a href=\"/{$file}\"{$cls}>" . e($label) . "</a>";
    };
    ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<nav class="topbar">
    <span class="brand">Tutorías</span>
    <div class="links">
        <?= $link('index.php', 'Inicio') ?>
        <?= $link('docentes.php', 'Docentes') ?>
        <?= $link('estudiantes.php', 'Estudiantes') ?>
        <?= $link('tutorias.php', 'Tutorías') ?>
    </div>
</nav>
<main>
    <h1><?= e($title) ?></h1>
<?php
}

function layout_footer(): void
{
    ?>
</main>
</body>
</html>
<?php
}
