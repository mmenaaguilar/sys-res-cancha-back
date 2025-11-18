<?php
// app/Repositories/ServicioPorDeporteRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class ServicioPorDeporteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- CREATE (Asignar deporte a un servicio) ---
    public function create(array $data): int
    {
        $sql = "INSERT INTO ServicioPorDeporte (servicio_id, tipo_deporte_id) 
                VALUES (:servicio_id, :tipo_deporte_id)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':servicio_id' => $data['servicio_id'],
                ':tipo_deporte_id' => $data['tipo_deporte_id']
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // Error 23000 es típicamente violación de restricción de unicidad/clave foránea
            if ($e->getCode() === '23000') {
                throw new Exception("Esta asignación de deporte ya existe para este servicio o ID inválido.", 409);
            }
            throw $e;
        }
    }

    // --- UPDATE (Edición) ---
    public function update(int $id, array $data): bool
    {
        // El Repositorio asume que el Servicio ha validado que al menos un campo existe.

        $setClauses = [];
        $params = [':id' => $id];

        if (isset($data['estado'])) {
            $setClauses[] = "estado = :estado";
            $params[':estado'] = $data['estado'];
        }

        if (isset($data['tipo_deporte_id'])) {
            $setClauses[] = "tipo_deporte_id = :tipo_deporte_id";
            $params[':tipo_deporte_id'] = $data['tipo_deporte_id'];
        }

        if (empty($setClauses)) {
            return false; // No hay campos para actualizar
        }

        $sql = "UPDATE ServicioPorDeporte 
                SET " . implode(", ", $setClauses) . " 
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // Capturar posibles violaciones de claves foráneas o únicas si el tipo_deporte_id no existe
                throw new Exception("Error al actualizar la asignación: ID de deporte inválido o asignación duplicada.", 409);
            }
            throw new Exception("Error en BD durante la actualización: " . $e->getMessage(), 500);
        }
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM ServicioPorDeporte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Capturamos el resultado, que puede ser un array o false
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Usamos el operador ternario para asegurar que devolvemos array o null
        // Si $result es TRUE (array), devuelve $result. Si es FALSE (no encontrado), devuelve NULL.
        return $result ? $result : null;
    }

    // --- READ (Listado Paginado filtrado por servicio_id) ---
    public function getDeportesPaginatedByServicio(?int $servicioId, int $limit, int $offset): array
    {
        if ($servicioId === null) {
            return ['total' => 0, 'data' => []];
        }

        $selectAndFrom = "
            SELECT 
                SPD.id, SPD.servicio_id, SPD.tipo_deporte_id,
                TD.nombre AS nombre_deporte 
            FROM ServicioPorDeporte SPD
            INNER JOIN TipoDeporte TD ON SPD.tipo_deporte_id = TD.tipo_deporte_id
            WHERE SPD.servicio_id = :servicio_id
        ";

        $totalFrom = "SELECT COUNT(id) AS total FROM ServicioPorDeporte WHERE servicio_id = :servicio_id";

        // 1. Obtener Total
        $totalStmt = $this->db->prepare($totalFrom);
        $totalStmt->bindParam(':servicio_id', $servicioId, PDO::PARAM_INT);
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. Obtener Datos
        $dataSql = $selectAndFrom . " ORDER BY TD.nombre ASC LIMIT :limit OFFSET :offset";
        $dataStmt = $this->db->prepare($dataSql);

        $dataStmt->bindParam(':servicio_id', $servicioId, PDO::PARAM_INT);
        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['total' => (int)$total, 'data' => $data];
    }

    public function changeStatus(int $id): bool
    {
        $sql = "UPDATE ServicioPorDeporte 
                SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // --- DELETE (Desasignar deporte del servicio) ---
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ServicioPorDeporte WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
