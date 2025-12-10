<?php
// app/Repositories/UbigeoRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class UbigeoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca distritos basándose en la jerarquía inferida del término de búsqueda.
     * @param array $components [Dep, Prov, Dist]
     * @param string $level 'departamento', 'provincia', 'distrito'
     * @return array
     */
    public function getAllDepartamentos(): array
    {
        $sql = "SELECT departamento_id AS id, nombre AS name FROM Departamento ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProvinciasByDepartamentoId(int $depId): array
    {
        $sql = "SELECT provincia_id AS id, nombre AS name FROM Provincia WHERE departamento_id = :id ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $depId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistritosByProvinciaId(int $provId): array
    {
        $sql = "SELECT distrito_id AS id, nombre AS name FROM Distrito WHERE provincia_id = :id ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $provId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchDistritosByHierarchy(array $components, string $level): array
    {
        try {
            $sql = "
                SELECT 
                    T3.distrito_id AS id, 
                    CONCAT(D.nombre, ', ', P.nombre, ', ', T3.nombre) AS label, 
                    'distrito' AS tipo,
                    T3.provincia_id AS padre_id
                FROM Distrito T3
                INNER JOIN Provincia P ON T3.provincia_id = P.provincia_id
                INNER JOIN Departamento D ON P.departamento_id = D.departamento_id
            ";

            $whereClauses = [];
            $binds = [];

            if (isset($components[0]) && !empty($components[0])) {
                $whereClauses[] = "D.nombre LIKE :dep_term";
                $binds['dep_term'] = "%{$components[0]}%";
            }

            if (in_array($level, ['provincia', 'distrito']) && isset($components[1]) && !empty($components[1])) {
                $whereClauses[] = "P.nombre LIKE :prov_term";
                $binds['prov_term'] = "%{$components[1]}%";
            }

            if ($level === 'distrito' && isset($components[2]) && !empty($components[2])) {
                $whereClauses[] = "T3.nombre LIKE :dist_term";
                $binds['dist_term'] = "%{$components[2]}%";
            }

            $sql .= " WHERE " . implode(" AND ", $whereClauses);
            $sql .= " ORDER BY label ASC LIMIT 30";

            $stmt = $this->db->prepare($sql);

            foreach ($binds as $param => &$value) {
                $stmt->bindParam(":$param", $value, PDO::PARAM_STR);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error en la ejecución de la consulta SQL de Ubigeo.");
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("DB Error: Falló la búsqueda de Ubigeo por jerarquía. " . $e->getMessage());
        }
    }
}
