<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class ComplejoDeportivoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM ComplejoDeportivo WHERE complejo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAll(?int $complejoId = null): array
    {
        if ($complejoId !== null) {
            $sql = "SELECT * FROM ComplejoDeportivo WHERE complejo_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $complejoId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? [$result] : [];
        } else {
            $sql = "SELECT * FROM ComplejoDeportivo ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO ComplejoDeportivo 
                (nombre, departamento_id, provincia_id, distrito_id, direccion_detalle, url_imagen, url_map, descripcion, estado)
                VALUES (:nombre, :departamento_id, :provincia_id, :distrito_id, :direccion_detalle, :url_imagen, :url_map, :descripcion, :estado)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':departamento_id', $data['departamento_id']);
        $stmt->bindParam(':provincia_id', $data['provincia_id']);
        $stmt->bindParam(':distrito_id', $data['distrito_id']);
        $stmt->bindParam(':direccion_detalle', $data['direccion_detalle']);
        $stmt->bindParam(':url_imagen', $data['url_imagen']);
        $stmt->bindParam(':url_map', $data['url_map']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':estado', $data['estado']);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }

        throw new Exception("Error al crear el complejo deportivo.");
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE ComplejoDeportivo
                SET nombre = :nombre,
                    departamento_id = :departamento_id,
                    provincia_id = :provincia_id,
                    distrito_id = :distrito_id,
                    direccion_detalle = :direccion_detalle,
                    url_imagen = :url_imagen,
                    url_map = :url_map,
                    descripcion = :descripcion,
                    estado = :estado
                WHERE complejo_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':departamento_id', $data['departamento_id']);
        $stmt->bindParam(':provincia_id', $data['provincia_id']);
        $stmt->bindParam(':distrito_id', $data['distrito_id']);
        $stmt->bindParam(':direccion_detalle', $data['direccion_detalle']);
        $stmt->bindParam(':url_imagen', $data['url_imagen']);
        $stmt->bindParam(':url_map', $data['url_map']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function changeStatus(int $id, string $estado): bool
    {
        $sql = "UPDATE ComplejoDeportivo SET estado = :estado WHERE complejo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ComplejoDeportivo WHERE complejo_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
