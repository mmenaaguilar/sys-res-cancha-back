<?php

namespace App\Controllers;

use App\Core\Helpers\ApiHelper;
use App\Services\HorarioBaseService;
use Exception;

class HorarioBaseController extends ApiHelper
{
    private HorarioBaseService $service;

    public function __construct()
    {
        $this->service = new HorarioBaseService();
    }

    /**
     * Listado paginado
     */
    public function getPaginated()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $result = $this->service->getPaginated($data);
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
            $this->sendResponse(['horario_base_id' => $id]);
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
            $updated = $this->service->update($id, $data);
            $this->sendResponse([
                'horario_base_id' => $id,
                'updated' => $updated
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Eliminar
     */
    public function delete(int $id)
    {
        try {
            $this->service->delete($id);
            $this->sendResponse(['deleted' => true]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
    public function changeStatus(int $id)
    {
        $data = $this->initRequest('PUT');

        try {
            $result = $this->service->changeStatus($id);
            $this->sendResponse([
                'contacto_id' => $id,
                'nuevo_estado' => $result['nuevo_estado'],
                'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
    public function cloneByDia(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $canchaId = (int)($input['cancha_id'] ?? 0);
            $fromDia = $input['from_dia'] ?? '';
            $toDia = $input['to_dia'] ?? '';

            $clonados = $this->service->cloneByDia($canchaId, $fromDia, $toDia);

            echo json_encode([
                'success' => true,
                'message' => 'Horarios clonados correctamente.',
                'clonados_ids' => $clonados
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
