<?php

namespace App\Patterns\Strategies\cancelacionStrategy;

class CancelacionContext
{
    private ?CancelacionStrategy $strategy = null;

    public function setStrategy(CancelacionStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function ejecutar(array $reserva, array $politica): array
    {
        if (!$this->strategy) {
            throw new \Exception("No se ha definido una estrategia de cancelación.");
        }

        return $this->strategy->ejecutar($reserva, $politica);
    }
}
