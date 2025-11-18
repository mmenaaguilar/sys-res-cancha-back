<?php
// app/Repositories/ServicioRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class ServicioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- Helper ---
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Servicios WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    // --- READ (MODIFICADO) ---
    /**
     * Lista servicios por complejo y opcionalmente por tipo de deporte, incluyendo el nombre del deporte.
     */
    public function listByFilters(int $complejoId, ?int $tipoDeporteId): array
    {
        $sql = "
            SELECT 
                S.servicio_id, S.complejo_id, S.nombre, S.descripcion, S.monto, S.is_obligatorio, S.estado,
                SPD.tipo_deporte_id,
                TD.nombre AS nombre_deporte  -- <<-- CAMBIO CLAVE AÑADIDO
            FROM Servicios S
            INNER JOIN ServicioPorDeporte SPD ON S.servicio_id = SPD.servicio_id
            INNER JOIN TipoDeporte TD ON SPD.tipo_deporte_id = TD.tipo_deporte_id -- <<-- JOIN A LA TABLA TIPO DEPORTE
            WHERE S.complejo_id = :complejo_id
        ";

        $binds = [':complejo_id' => $complejoId];

        if ($tipoDeporteId !== null) {
            $sql .= " AND SPD.tipo_deporte_id = :tipo_deporte_id";
            $binds[':tipo_deporte_id'] = $tipoDeporteId;
        }

        $sql .= " ORDER BY S.nombre ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($binds as $key => &$value) {
            // Pasando la variable por referencia (aunque PDO::PARAM_INT podría ser más adecuado para IDs)
            $stmt->bindParam($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CREATE (TRANSACCIONAL) ---
    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            // 1. Insertar en Servicios
            $sqlServicio = "INSERT INTO Servicios (complejo_id, nombre, descripcion, monto, is_obligatorio, estado) 
                            VALUES (:complejo_id, :nombre, :descripcion, :monto, :is_obligatorio, :estado)";
            $stmtS = $this->db->prepare($sqlServicio);
            $stmtS->execute([
                ':complejo_id' => $data['complejo_id'],
                ':nombre' => $data['nombre'],
                ':descripcion' => $data['descripcion'] ?? null,
                ':monto' => $data['monto'],
                ':is_obligatorio' => $data['is_obligatorio'] ? 1 : 0,
                ':estado' => $data['estado'],
            ]);
            $servicioId = (int) $this->db->lastInsertId();

            // 2. Insertar en ServicioPorDeporte
            $sqlSPD = "INSERT INTO ServicioPorDeporte (servicio_id, tipo_deporte_id) VALUES (:servicio_id, :tipo_deporte_id)";
            $stmtD = $this->db->prepare($sqlSPD);
            $stmtD->execute([
                ':servicio_id' => $servicioId,
                ':tipo_deporte_id' => $data['tipo_deporte_id'],
            ]);

            $this->db->commit();
            return $servicioId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error transaccional al crear el servicio: " . $e->getMessage());
        }
    }

    // --- UPDATE (TRANSACCIONAL) ---
    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Actualizar Servicios
            $sqlServicio = "UPDATE Servicios 
                            SET complejo_id = :complejo_id, nombre = :nombre, descripcion = :descripcion, 
                                monto = :monto, is_obligatorio = :is_obligatorio, estado = :estado
                            WHERE servicio_id = :id";
            $stmtS = $this->db->prepare($sqlServicio);
            $stmtS->execute([
                ':complejo_id' => $data['complejo_id'],
                ':nombre' => $data['nombre'],
                ':descripcion' => $data['descripcion'] ?? null,
                ':monto' => $data['monto'],
                ':is_obligatorio' => $data['is_obligatorio'] ? 1 : 0,
                ':estado' => $data['estado'],
                ':id' => $id,
            ]);

            // 2. Actualizar ServicioPorDeporte 
            $sqlSPD = "UPDATE ServicioPorDeporte SET tipo_deporte_id = :tipo_deporte_id WHERE servicio_id = :servicio_id";
            $stmtD = $this->db->prepare($sqlSPD);
            $stmtD->execute([
                ':tipo_deporte_id' => $data['tipo_deporte_id'],
                ':servicio_id' => $id,
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error transaccional al actualizar el servicio: " . $e->getMessage());
        }
    }

    // --- DELETE (FÍSICO) ---
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM Servicios WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // --- CHANGE STATUS ---
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE Servicios SET estado = :estado WHERE servicio_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
