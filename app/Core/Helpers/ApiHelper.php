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
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->sendError('Método no permitido.', 405);
            return null;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($method !== 'DELETE' && json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Formato JSON inválido.', 400);
            return null;
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
