<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class CanchaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene una cancha por su ID (opcional para validaciones).
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Cancha WHERE cancha_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * ✔️ Trae canchas activas por complejo_id
     * (Este es el método que usará tu Facade)
     */
    public function getByComplejo(int $complejoId, ?int $tipoDeporteId = null): array
    {
        $sql = "SELECT cancha_id, complejo_id, tipo_deporte_id, nombre, url_imagen, descripcion, estado
            FROM Cancha
            WHERE complejo_id = :id
              AND estado = 'activo'";

        $params = [':id' => $complejoId];

        // Solo agregamos el filtro si tipoDeporteId tiene valor
        if ($tipoDeporteId !== null && $tipoDeporteId > 0) {
            $sql .= " AND tipo_deporte_id = :tipoDeporteId";
            $params[':tipoDeporteId'] = $tipoDeporteId;
        }

        $sql .= " ORDER BY cancha_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /**
     * Crea una nueva cancha.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO Cancha (complejo_id, tipo_deporte_id, nombre, url_imagen, descripcion, estado)
                VALUES (:complejo_id, :tipo_deporte_id, :nombre, :url_imagen, :descripcion, :estado)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':complejo_id', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindParam(':tipo_deporte_id', $data['tipo_deporte_id'], PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':url_imagen', $data['url_imagen']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':estado', $data['estado']);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }

        throw new Exception("Error al crear la cancha.");
    }

    /**
     * Actualiza una cancha existente.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Cancha
                SET complejo_id = :complejo_id,
                    tipo_deporte_id = :tipo_deporte_id,
                    nombre = :nombre,
                    url_imagen = :url_imagen,
                    descripcion = :descripcion,
                    estado = :estado
                WHERE cancha_id = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':complejo_id', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindParam(':tipo_deporte_id', $data['tipo_deporte_id'], PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':url_imagen', $data['url_imagen']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina una cancha.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM Cancha WHERE cancha_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Cambia el estado (activo/inactivo).
     */
    public function changeStatus(int $id, string $estado): bool
    {
        $sql = "UPDATE Cancha SET estado = :estado WHERE cancha_id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
