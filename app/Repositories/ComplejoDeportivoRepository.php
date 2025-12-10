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
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT 
                    cd.*,
                    d.nombre AS distrito_nombre,
                    p.nombre AS provincia_nombre,
                    dep.nombre AS departamento_nombre,
                    CONCAT(cd.direccion_detalle, ', ', d.nombre, ', ', p.nombre) AS direccion_completa
                FROM ComplejoDeportivo cd
                LEFT JOIN Distrito d ON cd.distrito_id = d.distrito_id
                LEFT JOIN Provincia p ON cd.provincia_id = p.provincia_id
                LEFT JOIN Departamento dep ON cd.departamento_id = dep.departamento_id
                WHERE cd.complejo_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
 public function getComplejosByUbicacion(?int $depId, ?int $provId, ?int $distId): array
    {
        $sql = "SELECT 
                cd.complejo_id,
                cd.nombre,
                cd.url_imagen,
                cd.url_map,
                cd.descripcion,
                CONCAT(cd.direccion_detalle, ', ', d.nombre, ', ', p.nombre, ', ', dep.nombre) AS direccion_completa,
                cd.distrito_id, d.nombre as distrito_nombre,
                cd.provincia_id,
                cd.departamento_id
            FROM ComplejoDeportivo cd
            INNER JOIN Distrito d ON cd.distrito_id = d.distrito_id
            INNER JOIN Provincia p ON cd.provincia_id = p.provincia_id
            INNER JOIN Departamento dep ON cd.departamento_id = dep.departamento_id
            WHERE cd.estado = 'activo'";

        $params = [];

        if ($distId > 0) {
            $sql .= " AND cd.distrito_id = :dist";
            $params[':dist'] = $distId;
        } elseif ($provId > 0) {
            $sql .= " AND cd.provincia_id = :prov";
            $params[':prov'] = $provId;
        } elseif ($depId > 0) {
            $sql .= " AND cd.departamento_id = :dep";
            $params[':dep'] = $depId;
        }

        $sql .= " ORDER BY cd.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

      public function getAll(?int $usuarioId, ?string $searchTerm, int $limit, int $offset): array
    {
        $sql = "SELECT DISTINCT 
                        c.*, 
                        d.nombre AS distrito_nombre, 
                        p.nombre AS provincia_nombre,
                        dep.nombre AS departamento_nombre,
                        CONCAT(IFNULL(d.nombre,''), ', ', IFNULL(p.nombre,'')) AS ubicacion_completa,
                        ur.rol_id AS mi_rol  
                FROM ComplejoDeportivo c
                LEFT JOIN Distrito d ON c.distrito_id = d.distrito_id
                LEFT JOIN Provincia p ON c.provincia_id = p.provincia_id
                LEFT JOIN Departamento dep ON c.departamento_id = dep.departamento_id
                LEFT JOIN UsuarioRol ur ON c.complejo_id = ur.complejo_id
                WHERE 1=1"; 

        $params = [];

        if ($usuarioId !== null) {
            $sql .= " AND ur.usuario_id = :usuarioId AND ur.estado = 'activo'";
            $params[':usuarioId'] = $usuarioId;
        }

        if (!empty($searchTerm)) {
            $sql .= " AND (c.nombre LIKE :searchTerm OR c.direccion_detalle LIKE :searchTerm)";
            $params[':searchTerm'] = "%" . $searchTerm . "%";
        }

        $sql .= " ORDER BY c.complejo_id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(DISTINCT c.complejo_id) as total 
                     FROM ComplejoDeportivo c 
                     LEFT JOIN UsuarioRol ur ON c.complejo_id = ur.complejo_id 
                     WHERE 1=1";
        if ($usuarioId !== null) $countSql .= " AND ur.usuario_id = :usuarioId AND ur.estado = 'activo'";
        if (!empty($searchTerm)) $countSql .= " AND (c.nombre LIKE :searchTerm OR c.direccion_detalle LIKE :searchTerm)";

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        return ['total' => $total, 'data' => $data];
    }

  public function create(array $data): int
    {
        $sql = "INSERT INTO ComplejoDeportivo 
                (nombre, departamento_id, provincia_id, distrito_id, direccion_detalle, url_imagen, url_map, descripcion, estado)
                VALUES (:nombre, :departamento_id, :provincia_id, :distrito_id, :direccion_detalle, :url_imagen, :url_map, :descripcion, :estado)";

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':nombre', $data['nombre']);

            $stmt->bindValue(':departamento_id', !empty($data['departamento_id']) ? $data['departamento_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':provincia_id', !empty($data['provincia_id']) ? $data['provincia_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':distrito_id', !empty($data['distrito_id']) ? $data['distrito_id'] : null, PDO::PARAM_INT);
            
            $stmt->bindValue(':direccion_detalle', $data['direccion_detalle']);
            
            $urlImagen = $data['url_imagen'] ?? null;
            $urlMap = $data['url_map'] ?? null;
            $descripcion = $data['descripcion'] ?? null;
            
            $stmt->bindValue(':url_imagen', $urlImagen, is_null($urlImagen) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':url_map', $urlMap, is_null($urlMap) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, is_null($descripcion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            $stmt->bindValue(':estado', $data['estado'] ?? 'activo');

            $stmt->execute();
            $id = (int)$this->db->lastInsertId();
            if ($id === 0) throw new Exception("CRÍTICO: BD devolvió ID 0.");
            $this->db->commit();
            return $id;

        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception("Error SQL: " . $e->getMessage());
        }
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

        try {
            $stmt = $this->db->prepare($sql);
            
            $urlImagen = $data['url_imagen'] ?? null; 
            $urlMap = $data['url_map'] ?? null;
            $descripcion = $data['descripcion'] ?? null;
            $estado = $data['estado'] ?? 'activo';
            
            $stmt->bindValue(':nombre', $data['nombre']);
            
            $stmt->bindValue(':departamento_id', !empty($data['departamento_id']) ? $data['departamento_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':provincia_id', !empty($data['provincia_id']) ? $data['provincia_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':distrito_id', !empty($data['distrito_id']) ? $data['distrito_id'] : null, PDO::PARAM_INT);
            
            $stmt->bindValue(':direccion_detalle', $data['direccion_detalle'] ?? '');
            
            $stmt->bindValue(':url_imagen', $urlImagen, is_null($urlImagen) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            $stmt->bindValue(':url_map', $urlMap, is_null($urlMap) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, is_null($descripcion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':estado', $estado);
            
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\PDOException $e) {
            throw new Exception("Error SQL al actualizar: " . $e->getMessage());
        }
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

    public function getDistritosConComplejos(): array
    {
        $sql = "SELECT DISTINCT d.distrito_id, d.nombre 
                FROM ComplejoDeportivo c
                INNER JOIN Distrito d ON c.distrito_id = d.distrito_id
                WHERE c.estado = 'activo'
                ORDER BY d.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
