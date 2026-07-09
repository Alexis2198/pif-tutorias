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
function render_pager(int $total, int $page, string $path): void
{
    $pages = (int) max(1, ceil($total / PER_PAGE));
    if ($pages <= 1) {
        return;
    }
    $params = $_GET;
    echo '<nav class="pager">';
    for ($p = 1; $p <= $pages; $p++) {
        if ($p === $page) {
            echo '<span class="pager-item pager-current">' . $p . '</span>';
            continue;
        }
        $params['page'] = $p;
        $href = $path . '?' . http_build_query($params);
        echo '<a class="pager-item" href="' . e($href) . '">' . $p . '</a>';
    }
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
