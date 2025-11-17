<?php
// app/Controllers/UbigeoController.php

namespace App\Controllers;

use App\Services\UbigeoService;
use Exception;

class UbigeoController
{
    private UbigeoService $ubigeoService;

    public function __construct()
    {
        $this->ubigeoService = new UbigeoService();
    }

    /**
     * Maneja la solicitud GET /api/ubigeo/search?q={termino}
     * Detecta el nivel de búsqueda basado en el conteo de comas.
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

        $term = $_GET['q'] ?? '';
        $term = trim($term);

        // 1. PARSEO DE LA ENTRADA
        $components = array_map('trim', explode(',', $term));
        $numComponents = count($components);

        $level = null;

        if ($numComponents === 3) {
            $level = 'distrito'; // Ej: Tacna, Tacna, Tacna
        } elseif ($numComponents === 2) {
            $level = 'provincia'; // Ej: Tacna, Tacna
        } elseif ($numComponents === 1 && strlen($components[0]) >= 2) {
            $level = 'departamento'; // Ej: Tacna
        } else {
            // Si el término es vacío o muy corto (menos de 2 caracteres)
            http_response_code(400);
            echo json_encode(['error' => 'Término de búsqueda inválido o muy corto (mínimo 2 caracteres).']);
            return;
        }

        try {
            // 2. Llamar al Service con los componentes y el nivel detectado
            $data = $this->ubigeoService->findDistritosByHierarchy($components, $level);

            // 3. Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'total' => count($data),
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error interno del servidor durante la búsqueda de ubicación.',
                'detail' => $e->getMessage()
            ]);
        }
    }
}
