<?php
// app/Controllers/TipoDeporteController.php

namespace App\Controllers;

use App\Services\TipoDeporteService;
use Exception;

class TipoDeporteController
{
    private TipoDeporteService $tipoDeporteService;

    public function __construct()
    {
        // Instancia el Service
        $this->tipoDeporteService = new TipoDeporteService();
    }

    /**
     * Maneja la solicitud GET /api/tipodeporte/combo
     * @return void
     */
    public function combo()
    {
        // Asegurar que la respuesta sea JSON
        header('Content-Type: application/json');

        // Validar que el método sea GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Método no permitido. Use GET.']);
            return;
        }

        try {
            // 1. Llamar al Service para obtener la lista
            $data = $this->tipoDeporteService->getComboList();

            // 2. Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            // 3. Manejo de errores
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al obtener los tipos de deporte.',
                'detail' => $e->getMessage()
            ]);
        }
    }
}
