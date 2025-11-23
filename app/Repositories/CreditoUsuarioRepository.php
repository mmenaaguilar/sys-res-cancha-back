<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CreditoUsuarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function crearCredito(array $data): int
    {
        $sql = "INSERT INTO CreditoUsuario (usuario_id, monto, origen_reserva_id)
                VALUES (:usuario_id, :monto, :origen_reserva_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $data['usuario_id'],
            ':monto' => $data['monto'],
            ':origen_reserva_id' => $data['origen_reserva_id']
        ]);

        return (int) $this->db->lastInsertId();
    }
}
