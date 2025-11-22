<?php
// app/Repositories/ServicioPorHorarioRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class ServicioPorHorarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- CREATE (Asignar horario base a un servicio) ---
    public function create(array $data): int
    {
        $sql = "INSERT INTO ServicioPorHorario (servicio_id, horarioBase_id) 
                VALUES (:servicio_id, :horarioBase_id)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':servicio_id' => $data['servicio_id'],
                ':horarioBase_id' => $data['horarioBase_id'] // Cambio de nombre de campo
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Esta asignación de horario ya existe para este servicio o ID inválido.", 409);
            }
            throw $e;
        }
    }

    // --- UPDATE (Edición) ---
    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = [':id' => $id];

        if (isset($data['estado'])) {
            $setClauses[] = "estado = :estado";
            $params[':estado'] = $data['estado'];
        }

        // Cambio de nombre de campo de tipo_deporte_id a horarioBase_id
        if (isset($data['horarioBase_id'])) {
            $setClauses[] = "horarioBase_id = :horarioBase_id";
            $params[':horarioBase_id'] = $data['horarioBase_id'];
        }

        if (empty($setClauses)) {
            return false;
        }

        // Cambio de nombre de tabla: ServicioPorDeporte -> ServicioPorHorario
        $sql = "UPDATE ServicioPorHorario 
                SET " . implode(", ", $setClauses) . " 
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Error al actualizar la asignación: ID de horario base inválido o asignación duplicada.", 409);
            }
            throw new Exception("Error en BD durante la actualización: " . $e->getMessage(), 500);
        }
    }

    public function getById(int $id): ?array
    {
        // Cambio de nombre de tabla: ServicioPorDeporte -> ServicioPorHorario
        $sql = "SELECT * FROM ServicioPorHorario WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    // --- READ (Listado Paginado filtrado por servicio_id) ---
    // Renombrado para ser más específico con el nuevo dominio: getHorariosPaginatedByServicio
    public function getHorariosPaginatedByServicio(?int $servicioId, int $limit, int $offset): array
    {
        if ($servicioId === null) {
            return ['total' => 0, 'data' => []];
        }

        $selectAndFrom = "
            SELECT 
                SPH.id, SPH.servicio_id, SPH.horarioBase_id
            FROM ServicioPorHorario SPH
            WHERE SPH.servicio_id = :servicio_id
        ";

        // Cambio de nombre de tabla: ServicioPorDeporte -> ServicioPorHorario
        $totalFrom = "SELECT COUNT(id) AS total FROM ServicioPorHorario WHERE servicio_id = :servicio_id";

        // 1. Obtener Total (Lógica de conteo sin cambios)
        $totalStmt = $this->db->prepare($totalFrom);
        $totalStmt->bindParam(':servicio_id', $servicioId, PDO::PARAM_INT);
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. Obtener Datos (Lógica de datos sin cambios)
        $dataSql = $selectAndFrom . " ORDER BY SPH.id ASC LIMIT :limit OFFSET :offset";
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
        // Cambio de nombre de tabla: ServicioPorDeporte -> ServicioPorHorario
        $sql = "UPDATE ServicioPorHorario 
                SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // --- DELETE (Desasignar horario del servicio) ---
    public function delete(int $id): bool
    {
        // Cambio de nombre de tabla: ServicioPorDeporte -> ServicioPorHorario
        $sql = "DELETE FROM ServicioPorHorario WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
