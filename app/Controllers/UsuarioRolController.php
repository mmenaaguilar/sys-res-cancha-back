<?php
// app/Controllers/UsuarioRolController.php

namespace App\Controllers;

use App\Services\UsuarioRolService;
use App\Services\UsuarioService;
use App\Services\RolService;
use App\Core\Helpers\ApiHelper;
use Exception;

class UsuarioRolController extends ApiHelper
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

    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->usuarioRolService->createUsuarioRol($data);
            $this->sendResponse(['usuarioRol_id' => $newId, 'mensaje' => 'Rol asignado con éxito.'], 201);
        } catch (Exception $e) {
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    public function listUsuarioRoles()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;
        $searchTerm = $data['searchTerm'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $complejoId = (empty($complejoId) || !is_numeric($complejoId) || $complejoId <= 0) ? null : (int)$complejoId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $searchTerm = !empty($searchTerm) ? trim($searchTerm) : null;

            $list = $this->usuarioRolService->getUsuarioRolesPaginated($complejoId, $searchTerm, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
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
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            $updated = $this->usuarioRolService->changeUsuarioRolStatus($id);
            if (!$updated) {
                $this->sendError('No se pudo cambiar el estado. Verifique el ID.', 400);
                return;
            }
            $this->sendResponse(['usuarioRol_id' => $id, 'mensaje' => 'Estado de la asignación de rol actualizado con éxito.']);
        } catch (Exception $e) {
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    public function delete(int $id)
    {
        $data = $this->initRequest('DELETE');

        try {
            $deleted = $this->usuarioRolService->deleteUsuarioRol($id);
            if (!$deleted) {
                $this->sendError('Asignación de rol no encontrada o ya eliminada.', 404);
                return;
            }
            $this->sendResponse(['usuarioRol_id' => $id, 'mensaje' => 'Asignación de rol eliminada con éxito.']);
        } catch (Exception $e) {
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    public function getRolesCombo()
    {
        $data = $this->initRequest('GET');

        try {
            $roles = $this->rolService->getAllRolesCombo();
            $this->sendResponse(['total' => count($roles), 'roles' => $roles]);
        } catch (Exception $e) {
            $code = ($e->getCode() === 409 || $e->getCode() === 404) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }
}
