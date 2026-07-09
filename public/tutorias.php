<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Crud.php';

// sesion_id agrupa las filas de una misma tutoría grupal (una fila por estudiante).
$crud = new Crud('tutorias', [
    'docente_id', 'estudiante_id', 'materia_id', 'fecha',
    'hora_inicio', 'hora_fin', 'modalidad', 'motivo', 'estado', 'satisfaccion', 'sesion_id',
]);

$modalidades = ['Presencial', 'Virtual'];
$motivos = ['Refuerzo', 'Preparación examen', 'Proyecto', 'Consulta puntual', 'Recuperación'];
$estados = ['Realizada', 'Cancelada', 'Ausente'];
$carreras = ['Sistemas', 'Civil', 'Industrial', 'Contabilidad', 'Idiomas', 'Electrónica'];

// Catálogos de dimensiones para los selects compartidos.
$docentes = db()->query('SELECT id, nombre FROM docentes ORDER BY nombre')->fetchAll();
$materias = db()->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();

$action = get('action', 'list');
$id = (int) get('id', 0);

/** Normaliza satisfacción: entera 1..5 solo si la tutoría se realizó, si no NULL. */
function satisfaccion_valida(string $estado, $raw): ?int
{
    if ($estado !== 'Realizada' || $raw === '' || $raw === null) {
        return null;
    }
    return max(1, min(5, (int) $raw));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = post('estado', 'Realizada');
    $shared = [
        'docente_id'   => (int) post('docente_id'),
        'materia_id'   => (int) post('materia_id'),
        'fecha'        => post('fecha'),
        'hora_inicio'  => post('hora_inicio'),
        'hora_fin'     => post('hora_fin'),
        'modalidad'    => post('modalidad'),
        'motivo'       => post('motivo'),
        'estado'       => $estado,
        'satisfaccion' => satisfaccion_valida($estado, post('satisfaccion')),
    ];

    if (post('id')) {
        // Edición de una atención individual; el estudiante y el sesion_id no cambian.
        $shared['estudiante_id'] = (int) post('estudiante_id');
        $shared['sesion_id']     = (int) post('sesion_id');
        $crud->update((int) post('id'), $shared);
        redirect('/tutorias.php');
    }

    // Alta grupal: una fila por estudiante seleccionado, N >= 1.
    $ids = array_map('intval', (array) post('estudiante_ids', []));
    $ids = array_values(array_filter(array_unique($ids)));
    if (!$ids) {
        redirect('/tutorias.php?err=sin_estudiantes');
    }
    $crud->createGroup($shared, $ids);
    redirect('/tutorias.php');
}

if ($action === 'delete' && $id) {
    $crud->delete($id);
    redirect('/tutorias.php');
}

$editing = ($action === 'edit' && $id) ? $crud->find($id) : null;
$editingStudent = $editing
    ? db()->prepare('SELECT nombre, carrera, semestre FROM estudiantes WHERE id = ?')
    : null;
if ($editing) {
    $editingStudent->execute([(int) $editing['estudiante_id']]);
    $editingStudent = $editingStudent->fetch() ?: ['nombre' => '(desconocido)', 'carrera' => '', 'semestre' => ''];
}

// ---- Paso previo de selección de estudiantes para el alta grupal ----
// Preseleccionados que llegan desde estudiantes.php o de un filtro anterior.
$preIds = array_values(array_filter(array_map('intval', (array) get('estudiante_ids', []))));
$preStudents = [];
if ($preIds) {
    $ph = implode(',', array_fill(0, count($preIds), '?'));
    $stmt = db()->prepare("SELECT id, nombre, carrera, semestre FROM estudiantes WHERE id IN ($ph) ORDER BY nombre");
    $stmt->execute($preIds);
    $preStudents = $stmt->fetchAll();
}

