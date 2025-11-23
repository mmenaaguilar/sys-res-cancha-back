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

    public function getReservasPaginated(?int $usuarioId, ?int $complejoId, ?string $searchTerm, int $limit, int $offset): array
    {
        $baseSql = "FROM Reserva r
                    JOIN Usuarios u ON r.usuario_id = u.usuario_id
                    LEFT JOIN ReservaDetalle rd ON rd.reserva_id = r.reserva_id
                    LEFT JOIN Cancha c ON rd.cancha_id = c.cancha_id";

        $whereClauses = [];
        $params = [];

        if ($usuarioId !== null) {
            $whereClauses[] = "r.usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        if ($complejoId !== null) {
            $whereClauses[] = "c.complejo_id = :complejo_id";
            $params[':complejo_id'] = $complejoId;
        }

        if (!empty($searchTerm)) {
            $whereClauses[] = "(u.nombre LIKE :search OR u.correo LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $where = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

        // Total
        $totalSql = "SELECT COUNT(DISTINCT r.reserva_id) AS total " . $baseSql . $where;
        $stmt = $this->db->prepare($totalSql);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Datos
        $dataSql = "SELECT DISTINCT r.reserva_id, r.usuario_id, u.nombre AS usuario_nombre, r.metodo_pago_id,
                           r.total_pago, r.estado, r.fecha_creacion
                    " . $baseSql . $where . " 
                    ORDER BY r.fecha_creacion DESC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
    }

    public function getReservaDetallePaginated(int $reservaId, int $limit, int $offset): array
    {
        $totalSql = "SELECT COUNT(*) AS total FROM ReservaDetalle WHERE reserva_id = :reserva_id";
        $stmt = $this->db->prepare($totalSql);
        $stmt->bindParam(':reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $dataSql = "SELECT detalle_id, reserva_id, cancha_id, fecha, hora_inicio, hora_fin, precio
                    FROM ReservaDetalle
                    WHERE reserva_id = :reserva_id
                    ORDER BY fecha ASC, hora_inicio ASC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($dataSql);
        $stmt->bindParam(':reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
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
