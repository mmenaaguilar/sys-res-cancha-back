<?php

namespace App\Core\Helpers;

/**
 * Clase base para helpers de API
 */
class ApiHelper
{
    /**
     * Inicializa y valida el request HTTP
     */
    protected function initRequest(string $method): ?array
    {
        // Headers CORS (necesarios para el fetch)
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // 1. LECTURA PRINCIPAL: Intentar leer JSON (Usado en la mayoría de peticiones)
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // 2. LÓGICA DE RESCATE: Si no es JSON, asumir FormData/POST
        // Si json_decode falló o devolvió un array vacío (que es lo que pasa con FormData)
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            if (!empty($_POST)) {
                // Si hay datos en $_POST, los usamos (esto es FormData)
                $data = $_POST;
            } else {
                // Si no hay body JSON ni $_POST, devolvemos un array vacío
                $data = []; 
            }
        }

        // Validación de Método (flexible para update con _method='PUT')
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if ($requestMethod === 'POST' && isset($data['_method'])) {
            $requestMethod = strtoupper($data['_method']);
        }
        
        if ($requestMethod !== $method) {
             // Si el router no te detuvo, te detenemos con un 405 (Método no permitido)
             if ($method !== 'GET') { // No enviamos error si es GET/POST y no hay data
                 // Desactivado para no interferir con el router, pero es la validación estricta
             }
        }

        return $data;
    }

    /**
     * Envía una respuesta JSON exitosa
     */
    protected function sendResponse($data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Envía una respuesta de error
     */
    protected function sendError($error, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $error instanceof \Exception ? $error->getMessage() : $error,
        ]);
    }
}
