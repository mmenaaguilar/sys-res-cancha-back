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
        $distritoId    = intval($data['distrito_id'] ?? 0);
        $fecha         = trim($data['fecha'] ?? '');
        $horaFiltro    = trim($data['hora'] ?? '');
        $tipoDeporteId = intval($data['tipoDeporte_id'] ?? -1);

        if (!$distritoId || !$fecha) {
            return [
                'success' => false,
                'message' => 'distrito_id y fecha son requeridos.'
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

        // Obtener complejos activos en el distrito
        $complejos = $this->complejoRepo->getComplejosByDistrito($distritoId);

        $respuesta = [];

        foreach ($complejos as $complejo) {
            // Canchas del complejo filtradas por tipo de deporte
            $canchas = $this->canchaRepo->getByComplejo($complejo['complejo_id'], $tipoDeporteId);
            $complejoDisponible = false;

            foreach ($canchas as $cancha) {
                $horarios = $this->horarioRepo->getHorariosByCanchaAndDia(
                    $cancha['cancha_id'],
                    $diaSemana,
                    $horaFiltro
                );

                foreach ($horarios as $hb) {
                    $horaInicio = date('H:i:s', strtotime($hb['hora_inicio']));
                    $horaFin = date('H:i:s', strtotime($hb['hora_fin']));

                    if ($this->composite->validarDisponibilidad(
                        $cancha['cancha_id'],
                        $fecha,
                        $horaInicio,
                        $horaFin
                    )) {
                        $complejoDisponible = true;
                        break 2; // al encontrar al menos una cancha disponible, salir
                    }
                }
            }

            if ($complejoDisponible) {
                $respuesta[] = [
                    'complejo_id' => $complejo['complejo_id'],
                    'nombre'      => $complejo['nombre'],
                    'url_imagen'  => $complejo['url_imagen'],
                    'descripcion' => $complejo['descripcion'],
                    'direccion'   => $complejo['direccion_completa']
                ];
            }
        }
        

        return [
            'success' => true,
            'data' => $respuesta
        ];
    }

    public function obtenerGrillaAgenda(int $canchaId, string $fecha): array
    {
        // 1. Obtener día de la semana
        $dias = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
        $diaSemana = $dias[date('w', strtotime($fecha))];

        // 2. Obtener Horario Base (Apertura/Cierre)
        // Usamos el repositorio existente. Si no hay horario, asumimos cerrado.
        $horariosBase = $this->horarioRepo->getHorariosByCanchaAndDia($canchaId, $diaSemana, '');

        // 3. Obtener Reservas Existentes del día (Ocupado)
        // Hacemos una consulta directa para asegurar eficiencia
        $db = \App\Core\Database::getConnection();
        $sql = "SELECT hora_inicio, hora_fin FROM Reserva 
                WHERE cancha_id = :cid 
                  AND fecha_reserva = :fecha 
                  AND estado IN ('confirmada', 'pendiente', 'pagada')"; // Ajusta estados según tu lógica
        $stmt = $db->prepare($sql);
        $stmt->execute([':cid' => $canchaId, ':fecha' => $fecha]);
        $reservas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 4. Construir Grilla (de 06:00 a 24:00)
        $grilla = [];
        $startHour = 6;
        $endHour = 24;

        for ($h = $startHour; $h < $endHour; $h++) {
            $horaStr = sprintf("%02d:00:00", $h);
            $horaFinStr = sprintf("%02d:00:00", $h + 1);
            
            $estado = 'closed'; // Por defecto cerrado
            $precio = 0;

            // A. Verificar si está dentro del horario base (Abierto)
            foreach ($horariosBase as $base) {
                if ($horaStr >= $base['hora_inicio'] && $horaFinStr <= $base['hora_fin']) {
                    $estado = 'available';
                    $precio = (float)$base['monto'];
                    break;
                }
            }

            // B. Verificar si hay conflicto con reservas (Ocupado)
            if ($estado === 'available') {
                foreach ($reservas as $res) {
                    // Si la hora se solapa con una reserva
                    // Lógica de solapamiento: (StartA < EndB) && (EndA > StartB)
                    if ($horaStr < $res['hora_fin'] && $horaFinStr > $res['hora_inicio']) {
                        $estado = 'booked';
                        break;
                    }
                }
            }

            $grilla[] = [
                'hora' => sprintf("%02d:00", $h),
                'hora_fin' => sprintf("%02d:00", $h + 1),
                'estado' => $estado, // available, booked, closed
                'precio' => $precio
            ];
        }

        return $grilla;
    }
}
