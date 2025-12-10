<?php

namespace App\Patterns;

use App\Repositories\CanchaRepository;
use App\Repositories\HorarioBaseRepository;
use App\Repositories\HorarioEspecialRepository;
use App\Repositories\ComplejoDeportivoRepository;
use App\Patterns\Composity\ComposityDisponibilidadHorario\HorarioBaseComposite;
use App\Patterns\Composity\ComposityDisponibilidadHorario\ReservaLeaf;
use App\Patterns\Composity\ComposityDisponibilidadHorario\HorarioEspecialLeaf;
use App\Patterns\Strategies\precioStrategy\PrecioContext;
use App\Core\Database;
use PDO;

class AlquilerFacade
{
    private HorarioBaseComposite $composite;
    private CanchaRepository $canchaRepo;
    private ComplejoDeportivoRepository $complejoRepo;
    private HorarioBaseRepository $horarioRepo;
    private HorarioEspecialRepository $horarioEspecialRepo;
    private PrecioContext $precioContext;

    public function __construct()
    {
        // Crear un Composite nuevo y agregar hojas
        $this->composite = new HorarioBaseComposite();
        $this->composite->agregarComponente(new ReservaLeaf());
        $this->composite->agregarComponente(new HorarioEspecialLeaf());

        // Repositorios
        $this->canchaRepo = new CanchaRepository();
        $this->complejoRepo = new ComplejoDeportivoRepository();
        $this->horarioRepo = new HorarioBaseRepository();
        $this->horarioEspecialRepo = new HorarioEspecialRepository();

        // Contexto de Strategy para calcular precios
        $this->precioContext = new PrecioContext($this->horarioEspecialRepo);
    }

