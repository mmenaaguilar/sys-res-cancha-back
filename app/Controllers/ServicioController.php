<?php
// app/Controllers/ServicioController.php

namespace App\Controllers;

use App\Services\ServicioService;
use App\Core\Helpers\ApiHelper;
use Exception;

class ServicioController extends ApiHelper
{
    private ServicioService $servicioService;

    public function __construct()
    {
        $this->servicioService = new ServicioService();
    }

    // RUTA: POST /api/servicios
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->servicioService->createServicio($data);
            $this->sendResponse(['servicio_id' => $newId, 'mensaje' => 'Servicio creado con éxito.'], 201);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    // RUTA: POST /api/servicios/list
    public function listByFilters()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;
        $searchTerm = $data['termino_busqueda'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            // Saneamiento de parámetros
            $complejoId = (empty($complejoId) || !is_numeric($complejoId)) ? null : (int)$complejoId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->servicioService->getServiciosPaginatedByFilters($complejoId, $searchTerm, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    // RUTA: PUT /api/servicios/{id}
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $updated = $this->servicioService->updateServicio($id, $data);
            $this->sendResponse([
                'servicio_id' => $id,
                'mensaje' => $updated ? 'Servicio actualizado.' : 'No se realizaron cambios.'
            ], 200);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    // RUTA: PUT /api/servicios/status/{id}
    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            $updated = $this->servicioService->changeServicioStatus($id);
            $this->sendResponse([
                'servicio_id' => $id,
                'mensaje' => $updated ? 'Estado de servicio cambiado.' : 'Error al cambiar estado.'
            ], 200);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    // RUTA: DELETE /api/servicios/{id}
    public function delete(int $id)
    {
        $data = $this->initRequest('DELETE');

        try {
            $deleted = $this->servicioService->deleteServicio($id);
            if (!$deleted) {
                $this->sendError('Servicio no encontrado.', 404);
                return;
            }
            $this->sendResponse([
                'servicio_id' => $id,
                'mensaje' => 'Servicio eliminado.'
            ], 200);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }
}
