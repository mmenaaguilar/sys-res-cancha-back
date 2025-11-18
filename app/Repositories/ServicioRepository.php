<?php
// app/Repositories/ServicioRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class ServicioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- CREATE ---
    public function create(array $data): int
    {
        $sql = "INSERT INTO Servicios (complejo_id, nombre, descripcion, monto, is_obligatorio, estado) 
                VALUES (:complejo_id, :nombre, :descripcion, :monto, :is_obligatorio, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':complejo_id' => $data['complejo_id'],
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':monto' => $data['monto'],
            ':is_obligatorio' => $data['is_obligatorio'] ?? 0,
            ':estado' => $data['estado'] ?? 'activo'
        ]);
        return (int)$this->db->lastInsertId();
    }

    // --- READ (Listado Paginado con Filtros: complejoId y searchTerm) ---
    public function getServiciosPaginatedByFilters(?int $complejoId, ?string $searchTerm, int $limit, int $offset): array
    {
        $selectAndFrom = "SELECT servicio_id, complejo_id, nombre, descripcion, monto, is_obligatorio, estado 
                          FROM Servicios 
                          WHERE estado = 'activo'";
        $totalFrom = "SELECT COUNT(servicio_id) AS total FROM Servicios WHERE estado = 'activo'";

        $whereClauses = [];
        $params = [];

        // Filtro MANDATORIO por complejo_id
        if ($complejoId !== null) {
            $whereClauses[] = "complejo_id = :complejo_id";
            $params[':complejo_id'] = $complejoId;
        } else {
            return ['total' => 0, 'data' => []];
        }

        // Filtro opcional por término de búsqueda (nombre o descripción)
        if (!empty($searchTerm)) {
            $whereClauses[] = "(nombre LIKE :search_term OR descripcion LIKE :search_term)";
            $params[':search_term'] = '%' . $searchTerm . '%';
        }

        $whereSql = !empty($whereClauses) ? " AND " . implode(" AND ", $whereClauses) : "";

        $dataSql = $selectAndFrom . $whereSql . " ORDER BY nombre ASC LIMIT :limit OFFSET :offset";
        $totalSql = $totalFrom . $whereSql;

        // 1. Obtener Total
        $totalStmt = $this->db->prepare($totalSql);
        foreach ($params as $key => $value) {
            $totalStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. Obtener Datos
        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['total' => (int)$total, 'data' => $data];
    }

    // --- READ (por ID) ---
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Servicios WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- UPDATE ---
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Servicios 
                SET nombre = :nombre, descripcion = :descripcion, monto = :monto, is_obligatorio = :is_obligatorio 
                WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':monto' => $data['monto'],
            ':is_obligatorio' => $data['is_obligatorio'] ?? 0,
            ':id' => $id,
        ]);
    }

    // --- CHANGE STATUS (Activación/Inactivación) ---
    public function changeStatus(int $id): bool
    {
        $sql = "UPDATE Servicios 
                SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END 
                WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // --- DELETE (Eliminación física, usar con precaución) ---
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM Servicios WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
