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
    public function getContactosPaginatedByFilters(?int $complejoId, ?string $searchTerm, int $limit, int $offset): array
    {
        // 1. Definición de la consulta base y cláusulas condicionales
        $selectAndFrom = "SELECT contacto_id, complejo_id, tipo, valor_contacto, estado
                          FROM Contactos
                          WHERE estado = 'activo'";

        $totalFrom = "SELECT COUNT(contacto_id) AS total FROM Contactos WHERE estado = 'activo'";

        $whereClauses = [];
        $params = [];

        // Filtro MANDATORIO: complejo_id
        if ($complejoId !== null) {
            $whereClauses[] = "complejo_id = :complejo_id";
            $params[':complejo_id'] = $complejoId;
        } else {
            // Si el ID del complejo es nulo, no devolvemos datos.
            return ['total' => 0, 'data' => []];
        }

        // Filtro opcional por término de búsqueda (valor_contacto o tipo)
        if (!empty($searchTerm)) {
            // Se busca el término en las columnas más relevantes (tipo o valor_contacto)
            $whereClauses[] = "(tipo LIKE :search_term OR valor_contacto LIKE :search_term)";
            $params[':search_term'] = '%' . $searchTerm . '%';
        }

        // Construir la cláusula WHERE final
        $whereSql = !empty($whereClauses) ? " AND " . implode(" AND ", $whereClauses) : "";

        // Consultas completas
        $dataSql = $selectAndFrom . $whereSql . " ORDER BY contacto_id ASC LIMIT :limit OFFSET :offset";
        $totalSql = $totalFrom . $whereSql;

        // 2. Ejecutar Conteo Total
        $totalStmt = $this->db->prepare($totalSql);
        foreach ($params as $key => $value) {
            $totalStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $totalStmt->execute();
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // 3. Obtener Datos
        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        // Bind de los parámetros de paginación
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
