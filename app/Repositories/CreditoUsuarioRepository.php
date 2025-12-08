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
    public function getCreditosByUsuario(int $usuarioId): array
    {
        $sql = "SELECT credito_id, monto, fecha_otorgado, fecha_expiracion
                FROM CreditoUsuario
                WHERE 
                    usuario_id = :usuario_id 
                    AND estado = 'activo'
                    AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
                ORDER BY fecha_otorgado DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE CreditoUsuario SET estado = :estado WHERE credito_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
