<?php

namespace App\Controllers;

use App\Services\HorarioEspecialService;
use App\Core\Helpers\ApiHelper;
use Exception;

class HorarioEspecialController extends ApiHelper
{
    private HorarioEspecialService $service;

    public function __construct()
    {
        $this->service = new HorarioEspecialService();
    }

    /**
     * LISTAR con paginación y filtros
     * POST /api/horario-especial/list
     */
    public function list()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $canchaId = $data['cancha_id'] ?? null;
        $searchTerm = $data['termino_busqueda'] ?? null;
        $fecha = $data['fecha'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $result = $this->service->getPaginated($canchaId, $searchTerm, $fecha, $page, $limit);

            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Crear
     */
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $id = $this->service->create($data);
            $this->sendResponse(['horario_especial_id' => $id], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Actualizar
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->service->update($id, $data);
            $this->sendResponse(['horario_especial_id' => $id, 'mensaje' => 'Actualizado correctamente']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Eliminar
     */
    public function delete(int $id)
    {
        $this->initRequest('DELETE');

        try {
            $ok = $this->service->delete($id);
            if (!$ok) {
                $this->sendError("Horario especial no encontrado", 404);
                return;
            }

            $this->sendResponse([
                'horario_especial_id' => $id,
                'mensaje' => 'Horario especial eliminado correctamente'
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Cambiar estado
     */
    public function changeStatus(int $id)
    {
        

        try {
            $result = $this->service->changeStatus($id);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
