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
     * Confirma manualmente un pago (opcional, para pruebas o pagos offline).
     */
    public function confirmarPago(int $id)
    {
        try {
            $result = $this->service->confirmarPago($id);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }

    /**
     * Webhook de Izipay.
     * Izipay llama automáticamente este endpoint cuando un usuario completa el pago.
     */
    public function izipayWebhook()
    {
        $body = $this->initRequest("POST"); // Izipay envía un POST con status, transactionId, hash, orderId, etc.
        if ($body === null) return;

        try {
            // Validar la respuesta enviada por Izipay
            $izipayService = new IzipayService();
            $respuesta = $izipayService->validarRespuesta($body);

            // Extraer reservaId desde el orderId enviado por Izipay (ej: RES-123)
            $reservaId = (int) str_replace('RES-', '', $body['orderId']);

            // Confirmar pago si fue exitoso
            if ($respuesta['status'] === 'SUCCESS') {
                $resultado = $this->service->confirmarPago($reservaId);
            } else {
                $resultado = [
                    'mensaje' => 'Pago fallido o cancelado',
                    'status' => $respuesta['status']
                ];
            }

            $this->sendResponse($resultado);
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
