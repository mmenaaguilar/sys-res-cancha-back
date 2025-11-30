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
    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1;
        $page = min($page, (int)$totalPages);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,
            'next_page' => $hasNextPage,
            'prev_page' => $hasPrevPage,
            'data' => $result['data']
        ];
    }
    public function listReservas(?int $usuarioId, ?int $complejoId, ?string $searchTerm, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $result = $this->reservaRepo->getReservasPaginated($usuarioId, $complejoId, $searchTerm, $limit, $offset);
        return $this->formatPaginationResponse($result, $page, $limit);
    }

    public function listReservaDetalle(int $reservaId, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $result = $this->reservaRepo->getReservaDetallePaginated($reservaId, $limit, $offset);
        return $this->formatPaginationResponse($result, $page, $limit);
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
    public function crearReserva(array $data): array
    {
        // Validación básica
        if (!isset($data['usuario_id']) || !isset($data['metodo_pago_id']) || !isset($data['detalles'])) {
            throw new Exception("Datos incompletos para crear reserva.");
        }

        // Calcular total
        $total = 0;
        foreach ($data['detalles'] as $d) {
            $total += $d['precio'];
        }

        // Crear reserva (CABEZA)
        $reservaId = $this->reservaRepo->createReserva([
            'usuario_id'     => $data['usuario_id'],
            'metodo_pago_id' => $data['metodo_pago_id'],
            'total_pago'     => $total,
            'estado'         => 'confirmada']);

        // Crear DETALLES
        foreach ($data['detalles'] as $d) {
            $this->reservaRepo->addDetalle($reservaId, $d);
        }

        return [
            'reserva_id' => $reservaId,
            'total'      => $total
        ];
    }
}
