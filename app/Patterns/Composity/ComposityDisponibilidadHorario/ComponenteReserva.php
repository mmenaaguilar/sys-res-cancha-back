<?php

namespace App\Patterns\Composity\ComposityDisponibilidadHorario;

/**
 * Interface ComponenteReserva
 * Define la interfaz común para todos los componentes de reserva.
 */
interface ComponenteReserva
{
    /**
     * Calcula el subtotal del componente de reserva.
     * @return float
     */
    public function calcularSubtotal(): float;

    /**
     * Valida la disponibilidad para una franja horaria en una cancha específica.
     *
     * @param int $canchaId ID de la cancha a verificar.
     * @param string $fechaDeseada Fecha de la potencial reserva (formato Y-m-d).
     * @param string $horaInicio Hora de inicio del intervalo (H:i:s).
     * @param string $horaFin Hora de fin del intervalo (H:i:s).
     * @return bool True si está disponible, False si hay colisión o no aplica.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool;
}
