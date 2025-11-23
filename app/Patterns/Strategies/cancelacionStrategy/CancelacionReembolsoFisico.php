<?php

namespace App\Patterns\Strategies\cancelacionStrategy;

class CancelacionReembolsoFisico implements CancelacionStrategy
{
    public function ejecutar(array $reserva, array $politica): array
    {
        return [
            'tipo' => 'ReembolsoFisico',
            'mensaje' => 'El usuario debe recibir el reembolso en efectivo o por el medio configurado.'
        ];
    }
}
