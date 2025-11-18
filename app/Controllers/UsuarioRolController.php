<?php
// app/Controllers/UsuarioRolController.php

namespace App\Controllers;

use App\Services\UsuarioRolService;
use App\Services\UsuarioService;
use App\Services\RolService;
use Exception;

class UsuarioRolController
{
    private UsuarioRolService $usuarioRolService;
    private UsuarioService $usuarioService;
    private RolService $rolService;

    public function __construct()
    {
        $this->usuarioRolService = new UsuarioRolService();
        $this->usuarioService = new UsuarioService();
        $this->rolService = new RolService();
    }

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

    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->usuarioRolService->createUsuarioRol($data);
            $this->sendResponse(['usuarioRol_id' => $newId, 'mensaje' => 'Rol asignado con éxito.'], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function listUsuarioRoles()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $complejoId = (empty($complejoId) || !is_numeric($complejoId) || $complejoId <= 0) ? null : (int)$complejoId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->usuarioRolService->getUsuarioRolesPaginated($complejoId, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->usuarioRolService->updateUsuarioRol($id, $data);
            $this->sendResponse(['usuarioRol_id' => $id, 'mensaje' => 'Asignación de rol actualizada con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function changeStatus(int $id)
    {
        $this->initRequest('PUT');

        try {
            $updated = $this->usuarioRolService->changeUsuarioRolStatus($id);
            if (!$updated) {
                throw new Exception("No se pudo cambiar el estado. Verifique el ID.", 400);
            }
            $this->sendResponse(['usuarioRol_id' => $id, 'mensaje' => 'Estado de la asignación de rol actualizado con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function delete(int $id)
    {
        $this->initRequest('DELETE');

        try {
            $deleted = $this->usuarioRolService->deleteUsuarioRol($id);
            if (!$deleted) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Asignación de rol no encontrada o ya eliminada.']);
                return;
            }
            $this->sendResponse(['usuarioRol_id' => $id, 'mensaje' => 'Asignación de rol eliminada con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function getRolesCombo()
    {
        $this->initRequest('GET');

        try {
            $roles = $this->rolService->getAllRolesCombo();
            $this->sendResponse(['total' => count($roles), 'roles' => $roles]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
