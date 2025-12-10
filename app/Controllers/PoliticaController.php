<?php
// app/Controllers/PoliticaController.php

namespace App\Controllers;

use App\Services\PoliticaService;
use App\Core\Helpers\ApiHelper;
use Exception;
use PDOException; 

class PoliticaController extends ApiHelper
{
    private PoliticaService $politicaService;

    public function __construct()
    {
        $this->politicaService = new PoliticaService();
    }

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
            // Mantener la lógica específica para código 409
            $code = ($e->getCode() === 409) ? 409 : 400;
            $this->sendError($e, $code);
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
        $data = $this->initRequest('DELETE');

        try {
            $deleted = $this->politicaService->deletePolicy($id);
            if (!$deleted) {
                $this->sendError('Política no encontrada o ya eliminada.', 404);
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
        $data = $this->initRequest('PUT');

        try {
            $result = $this->politicaService->toggleStatus($id);
            $this->sendResponse([
                'politica_id' => $id,
                'nuevo_estado' => $result['nuevo_estado'],
                'mensaje' => "Estado cambiado a {$result['nuevo_estado']}."
            ]);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
