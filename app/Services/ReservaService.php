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

        // 0. Datos del crédito
        $creditoId = $data['credito_id'] ?? null;
        if ($creditoId === '0' || $creditoId === 'null' || $creditoId === '-1' || $creditoId === 0) {
            $creditoId = null;
        }
        $montoCredito = isset($data['monto_credito']) ? floatval($data['monto_credito']) : 0;

        // 1. Calcular Subtotal (Precio Original)
        $subtotal = 0;
        foreach ($data['detalles'] as $d) {
            $subtotal += $d['precio'];
        }

        // 2. Calcular Total Final Real (Aplicando descuento)
        $totalPagarReal = $subtotal;
        if ($creditoId && $montoCredito > 0) {
            $descuentoAplicable = min($subtotal, $montoCredito);
            $totalPagarReal = $subtotal - $descuentoAplicable;
        }
        
        // Evitar negativos o errores de punto flotante pequeños
        $totalPagarReal = max(0, round($totalPagarReal, 2));

        // -----------------------------------------------------------
        // 3. CALCULAR FACTOR DE PRORRATEO
        // Esto sirve para reducir proporcionalmente el precio de cada detalle
        // Si el subtotal era 100 y pagan 80, el factor es 0.8
        // -----------------------------------------------------------
        $factorDescuento = ($subtotal > 0) ? ($totalPagarReal / $subtotal) : 1;


        // 4. Lógica de Agrupación (Merge)
        $detallesOriginales = $data['detalles'];
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

            if ($ultimoSlot['hora_fin'] === $slot['hora_inicio']) {
                $ultimoSlot['hora_fin'] = $slot['hora_fin'];
                $ultimoSlot['precio'] += $slot['precio'];
            } else {
                $detallesAgrupados[] = $slot;
            }
        }

        try {
            $this->db->beginTransaction();

            // 5. Crear Cabecera (Reserva)
            $reservaId = $this->reservaRepo->createReserva([
                'usuario_id'     => $data['usuario_id'],
                'metodo_pago_id' => $data['metodo_pago_id'],
                'total_pago'     => $totalPagarReal, // Precio ya descontado
                'estado'         => 'confirmada',
                'fecha_pago'     => date('Y-m-d H:i:s')
            ]);

            // 6. Crear Detalles (APLICANDO EL FACTOR DE DESCUENTO)
            foreach ($detallesAgrupados as $d) {
                
                // Aquí aplicamos el descuento a cada item individualmente
                $precioOriginalItem = floatval($d['precio']);
                $precioConDescuentoItem = $precioOriginalItem * $factorDescuento;

                $this->reservaRepo->addDetalle($reservaId, [
                    'cancha_id'   => $data['cancha_id'],
                    'fecha'       => $data['fecha_reserva'],
                    'hora_inicio' => $d['hora_inicio'],
                    'hora_fin'    => $d['hora_fin'],
                    // Guardamos el precio reducido en el detalle también
                    'precio'      => round($precioConDescuentoItem, 2) 
                ]);
            }
            
            // 7. Actualizar estado del crédito
            if ($creditoId) {
                $this->creditoRepo->changeStatus((int)$creditoId, 'usado');
            }

            $this->db->commit();

            return [
                'reserva_id' => $reservaId,
                'total'      => $totalPagarReal,
                'mensaje'    => 'Reserva creada con éxito.'
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