     public function buscarComplejosDisponiblesPorDistrito(array $data): array
    {
        // 1. Recibimos los 3 niveles de ubicación
        $depId  = intval($data['departamento_id'] ?? 0);
        $provId = intval($data['provincia_id'] ?? 0);
        $distId = intval($data['distrito_id'] ?? 0);
        
        $fecha         = trim($data['fecha'] ?? '');
        $horaFiltro    = trim($data['hora'] ?? '');
        $tipoDeporteId = intval($data['tipoDeporte_id'] ?? -1);

        // Validación: Al menos una fecha y un nivel de ubicación (departamento mínimo)
        if (!$fecha) {
            return ['success' => false, 'message' => 'La fecha es requerida.'];
        }
        
        /*
        if ($depId === 0 && $provId === 0 && $distId === 0) {
             return ['success' => false, 'message' => 'Seleccione al menos un Departamento.'];
        }
        */

        $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
        $diaSemana = $dias[date('w', strtotime($fecha))];

        // ✅ LLAMADA AL NUEVO MÉTODO DEL REPO
        $complejos = $this->complejoRepo->getComplejosByUbicacion($depId, $provId, $distId);
        
        $respuesta = [];

        foreach ($complejos as $complejo) {
            $canchas = $this->canchaRepo->getByComplejo($complejo['complejo_id'], $tipoDeporteId);
            $complejoDisponible = false;

            foreach ($canchas as $cancha) {
                // ... (Lógica de verificación de horarios igual que antes) ...
                $horarios = $this->horarioRepo->getHorariosByCanchaAndDia($cancha['cancha_id'], $diaSemana, $horaFiltro);
                foreach ($horarios as $hb) {
                    $horaInicio = date('H:i:s', strtotime($hb['hora_inicio']));
                    $horaFin = date('H:i:s', strtotime($hb['hora_fin']));
                    if ($this->composite->validarDisponibilidad($cancha['cancha_id'], $fecha, $horaInicio, $horaFin)) {
                        $complejoDisponible = true;
                        break 2;
                    }
                }
            }

            if ($complejoDisponible) {
                $respuesta[] = [
                    'complejo_id' => $complejo['complejo_id'],
                    'nombre'      => $complejo['nombre'],
                    'url_imagen'  => $complejo['url_imagen'],
                    'url_map'     => $complejo['url_map'] ?? null,
                    'descripcion' => $complejo['descripcion'],
                    'direccion'   => $complejo['direccion_completa'],
                    'distrito_nombre' => $complejo['distrito_nombre'] ?? ''
                ];
            }
        }

        return ['success' => true, 'data' => $respuesta];
    }

public function obtenerGrillaAgenda(int $canchaId, string $fecha): array
    {
        $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
        $timestampFecha = strtotime($fecha);
        $diaSemana = $dias[date('w', $timestampFecha)];

        $horariosBase = $this->horarioRepo->getHorariosByCanchaAndDia($canchaId, $diaSemana, '');

        $db = Database::getConnection();
        $sql = "SELECT rd.hora_inicio, rd.hora_fin 
                FROM ReservaDetalle rd
                INNER JOIN Reserva r ON rd.reserva_id = r.reserva_id
                WHERE rd.cancha_id = :cid 
                  AND rd.fecha = :fecha 
                  AND r.estado IN ('pendiente_pago', 'confirmada')"; // Ignoramos canceladas
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':cid' => $canchaId, ':fecha' => $fecha]);
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grilla = [];
        $startHour = 0; 
        $endHour = 24;

        for ($h = $startHour; $h < $endHour; $h++) {
            $slotInicio = strtotime("$fecha " . sprintf("%02d:00:00", $h));
            $slotFin    = strtotime("$fecha " . sprintf("%02d:00:00", $h + 1));
            
            $estado = 'closed';
            $precio = 0;

            foreach ($horariosBase as $base) {
                $baseInicio = strtotime("$fecha " . $base['hora_inicio']);
                $baseFin    = strtotime("$fecha " . $base['hora_fin']);
                
                if ($base['hora_fin'] === '00:00:00') {
                    $baseFin = strtotime("$fecha 23:59:59");
                }

                if ($slotInicio >= $baseInicio && $slotFin <= $baseFin) {
                    $estado = 'available';
                    $precio = (float)$base['monto'];
                    break;
                }
            }

            if ($estado === 'available') {
                foreach ($reservas as $res) {
                    $resInicio = strtotime("$fecha " . $res['hora_inicio']);
                    $resFin    = strtotime("$fecha " . $res['hora_fin']);

                    if ($slotInicio < $resFin && $slotFin > $resInicio) {
                        $estado = 'booked';
                        break;
                    }
                }
            }

            $grilla[] = [
                'hora'     => sprintf("%02d:00", $h),
                'hora_fin' => sprintf("%02d:00", $h + 1),
                'estado'   => $estado, 
                'precio'   => $precio
            ];
        }

        return $grilla;
    }

    public function validarDisponibilidad(int $canchaId, string $fecha): array
    {
        if (!$canchaId || !$fecha) {
            return []; 
        }
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

        $horariosBase = $this->horarioRepo->getHorariosByCanchaAndDia(
            $canchaId,
            $diaSemana,
            ''
        );

        $listaHorarios = [];

        foreach ($horariosBase as $hb) {
            $startTs = strtotime($fecha . ' ' . $hb['hora_inicio']);
            $endTs   = strtotime($fecha . ' ' . $hb['hora_fin']);

            if ($hb['hora_fin'] === '00:00:00') {
                $endTs = strtotime($fecha . ' 23:59:59');
            }

            while ($startTs < $endTs) {
                $nextTs = strtotime('+1 hour', $startTs);
                if ($nextTs > $endTs) {
                    $nextTs = $endTs;
                }

                $horaInicioSlot = date('H:i:s', $startTs);
                $horaFinSlot    = date('H:i:s', $nextTs);

                if ($startTs >= $nextTs) break;

                $disponible = $this->composite->validarDisponibilidad(
                    $canchaId,
                    $fecha,
                    $horaInicioSlot,
                    $horaFinSlot
                );

                $montoFinal = $this->precioContext->calcularMonto(
                    $canchaId,
                    $fecha,
                    $horaInicioSlot,
                    $horaFinSlot
                );

                $estado = $disponible ? 'available' : 'booked';

                $listaHorarios[] = [
                    'hora'       => $horaInicioSlot, 
                    'hora_fin'   => $horaFinSlot,  
                    'precio'     => round($montoFinal, 2),
                    'estado'     => $estado
                ];
                $startTs = $nextTs;
            }
        }

        return $listaHorarios;
    }
}