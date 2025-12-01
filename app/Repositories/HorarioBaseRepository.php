<?php

namespace App\Repositories;

use App\Core\Database;
use App\Patterns\Prototype\horarioPrototype\HorarioBasePrototype;

use PDO;
use Exception;

class HorarioBaseRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM HorarioBase WHERE horario_base_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Listado paginado con filtros
     */
    public function getPaginated(int $limit, int $offset, int $canchaId, ?string $diaSemana = null): array
    {
        $where = "WHERE cancha_id = :cancha_id";
        $params = [':cancha_id' => $canchaId];

        if (!empty($diaSemana)) {
            $where .= " AND dia_semana = :dia_semana";
            $params[':dia_semana'] = $diaSemana;
        }

        // Total
        $totalSql = "SELECT COUNT(horario_base_id) AS total FROM HorarioBase $where";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute($params);
        $total = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Data
        $dataSql = "
            SELECT * 
            FROM HorarioBase 
            $where
            ORDER BY hora_inicio ASC
            LIMIT :limit OFFSET :offset
        ";

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'total' => $total,
            'data' => $dataStmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function getHorariosByCanchaAndDia($canchaId, $diaSemana, $horaFiltro)
    {
        $params = [
            'cancha' => $canchaId,
            'dia' => $diaSemana
        ];

        $sql = "SELECT * FROM HorarioBase
            WHERE cancha_id = :cancha
              AND dia_semana = :dia";

        if ($horaFiltro !== '') {
            $sql .= " AND hora_inicio <= :hora
                  AND hora_fin > :hora";
            $params['hora'] = $horaFiltro;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByCanchaYDia(int $canchaId, string $diaSemana, string $horaInicio, string $horaFin): ?array
    {
        // 1. Obtener el horario base
        $sql = "SELECT * FROM HorarioBase
            WHERE cancha_id = :cancha_id
              AND dia_semana = :dia_semana
              AND hora_inicio <= :hora_inicio
              AND hora_fin >= :hora_fin
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $canchaId,
            ':dia_semana' => $diaSemana,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ]);

        $horario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$horario) {
            return null;
        }

        // 2. Consultar si existen servicios obligatorios asociados a este horario
        $sqlServicio = "SELECT S.monto 
                    FROM ServicioPorHorario SPH
                    JOIN Servicios S ON SPH.servicio_id = S.servicio_id
                    WHERE SPH.horarioBase_id = :horario_id
                      AND SPH.is_obligatorio = 1
                      AND SPH.estado = 'activo'
                      AND S.estado = 'activo'";

        $stmtServicio = $this->db->prepare($sqlServicio);
        $stmtServicio->execute([
            ':horario_id' => $horario['horario_base_id']
        ]);

        $servicios = $stmtServicio->fetchAll(PDO::FETCH_ASSOC);

        // 3. Sumar los montos de los servicios obligatorios
        $montoAdicional = 0;
        foreach ($servicios as $servicio) {
            $montoAdicional += (float) $servicio['monto'];
        }

        $horario['monto_total'] = (float) $horario['monto'] + $montoAdicional;

        return $horario;
    }


    /**
     * Crear nuevo registro
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO HorarioBase
                (cancha_id, dia_semana, hora_inicio, hora_fin, monto)
                VALUES (:cancha_id, :dia_semana, :hora_inicio, :hora_fin, :monto)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $data['cancha_id'],
            ':dia_semana' => $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':monto' => $data['monto'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualizar
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE HorarioBase 
                SET cancha_id = :cancha_id,
                    dia_semana = :dia_semana,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    monto = :monto
                WHERE horario_base_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $data['cancha_id'],
            ':dia_semana' => $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':monto' => $data['monto'],
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Eliminar
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM HorarioBase WHERE horario_base_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE HorarioBase SET estado = :estado WHERE horario_base_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    public function getHorariosByCanchaYDia(int $canchaId, string $diaSemana): array
    {
        $sql = "SELECT * FROM HorarioBase
            WHERE cancha_id = :cancha_id
              AND dia_semana = :dia_semana
              AND estado = 'activo'
            ORDER BY hora_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $canchaId,
            ':dia_semana' => $diaSemana
        ]);

        if (!empty($horaFiltro)) {
            $sql .= " AND hora_inicio <= :hora AND hora_fin > :hora";
            $params[':hora'] = $horaFiltro;
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        $sql = "INSERT INTO HorarioBase (cancha_id, dia_semana, hora_inicio, hora_fin, monto, estado)
            VALUES (:cancha_id, :dia_semana, :hora_inicio, :hora_fin, :monto, :estado)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cancha_id' => $data['cancha_id'],
            ':dia_semana' => $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':monto' => $data['monto'],
            ':estado' => $data['estado'] ?? 'activo'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function cloneByDia(int $canchaId, string $fromDia, string $toDia): array
    {
        $originales = $this->getHorariosByCanchaYDia($canchaId, $fromDia);
        if (empty($originales)) {
            throw new Exception("No existen horarios en el día {$fromDia} para esta cancha.");
        }

        $clonadosIds = [];
        foreach ($originales as $horario) {
            $clonado = $horario->clone(['dia_semana' => $toDia]);
            $clonadosIds[] = $this->insert($clonado);
        }

        return $clonadosIds;
    }
}
