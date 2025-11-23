<?php

namespace App\Patterns\Composity\ComposityDisponibilidadHorario;

use App\Core\Database;
use PDO;
use Exception;

class HorarioEspecialLeaf implements ComponenteReserva
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function calcularSubtotal(): float
    {
        return 0.0;
    }

    /**
     * Valida si existe un bloqueo por 'HorarioEspecial' para el slot dado.
     * @return bool True si está disponible, False si hay bloqueo.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
        SELECT COUNT(*) 
        FROM HorarioEspecial HE
        WHERE HE.cancha_id = :cancha_id
          AND DATE(HE.fecha) = :fecha
          AND HE.estado_horario IN ('bloqueado','mantenimiento')
          AND TIME(HE.hora_fin) > :hora_inicio
          AND TIME(HE.hora_inicio) < :hora_fin
    ";

        $params = [
            ':cancha_id' => $canchaId,
            ':fecha' => $fechaDeseada,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $count = (int)$stmt->fetchColumn();
            return $count === 0; // True si está libre
        } catch (Exception $e) {
            error_log("Error en HorarioEspecialLeaf: " . $e->getMessage());
            return false;
        }
    }
}
