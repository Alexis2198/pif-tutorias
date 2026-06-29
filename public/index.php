<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

$stats = null;
$error = null;
try {
    $stats = [
        'docentes'    => db()->query('SELECT COUNT(*) c FROM docentes')->fetch()['c'],
        'estudiantes' => db()->query('SELECT COUNT(*) c FROM estudiantes')->fetch()['c'],
        'tutorias'    => db()->query('SELECT COUNT(*) c FROM tutorias')->fetch()['c'],
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
    <div class="card">
        <p>Conexión a Aiven activa.</p>
        <ul>
            <li>Docentes registrados: <?= e($stats['docentes']) ?></li>
            <li>Estudiantes registrados: <?= e($stats['estudiantes']) ?></li>
            <li>Tutorías registradas: <?= e($stats['tutorias']) ?></li>
        </ul>
    </div>
<?php endif; ?>
<?php
layout_footer();
