<?php

namespace App\Services;

use App\Repositories\ReservaRepository;
use App\Repositories\PoliticaRepository;
use App\Repositories\CreditoUsuarioRepository;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionContext;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionCreditoCompleto;
use App\Patterns\Strategies\cancelacionStrategy\CancelacionReembolsoFisico;
use App\Core\Database;
use Exception;
use PDO;

class ReservaService
{
    private ReservaRepository $reservaRepo;
    private PoliticaRepository $politicaRepo;
    private CreditoUsuarioRepository $creditoRepo;
    private PDO $db;

    public function __construct()
    {
        $this->reservaRepo = new ReservaRepository();
        $this->politicaRepo = new PoliticaRepository();
        $this->creditoRepo = new CreditoUsuarioRepository();
        $this->db = Database::getConnection();
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
            $detallePrincipal['complejo_id'], // Usamos el ID del detalle, no de la reserva
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
        if (empty($data['usuario_id']) || empty($data['metodo_pago_id']) || empty($data['detalles'])) {
            throw new Exception("Datos incompletos para crear reserva.", 400);
        }

        // 0.1 Obtener el ID de crédito si existe y validar que no sea nulo o -1
        $creditoId = $data['credito_id'] ?? null;
        if ($creditoId === '0' && ($creditoId === 'null' || $creditoId === '-1' || $creditoId === null)) {
            $creditoId = null;
        }

        // 1. Calcular Total y Preparar Datos
        $total = 0;
        foreach ($data['detalles'] as $d) {
            $total += $d['precio'];
        }

        // ✅ 2. LÓGICA DE AGRUPACIÓN (MERGE) DE HORARIOS CONSECUTIVOS (Mantenida)
        $detallesOriginales = $data['detalles'];
        
        // A. Ordenar por hora de inicio para asegurar continuidad
        usort($detallesOriginales, function($a, $b) {
            return strcmp($a['hora_inicio'], $b['hora_inicio']);
        });

        $detallesAgrupados = [];
        
        foreach ($detallesOriginales as $slot) {
            if (empty($detallesAgrupados)) {
                $detallesAgrupados[] = $slot;
                continue;
            }

            $ultimoIndex = count($detallesAgrupados) - 1;
            $ultimoSlot = &$detallesAgrupados[$ultimoIndex];

            // B. Verificar continuidad
            if ($ultimoSlot['hora_fin'] === $slot['hora_inicio']) {
                $ultimoSlot['hora_fin'] = $slot['hora_fin'];
                $ultimoSlot['precio'] += $slot['precio'];
            } else {
                $detallesAgrupados[] = $slot;
            }
        }
        // ---------------------------------------------------------

        try {
            $this->db->beginTransaction();

            // 3. Crear Cabecera
            $reservaId = $this->reservaRepo->createReserva([
                'usuario_id'  => $data['usuario_id'],
                'metodo_pago_id' => $data['metodo_pago_id'],
                'total_pago'  => $total,
                'estado' => 'confirmada',
                'fecha_pago' => date('Y-m-d H:i:s')
            ]);

            // 4. Crear Detalles (Usando la lista AGRUPADA)
            foreach ($detallesAgrupados as $d) {
                $this->reservaRepo->addDetalle($reservaId, [
                    'cancha_id' => $data['cancha_id'],
                    'fecha'  => $data['fecha_reserva'],
                    'hora_inicio' => $d['hora_inicio'],
                    'hora_fin' => $d['hora_fin'],
                    'precio' => $d['precio']
                ]);
            }
            
            // 5. 🚨 LÓGICA DE CRÉDITO: Marcar el crédito como 'usado' si se utilizó
            if ($creditoId) {
                $this->creditoRepo->changeStatus((int)$creditoId, 'usado');
            }

            $this->db->commit();

            return [
                'reserva_id' => $reservaId,
                'total' => $total,
                'mensaje' => 'Reserva creada (Confirmada).'
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
