<?php
/**
 * Utilidades compartidas por todas las vistas.
 */

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

function layout_header(string $title): void
{
    ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<nav>
    <a href="/index.php">Inicio</a>
    <a href="/docentes.php">Docentes</a>
    <a href="/estudiantes.php">Estudiantes</a>
    <a href="/tutorias.php">Tutorías</a>
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
