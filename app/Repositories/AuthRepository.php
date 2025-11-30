<?php
// app/Repositories/UserRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class AuthRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca un usuario por su correo para el Login.
     * @param string $correo
     * @return array|null Devuelve los datos necesarios (incluyendo la contrasena).
     */
    public function findByCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare("SELECT usuario_id, nombre, correo, contrasena, telefono FROM Usuarios WHERE correo = :correo LIMIT 1");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario en la tabla Usuarios y le asigna el rol 'Deportista'.
     * @param array $data Contiene 'nombre', 'correo', 'contrasena' (ya hasheada), 'telefono'.
     * @return int El ID del usuario creado.
     */
    public function create(array $data): int
    {
        // Usamos transacciones para asegurar que ambas tablas se actualicen
        $this->db->beginTransaction();

        try {
            // 1. Insertar en la tabla Usuarios
            $stmt = $this->db->prepare("INSERT INTO Usuarios (nombre, telefono, correo, contrasena) VALUES (:nombre, :telefono, :correo, :contrasena)");
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':telefono' => $data['telefono'] ?? null, // Puede ser opcional
                ':correo' => $data['correo'],
                ':contrasena' => $data['contrasena'],
            ]);

            $usuarioId = $this->db->lastInsertId();

            // 2. Insertar en la tabla UsuarioRol (Asignar Rol 'Deportista' = 1)
            // Asumimos rol_id = 1 es Deportista y complejo_id es NULL.
            $rolIdDeportista = 3;

            $stmtRol = $this->db->prepare("INSERT INTO UsuarioRol (usuario_id, rol_id, complejo_id) VALUES (:usuario_id, :rol_id, NULL)");
            $stmtRol->execute([
                ':usuario_id' => $usuarioId,
                ':rol_id' => $rolIdDeportista,
            ]);

            $this->db->commit();
            return (int)$usuarioId;
        } catch (Exception $e) {
            $this->db->rollBack();
            // Lanza el error para que el Service y Controller puedan manejarlo.
            throw new Exception("Error al crear el usuario: " . $e->getMessage());
        }
    }
}
