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
        $this->tipoDeporteService = new TipoDeporteService();
    }

    /**
     * Maneja la solicitud GET /api/tipodeporte/combo
     * @return void
     */
    public function combo()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Método no permitido. Use GET.']);
            return;
        }

        try {
            $data = $this->tipoDeporteService->getComboList();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error al obtener los tipos de deporte.',
                'detail' => $e->getMessage()
            ]);
        }
    }
}
