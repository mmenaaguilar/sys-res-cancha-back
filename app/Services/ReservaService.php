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
        // 1. Obtener la cabecera de la reserva
        $reserva = $this->reservaRepo->getById($id);

        if (!$reserva) {
            throw new Exception("Reserva no encontrada.");
        }

        // 2. CORRECCIÓN: Obtener los detalles para saber la FECHA y HORA
        // (La tabla Reserva no tiene fecha/hora, la tabla ReservaDetalle sí)
        $detalles = $this->reservaRepo->getDetalles($id);

        if (empty($detalles)) {
            // Si por algún motivo no tiene detalles (error de datos), cancelamos forzosamente sin validar políticas
            $this->reservaRepo->cancelarReserva($id);
            return ['mensaje' => 'Reserva cancelada (sin detalles técnicos)'];
        }

        // Tomamos el primer detalle para calcular el tiempo (asumiendo que es el más próximo)
        $detallePrincipal = $detalles[0];

        // 3. Calcular horas disponibles usando los datos del DETALLE
        $fechaHoraInicio = new \DateTime($detallePrincipal['fecha'] . ' ' . $detallePrincipal['hora_inicio']);
        $ahora = new \DateTime();
        
        // Comparar
        if ($fechaHoraInicio < $ahora) {
             throw new Exception("No se puede cancelar una reserva pasada.");
        }

        $diff = $ahora->diff($fechaHoraInicio);
        $horasDisponibles = ($diff->days * 24) + $diff->h + ($diff->i / 60);

        // 4. Obtener política usando el ID de la cancha del detalle
        $politica = $this->politicaRepo->getPoliticaMasEstricta(
            $detallePrincipal['cancha_id'], // Usamos el ID del detalle, no de la reserva
            $horasDisponibles
        );

        // 5. Si NO hay política, solo cancelamos (o aplicamos regla por defecto)
        if (!$politica) {
            $this->reservaRepo->cancelarReserva($id);
            return [
                'reserva_id' => $id,
                'resultado' => [
                    'tipo' => 'sin_politica',
                    'mensaje' => 'Cancelación exitosa sin penalidad específica.',
                ]
            ];
        }

        // 6. Aplicar estrategia si existe política
        $context = new CancelacionContext();

        switch ($politica['estrategia_temprana']) {
            case 'CreditoCompleto':
                $context->setStrategy(new CancelacionCreditoCompleto());
                break;

            case 'ReembolsoFisico':
                $context->setStrategy(new CancelacionReembolsoFisico());
                break;

            default:
                // Si la estrategia no está definida en código, procedemos a cancelar simple
                $this->reservaRepo->cancelarReserva($id);
                 return ['mensaje' => 'Estrategia desconocida, cancelación forzada realizada.'];
        }

        // Ejecutar estrategia (reembolsos, movimientos de saldo, etc.)
        $resultado = $context->ejecutar($reserva, $politica);

        // Finalmente cambiar estado en BD
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
