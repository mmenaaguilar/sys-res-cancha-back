<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class HorarioEspecialRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Obtener por ID */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM HorarioEspecial WHERE horario_especial_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Crear nuevo registro */
    public function create(array $data): int
    {
        $sql = "INSERT INTO HorarioEspecial
                (cancha_id, fecha, hora_inicio, hora_fin, monto, estado_horario, estado, descripcion)
                VALUES (:cancha_id, :fecha, :hora_inicio, :hora_fin, :monto, :estado_horario, :estado, :descripcion)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $data['cancha_id'],
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':monto' => $data['monto'] ?? null,
            ':estado_horario' => $data['estado_horario'] ?? null,
            ':estado' => $data['estado'] ?? 'disponible',
            ':descripcion' => $data['descripcion'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /** Actualizar */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE HorarioEspecial
                SET cancha_id = :cancha_id,
                    fecha = :fecha,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    monto = :monto,
                    estado_horario = :estado_horario,
                    estado = :estado,
                    descripcion = :descripcion
                WHERE horario_especial_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $data['cancha_id'],
            ':fecha' => $data['fecha'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':monto' => $data['monto'] ?? null,
            ':estado_horario' => $data['estado_horario'] ?? null,
            ':estado' => $data['estado'] ?? 'disponible',
            ':descripcion' => $data['descripcion'] ?? null,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    /** Eliminar */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM HorarioEspecial WHERE horario_especial_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** Listado paginado con filtros */
    public function getPaginated(int $limit, int $offset, int $canchaId, ?string $fecha = null, ?string $search = null): array
    {
        $where = "WHERE cancha_id = :cancha_id";
        $params = [':cancha_id' => $canchaId];

        if (!empty($fecha)) {
            $where .= " AND fecha = :fecha";
            $params[':fecha'] = $fecha;
        }

        if (!empty($search)) {
            $where .= " AND (descripcion LIKE :search OR estado LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // Total
        $totalSql = "SELECT COUNT(*) AS total FROM HorarioEspecial $where";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute($params);
        $total = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Datos
        $sql = "SELECT * FROM HorarioEspecial $where 
            ORDER BY fecha DESC, hora_inicio ASC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total' => $total,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    /** Método usado por Strategy: busca HorarioEspecial disponible */
    public function getDisponibleByCanchaYFecha(int $canchaId, string $fecha, string $horaInicio, string $horaFin): ?array
    {
        $sql = "SELECT * FROM HorarioEspecial
                WHERE cancha_id = :cancha_id
                  AND fecha = :fecha
                  AND hora_inicio <= :hora_inicio
                  AND hora_fin >= :hora_fin
                  AND estado_horario = 'disponible'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $canchaId,
            ':fecha' => $fecha,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE HorarioEspecial SET estado = :estado WHERE horario_especial_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
