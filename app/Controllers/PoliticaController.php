<?php
// app/Controllers/PoliticaController.php

namespace App\Controllers;

use App\Services\PoliticaService;
use Exception;
use PDOException; // Importar PDOException para manejo específico

class PoliticaController
{
    private PoliticaService $politicaService;

    public function __construct()
    {
        $this->politicaService = new PoliticaService();
    }

    // --- Helper Methods ---
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
        // Usar 409 Conflict si el error viene de la restricción UNIQUE del repositorio
        $responseCode = ($e->getCode() === 409) ? 409 : $code;
        http_response_code($responseCode);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    // ----------------------------------------------------------------------------------

    /**
     * [CREATE] Crea la política (POST /api/politicas)
     */
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->politicaService->createPolicy($data);
            $this->sendResponse(['politica_id' => $newId], 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [READ] Lista las políticas por complejo_id (POST /api/politicas/list)
     */
    public function getByComplejo()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;

        if (empty($complejoId)) {
            $this->sendError(new Exception("El campo 'complejo_id' es requerido en el cuerpo de la solicitud."), 400);
            return;
        }

        try {
            $politicas = $this->politicaService->listPoliciesByComplejo((int)$complejoId);

            $this->sendResponse(['total' => count($politicas), 'politicas' => $politicas]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [UPDATE] Edita la política (PUT /api/politicas/{id})
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->politicaService->updatePolicy($id, $data);
            $this->sendResponse(['politica_id' => $id, 'mensaje' => 'Política actualizada con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [DELETE] Elimina la política (DELETE /api/politicas/{id})
     */
    public function delete(int $id)
    {
        $this->initRequest('DELETE');

        try {
            $deleted = $this->politicaService->deletePolicy($id);
            if (!$deleted) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Política no encontrada o ya eliminada.']);
                return;
            }
            $this->sendResponse(['politica_id' => $id, 'mensaje' => 'Política eliminada físicamente con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CHANGE STATUS] Cambia el estado (PUT /api/politicas/status/{id})
     */
    public function changeStatus(int $id)
    {
        $this->initRequest('PUT');

        try {
            $result = $this->politicaService->toggleStatus($id);
            $this->sendResponse(['politica_id' => $id, 'nuevo_estado' => $result['nuevo_estado'], 'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
