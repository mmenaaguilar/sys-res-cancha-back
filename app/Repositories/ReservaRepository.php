<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReservaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Reserva WHERE reserva_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function cancelarReserva(int $id): bool
    {
        $sql = "UPDATE Reserva SET estado = 'cancelado' WHERE reserva_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
