<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Crud.php';

$crud = new Crud('docentes', ['nombre', 'departamento', 'categoria']);
$departamentos = ['Ingeniería', 'Ciencias', 'Administración', 'Idiomas'];
$categorias = ['Titular', 'Agregado', 'Auxiliar', 'Ocasional'];

$action = get('action', 'list');
$id = (int) get('id', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre'       => trim(post('nombre', '')),
        'departamento' => post('departamento'),
        'categoria'    => post('categoria'),
    ];
    if (post('id')) {
        $crud->update((int) post('id'), $data);
    } else {
        $crud->create($data);
    }
    redirect('/docentes.php');
}

if ($action === 'delete' && $id) {
    $crud->delete($id);
    redirect('/docentes.php');
}

$editing = ($action === 'edit' && $id) ? $crud->find($id) : null;
$rows = $crud->all();

layout_header('Docentes');
?>
<form method="post" class="card">
    <input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
    <label>Nombre
        <input type="text" name="nombre" required
               value="<?= e($editing['nombre'] ?? '') ?>">
    </label>
    <label>Departamento
        <select name="departamento" required>
            <?php foreach ($departamentos as $d): ?>
                <option value="<?= e($d) ?>"
                    <?= ($editing['departamento'] ?? '') === $d ? 'selected' : '' ?>>
                    <?= e($d) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Categoría
        <select name="categoria" required>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= e($c) ?>"
                    <?= ($editing['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                    <?= e($c) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit"><?= $editing ? 'Actualizar' : 'Guardar' ?></button>
    <?php if ($editing): ?><a href="/docentes.php">Cancelar</a><?php endif; ?>
</form>

<table>
    <thead><tr><th>ID</th><th>Nombre</th><th>Departamento</th><th>Categoría</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['nombre']) ?></td>
            <td><?= e($r['departamento']) ?></td>
            <td><?= e($r['categoria']) ?></td>
            <td>
                <a href="/docentes.php?action=edit&id=<?= e($r['id']) ?>">Editar</a>
                <a href="/docentes.php?action=delete&id=<?= e($r['id']) ?>"
                   onclick="return confirm('¿Eliminar este docente?')">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
layout_footer();
