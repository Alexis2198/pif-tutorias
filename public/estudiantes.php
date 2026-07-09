<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Crud.php';

$crud = new Crud('estudiantes', ['nombre', 'carrera', 'semestre']);
$carreras = ['Sistemas', 'Civil', 'Industrial', 'Contabilidad', 'Idiomas', 'Electrónica'];

$action = get('action', 'list');
$id = (int) get('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sem = max(1, min(10, (int) post('semestre', 1))); // clamp al CHECK de la BD
    $data = [
        'nombre'   => trim(post('nombre', '')),
        'carrera'  => post('carrera'),
        'semestre' => $sem,
    ];
    if (post('id')) {
        $crud->update((int) post('id'), $data);
    } else {
        $crud->create($data);
    }
    redirect('/estudiantes.php');
}

if ($action === 'delete' && $id) {
    $crud->delete($id);
    redirect('/estudiantes.php');
}

$editing = ($action === 'edit' && $id) ? $crud->find($id) : null;

$page = current_page();
$total = $crud->count();
$offset = ($page - 1) * PER_PAGE;
$rows = $crud->all('id DESC', PER_PAGE, $offset);

layout_header('Estudiantes');
?>
<details class="panel"<?= $editing ? ' open' : '' ?>>
    <summary><?= $editing ? 'Editar estudiante' : 'Agregar estudiante' ?></summary>
    <form method="post" class="card">
        <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
        <label>Nombre
            <input type="text" name="nombre" required value="<?= e($editing['nombre'] ?? '') ?>">
        </label>
        <div class="grid-2">
            <label>Carrera
                <select name="carrera" required>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($editing['carrera'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Semestre
                <input type="number" name="semestre" min="1" max="10" required value="<?= e($editing['semestre'] ?? '1') ?>">
            </label>
        </div>
        <div class="form-actions">
            <button type="submit"><?= $editing ? 'Actualizar' : 'Guardar' ?></button>
            <?php if ($editing): ?><a href="/estudiantes.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</details>

<!-- Selección múltiple: marca estudiantes y crea una tutoría grupal.
     Los seleccionados viajan por GET a tutorias.php como estudiante_ids[]. -->
<form method="get" action="/tutorias.php">
    <table>
        <thead><tr>
            <th></th><th>ID</th><th>Nombre</th><th>Carrera</th><th>Semestre</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><input type="checkbox" name="estudiante_ids[]" value="<?= e($r['id']) ?>"></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e($r['nombre']) ?></td>
                <td><?= e($r['carrera']) ?></td>
                <td><?= e($r['semestre']) ?></td>
                <td>
                    <a href="/estudiantes.php?action=edit&id=<?= e($r['id']) ?>">Editar</a>
                    <a class="del" href="/estudiantes.php?action=delete&id=<?= e($r['id']) ?>"
                       onclick="return confirm('¿Eliminar este estudiante?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="form-actions" style="margin-top:1rem;">
        <button type="submit">Crear tutoría grupal con los seleccionados</button>
        <span class="hint">La selección aplica a los estudiantes visibles en esta página.</span>
    </div>
</form>
<?php render_pager($total, $page, '/estudiantes.php'); ?>
<?php
layout_footer();
