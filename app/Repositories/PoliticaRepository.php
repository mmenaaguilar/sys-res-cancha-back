<?php
// app/Repositories/PoliticaRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

class PoliticaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- Helper ---
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM PoliticaCancelacion WHERE politica_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // --- READ (LIST) ---
    /**
     * Lista políticas por complejo (Maneja 0 a N filas).
     */
    public function listByComplejoId(int $complejoId): array
    {
        $sql = "SELECT * FROM PoliticaCancelacion WHERE complejo_id = :complejo_id ORDER BY politica_id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':complejo_id', $complejoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CREATE ---
    public function create(array $data): int
    {
        $sql = "INSERT INTO PoliticaCancelacion 
                (complejo_id, horas_limite, estrategia_temprana, estado) 
                VALUES (:complejo_id, :horas_limite, :estrategia_temprana, :estado)";

        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':complejo_id' => $data['complejo_id'],
                ':horas_limite' => $data['horas_limite'],
                ':estrategia_temprana' => $data['estrategia_temprana'],
                ':estado' => $data['estado'],
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException  $e) {
            // Manejo de error de clave única para la restricción global
            if ($e->getCode() === '23000') {
                throw new PDOException("Error de clave única: La estrategia temprana '{$data['estrategia_temprana']}' ya está siendo utilizada globalmente por otra política.", 409);
            }
            throw $e;
        }
    }

    // --- UPDATE ---
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE PoliticaCancelacion 
                SET complejo_id = :complejo_id, horas_limite = :horas_limite, 
                    estrategia_temprana = :estrategia_temprana, estado = :estado
                WHERE politica_id = :id";

        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([
                ':complejo_id' => $data['complejo_id'],
                ':horas_limite' => $data['horas_limite'],
                ':estrategia_temprana' => $data['estrategia_temprana'],
                ':estado' => $data['estado'],
                ':id' => $id,
            ]);
        } catch (PDOException  $e) {
            if ($e->getCode() === '23000') {
                throw new PDOException("Error de clave única: La estrategia temprana '{$data['estrategia_temprana']}' ya está siendo utilizada globalmente por otra política.", 409);
            }
            throw $e;
        }
    }

    // --- DELETE ---
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM PoliticaCancelacion WHERE politica_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // --- CHANGE STATUS ---
    public function changeStatus(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE PoliticaCancelacion SET estado = :estado WHERE politica_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
