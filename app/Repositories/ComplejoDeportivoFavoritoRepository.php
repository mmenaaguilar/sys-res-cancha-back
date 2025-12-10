<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class ComplejoDeportivoFavoritoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO ComplejoDeportivoFavoritos (usuario_id, complejo_id) 
                VALUES (:usuario_id, :complejo_id)";
        $stmt = $this->db->prepare($sql);

        $params = [
            ':usuario_id' => $data['usuario_id'],
            ':complejo_id' => $data['complejo_id'],
        ];

        try {
            $stmt->execute($params);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Este complejo ya está agregado como favorito para este usuario.", 409);
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ComplejoDeportivoFavoritos WHERE favorito_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM ComplejoDeportivoFavoritos WHERE favorito_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function listByUsuarioPaginated(?int $usuarioId, ?string $searchTerm, int $limit, int $offset): array
    {
        $baseSql = "FROM ComplejoDeportivoFavoritos f
                    JOIN ComplejoDeportivo c ON f.complejo_id = c.complejo_id
                    LEFT JOIN Distrito d ON c.distrito_id = d.distrito_id
                    LEFT JOIN Provincia p ON c.provincia_id = p.provincia_id
                    LEFT JOIN Departamento dep ON c.departamento_id = dep.departamento_id";

        $whereClauses = [];
        $params = [];

        if ($usuarioId !== null) {
            $whereClauses[] = "f.usuario_id = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        if (!empty($searchTerm)) {
            $whereClauses[] = "(c.nombre LIKE :search OR c.direccion_detalle LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }

        $where = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

        $totalSql = "SELECT COUNT(*) AS total " . $baseSql . $where;
        $stmt = $this->db->prepare($totalSql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $dataSql = "SELECT 
                        f.favorito_id, 
                        f.usuario_id, 
                        f.complejo_id, 
                        f.fecha_agregado,
                        c.nombre,                
                        c.nombre AS complejo_nombre, 
                        c.url_imagen,             
                        c.url_map,                
                        c.direccion_detalle,     
                        c.estado AS complejo_estado,
                        d.nombre AS distrito_nombre,
                        p.nombre AS provincia_nombre,
                        dep.nombre AS departamento_nombre

                    " . $baseSql . $where . " 
                    ORDER BY f.fecha_agregado DESC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($dataSql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
    }

}
