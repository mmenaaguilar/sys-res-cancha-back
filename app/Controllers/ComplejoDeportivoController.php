<?php

namespace App\Controllers;

use App\Services\ComplejoDeportivoService;
use App\Core\Helpers\ApiHelper;
use Exception;

class ComplejoDeportivoController extends ApiHelper
{
    private ComplejoDeportivoService $service;

    public function __construct()
    {
        $this->service = new ComplejoDeportivoService();
    }

    public function getComplejo()
    {
        try {
            // Obtener complejo_id desde el body si se envía
            $data = $this->initRequest('POST');
            if ($data === null) return;
             $complejoId = $data['complejo_id'] ?? null;


            if (empty($complejoId)) {
                $this->sendError(new Exception("El campo 'complejo_id' es requerido en el cuerpo de la solicitud."), 400);
                return;
            }

            $complejos = $this->service->getAll($complejoId);
            $this->sendResponse($complejos);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }


    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $id = $this->service->create($data);
            $this->sendResponse(['complejo_id' => $id], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->service->update($id, $data);
            $this->sendResponse(['complejo_id' => $id, 'mensaje' => 'Actualizado correctamente']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function changeStatus(int $id)
    {
        try {
            $result = $this->service->changeStatus($id);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->service->delete($id);
            $this->sendResponse(['complejo_id' => $id, 'mensaje' => 'Eliminado correctamente']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
