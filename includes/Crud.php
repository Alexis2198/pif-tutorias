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

    public function all(string $orderBy = 'id DESC'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        return $this->db->query($sql)->fetchAll();
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
