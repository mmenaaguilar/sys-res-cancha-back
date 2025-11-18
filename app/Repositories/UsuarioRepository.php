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
    public function getUsuariosPaginated(int $limit, int $offset): array
    {
        // 1. Consulta para el total
        $totalSql = "SELECT COUNT(usuario_id) AS total FROM Usuarios WHERE estado = 'activo'";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 2. Consulta para los datos (sin contraseña)
        $dataSql = "SELECT usuario_id, nombre, telefono, correo, estado
                    FROM Usuarios
                    WHERE estado = 'activo'
                    ORDER BY nombre ASC LIMIT :limit OFFSET :offset";

        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$total,
            'data' => $data
        ];
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
            // Manejar error de clave única si el correo ya existe (código 23000)
            if ($e->getCode() === '23000') {
                throw new Exception("El correo electrónico ya está registrado.", 409);
            }
            throw $e;
        }
    }
}
