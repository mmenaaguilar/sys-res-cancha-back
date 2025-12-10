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
                    LEFT JOIN Cancha c ON rd.cancha_id = c.cancha_id
                    LEFT JOIN ComplejoDeportivo cd ON c.complejo_id = cd.complejo_id
                    LEFT JOIN TipoDeporte tp ON c.tipo_deporte_id = tp.tipo_deporte_id";

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
            $whereClauses[] = "(u.nombre LIKE :search OR u.correo LIKE :search OR cd.nombre LIKE :search OR c.nombre LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $where = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

        // Para el total, contamos reservas únicas
        $totalSql = "SELECT COUNT(DISTINCT r.reserva_id) AS total " . $baseSql . $where;
        $stmt = $this->db->prepare($totalSql);
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // SELECT MAESTRO: Corregido para cumplir con ONLY_FULL_GROUP_BY
        // Agrupamos por los campos principales (Reserva, Usuario, Complejo)
        // y usamos funciones de agregación para los detalles (rd.*, c.nombre, tp.nombre).

        $dataSql = "SELECT 
                        r.reserva_id, 
                        r.estado,
                        r.total_pago,
                        r.metodo_pago_id,
                        r.fecha_creacion,
                        
                        u.nombre AS usuario_nombre,
                        u.correo,
                        u.telefono,

                        -- Funciones de agregación para el detalle de la reserva (primer bloque)
                        MIN(rd.fecha) AS fecha,
                        MIN(rd.hora_inicio) AS hora_inicio,
                        MAX(rd.hora_fin) AS hora_fin,
                        SUM(rd.precio) AS subtotal_detalles, -- Sumamos los precios de todos los detalles

                        cd.nombre AS complejo_nombre,
                        -- GROUP_CONCAT para listar todas las canchas y deportes asociados
                        GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') AS cancha_nombre,
                        GROUP_CONCAT(DISTINCT tp.nombre SEPARATOR ', ') AS deporte
                    " . $baseSql . $where . " 
                    
                    -- ** SOLUCIÓN al error 1055: Incluir todas las columnas no agregadas en el GROUP BY **
                    GROUP BY 
                        r.reserva_id, 
                        r.estado, 
                        r.total_pago, 
                        r.metodo_pago_id, 
                        r.fecha_creacion,
                        u.nombre, 
                        u.correo, 
                        u.telefono,
                        cd.nombre

                    ORDER BY r.fecha_creacion DESC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'total' => (int)$total,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function getReservaDetallePaginated(int $reservaId, int $limit, int $offset): array
    {
        // 1. Total (sin cambios)
        $totalSql = "SELECT COUNT(*) AS total FROM ReservaDetalle WHERE reserva_id = :reserva_id";
        $stmt = $this->db->prepare($totalSql);
        $stmt->bindParam(':reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->execute();
        $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        $dataSql = "SELECT 
                        rd.detalle_id, 
                        rd.reserva_id, 
                        rd.cancha_id, 
                        rd.fecha, 
                        rd.hora_inicio, 
                        rd.hora_fin, 
                        rd.precio,
                        c.nombre AS cancha_nombre,
                        cd.nombre AS complejo_nombre
                    FROM ReservaDetalle rd
                    INNER JOIN Cancha c ON rd.cancha_id = c.cancha_id
                    INNER JOIN ComplejoDeportivo cd ON c.complejo_id = cd.complejo_id
                    WHERE rd.reserva_id = :reserva_id
                    ORDER BY rd.fecha ASC, rd.hora_inicio ASC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($dataSql);
        $stmt->bindParam(':reserva_id', $reservaId, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
    }

    public function createReserva(array $data): int
    {
        $sql = "INSERT INTO Reserva (
                    usuario_id, metodo_pago_id, total_pago, estado, fecha_pago
                ) VALUES (
                    :usuario_id, :metodo_pago_id, :total_pago, :estado, :fecha_pago
                )";

        $stmt = $this->db->prepare($sql);
        $fechaPago = $data['fecha_pago'] ?? null;

        $stmt->execute([
            ':usuario_id'     => $data['usuario_id'],
            ':metodo_pago_id' => $data['metodo_pago_id'],
            ':total_pago'     => $data['total_pago'],
            ':estado'     => $data['estado'],
            ':fecha_pago' => $fechaPago
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

    /**
     * Obtiene los detalles de una reserva, incluyendo el complejo_id de la cancha.
     *
     * @param int $reservaId El ID de la reserva.
     * @return array Los detalles de la reserva.
     */
    public function getDetalles(int $reservaId): array
    {
        $sql = "
            SELECT 
                rd.*, 
                c.complejo_id
            FROM 
                ReservaDetalle rd
            JOIN 
                Cancha c ON rd.cancha_id = c.cancha_id
            WHERE 
                rd.reserva_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $reservaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
