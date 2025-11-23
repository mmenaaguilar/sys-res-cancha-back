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

    public function createReserva(array $data): int
    {
        $sql = "INSERT INTO Reserva (
                    usuario_id, metodo_pago_id, total_pago, estado,
                    izipay_token, izipay_estado
                ) VALUES (
                    :usuario_id, :metodo_pago_id, :total_pago, 'pendiente_pago',
                    :token, 'pendiente'
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id'     => $data['usuario_id'],
            ':metodo_pago_id' => $data['metodo_pago_id'],
            ':total_pago'     => $data['total_pago'],
            ':token'          => $data['izipay_token'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function addDetalle(int $reservaId, array $d)
    {
        $sql = "INSERT INTO ReservaDetalle (
                    reserva_id, cancha_id, fecha, hora_inicio, hora_fin, precio
                ) VALUES (
                    :reserva_id, :cancha_id, :fecha, :inicio, :fin, :precio
                )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':reserva_id' => $reservaId,
            ':cancha_id'  => $d['cancha_id'],
            ':fecha'      => $d['fecha'],
            ':inicio'     => $d['hora_inicio'],
            ':fin'        => $d['hora_fin'],
            ':precio'     => $d['precio']
        ]);
    }

    public function confirmarPago(int $reservaId)
    {
        $sql = "UPDATE Reserva 
                SET izipay_estado = 'pagado',
                    estado = 'confirmada',
                    fecha_pago = NOW()
                WHERE reserva_id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $reservaId]);
    }

    public function cancelarReserva(int $reservaId)
    {
        $sql = "UPDATE Reserva SET estado = 'cancelado' WHERE reserva_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $reservaId]);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Reserva WHERE reserva_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDetalles(int $reservaId): array
    {
        $sql = "SELECT * FROM ReservaDetalle WHERE reserva_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $reservaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
