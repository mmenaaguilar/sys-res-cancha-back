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

    // --- Helper Methods (as seen in ContactoController) ---
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
            // El error que tuviste antes, aquí está el manejo.
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
    // --------------------------------------------------------

    /**
     * [READ - LISTAR] Lista servicios por filtros (POST /api/servicios/list)
     * Espera complejo_id y opcionalmente tipo_deporte_id en el body.
     */
    public function listByFilters()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $servicios = $this->servicioService->getServiciosByFilters($data);
            $this->sendResponse(['total' => count($servicios), 'servicios' => $servicios]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CREATE] Crea un nuevo servicio (POST /api/servicios)
     */
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->servicioService->createService($data);
            $this->sendResponse(['servicio_id' => $newId], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [UPDATE] Edita un servicio existente (PUT /api/servicios/{id})
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->servicioService->updateService($id, $data);
            $this->sendResponse(['servicio_id' => $id, 'mensaje' => 'Servicio actualizado con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [DELETE] Elimina físicamente un servicio (DELETE /api/servicios/{id})
     */
    public function delete(int $id)
    {
        $this->initRequest('DELETE'); // Leer body no es necesario

        try {
            $deleted = $this->servicioService->deleteService($id);
            if (!$deleted) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Servicio no encontrado o ya eliminado.']);
                return;
            }
            $this->sendResponse(['servicio_id' => $id, 'mensaje' => 'Servicio eliminado físicamente con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CHANGE STATUS] Cambia el estado (PUT /api/servicios/status/{id})
     */
    public function changeStatus(int $id)
    {
        $this->initRequest('PUT'); // Leer body no es necesario

        try {
            $result = $this->servicioService->changeStatus($id);
            $this->sendResponse(['servicio_id' => $id, 'nuevo_estado' => $result['nuevo_estado'], 'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
