<?php

namespace App\Controllers;

use App\Services\ComplejoDeportivoFavoritoService;
use App\Core\Helpers\ApiHelper;
use Exception;

class ComplejoDeportivoFavoritoController extends ApiHelper
{
    private ComplejoDeportivoFavoritoService $service;

    public function __construct()
    {
        $this->service = new ComplejoDeportivoFavoritoService();
    }

    public function create()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        try {
            $id = $this->service->create($data);
            $this->sendResponse(['favorito_id' => $id, 'mensaje' => 'Favorito agregado con éxito.'], 201);
        } catch (Exception $e) {
            $code = $e->getCode() === 409 ? 409 : 400;
            $this->sendError($e, $code);
        }
    }

    public function delete(int $id)
    {
        try {
            $deleted = $this->service->delete($id);
            if (!$deleted) {
                $this->sendError('Favorito no encontrado o ya eliminado.', 404);
                return;
            }
            $this->sendResponse(['favorito_id' => $id, 'mensaje' => 'Favorito eliminado con éxito.']);
        } catch (Exception $e) {
            $code = $e->getCode() === 409 ? 409 : 400;
            $this->sendError($e, $code);
        }
    }

    public function getById(int $id)
    {
        try {
            $favorito = $this->service->getById($id);
            if (!$favorito) {
                $this->sendError('Favorito no encontrado.', 404);
                return;
            }
            $this->sendResponse($favorito);
        } catch (Exception $e) {
            $this->sendError($e, 400);
        }
    }

    public function listByUsuario()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $usuarioId = $data['usuario_id'] ?? null;
        $searchTerm = $data['searchTerm'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $usuarioId = $usuarioId ? (int)$usuarioId : null;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->service->listByUsuario($usuarioId, $searchTerm, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $this->sendError($e, 400);
        }
    }
}
