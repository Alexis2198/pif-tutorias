<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

$stats = null;
$error = null;
try {
    $stats = [
        'docentes'    => db()->query('SELECT COUNT(*) c FROM docentes')->fetch()['c'],
        'estudiantes' => db()->query('SELECT COUNT(*) c FROM estudiantes')->fetch()['c'],
        'atenciones'  => db()->query('SELECT COUNT(*) c FROM tutorias')->fetch()['c'],
        'sesiones'    => db()->query('SELECT COUNT(DISTINCT sesion_id) c FROM tutorias')->fetch()['c'],
    ];
} catch (Throwable $ex) {
    $error = $ex->getMessage();
}

layout_header('Registro de Tutorías');
?>
<?php if ($error): ?>
    <div class="card error">
        <strong>Sin conexión a la base de datos.</strong>
        <p><?= e($error) ?></p>
    </div>
<?php else: ?>
    <p class="status-ok">Conexión a Aiven activa.</p>
    <section class="metrics">
        <article class="metric">
            <span class="metric-label">Docentes</span>
            <span class="metric-value"><?= e($stats['docentes']) ?></span>
        </article>
        <article class="metric">
            <span class="metric-label">Estudiantes</span>
            <span class="metric-value"><?= e($stats['estudiantes']) ?></span>
        </article>
        <article class="metric">
            <span class="metric-label">Atenciones</span>
            <span class="metric-value"><?= e($stats['atenciones']) ?></span>
        </article>
        <article class="metric">
            <span class="metric-label">Sesiones</span>
            <span class="metric-value"><?= e($stats['sesiones']) ?></span>
        </article>
    </section>
<?php endif; ?>
<?php
layout_footer();
