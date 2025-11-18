<?php
// app/Controllers/ServicioController.php

namespace App\Controllers;

use App\Services\ServicioService;
use Exception;

class ServicioController
{
    private ServicioService $servicioService;

    public function __construct()
    {
        $this->servicioService = new ServicioService();
    }


    // --- Helper para leer JSON y setear headers ---
    private function initRequest(string $method): ?array
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
            return null;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($method !== 'DELETE' && json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Formato JSON inválido.']);
            return null;
        }
        return $data;
    }

    private function sendResponse($data, int $code = 200)
    {
        http_response_code($code);
        echo json_encode(['success' => true, 'data' => $data]);
    }

    private function sendError(Exception $e, int $code = 400)
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    // ---------------------------------------------

    // RUTA: POST /api/servicios
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;
        try {
            $newId = $this->servicioService->createServicio($data);
            $this->sendResponse(['servicio_id' => $newId, 'mensaje' => 'Servicio creado con éxito.'], 201);
        } catch (Exception $e) {
            $this->sendError($e, $e->getCode() > 0 ? $e->getCode() : 400);
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
            $this->sendError($e, $e->getCode() > 0 ? $e->getCode() : 400);
        }
    }

    // RUTA: PUT /api/servicios/{id}
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;
        try {
            $updated = $this->servicioService->updateServicio($id, $data);
            $this->sendResponse(['servicio_id' => $id, 'mensaje' => $updated ? 'Servicio actualizado.' : 'No se realizaron cambios.'], 200);
        } catch (Exception $e) {
            $this->sendError($e, $e->getCode() > 0 ? $e->getCode() : 400);
        }
    }

    // RUTA: PUT /api/servicios/status/{id}
    public function changeStatus(int $id)
    {
        $this->initRequest('PUT');
        try {
            $updated = $this->servicioService->changeServicioStatus($id);
            $this->sendResponse(['servicio_id' => $id, 'mensaje' => $updated ? 'Estado de servicio cambiado.' : 'Error al cambiar estado.'], 200);
        } catch (Exception $e) {
            $this->sendError($e, $e->getCode() > 0 ? $e->getCode() : 400);
        }
    }

    // RUTA: DELETE /api/servicios/{id}
    public function delete(int $id)
    {
        $this->initRequest('DELETE');
        try {
            $deleted = $this->servicioService->deleteServicio($id);
            $this->sendResponse(['servicio_id' => $id, 'mensaje' => $deleted ? 'Servicio eliminado.' : 'Servicio no encontrado.'], $deleted ? 200 : 404);
        } catch (Exception $e) {
            $this->sendError($e, $e->getCode() > 0 ? $e->getCode() : 400);
        }
    }
}
