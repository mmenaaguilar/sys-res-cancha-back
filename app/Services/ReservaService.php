<?php

namespace App\Services;

use App\Repositories\ReservaRepository;
use App\Repositories\PoliticaRepository;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionContext;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionCreditoCompleto;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionReembolsoFisico;
use Exception;

class ReservaService
{
    private ReservaRepository $reservaRepo;
    private PoliticaRepository $politicaRepo;

    public function __construct()
    {
        $this->reservaRepo = new ReservaRepository();
        $this->politicaRepo = new PoliticaRepository();
    }

    public function cancelarReserva(int $id): array
    {
        $reserva = $this->reservaRepo->getById($id);

        if (!$reserva) {
            throw new Exception("Reserva no encontrada.");
        }

        // Calcular horas disponibles
        $fechaHoraInicio = new \DateTime($reserva['fecha'] . ' ' . $reserva['hora_inicio']);
        $ahora = new \DateTime();
        $diff = $ahora->diff($fechaHoraInicio);

        // Si la reserva ya pasó → no permitir cancelar
        if ($diff->invert === 1) {
            throw new Exception("No se puede cancelar una reserva pasada.");
        }

        $horasDisponibles = ($diff->days * 24) + $diff->h + ($diff->i / 60);

        // Obtener política más estricta aplicable
        $politica = $this->politicaRepo->getPoliticaMasEstricta(
            $reserva['cancha_id'],
            $horasDisponibles
        );

        // 🟡 Si NO hay política → NO ERROR, aplicar retención de dinero
        if (!$politica) {

            return [
                'reserva_id' => $id,
                'resultado' => [
                    'tipo' => 'sin_politica',
                    'mensaje' => 'No se aplica ninguna política. Se retendra el dinero segun las reglas del complejo.',
                ]
            ];
        }

        // Aplicar estrategia
        $context = new CancelacionContext();

        switch ($politica['estrategia_temprana']) {
            case 'CreditoCompleto':
                $context->setStrategy(new CancelacionCreditoCompleto());
                break;

            case 'ReembolsoFisico':
                $context->setStrategy(new CancelacionReembolsoFisico());
                break;

            default:
                throw new Exception("Estrategia de cancelación desconocida.");
        }

        $resultado = $context->ejecutar($reserva, $politica);

        // Cancelar reserva en BD
        $this->reservaRepo->cancelarReserva($id);

        return [
            'reserva_id' => $id,
            'resultado' => $resultado,
            'horas_disponibles' => $horasDisponibles
        ];
    }
}
