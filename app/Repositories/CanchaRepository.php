<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class CanchaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // --- CREAR (Solución al error HY093) ---
    public function create(array $data): int
    {
        $sql = "INSERT INTO Cancha (complejo_id, tipo_deporte_id, nombre, descripcion, estado) 
                VALUES (:complejo_id, :tipo_deporte_id, :nombre, :descripcion, :estado)";

        $stmt = $this->db->prepare($sql);
        
        // Usamos bindValue para evitar problemas de referencia
        $stmt->bindValue(':complejo_id', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindValue(':tipo_deporte_id', $data['tipo_deporte_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':descripcion', $data['descripcion'] ?? '');
        $stmt->bindValue(':estado', $data['estado'] ?? 'activo');

        if (!$stmt->execute()) {
            throw new Exception("Error al insertar cancha en BD.");
        }

        return (int)$this->db->lastInsertId();
    }

    public function getByComplejo(int $complejoId, ?int $tipoDeporteId = null): array
    {
        $sql = "SELECT c.cancha_id, c.complejo_id, c.tipo_deporte_id, c.nombre, c.descripcion, c.estado
            FROM Cancha c
            WHERE c.complejo_id = :id
              AND c.estado = 'activo'
              AND EXISTS (
                  SELECT 1 
                  FROM HorarioBase h
                  WHERE h.cancha_id = c.cancha_id
                    AND h.estado = 'activo'
              )";

        $params = [':id' => $complejoId];

        // Filtro por tipo de deporte si se recibe
        if ($tipoDeporteId !== null && $tipoDeporteId > 0) {
            $sql .= " AND c.tipo_deporte_id = :tipoDeporteId";
            $params[':tipoDeporteId'] = $tipoDeporteId;
        }

        $sql .= " ORDER BY c.cancha_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- LISTAR PAGINADO (Solución a "Error al cargar datos") ---
    public function getByComplejoPaginated($complejoId, $tipoDeporteId, $search, $limit, $offset)
    {
        $params = [':cid' => $complejoId];
        $whereClause = "WHERE c.complejo_id = :cid AND c.estado != 'eliminado'";

        // Filtro Deporte
        if ($tipoDeporteId !== null && $tipoDeporteId > 0) {
            $whereClause .= " AND c.tipo_deporte_id = :tid";
            $params[':tid'] = $tipoDeporteId;
        }

        // Filtro Búsqueda
        if (!empty($search)) {
            $whereClause .= " AND (c.nombre LIKE :search OR c.descripcion LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // A. Contar Total
        $countSql = "SELECT COUNT(*) as total FROM Cancha c $whereClause";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // B. Obtener Datos (Con LEFT JOIN para seguridad)
        $sql = "SELECT c.*, td.nombre as tipo_deporte_nombre 
                FROM Cancha c
                LEFT JOIN TipoDeporte td ON c.tipo_deporte_id = td.tipo_deporte_id
                $whereClause 
                ORDER BY c.cancha_id DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind de filtros
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        // Bind de Paginación (CRUCIAL: Deben ser INT explícitos)
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => (int)$total
        ];
    }

    // --- ACTUALIZAR ---
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE Cancha SET 
                complejo_id = :cid,
                tipo_deporte_id = :tid,
                nombre = :nombre, 
                descripcion = :desc, 
                estado = :estado 
                WHERE cancha_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $data['complejo_id'], PDO::PARAM_INT);
        $stmt->bindValue(':tid', $data['tipo_deporte_id'], PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':desc', $data['descripcion'] ?? '');
        $stmt->bindValue(':estado', $data['estado']);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // --- CAMBIAR ESTADO ---
    public function changeStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE Cancha SET estado = :st WHERE cancha_id = :id");
        return $stmt->execute([':st' => $status, ':id' => $id]);
    }

    // --- ELIMINAR ---
    public function delete(int $id): bool
    {
        // Esto eliminará la fila de la base de datos permanentemente
        $sql = "DELETE FROM Cancha WHERE cancha_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM Cancha WHERE cancha_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIdWithDetails(int $id): ?array
    {
        $sql = "SELECT 
                    c.*,
                    cd.nombre AS complejo_nombre,
                    cd.direccion_detalle,
                    td.nombre AS tipo_deporte_nombre
                FROM Cancha c
                INNER JOIN ComplejoDeportivo cd ON c.complejo_id = cd.complejo_id
                INNER JOIN TipoDeporte td ON c.tipo_deporte_id = td.tipo_deporte_id
                WHERE c.cancha_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}