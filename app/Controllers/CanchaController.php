<?php
// app/Controllers/CanchaController.php

namespace App\Controllers;

use App\Services\CanchaService;
use App\Core\Helpers\ApiHelper;
use Exception;

class CanchaController extends ApiHelper
{
    private CanchaService $canchaService;

    public function __construct()
    {
        $this->canchaService = new CanchaService();
    }

    /**
     * [READ] Listar canchas activas por complejo.
     */
    public function listByComplejo()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;

        try {
            $complejoId = (empty($complejoId) || !is_numeric($complejoId) || $complejoId <= 0) ? null : (int)$complejoId;

            $list = $this->canchaService->getByComplejo($complejoId);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CREATE] Crear una nueva cancha.
     */
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->canchaService->createCancha($data);
            $this->sendResponse(['cancha_id' => $newId], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [UPDATE] Editar una cancha existente.
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->canchaService->updateCancha($id, $data);
            $this->sendResponse(['cancha_id' => $id, 'mensaje' => 'Cancha actualizada con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [DELETE] Eliminar físicamente una cancha.
     */
    public function delete(int $id)
    {
        $data = $this->initRequest('DELETE');

        try {
            $deleted = $this->canchaService->deleteCancha($id);
            if (!$deleted) {
                $this->sendError('Cancha no encontrada o ya eliminada.', 404);
                return;
            }
            $this->sendResponse(['cancha_id' => $id, 'mensaje' => 'Cancha eliminada físicamente con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CHANGE STATUS] Cambiar estado activo/inactivo.
     */
    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            $result = $this->canchaService->changeStatus($id);
            $this->sendResponse([
                'cancha_id' => $id,
                'nuevo_estado' => $result['nuevo_estado'],
                'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
