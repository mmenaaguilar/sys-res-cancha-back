<?php
// app/Controllers/ComplejoDeportivoController.php

namespace App\Controllers;

use App\Services\ComplejoDeportivoService;
use Exception;

class ComplejoDeportivoController
{
    private ComplejoDeportivoService $complejoDeportivoService;

    public function __construct()
    {
        $this->complejoDeportivoService = new ComplejoDeportivoService();
    }

    /**
     * Maneja la solicitud GET /api/complejos/search?q={ubicacion}
     * Busca complejos por coincidencia en Departamento, Provincia o Distrito.
     * @return void
     */
    public function search()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido. Use GET.']);
            return;
        }

        // 1. Obtener el término de búsqueda
        $term = $_GET['q'] ?? '';

        try {
            // 2. Llamar al Service
            $data = $this->complejoDeportivoService->findComplejosByUbicacion($term);

            // Manejo de la validación del término corto (desde el servicio)
            if (empty($data) && strlen(trim($term)) > 0 && strlen(trim($term)) < 3) {
                http_response_code(400);
                echo json_encode(['error' => 'El término de búsqueda debe tener al menos 3 caracteres para Ubigeo.']);
                return;
            }

            // 3. Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'total' => count($data),
                'data' => $data
            ]);
        } catch (Exception $e) {
            // 4. Manejo de errores
            http_response_code(500);
            echo json_encode([
                'error' => 'Error interno del servidor durante la búsqueda.',
                'detail' => $e->getMessage()
            ]);
        }
    }
}
