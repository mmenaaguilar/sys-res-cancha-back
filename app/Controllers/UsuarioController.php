<?php
// app/Controllers/UsuarioController.php

namespace App\Controllers;

use App\Services\UsuarioService;
use App\Core\Helpers\ApiHelper;
use Exception;

class UsuarioController extends ApiHelper
{
    private UsuarioService $usuarioService;

    public function __construct()
    {
        $this->usuarioService = new UsuarioService();
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
            // Mantener la lógica específica para códigos 409 y 404
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
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
            // Mantener la lógica específica para códigos 409 y 404
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }
}
