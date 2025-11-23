<?php

namespace App\Patterns\Strategies\cancelacionStrategy;

use App\Repositories\CreditoUsuarioRepository;

class CancelacionCreditoCompleto implements CancelacionStrategy
{
    private CreditoUsuarioRepository $creditoRepo;

    public function __construct()
    {
        $this->creditoRepo = new CreditoUsuarioRepository();
    }

    public function ejecutar(array $reserva, array $politica): array
    {
        $monto = $reserva['total_pago'];

        $creditoId = $this->creditoRepo->crearCredito([
            'usuario_id' => $reserva['usuario_id'],
            'monto' => $monto,
            'origen_reserva_id' => $reserva['reserva_id'],
        ]);

        return [
            'tipo' => 'CreditoCompleto',
            'monto_credito' => $monto,
            'credito_id' => $creditoId
        ];
    }
}
