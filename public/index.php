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
    <div class="card stats">
        <p>Conexión a Aiven activa.</p>
        <ul>
            <li>Docentes registrados: <?= e($stats['docentes']) ?></li>
            <li>Estudiantes registrados: <?= e($stats['estudiantes']) ?></li>
            <li>Atenciones registradas (filas): <?= e($stats['atenciones']) ?></li>
            <li>Sesiones de tutoría (sesion_id distintos): <?= e($stats['sesiones']) ?></li>
        </ul>
        <p class="hint">Una sesión grupal agrupa varias atenciones bajo un mismo sesion_id. En Power BI, tutorías = DISTINCTCOUNT(sesion_id) y atenciones = conteo de filas.</p>
    </div>
<?php endif; ?>
<?php
layout_footer();
