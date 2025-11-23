<?php

namespace App\Patterns\Strategies\precioStrategy;

interface PrecioStrategy
{
    /**
     * Devuelve el monto de la cancha según la estrategia.
     *
     * @param int $canchaId
     * @param string $fecha Formato 'Y-m-d'
     * @param string $horaInicio Formato 'H:i:s'
     * @param string $horaFin Formato 'H:i:s'
     */
    public function calcularMonto(int $canchaId, string $fecha, string $horaInicio, string $horaFin): float;
}
