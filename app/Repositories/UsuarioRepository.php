<?php
// app/Repositories/UsuarioRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;
use PDOException;

class UsuarioRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT usuario_id, nombre, telefono, correo, estado FROM Usuarios WHERE usuario_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una lista paginada de usuarios (solo activos).
     */
    public function getUsuariosPaginatedByFilters(?string $searchTerm, int $limit, int $offset): array
    {
        $whereClauses = ["estado = 'activo'"];
        $params = [];

        if (!empty($searchTerm)) {
            $whereClauses[] = "(nombre LIKE :search OR correo LIKE :search OR telefono LIKE :search)";
            $params[':search'] = "%$searchTerm%";
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        // Total de registros
        $totalSql = "SELECT COUNT(usuario_id) AS total FROM Usuarios $whereSql";
        $stmtTotal = $this->db->prepare($totalSql);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Datos paginados
        $dataSql = "SELECT usuario_id, nombre, telefono, correo, estado
                    FROM Usuarios
                    $whereSql
                    ORDER BY nombre ASC
                    LIMIT :limit OFFSET :offset";

        $stmtData = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmtData->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtData->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmtData->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmtData->execute();
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return ['total' => (int)$total, 'data' => $data];
    }



    /**
     * Actualiza el nombre, telefono y correo de un usuario.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Usuarios 
                SET nombre = :nombre, telefono = :telefono, correo = :correo 
                WHERE usuario_id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':telefono' => $data['telefono'],
                ':correo' => $data['correo'],
                ':id' => $id,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("El correo electrónico ya está registrado.", 409);
            }
            throw $e;
        }
    }

    public function getContrasenaHash(int $usuarioId): ?string
    {
        $sql = "SELECT contrasena FROM Usuarios WHERE usuario_id = :usuario_id"; 
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['contrasena'] : null; 
    }

    /**
     * Actualiza la contraseña de un usuario.
     */
    public function actualizarContrasena(int $usuarioId, string $nuevoHash): bool
    {
        error_log("REPOSITORY - actualizarContrasena llamado");
        error_log("REPOSITORY - usuarioId: " . $usuarioId);
        error_log("REPOSITORY - nuevoHash: " . $nuevoHash);
        
        $sql = "UPDATE Usuarios SET contrasena = :contrasena WHERE usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        
        $resultado = $stmt->execute([
            ':contrasena' => $nuevoHash,
            ':usuario_id' => $usuarioId
        ]);
        
        error_log("REPOSITORY - execute resultado: " . ($resultado ? 'true' : 'false'));
        error_log("REPOSITORY - rowCount: " . $stmt->rowCount());
        
        return $resultado && $stmt->rowCount() > 0;
    }

    
}
