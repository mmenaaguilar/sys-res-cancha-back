<?php

namespace App\Controllers;

use App\Core\Helpers\ApiHelper;
use App\Services\ReservaService;
use Exception;

class ReservaController extends ApiHelper
{
    private ReservaService $service;

    public function __construct()
    {
        $this->service = new ReservaService();
    }

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
