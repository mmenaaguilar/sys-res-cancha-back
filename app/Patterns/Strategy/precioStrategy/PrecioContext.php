<?php

namespace App\Patterns\Strategy\precioStrategy;

class PrecioContext
{
    private PrecioStrategy $strategyEspecial;
    private PrecioStrategy $strategyBase;

    public function __construct()
    {
        $this->strategyEspecial = new PrecioHorarioEspecial();
        $this->strategyBase = new PrecioHorarioBase();
    }

    /**
     * @param int $canchaId
     * @param string $fecha  Formato 'Y-m-d'
     * @param string $horaInicio Formato 'H:i:s'
     * @param string $horaFin Formato 'H:i:s'
     */
    public function calcularMonto(int $canchaId, string $fecha, string $horaInicio, string $horaFin): float
    {
        $montoEspecial = $this->strategyEspecial->calcularMonto($canchaId, $fecha, $horaInicio, $horaFin);

        if ($montoEspecial >= 0) {
            return $montoEspecial;
        }

        return $this->strategyBase->calcularMonto($canchaId, $fecha, $horaInicio, $horaFin);
    }
}
