<?php

namespace App\Patterns\Reserva;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Clase HorarioEspecial
 * Hoja (Leaf) que valida la disponibilidad revisando si el slot está bloqueado
 * por un Horario Especial en la base de datos.
 */
class HorarioEspecialLeaf implements ComponenteReserva
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function calcularSubtotal(): float
    {
        // Esta hoja solo valida, no tiene un monto acumulativo directo.
        return 0.0;
    }

    /**
     * Valida si existe un bloqueo por 'HorarioEspecial' para el slot dado.
     * @return bool True si está disponible (NO hay bloqueo), False si SÍ hay bloqueo.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {
        $params = [
            ':cancha_id' => $canchaId,
            ':fecha' => $fechaDeseada,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ];

        // Se revisa si existe un solapamiento en la tabla 'HorarioEspecial'
        // donde el estado sea 'bloqueado' o 'mantenimiento'.
        $sql = "
            SELECT 
                COUNT(horario_especial_id) 
            FROM HorarioEspecial HE
            WHERE 
                HE.cancha_id = :cancha_id AND 
                HE.fecha = :fecha AND 
                HE.estado IN ('bloqueado', 'mantenimiento') AND
                HE.hora_fin > :hora_inicio AND 
                HE.hora_inicio < :hora_fin;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $count = (int)$stmt->fetchColumn();

            // Devuelve TRUE si NO hay bloqueos (count es 0)
            return $count === 0;
        } catch (Exception $e) {
            error_log("Error de BD al verificar Horario Especial: " . $e->getMessage());
            return false;
        }
    }
}
