<?php

namespace App\Services\ExternalServices;

use Exception;

class IzipayService
{
    private string $merchantId;
    private string $keyTest;
    private string $endpoint;

    public function __construct()
    {
        // Configuración desde .env
        $this->merchantId = $_ENV['IZIPAY_MERCHANT_ID'];
        $this->keyTest = $_ENV['IZIPAY_API_KEY'];
        $this->endpoint = "https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment";
    }

    /**
     * Genera un "Payment Token" para enviar al frontend.
     * @param array $data ['monto_total'=>float, 'email'=>string, 'reserva_id'=>int, 'callbackUrl'=>string]
     */
    public function crearIntentoPago(array $data): array
    {
        $payload = [
            "amount"   => intval($data['monto_total'] * 100), // Izipay usa centavos
            "currency" => "PEN",
            "orderId"  => "RES-" . $data['reserva_id'],
            "customer" => [
                "email" => $data["email"] ?? "no-email@cliente.com"
            ],
            "callbackUrl" => $data["callbackUrl"] ?? null // endpoint webhook
        ];

        $json = json_encode($payload);

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_USERPWD, $this->merchantId . ":" . $this->keyTest);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new Exception("Error conectando con Izipay.");
        }

        $decoded = json_decode($response, true);

        if (!isset($decoded["answer"]["formToken"])) {
            throw new Exception("No se pudo generar un token de pago Izipay.");
        }

        return [
            "transaction_token" => $decoded["answer"]["formToken"],
            "orderId" => $payload["orderId"]
        ];
    }

    /**
     * Valida la respuesta enviada por Izipay al backend (webhook).
     * @param array $payload POST enviado por Izipay al callbackUrl
     */
    public function validarRespuesta(array $payload): array
    {
        if (!isset($payload['status'])) {
            throw new Exception("Respuesta inválida de Izipay.");
        }

        return [
            "status" => $payload["status"], // SUCCESS, FAILED, CANCELED
            "transactionId" => $payload["transactionId"] ?? null,
            "amount" => $payload["amount"] ?? 0
        ];
    }
}
