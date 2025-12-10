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

                // Obtener monto usando Strategy
                $montoFinal = $this->precioContext->calcularMonto(
                    $cancha['cancha_id'],
                    $fecha,
                    $horaInicio,
                    $horaFin,
                );

                $listaHorarios[] = [
                    'horario_base_id' => $hb['horario_base_id'],
                    'dia_semana'      => $hb['dia_semana'],
                    'hora_inicio'     => $horaInicio,
                    'hora_fin'        => $horaFin,
                    'monto'           => $montoFinal,
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
        // 1. Obtener día de la semana
        $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
        $timestampFecha = strtotime($fecha);
        $diaSemana = $dias[date('w', $timestampFecha)];

        // 2. Obtener Horario de Apertura (Base)
        // El 3er parámetro '' es vital para traer todos los rangos
        $horariosBase = $this->horarioRepo->getHorariosByCanchaAndDia($canchaId, $diaSemana, '');

        // 3. Obtener Reservas Ocupadas (CORREGIDO: Usamos ReservaDetalle)
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

        // 4. Armar Grilla 24h (06:00 a 24:00 o lo que prefieras)
        $grilla = [];
        $startHour = 0; 
        $endHour = 24;

        for ($h = $startHour; $h < $endHour; $h++) {
            $slotInicio = strtotime("$fecha " . sprintf("%02d:00:00", $h));
            $slotFin    = strtotime("$fecha " . sprintf("%02d:00:00", $h + 1));
            
            $estado = 'closed';
            $precio = 0;

            // A. Revisar si el complejo está ABIERTO
            foreach ($horariosBase as $base) {
                $baseInicio = strtotime("$fecha " . $base['hora_inicio']);
                $baseFin    = strtotime("$fecha " . $base['hora_fin']);
                
                // Fix para cierre a medianoche
                if ($base['hora_fin'] === '00:00:00') {
                    $baseFin = strtotime("$fecha 23:59:59");
                }

                if ($slotInicio >= $baseInicio && $slotFin <= $baseFin) {
                    $estado = 'available';
                    $precio = (float)$base['monto'];
                    break;
                }
            }

            // B. Revisar si ya está RESERVADO (Ocupado)
            if ($estado === 'available') {
                foreach ($reservas as $res) {
                    $resInicio = strtotime("$fecha " . $res['hora_inicio']);
                    $resFin    = strtotime("$fecha " . $res['hora_fin']);

                    // Si hay solapamiento de horarios
                    if ($slotInicio < $resFin && $slotFin > $resInicio) {
                        $estado = 'booked';
                        break;
                    }
                }
            }

            $grilla[] = [
                'hora'     => sprintf("%02d:00", $h),
                'hora_fin' => sprintf("%02d:00", $h + 1),
                'estado'   => $estado, // available, booked, closed
                'precio'   => $precio
            ];
        }

        return $grilla;
    }
    
    /**
     * Genera una grilla horaria para una cancha específica, utilizando los rangos 
     * de tiempo exactos definidos en los horarios base (ej. 18:00:00 a 19:30:00).
     * Aplica las lógicas de validación (Composite) y cálculo de monto (Strategy).
     * * @param int $canchaId ID de la cancha.
     * @param string $fecha Fecha (YYYY-MM-DD).
     * @return array
     */
    public function obtenerGrillaPorCancha(int $canchaId, string $fecha): array
    {
        // 1. Validar parámetros requeridos
        if (!$canchaId || !$fecha) {
            return [
                'success' => false,
                'message' => 'canchaId y fecha son requeridos.'
            ];
        }

        // 2. Determinar el día de la semana
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

        // 3. Obtener TODOS los Horarios de Apertura (Base) para la cancha y día
        // El 3er parámetro '' asegura que se obtengan todos los rangos del día.
        $horariosBase = $this->horarioRepo->getHorariosByCanchaAndDia(
            $canchaId,
            $diaSemana,
            ''
        );

        $listaHorarios = [];

        // 4. Iterar sobre cada Horario Base, que se convierte en un slot de la grilla
        foreach ($horariosBase as $hb) {

            // Unificar formato de hora para el cálculo (H:i:s)
            $horaInicio = date('H:i:s', strtotime($hb['hora_inicio']));
            $horaFin    = date('H:i:s', strtotime($hb['hora_fin']));

            // 4.1 Validar disponibilidad con Composite
            $disponible = $this->composite->validarDisponibilidad(
                $canchaId,
                $fecha,
                $horaInicio,
                $horaFin
            );

            // 4.2 Obtener monto usando Strategy
            $montoFinal = $this->precioContext->calcularMonto(
                $canchaId,
                $fecha,
                $horaInicio,
                $horaFin
            );

            // 4.3 Determinar el estado del slot
            // 'available' si está disponible, 'booked' u 'occupied' si está ocupado
            $estado = $disponible ? 'available' : 'booked';

            // 4.4 Agregar el slot a la lista
            $listaHorarios[] = [
                'hora'     => $horaInicio, // Ej: "18:00:00"
                'hora_fin'        => $horaFin,    // Ej: "19:30:00"
                'precio'           => round($montoFinal, 2),
                'estado'     => $estado // Campo adicional para el frontend
            ];
        }

        // 5. Devolver la respuesta final
        return $listaHorarios;
    }
}