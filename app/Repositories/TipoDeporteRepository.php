<?php
// app/Repositories/TipoDeporteRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Exception;

class TipoDeporteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene todos los tipos de deporte para un combo box.
     * Selecciona el ID como 'value' y el nombre como 'label'.
     * @return array
     */
    public function getAllForCombo(): array
    {
        try {
            $sql = "SELECT tipo_deporte_id AS value, nombre AS label 
                    FROM TipoDeporte 
                    ORDER BY nombre ASC";

            $stmt = $this->db->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener la lista de tipos de deporte: " . $e->getMessage());
        }
    }
}
