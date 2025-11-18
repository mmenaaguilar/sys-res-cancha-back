<?php
// app/Controllers/ServicioPorDeporteController.php

namespace App\Controllers;

use App\Services\ServicioPorDeporteService;
use Exception;

class ServicioPorDeporteController
{
    private ServicioPorDeporteService $servicioPorDeporteService;

    public function __construct()
    {
        $this->servicioPorDeporteService = new ServicioPorDeporteService();
    }


    // --- Helper para leer JSON y setear headers ---
    private function initRequest(string $method): ?array
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
            exit; // <-- ¡Añadir exit aquí!
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Este bloque maneja el error 'Formato JSON inválido.'
        if (($method === 'POST' || $method === 'PUT') && json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Formato JSON inválido.']);
            exit; // <-- ¡Añadir exit aquí!
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
            // El Controller llama al servicio con los datos que llegaron
            $updated = $this->servicioPorDeporteService->updateAsignacion($id, $data);
            $this->sendResponse(['id' => $id, 'mensaje' => $updated ? 'Asignación actualizada.' : 'No se realizaron cambios.'], 200);
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

    public function changeStatus(int $id)
    {
        // 1. Inicializar la solicitud
        $data = $this->initRequest('PUT');

        // 2. Manejar el error de JSON aquí (si initRequest devuelve nuestro marcador especial)
        if (isset($data['json_error'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $data['json_error']]);
            return; // <<-- ¡IMPORTANTE! Detener la ejecución del controlador aquí.
        }

        // Si no hay body o está vacío, $data será [] o null. 
        // Para changeStatus, realmente no necesitamos el body, así que podemos ignorarlo.

        try {
            $updated = $this->servicioPorDeporteService->changeServicioPorDeportetatus($id);
            $this->sendResponse(['servicioDeporte_id' => $id, 'mensaje' => $updated ? 'Estado de servicio por deporte cambiado.' : 'Error al cambiar estado.'], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    // RUTA: DELETE /api/servicio-deportes/{id}
    public function delete(int $id)
    {
        $this->initRequest('DELETE');
        try {
            $this->servicioPorDeporteService->deleteAsignacion($id);
            $this->sendResponse(['id' => $id, 'mensaje' => 'Asignación de deporte eliminada.'], 200);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
