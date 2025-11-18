<?php
// app/Repositories/UsuarioRolRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class UsuarioRolRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT ur.*, r.nombre AS rol_nombre, u.nombre AS usuario_nombre, cd.nombre AS complejo_nombre
                FROM UsuarioRol ur
                JOIN Roles r ON ur.rol_id = r.rol_id
                JOIN Usuarios u ON ur.usuario_id = u.usuario_id
                LEFT JOIN ComplejoDeportivo cd ON ur.complejo_id = cd.complejo_id
                WHERE ur.usuarioRol_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUsuarioRolesPaginatedByComplejo(?int $complejoId, int $limit, int $offset): array
    {
        $baseSql = "FROM UsuarioRol ur
                    JOIN Roles r ON ur.rol_id = r.rol_id
                    JOIN Usuarios u ON ur.usuario_id = u.usuario_id
                    LEFT JOIN ComplejoDeportivo cd ON ur.complejo_id = cd.complejo_id";

        $params = [];
        $whereClauses = ["ur.estado = 'activo'"]; // FILTRO POR ESTADO

        if ($complejoId !== null) {
            $whereClauses[] = "ur.complejo_id = :complejo_id";
            $params[':complejo_id'] = $complejoId;
        }

        $where = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

        $totalSql = "SELECT COUNT(ur.usuarioRol_id) AS total " . $baseSql . $where;
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute($params);
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $dataSql = "SELECT ur.usuarioRol_id, u.nombre AS usuario_nombre, u.correo, u.telefono, 
                           r.nombre AS rol_nombre, r.rol_id, ur.complejo_id, cd.nombre AS complejo_nombre, ur.estado "
            . $baseSql . $where
            . " ORDER BY u.nombre ASC, r.nombre ASC LIMIT :limit OFFSET :offset";

        $dataStmt = $this->db->prepare($dataSql);

        foreach ($params as $key => &$val) {
            $dataStmt->bindParam($key, $val);
        }

        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $dataStmt->execute();
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO UsuarioRol (usuario_id, rol_id, complejo_id, estado) 
            VALUES (:usuario_id, :rol_id, :complejo_id, :estado)";
        $stmt = $this->db->prepare($sql);

        $complejoId = $data['complejo_id'] ?? null;

        // Crea el array de parámetros con 4 tokens, incluyendo complejo_id
        $params = [
            ':usuario_id' => $data['usuario_id'],
            ':rol_id' => $data['rol_id'],
            ':estado' => $data['estado'],
            ':complejo_id' => $complejoId,
        ];

        try {
            $stmt->execute($params);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Error de clave única: El usuario ya tiene este rol asignado para este complejo o a nivel global.", 409);
            }
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE UsuarioRol SET usuario_id = :usuario_id, rol_id = :rol_id, complejo_id = :complejo_id, estado = :estado 
                WHERE usuarioRol_id = :id";
        $stmt = $this->db->prepare($sql);

        $complejoId = $data['complejo_id'] ?? null;

        if ($complejoId === null) {
            $stmt->bindValue(':complejo_id', null, PDO::PARAM_NULL);
        }

        try {
            $params = [
                ':usuario_id' => $data['usuario_id'],
                ':rol_id' => $data['rol_id'],
                ':estado' => $data['estado'],
                ':id' => $id,
            ];

            if ($complejoId !== null) {
                $params[':complejo_id'] = $complejoId;
            }

            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("Error de clave única: El usuario ya tiene este rol asignado para este complejo o a nivel global.", 409);
            }
            throw $e;
        }
    }

    public function changeStatus(int $id, string $status): bool
    {
        $sql = "UPDATE UsuarioRol SET estado = :estado WHERE usuarioRol_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM UsuarioRol WHERE usuarioRol_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
