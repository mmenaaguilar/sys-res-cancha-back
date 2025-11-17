<?php
// app/Repositories/ComplejoDeportivoRepository.php

namespace App\Repositories;

use App\Core\Database; // Clase de conexión asumida
use PDO;
use Exception;

class ComplejoDeportivoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca complejos deportivos por coincidencia parcial en el nombre del 
     * Departamento, Provincia o Distrito al que pertenecen.
     * @param string $term Término de búsqueda (ej. 'Tacna', 'Lima').
     * @return array
     */
    public function searchByUbicacion(string $term): array
    {
        try {
            // 1. Prepara el término para la búsqueda LIKE: %término%
            $search_term = "%{$term}%";

            $sql = "
                SELECT 
                    CD.complejo_id, 
                    CD.nombre, 
                    CD.direccion_detalle,
                    D.nombre AS departamento_nombre,
                    P.nombre AS provincia_nombre,
                    DI.nombre AS distrito_nombre,
                    CD.url_imagen
                FROM ComplejoDeportivo CD
                -- Joins a las tablas de Ubigeo
                INNER JOIN Departamento D ON CD.departamento_id = D.departamento_id
                INNER JOIN Provincia P ON CD.provincia_id = P.provincia_id
                INNER JOIN Distrito DI ON CD.distrito_id = DI.distrito_id
                WHERE 
                    (
                        D.nombre LIKE :term 
                        OR P.nombre LIKE :term 
                        OR DI.nombre LIKE :term
                    )
                    AND CD.eliminado = 0 
                    AND CD.estado = 'activo'
                ORDER BY CD.nombre ASC
                LIMIT 20
            ";

            $stmt = $this->db->prepare($sql);
            // Usamos el mismo placeholder :term para los tres campos en la cláusula WHERE
            $stmt->bindParam(':term', $search_term);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Lanza una excepción para que la capa de Servicio la maneje
            throw new Exception("DB Error: Búsqueda fallida en Ubigeo. " . $e->getMessage());
        }
    }
}
