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
            $data = $this->initRequest('POST');
            if ($data === null) return;
            $complejoId = $data['complejo_id'] ?? null;
            if (empty($complejoId)) {
                $this->sendError(new Exception("El campo 'complejo_id' es requerido."), 400);
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
        $file = $_FILES['imagen'] ?? null;

        try {
            $id = $this->service->create($data, $file);
            $this->sendResponse(['complejo_id' => $id], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;
        $file = $_FILES['imagen'] ?? null;

        try {
            $this->service->update($id, $data, $file);
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
