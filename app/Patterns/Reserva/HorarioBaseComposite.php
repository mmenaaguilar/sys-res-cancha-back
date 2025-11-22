<?php

namespace App\Patterns\Reserva;

use Exception;

/**
 * Clase HorarioBaseComposite
 * Composite que agrupa validadores como ReservaLeaf y HorarioEspecialLeaf.
 * Si cualquier hoja indica NO disponibilidad → el horario está ocupado.
 */
class HorarioBaseComposite implements ComponenteReserva
{
    /** @var ComponenteReserva[] */
    private array $componentes = [];

    /**
     * Permite agregar hojas o composites hijos.
     */
    public function agregarComponente(ComponenteReserva $componente): void
    {
        $this->componentes[] = $componente;
    }

    /**
     * En un composite, el subtotal puede ser la suma de hijos,
     * pero en este caso es irrelevante.
     */
    public function calcularSubtotal(): float
    {
        $subtotal = 0.0;

        foreach ($this->componentes as $componente) {
            $subtotal += $componente->calcularSubtotal();
        }

        return $subtotal;
    }

    /**
     * Valida disponibilidad recorriendo todas las hojas.
     * 
     * Si todas retornan TRUE → Disponible.
     * Si alguna retorna FALSE → NO Disponible.
     */
    public function validarDisponibilidad(
        int $canchaId,
        string $fechaDeseada,
        string $horaInicio,
        string $horaFin
    ): bool {

        foreach ($this->componentes as $componente) {
            $disponible = $componente->validarDisponibilidad(
                $canchaId,
                $fechaDeseada,
                $horaInicio,
                $horaFin
            );

            // Si una hoja detecta colisión → Composite retorna falso
            if (!$disponible) {
                return false;
            }
        }

        // Si ninguna hoja detectó colisiones → Disponible
        return true;
    }
}
