<?php
// app/Controllers/ContactoController.php

namespace App\Controllers;

use App\Services\ContactoService;
use Exception;

class ContactoController
{
    private ContactoService $contactoService;

    public function __construct()
    {
        $this->contactoService = new ContactoService();
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


    /**
     * [READ - LISTAR] Obtiene contactos por complejo_id (POST /api/contactos/list)
     */
    public function listByComplejo()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $complejoId = $data['complejo_id'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            // Saneamiento de parámetros
            $complejoId = (empty($complejoId) || !is_numeric($complejoId) || $complejoId <= 0) ? null : (int)$complejoId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->contactoService->getContactosPaginatedByComplejo($complejoId, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CREATE] Crea un nuevo contacto (POST /api/contactos)
     */
    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $newId = $this->contactoService->createContact($data);
            $this->sendResponse(['contacto_id' => $newId], 201); // 201 Created
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [UPDATE] Edita un contacto existente (PUT /api/contactos/{id})
     */
    public function update(int $id)
    {
        $data = $this->initRequest('PUT');
        if ($data === null) return;

        try {
            $this->contactoService->updateContact($id, $data);
            $this->sendResponse(['contacto_id' => $id, 'mensaje' => 'Contacto actualizado con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [DELETE] Elimina físicamente un contacto (DELETE /api/contactos/{id})
     */
    public function delete(int $id)
    {
        $this->initRequest('DELETE');

        try {
            $deleted = $this->contactoService->deleteContact($id);
            if (!$deleted) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Contacto no encontrado o ya eliminado.']);
                return;
            }
            $this->sendResponse(['contacto_id' => $id, 'mensaje' => 'Contacto eliminado físicamente con éxito.']);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * [CHANGE STATUS] Cambia el estado (PUT /api/contactos/status/{id})
     */
    public function changeStatus(int $id)
    {
        $this->initRequest('PUT');

        try {
            $result = $this->contactoService->changeStatus($id);
            $this->sendResponse(['contacto_id' => $id, 'nuevo_estado' => $result['nuevo_estado'], 'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
