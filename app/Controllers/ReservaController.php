<?php

namespace App\Controllers;

use App\Core\Helpers\ApiHelper;
use App\Services\ReservaService;
use App\Services\ExternalServices\IzipayService;
use Exception;

class ReservaController extends ApiHelper
{
    private ReservaService $service;

    public function __construct()
    {
        $this->service = new ReservaService();
    }
    public function listReservas()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $usuarioId = $data['usuario_id'] ?? null;
        $complejoId = $data['complejo_id'] ?? null;
        $searchTerm = $data['searchTerm'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        try {
            $usuarioId = $usuarioId ? (int)$usuarioId : null;
            $complejoId = $complejoId ? (int)$complejoId : null;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->service->listReservas($usuarioId, $complejoId, $searchTerm, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $code = in_array($e->getCode(), [404, 409]) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }

    public function listReservaDetalle()
    {
        $data = $this->initRequest('POST');
        if ($data === null) return;

        $reservaId = $data['reserva_id'] ?? null;
        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;

        if (!$reservaId || !is_numeric($reservaId)) {
            $this->sendError("reserva_id es requerido y debe ser numérico.", 400);
            return;
        }

        try {
            $reservaId = (int)$reservaId;
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $list = $this->service->listReservaDetalle($reservaId, $page, $limit);
            $this->sendResponse($list);
        } catch (Exception $e) {
            $code = in_array($e->getCode(), [404, 409]) ? $e->getCode() : 400;
            $this->sendError($e, $code);
        }
    }
    /**
     * Crea una nueva reserva.
     */
    public function crear()
    {
        $body = $this->initRequest("POST");
        if ($body === null) return;

        try {
            $result = $this->service->crearReserva($body);
            $this->sendResponse($result, 201);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
    /**
     * Cancela una reserva según política de cancelación.
     */
    public function cancelar(int $id)
    {
        try {
            $result = $this->service->cancelarReserva($id);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}
