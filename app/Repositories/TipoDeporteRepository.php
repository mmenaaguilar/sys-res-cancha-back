<?php
// app/Repositories/TipoDeporteRepository.php

namespace App\Repositories;

// Asumimos que esta clase maneja la conexión a la DB y devuelve una instancia de PDO
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
            // Usamos AS para renombrar las columnas a 'value' y 'label'
            $sql = "SELECT tipo_deporte_id AS value, nombre AS label 
                    FROM TipoDeporte 
                    ORDER BY nombre ASC";

            $stmt = $this->db->query($sql);

            // Devuelve un array de arrays asociativos
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Manejo de errores de base de datos
            throw new Exception("Error al obtener la lista de tipos de deporte: " . $e->getMessage());
        }
    }
}
