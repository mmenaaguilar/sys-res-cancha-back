<?php
// app/Repositories/ContactoRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class ContactoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene un contacto por su ID (uso interno/validación).
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM Contactos WHERE contacto_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lista todos los contactos asociados a un Complejo Deportivo.
     */
    public function listByComplejoId(int $complejoId): array
    {
        $sql = "SELECT * FROM Contactos WHERE complejo_id = :complejo_id ORDER BY tipo, contacto_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complejo_id', $complejoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo contacto.
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO Contactos (complejo_id, tipo, valor_contacto, estado) 
                VALUES (:complejo_id, :tipo, :valor_contacto, :estado)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complejo_id', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $data['tipo']);
        $stmt->bindParam(':valor_contacto', $data['valor_contacto']);
        $stmt->bindParam(':estado', $data['estado']);

        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        throw new Exception("Error al crear el contacto.");
    }

    /**
     * Actualiza un contacto existente.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Contactos 
                SET complejo_id = :complejo_id, tipo = :tipo, valor_contacto = :valor_contacto, estado = :estado
                WHERE contacto_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complejo_id', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $data['tipo']);
        $stmt->bindParam(':valor_contacto', $data['valor_contacto']);
        $stmt->bindParam(':estado', $data['estado']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina físicamente un contacto.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM Contactos WHERE contacto_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Cambia el estado (activo/inactivo) de un contacto.
     */
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE Contactos SET estado = :estado WHERE contacto_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
