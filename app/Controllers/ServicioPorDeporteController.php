<?php
// app/Controllers/ServicioPorDeporteController.php

namespace App\Controllers;

use App\Services\ServicioPorDeporteService;
use App\Core\Helpers\ApiHelper;
use Exception;

class ServicioPorDeporteController extends ApiHelper
{
    private ServicioPorDeporteService $servicioPorDeporteService;

    public function __construct()
    {
        $this->servicioPorDeporteService = new ServicioPorDeporteService();
    }

    // RUTA: POST /api/servicio-deportes
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->servicioPorDeporteService->createAsignacion($data);
            $this->sendResponse(['id' => $newId, 'mensaje' => 'Asignación de deporte creada con éxito.'], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: PUT /api/servicio-deportes/{id} -> Método EDITAR
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $updated = $this->servicioPorDeporteService->updateAsignacion($id, $data);
            $this->sendResponse([
                'id' => $id,
                'mensaje' => $updated ? 'Asignación actualizada.' : 'No se realizaron cambios.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: POST /api/servicio-deportes/list
    public function listByFilters()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $servicioId = $data['servicio_id'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $servicioId = (empty($servicioId) || !is_numeric($servicioId)) ? null : (int)$servicioId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->servicioPorDeporteService->getDeportesPaginatedByServicio($servicioId, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: PUT /api/servicio-deportes/status/{id}
    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            $updated = $this->servicioPorDeporteService->changeServicioPorDeportetatus($id);
            $this->sendResponse([
                'servicioDeporte_id' => $id,
                'mensaje' => $updated ? 'Estado de servicio por deporte cambiado.' : 'Error al cambiar estado.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: DELETE /api/servicio-deportes/{id}
    public function delete(int $id)
    {
        $data = $this->initRequest('DELETE');

        try {
            $this->servicioPorDeporteService->deleteAsignacion($id);
            $this->sendResponse([
                'id' => $id,
                'mensaje' => 'Asignación de deporte eliminada.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