// Filtro carrera/semestre; solo trae candidatos si se aplicó algún criterio.
$fCarrera = trim((string) get('f_carrera', ''));
$fSemestre = (int) get('f_semestre', 0);
$candidates = [];
$filterApplied = ($fCarrera !== '' || $fSemestre > 0);
if ($filterApplied) {
    $where = [];
    $args = [];
    if ($fCarrera !== '') { $where[] = 'carrera = ?'; $args[] = $fCarrera; }
    if ($fSemestre > 0)   { $where[] = 'semestre = ?'; $args[] = $fSemestre; }
    if ($preIds) {
        $where[] = 'id NOT IN (' . implode(',', array_fill(0, count($preIds), '?')) . ')';
        $args = array_merge($args, $preIds);
    }
    $sql = 'SELECT id, nombre, carrera, semestre FROM estudiantes';
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY nombre';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $candidates = $stmt->fetchAll();
}

$panelOpen = $editing || $preIds || $filterApplied || get('err');

// ---- Listado con joins y paginación ----
$page = current_page();
$total = (int) db()->query('SELECT COUNT(*) c FROM tutorias')->fetch()['c'];
$offset = ($page - 1) * PER_PAGE;
$rows = db()->query("
    SELECT t.id, t.sesion_id, t.fecha, t.hora_inicio, t.hora_fin,
           d.nombre AS docente, e.nombre AS estudiante, m.nombre AS materia,
           t.modalidad, t.motivo, t.estado, t.satisfaccion
    FROM tutorias t
    JOIN docentes d    ON d.id = t.docente_id
    JOIN estudiantes e ON e.id = t.estudiante_id
    JOIN materias m    ON m.id = t.materia_id
    ORDER BY t.fecha DESC, t.sesion_id DESC, t.id
    LIMIT " . PER_PAGE . " OFFSET " . (int) $offset . "
")->fetchAll();

/** Select de dimensión con selección activa. */
function dim_select(string $name, array $options, $current): void
{
    echo "<select name=\"{$name}\" required>";
    foreach ($options as $o) {
        $sel = ((string) $o['id'] === (string) $current) ? 'selected' : '';
        echo "<option value=\"" . e($o['id']) . "\" {$sel}>" . e($o['nombre']) . "</option>";
    }
    echo "</select>";
}

/** Campos de sesión compartidos, reutilizados por alta grupal y edición. */
function session_fields(array $docentes, array $materias, array $lists, ?array $ed): void
{
    [$modalidades, $motivos, $estados] = $lists;
    ?>
    <div class="grid-2">
        <label>Docente <?php dim_select('docente_id', $docentes, $ed['docente_id'] ?? ''); ?></label>
        <label>Materia <?php dim_select('materia_id', $materias, $ed['materia_id'] ?? ''); ?></label>
    </div>
    <div class="grid-2">
        <label>Fecha <input type="date" name="fecha" required value="<?= e($ed['fecha'] ?? '') ?>"></label>
        <label>Hora inicio <input type="time" name="hora_inicio" required value="<?= e($ed['hora_inicio'] ?? '') ?>"></label>
        <label>Hora fin <input type="time" name="hora_fin" required value="<?= e($ed['hora_fin'] ?? '') ?>"></label>
    </div>
    <div class="grid-2">
        <label>Modalidad
            <select name="modalidad" required>
                <?php foreach ($modalidades as $m): ?>
                    <option value="<?= e($m) ?>" <?= ($ed['modalidad'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Motivo
            <select name="motivo" required>
                <?php foreach ($motivos as $m): ?>
                    <option value="<?= e($m) ?>" <?= ($ed['motivo'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Estado
            <select name="estado" required>
                <?php foreach ($estados as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($ed['estado'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Satisfacción (1 a 5, solo si realizada)
            <input type="number" name="satisfaccion" min="1" max="5" value="<?= e($ed['satisfaccion'] ?? '') ?>">
        </label>
    </div>
    <?php
}

layout_header('Tutorías');
?>
<?php if (get('err') === 'sin_estudiantes'): ?>
    <div class="flash warn">Selecciona al menos un estudiante antes de guardar la tutoría.</div>
<?php endif; ?>

<details class="panel"<?= $panelOpen ? ' open' : '' ?>>
    <summary><?= $editing ? 'Editar tutoría' : 'Agregar tutoría' ?></summary>

    <?php if ($editing): ?>
        <!-- Edición de una atención individual dentro de una sesión. -->
        <form method="post" class="card">
            <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
            <input type="hidden" name="estudiante_id" value="<?= e($editing['estudiante_id']) ?>">
            <input type="hidden" name="sesion_id" value="<?= e($editing['sesion_id'] ?? '') ?>">
            <p class="hint">Estudiante: <strong><?= e(student_label($editingStudent)) ?></strong>. La membresía de la sesión no se modifica aquí.</p>
            <?php session_fields($docentes, $materias, [$modalidades, $motivos, $estados], $editing); ?>
            <div class="form-actions">
                <button type="submit">Actualizar</button>
                <a href="/tutorias.php">Cancelar</a>
            </div>
        </form>
    <?php else: ?>
        <!-- Paso 1: filtro que discrimina estudiantes. Form propio en GET. -->
        <div class="card">
            <h2>1. Selecciona estudiantes</h2>
            <form method="get" action="/tutorias.php" class="filterbar">
                <?php foreach ($preIds as $pid): ?>
                    <input type="hidden" name="estudiante_ids[]" value="<?= e($pid) ?>">
                <?php endforeach; ?>
                <label>Carrera
                    <select name="f_carrera">
                        <option value="">Todas</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= e($c) ?>" <?= $fCarrera === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Semestre
                    <select name="f_semestre">
                        <option value="0">Todos</option>
                        <?php for ($s = 1; $s <= 10; $s++): ?>
                            <option value="<?= $s ?>" <?= $fSemestre === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <button type="submit" class="btn ghost">Filtrar</button>
            </form>

            <!-- Paso 2: la selección viaja en el form de guardado. -->
            <form method="post" class="card" style="box-shadow:none;border:none;padding:0;">
                <?php if ($preStudents): ?>
                    <fieldset>
                        <legend>Preseleccionados</legend>
                        <div class="checklist">
                            <?php foreach ($preStudents as $s): ?>
                                <label><input type="checkbox" name="estudiante_ids[]" value="<?= e($s['id']) ?>" checked> <?= e(student_label($s)) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <fieldset>
                    <legend>Candidatos según filtro</legend>
                    <?php if (!$filterApplied): ?>
                        <div class="checklist empty">Aplica un filtro de carrera o semestre para listar estudiantes.</div>
                    <?php elseif (!$candidates): ?>
                        <div class="checklist empty">Ningún estudiante coincide con el filtro.</div>
                    <?php else: ?>
                        <div class="checklist">
                            <?php foreach ($candidates as $s): ?>
                                <label><input type="checkbox" name="estudiante_ids[]" value="<?= e($s['id']) ?>"> <?= e(student_label($s)) ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </fieldset>

                <h2 style="margin-top:.5rem;">2. Datos de la sesión</h2>
                <?php session_fields($docentes, $materias, [$modalidades, $motivos, $estados], null); ?>
                <p class="hint">Todos los estudiantes marcados comparten estos datos y quedan bajo un mismo identificador de sesión. La satisfacción individual se ajusta luego editando cada fila.</p>
                <div class="form-actions">
                    <button type="submit">Guardar tutoría grupal</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</details>

<div class="table-wrap">
<table>
    <thead><tr>
        <th>Sesión</th><th>Fecha</th><th>Docente</th><th>Estudiante</th><th>Materia</th>
        <th>Modalidad</th><th>Estado</th><th>Satisf.</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['sesion_id'] ?? '—') ?></td>
            <td><?= e($r['fecha']) ?></td>
            <td><?= e($r['docente']) ?></td>
            <td><?= e($r['estudiante']) ?></td>
            <td><?= e($r['materia']) ?></td>
            <td><?= e($r['modalidad']) ?></td>
            <td><?= e($r['estado']) ?></td>
            <td><?= e($r['satisfaccion'] ?? '—') ?></td>
            <td>
                <a href="/tutorias.php?action=edit&id=<?= e($r['id']) ?>">Editar</a>
                <a class="del" href="/tutorias.php?action=delete&id=<?= e($r['id']) ?>"
                   onclick="return confirm('¿Eliminar esta tutoría?')">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php render_pager($total, $page, '/tutorias.php'); ?>
<?php
layout_footer();
