<?php
/**
 * CRUD genérico parametrizado por tabla y lista de columnas.
 * Análogo a un repositorio mínimo; evita repetir prepared statements
 * por cada entidad. Las columnas se validan contra una lista blanca
 * para impedir inyección por nombre de columna.
 */

require_once __DIR__ . '/../config/database.php';

class Crud
{
    private PDO $db;
    private string $table;
    /** @var string[] columnas editables (excluye id y created_at) */
    private array $columns;

    public function __construct(string $table, array $columns)
    {
        $this->db = db();
        $this->table = $table;
        $this->columns = $columns;
    }

    /**
     * Devuelve filas con paginación opcional. Si $limit es null trae todo.
     * $limit y $offset se castean a int, así que no abren inyección.
     */
    public function all(string $orderBy = 'id DESC', ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) max(0, $offset ?? 0);
        }
        return $this->db->query($sql)->fetchAll();
    }

    /** Total de filas de la tabla, base para calcular páginas. */
    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) c FROM {$this->table}")->fetch()['c'];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $cols = $this->columns;
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colList = implode(',', $cols);
        $sql = "INSERT INTO {$this->table} ({$colList}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->orderedValues($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Inserta una fila por estudiante compartiendo los datos de sesión.
     * Todas las filas del grupo llevan el mismo sesion_id, igual al id de
     * la primera fila insertada, lo que garantiza unicidad sin secuencias.
     * Una tutoría individual es el caso N = 1. Devuelve el sesion_id.
     * Requiere que 'estudiante_id' y 'sesion_id' estén en $this->columns.
     */
    public function createGroup(array $shared, array $studentIds): int
    {
        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if (!$studentIds) {
            throw new InvalidArgumentException('Sin estudiantes seleccionados.');
        }

        $this->db->beginTransaction();
        try {
            $sesionId = null;
            foreach ($studentIds as $sid) {
                $row = $shared;
                $row['estudiante_id'] = $sid;
                $row['sesion_id'] = $sesionId; // null en la primera fila
                $newId = $this->create($row);
                if ($sesionId === null) {
                    $sesionId = $newId;
                    $this->db->prepare("UPDATE {$this->table} SET sesion_id = ? WHERE id = ?")
                             ->execute([$sesionId, $newId]);
                }
            }
            $this->db->commit();
            return $sesionId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        $assignments = implode(',', array_map(fn($c) => "{$c} = ?", $this->columns));
        $sql = "UPDATE {$this->table} SET {$assignments} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $values = $this->orderedValues($data);
        $values[] = $id;
        $stmt->execute($values);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** Ordena los valores según el orden declarado de columnas; NULL si falta. */
    private function orderedValues(array $data): array
    {
        return array_map(fn($c) => $data[$c] ?? null, $this->columns);
    }
}
