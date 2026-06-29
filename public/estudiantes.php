<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Crud.php';

$crud = new Crud('estudiantes', ['nombre', 'carrera', 'semestre']);
$carreras = ['Sistemas', 'Civil', 'Industrial', 'Contabilidad', 'Idiomas', 'Electrónica'];

$action = get('action', 'list');
$id = (int) get('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sem = (int) post('semestre', 1);
    $sem = max(1, min(10, $sem)); // clamp al rango del CHECK de la BD
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
$rows = $crud->all();

layout_header('Estudiantes');
?>
<form method="post" class="card">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
    <label>Nombre
        <input type="text" name="nombre" required
               value="<?= e($editing['nombre'] ?? '') ?>">
    </label>
    <label>Carrera
        <select name="carrera" required>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= e($c) ?>"
                    <?= ($editing['carrera'] ?? '') === $c ? 'selected' : '' ?>>
                    <?= e($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Semestre
        <input type="number" name="semestre" min="1" max="10" required
               value="<?= e($editing['semestre'] ?? '1') ?>">
    </label>
    <button type="submit"><?= $editing ? 'Actualizar' : 'Guardar' ?></button>
    <?php if ($editing): ?><a href="/estudiantes.php">Cancelar</a><?php endif; ?>
</form>

<table>
    <thead><tr><th>ID</th><th>Nombre</th><th>Carrera</th><th>Semestre</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['nombre']) ?></td>
            <td><?= e($r['carrera']) ?></td>
            <td><?= e($r['semestre']) ?></td>
            <td>
                <a href="/estudiantes.php?action=edit&id=<?= e($r['id']) ?>">Editar</a>
                <a href="/estudiantes.php?action=delete&id=<?= e($r['id']) ?>"
                   onclick="return confirm('¿Eliminar este estudiante?')">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
layout_footer();
