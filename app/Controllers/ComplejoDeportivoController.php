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

    public function show(int $id)
    {
        try {

            $complejo = $this->service->getById($id);
            $this->sendResponse($complejo);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function getComplejo()
    {
        try {
            $data = $this->initRequest('POST');
            if ($data === null) return;
            $usuarioId = $data['usuario_id'] ?? null;
            $searchTerm = $data['termino_busqueda'] ?? null;
            $page = $data['page'] ?? 1;
            $limit = $data['limit'] ?? 10;

            if (empty($usuarioId)) {
                $this->sendError(new Exception("El usuario es requerido."), 400);
                return;
            }
            $usuarioId = (empty($usuarioId) || !is_numeric($usuarioId)) ? null : (int)$usuarioId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $complejos = $this->service->getAll($usuarioId, $searchTerm, $page, $limit);
            $this->sendResponse($complejos);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

 public function create()
    {
        try {
            $data = $this->initRequest('POST');

            if (empty($data)) {
                throw new Exception("No se recibieron datos para crear el complejo.");
            }

            // 3. Obtener el archivo desde 
            $file = $_FILES['cFile'] ?? $_FILES['imagen'] ?? null; 

            $id = $this->service->create($data, $file);
            
            $this->sendResponse(['complejo_id' => $id], 201);

        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    public function update(int $id)
    {
        try {

            $data = $this->initRequest('PUT'); 
            
            if (empty($data)) {
                 
                 $data = $this->initRequest('POST');
            }

            if ($data === null || empty($data)) {
                throw new Exception("No se recibieron datos para actualizar el complejo.");
            }

            // Obtener el archivo subido
            $file = $_FILES['cFile'] ?? $_FILES['imagen'] ?? null;

            $this->service->update($id, $data, $file);
            
            $this->sendResponse([
                'complejo_id' => $id,
                'mensaje' => 'Actualizado correctamente'
            ]);
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

    public function getUbicaciones()
    {
        try {
            $data = $this->service->getUbicacionesDisponibles();
            $this->sendResponse($data);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
