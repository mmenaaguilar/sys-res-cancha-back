<?php

namespace App\Patterns\Strategies\cancelacionStrategy;

interface CancelacionStrategy
{
    /**
     * Ejecuta la estrategia de cancelación.
     * Debe retornar un arreglo con detalles del resultado.
     */
    public function ejecutar(array $reserva, array $politica): array;
}
