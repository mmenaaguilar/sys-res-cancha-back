<?php

namespace App\Patterns;

use App\Repositories\CanchaRepository;
use App\Repositories\HorarioBaseRepository;
use App\Patterns\Reserva\HorarioBaseComposite;
use App\Patterns\Reserva\ReservaLeaf;
use App\Patterns\Reserva\HorarioEspecialLeaf;

class AlquilerFacade
{
    private HorarioBaseComposite $composite;
    private CanchaRepository $canchaRepo;
    private HorarioBaseRepository $horarioRepo;

    public function __construct()
    {
        // Crear un Composite nuevo y agregar hojas
        $this->composite = new HorarioBaseComposite();
        $this->composite->agregarComponente(new ReservaLeaf());
        $this->composite->agregarComponente(new HorarioEspecialLeaf());

        // Repositorios
        $this->canchaRepo = new CanchaRepository();
        $this->horarioRepo = new HorarioBaseRepository();
    }

    public function validarDisponibilidad(array $data): array
    {
        $complejoId = intval($data['complejo_id'] ?? 0);
        $fecha      = trim($data['fecha'] ?? '');
        $horaFiltro = trim($data['hora'] ?? '');
        $tipoDeporteId = intval($data['tipoDeporte_id'] ?? -1);

        if (!$complejoId || !$fecha) {
            return [
                'success' => false,
                'message' => 'complejo_id y fecha son requeridos.'
            ];
        }

        // Día de la semana
        $dias = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];
        $diaSemana = $dias[date('w', strtotime($fecha))];

        // Canchas del complejo
        $canchas = $this->canchaRepo->getByComplejo($complejoId, $tipoDeporteId);
        $respuesta = [];

        foreach ($canchas as $cancha) {

            // Obtener horarios según horaFiltro
            $horarios = $this->horarioRepo->getHorariosByCanchaAndDia(
                $cancha['cancha_id'],
                $diaSemana,
                $horaFiltro
            );
            $listaHorarios = [];

            foreach ($horarios as $hb) {

                // Unificar formato de hora
                $horaInicio = date('H:i:s', strtotime($hb['hora_inicio']));
                $horaFin = date('H:i:s', strtotime($hb['hora_fin']));

                // Validar disponibilidad con Composite
                $disponible = $this->composite->validarDisponibilidad(
                    $cancha['cancha_id'],
                    $fecha,
                    $horaInicio,
                    $horaFin
                );

                $listaHorarios[] = [
                    'horario_base_id' => $hb['horario_base_id'],
                    'dia_semana'      => $hb['dia_semana'],
                    'hora_inicio'     => $horaInicio,
                    'hora_fin'        => $horaFin,
                    'monto'           => $hb['monto'],
                    'disponible'      => $disponible
                ];
            }

            $respuesta[] = [
                'cancha_id' => $cancha['cancha_id'],
                'nombre'    => $cancha['nombre'],
                'tipo_deporte_id' => $cancha['tipo_deporte_id'],
                'horarios'  => $listaHorarios
            ];
        }

        return [
            'success' => true,
            'data' => $respuesta
        ];
    }
}
