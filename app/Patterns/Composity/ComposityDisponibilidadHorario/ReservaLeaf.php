<?php

namespace App\Patterns\Composity\ComposityDisponibilidadHorario;

use App\Core\Database;
use PDO;
use Exception;

class ReservaLeaf implements ComponenteReserva
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
     * Valida si existe una colisión de reserva en la base de datos.
     * @return bool True si está disponible, False si hay colisión.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
        SELECT COUNT(*) 
        FROM ReservaDetalle R
        INNER JOIN Reserva A ON A.reserva_id = R.reserva_id
        WHERE R.cancha_id = :cancha_id
          AND DATE(R.fecha) = :fecha
          AND A.estado = 'confirmada'
          AND TIME(R.hora_fin) > :hora_inicio
          AND TIME(R.hora_inicio) < :hora_fin
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
            return $count === 0; 
        } catch (Exception $e) {
            error_log("Error en ReservaLeaf: " . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Patterns\Composity\ComposityDisponibilidadHorario;

use App\Core\Database;
use PDO;
use Exception;

class ReservaLeaf implements ComponenteReserva
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
     * Valida si existe una colisión de reserva en la base de datos.
     * @return bool True si está disponible, False si hay colisión.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {
        $sql = "
        SELECT COUNT(*) 
        FROM ReservaDetalle R
        INNER JOIN Reserva A ON A.reserva_id = R.reserva_id
        WHERE R.cancha_id = :cancha_id
          AND DATE(R.fecha) = :fecha
          AND A.estado = 'confirmada'
          AND TIME(R.hora_fin) > :hora_inicio
          AND TIME(R.hora_inicio) < :hora_fin
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
            error_log("Error en ReservaLeaf: " . $e->getMessage());
            return false;
        }
    }
}
