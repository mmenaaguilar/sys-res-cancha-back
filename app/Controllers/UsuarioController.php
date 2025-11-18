<?php
// app/Controllers/UsuarioController.php

namespace App\Controllers;

use App\Services\UsuarioService;
use Exception;

class UsuarioController
{
    private UsuarioService $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
    }

    // Nota: Es mejor heredar o usar un trait para estas funciones helpers, pero para
    // mantener el código autocontenido, las repetimos aquí.
    private function initRequest(string $method): ?array
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
            return null;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($method !== 'DELETE' && json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Formato JSON inválido.']);
            return null;
        }
        return $data;
    }

    private function sendResponse($data, int $code = 200)
    {
        http_response_code($code);
        echo json_encode(['success' => true, 'data' => $data]);
    }

    private function sendError(Exception $e, int $code = 400)
    {
        $responseCode = ($e->getCode() === 409) ? 409 : $code;
        $responseCode = ($responseCode === 404) ? 404 : $responseCode;
        http_response_code($responseCode);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    /**
     * Endpoint para obtener usuarios paginados.
     */
    public function getUsuariosPaginated()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $usuariosPaginated = $this->usuarioService->getUsuariosPaginated($page, $limit);

            $this->sendResponse($usuariosPaginated);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Endpoint para editar un usuario existente.
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $updated = $this->usuarioService->updateUsuario($id, $data);

            if ($updated === true) {
                $this->sendResponse(['usuario_id' => $id, 'mensaje' => 'Usuario actualizado con éxito.']);
            } else {
                $this->sendResponse(['usuario_id' => $id, 'mensaje' => 'No se realizaron cambios en el usuario.'], 200);
            }
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
