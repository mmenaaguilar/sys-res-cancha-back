<?php

namespace App\Patterns\Reserva;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Clase Reserva
 * Representa un "Leaf" (Hoja) en el patrón Composite. En este contexto,
 * encapsula la lógica para verificar si un slot ya está ocupado en la BD.
 */
class ReservaLeaf implements ComponenteReserva
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function calcularSubtotal(): float
    {
        // Una reserva individual no tiene un subtotal directo en este punto del flujo,
        // pero debe implementar el método. Podría devolver 0.0 o lanzar una excepción.
        return 0.0;
    }

    /**
     * Valida si existe una colisión de reserva en la base de datos.
     *
     * @param int $canchaId ID de la cancha a verificar.
     * @param string $fechaDeseada Fecha de la potencial reserva (formato Y-m-d).
     * @param string $horaInicio Hora de inicio del intervalo (H:i:s).
     * @param string $horaFin Hora de fin del intervalo (H:i:s).
     * @return bool True si NO está disponible (hay colisión), False si SÍ está disponible.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {
        // Usar parámetros nombrados para prevenir inyección SQL
        $params = [
            ':cancha_id' => $canchaId,
            ':fecha' => $fechaDeseada,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ];

        // Consulta SQL para verificar solapamiento de reservas confirmadas
        $sql = "
            SELECT 
                COUNT(id) 
            FROM Reservas R
            WHERE 
                R.cancha_id = :cancha_id AND 
                R.fecha_reserva = :fecha AND 
                R.estado = 'confirmada' AND 
                R.hora_fin > :hora_inicio AND 
                R.hora_inicio < :hora_fin;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $count = (int)$stmt->fetchColumn();

            // Si count > 0, hay al menos una reserva que colisiona (NO DISPONIBLE)
            return $count === 0; // Devolver True si está disponible (Count es 0)
        } catch (Exception $e) {
            error_log("Error al verificar colisión de reserva en BD: " . $e->getMessage());
            // En caso de fallo de BD, asumimos NO disponibilidad por seguridad.
            return false;
        }
    }
}
