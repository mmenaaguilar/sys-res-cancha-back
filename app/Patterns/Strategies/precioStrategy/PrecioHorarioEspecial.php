<?php

namespace App\Patterns\Strategies\precioStrategy;

use App\Repositories\HorarioEspecialRepository;

class PrecioHorarioEspecial implements PrecioStrategy
{
    private HorarioEspecialRepository $horarioEspecialRepo;

    public function __construct()
    {
        $this->horarioEspecialRepo = new HorarioEspecialRepository();
    }

    public function calcularMonto(int $canchaId, string $fecha, string $horaInicio, string $horaFin): float
    {
        $horarioEspecial = $this->horarioEspecialRepo->getDisponibleByCanchaYFecha(
            $canchaId,
            $fecha, 
            $horaInicio,
            $horaFin
        );

        if ($horarioEspecial && !empty($horarioEspecial['monto'])) {
            return (float)$horarioEspecial['monto'];
        }

        return -1; 
    }
}
