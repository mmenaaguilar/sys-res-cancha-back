<?php

namespace App\Patterns\Strategies\precioStrategy;

use App\Repositories\HorarioBaseRepository;
use Exception;

class PrecioHorarioBase implements PrecioStrategy
{
    private HorarioBaseRepository $horarioBaseRepo;

    public function __construct()
    {
        $this->horarioBaseRepo = new HorarioBaseRepository();
    }

    public function calcularMonto(int $canchaId, string $fecha, string $horaInicio, string $horaFin): float
    {
        // Día de semana en inglés
        $diaIngles = date('l', strtotime($fecha));

        // Mapa inglés → español
        $map = [
            'Monday'    => 'Lunes',
            'Tuesday'   => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday'  => 'Jueves',
            'Friday'    => 'Viernes',
            'Saturday'  => 'Sábado',
            'Sunday'    => 'Domingo',
        ];

        // Convertimos el día
        $diaSemana = $map[$diaIngles] ?? null;

        if (!$diaSemana) {
            throw new Exception("No se pudo obtener el día de la semana para la fecha: $fecha");
        }

        // Buscar horario base
        $horarioBase = $this->horarioBaseRepo->getByCanchaYDia(
            $canchaId,
            $diaSemana,
            $horaInicio,
            $horaFin
        );

        if (!$horarioBase) {
            throw new Exception(
                "No existe horario base para la cancha $canchaId el día $diaSemana entre $horaInicio y $horaFin."
            );
        }

        return (float)$horarioBase['monto_total'];
    }
}
