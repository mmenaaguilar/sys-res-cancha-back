<?php
// app/Controllers/ServicioPorHorarioController.php

namespace App\Controllers;

use App\Services\ServicioPorHorarioService;
use App\Core\Helpers\ApiHelper;
use Exception;

// Asumo que ApiHelper es tu BaseController
class ServicioPorHorarioController extends ApiHelper
{
    private ServicioPorHorarioService $servicioPorHorarioService; // Cambio de propiedad

    public function __construct()
    {
        $this->servicioPorHorarioService = new ServicioPorHorarioService(); // Cambio de instanciación
    }

    // RUTA: POST /api/servicio-horarios
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->servicioPorHorarioService->createAsignacion($data);
            $this->sendResponse(['id' => $newId, 'mensaje' => 'Asignación de horario creada con éxito.'], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: PUT /api/servicio-horarios/{id} -> Método EDITAR
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $updated = $this->servicioPorHorarioService->updateAsignacion($id, $data);
            $this->sendResponse([
                'id' => $id,
                'mensaje' => $updated ? 'Asignación de horario actualizada.' : 'No se realizaron cambios.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: POST /api/servicio-horarios/list
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

            // Llamada al método renombrado
            $list = $this->servicioPorHorarioService->getHorariosPaginatedByServicio($servicioId, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: PUT /api/servicio-horarios/status/{id}
    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            // Llamada al método renombrado
            $updated = $this->servicioPorHorarioService->changeServicioPorHorarioStatus($id);
            $this->sendResponse([
                'servicioHorario_id' => $id, // Cambio de nombre de clave
                'mensaje' => $updated ? 'Estado de asignación de horario cambiado.' : 'Error al cambiar estado.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: DELETE /api/servicio-horarios/{id}
    public function delete(int $id)
    {
        $data = $this->initRequest('DELETE');

        try {
            $this->servicioPorHorarioService->deleteAsignacion($id);
            $this->sendResponse([
                'id' => $id,
                'mensaje' => 'Asignación de horario eliminada.'
            ], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
