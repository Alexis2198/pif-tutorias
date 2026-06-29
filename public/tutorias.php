<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Crud.php';

$crud = new Crud('tutorias', [
    'docente_id', 'estudiante_id', 'materia_id', 'fecha',
    'hora_inicio', 'hora_fin', 'modalidad', 'motivo', 'estado', 'satisfaccion',
]);

$modalidades = ['Presencial', 'Virtual'];
$motivos = ['Refuerzo', 'Preparación examen', 'Proyecto', 'Consulta puntual', 'Recuperación'];
$estados = ['Realizada', 'Cancelada', 'Ausente'];

// Catálogos para los selects de dimensiones.
$docentes = db()->query('SELECT id, nombre FROM docentes ORDER BY nombre')->fetchAll();
$estudiantes = db()->query('SELECT id, nombre FROM estudiantes ORDER BY nombre')->fetchAll();
$materias = db()->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();

$action = get('action', 'list');
$id = (int) get('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = post('estado', 'Realizada');
    // Satisfacción solo aplica a tutorías realizadas; el resto va NULL
    // para no sesgar el promedio en Power BI.
    $sat = ($estado === 'Realizada' && post('satisfaccion') !== '')
        ? (int) post('satisfaccion')
        : null;

    $data = [
        'docente_id'    => (int) post('docente_id'),
        'estudiante_id' => (int) post('estudiante_id'),
        'materia_id'    => (int) post('materia_id'),
        'fecha'         => post('fecha'),
        'hora_inicio'   => post('hora_inicio'),
        'hora_fin'      => post('hora_fin'),
        'modalidad'     => post('modalidad'),
        'motivo'        => post('motivo'),
        'estado'        => $estado,
        'satisfaccion'  => $sat,
    ];

    if (post('id')) {
        $crud->update((int) post('id'), $data);
    } else {
        $crud->create($data);
    }
    redirect('/tutorias.php');
}

if ($action === 'delete' && $id) {
    $crud->delete($id);
    redirect('/tutorias.php');
}

$editing = ($action === 'edit' && $id) ? $crud->find($id) : null;

// Listado con joins para mostrar nombres en lugar de IDs.
$rows = db()->query("
    SELECT t.id, t.fecha, t.hora_inicio, t.hora_fin,
           d.nombre AS docente, e.nombre AS estudiante, m.nombre AS materia,
           t.modalidad, t.motivo, t.estado, t.satisfaccion
    FROM tutorias t
    JOIN docentes d   ON d.id = t.docente_id
    JOIN estudiantes e ON e.id = t.estudiante_id
    JOIN materias m   ON m.id = t.materia_id
    ORDER BY t.fecha DESC, t.id DESC
")->fetchAll();

/** Render de un select de dimensión con selección activa. */
function dim_select(string $name, array $options, ?array $editing): void
{
    echo "<select name=\"{$name}\" required>";
    $current = $editing[$name] ?? '';
    foreach ($options as $o) {
        $sel = ((string) $o['id'] === (string) $current) ? 'selected' : '';
        echo "<option value=\"" . e($o['id']) . "\" {$sel}>" . e($o['nombre']) . "</option>";
    }
    echo "</select>";
}

layout_header('Tutorías');
?>
<form method="post" class="card">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
    <label>Docente <?php dim_select('docente_id', $docentes, $editing); ?></label>
    <label>Estudiante <?php dim_select('estudiante_id', $estudiantes, $editing); ?></label>
    <label>Materia <?php dim_select('materia_id', $materias, $editing); ?></label>
    <label>Fecha
        <input type="date" name="fecha" required value="<?= e($editing['fecha'] ?? '') ?>">
    </label>
    <label>Hora inicio
        <input type="time" name="hora_inicio" required value="<?= e($editing['hora_inicio'] ?? '') ?>">
    </label>
    <label>Hora fin
        <input type="time" name="hora_fin" required value="<?= e($editing['hora_fin'] ?? '') ?>">
    </label>
    <label>Modalidad
        <select name="modalidad" required>
            <?php foreach ($modalidades as $m): ?>
                <option value="<?= e($m) ?>" <?= ($editing['modalidad'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Motivo
        <select name="motivo" required>
            <?php foreach ($motivos as $m): ?>
                <option value="<?= e($m) ?>" <?= ($editing['motivo'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Estado
        <select name="estado" required>
            <?php foreach ($estados as $s): ?>
                <option value="<?= e($s) ?>" <?= ($editing['estado'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Satisfacción (1-5, solo si realizada)
        <input type="number" name="satisfaccion" min="1" max="5"
               value="<?= e($editing['satisfaccion'] ?? '') ?>">
    </label>
    <button type="submit"><?= $editing ? 'Actualizar' : 'Guardar' ?></button>
    <?php if ($editing): ?><a href="/tutorias.php">Cancelar</a><?php endif; ?>
</form>

<table>
    <thead><tr>
        <th>ID</th><th>Fecha</th><th>Docente</th><th>Estudiante</th><th>Materia</th>
        <th>Modalidad</th><th>Estado</th><th>Satisf.</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['fecha']) ?></td>
            <td><?= e($r['docente']) ?></td>
            <td><?= e($r['estudiante']) ?></td>
            <td><?= e($r['materia']) ?></td>
            <td><?= e($r['modalidad']) ?></td>
            <td><?= e($r['estado']) ?></td>
            <td><?= e($r['satisfaccion'] ?? '—') ?></td>
            <td>
                <a href="/tutorias.php?action=edit&id=<?= e($r['id']) ?>">Editar</a>
                <a href="/tutorias.php?action=delete&id=<?= e($r['id']) ?>"
                   onclick="return confirm('¿Eliminar esta tutoría?')">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
layout_footer();
