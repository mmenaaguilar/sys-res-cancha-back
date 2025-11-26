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
    
    public function getComplejosByDistrito(int $distritoId): array
    {
        $sql = "SELECT 
                cd.complejo_id,
                cd.nombre,
                cd.url_imagen,
                cd.descripcion,
                CONCAT(cd.direccion_detalle, ', ', d.nombre, ', ', p.nombre, ', ', dep.nombre) AS direccion_completa,
                cd.distrito_id,
                cd.provincia_id,
                cd.departamento_id
            FROM ComplejoDeportivo cd
            INNER JOIN Distrito d ON cd.distrito_id = d.distrito_id
            INNER JOIN Provincia p ON cd.provincia_id = p.provincia_id
            INNER JOIN Departamento dep ON cd.departamento_id = dep.departamento_id
            WHERE cd.distrito_id = :distritoId
              AND cd.estado = 'activo'
            ORDER BY cd.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':distritoId', $distritoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(?int $usuarioId, ?string $searchTerm = null, int $limit, int $offset): array
    {
        $params = [];
        $whereClauses = ["c.estado = 'activo'"]; // condición obligatoria

        // 🔹 Si filtra por usuario
        $join = "";
        if ($usuarioId !== null) {
            $join = "INNER JOIN UsuarioRol ur ON ur.complejo_id = c.complejo_id";
            $whereClauses[] = "ur.estado = 'activo'";
            $whereClauses[] = "ur.usuario_id = :usuarioId";
            $params[':usuarioId'] = $usuarioId;
        }

        // 🔹 Si hay término de búsqueda
        if (!empty($searchTerm)) {
            $whereClauses[] = "(c.nombre LIKE :searchTerm OR c.descripcion LIKE :searchTerm OR c.direccion_detalle LIKE :searchTerm)";
            $params[':searchTerm'] = "%" . $searchTerm . "%";
        }

        // WHERE final
        $whereSQL = "WHERE " . implode(" AND ", $whereClauses);

        // ---- TOTAL ----
        $totalSql = "
        SELECT COUNT(c.complejo_id) AS total
        FROM ComplejoDeportivo c
        $join
        $whereSQL
    ";
        $totalStmt = $this->db->prepare($totalSql);

        foreach ($params as $key => $value) {
            $totalStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $totalStmt->execute();
        $total = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // ---- DATA ----
        $dataSql = "
        SELECT c.*
        FROM ComplejoDeportivo c
        $join
        $whereSQL
        ORDER BY c.nombre ASC
        LIMIT :limit OFFSET :offset
    ";
        $dataStmt = $this->db->prepare($dataSql);

        // Bind parámetros dinámicos
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $dataStmt->execute();
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'data' => $data
        ];
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
