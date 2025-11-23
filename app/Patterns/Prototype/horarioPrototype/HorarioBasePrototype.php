<?php

namespace App\Patterns\Prototype\horarioPrototype;

use App\Patterns\Prototype\horarioPrototype\PrototipoClonable;

class HorarioBasePrototype implements PrototipoClonable
{
    public int $horario_base_id;
    public int $cancha_id;
    public string $dia_semana;
    public string $hora_inicio;
    public string $hora_fin;
    public float $monto;
    public string $estado;

    public function __construct(array $data)
    {
        $this->horario_base_id = $data['horario_base_id'] ?? 0;
        $this->cancha_id       = $data['cancha_id'];
        $this->dia_semana      = $data['dia_semana'];
        $this->hora_inicio     = $data['hora_inicio'];
        $this->hora_fin        = $data['hora_fin'];
        $this->monto           = (float)$data['monto'];
        $this->estado          = $data['estado'] ?? 'activo';
    }
    public function toArray(): array
    {
        return [
            'cancha_id' => $this->cancha_id,
            'dia_semana' => $this->dia_semana,
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
            'monto' => $this->monto,
            'estado' => $this->estado,
        ];
    }

    public function clone(array $overrides = []): self
    {
        $data = [
            'horario_base_id' => 0,
            'cancha_id'       => $overrides['cancha_id'] ?? $this->cancha_id,
            'dia_semana'      => $overrides['dia_semana'] ?? $this->dia_semana,
            'hora_inicio'     => $overrides['hora_inicio'] ?? $this->hora_inicio,
            'hora_fin'        => $overrides['hora_fin'] ?? $this->hora_fin,
            'monto'           => $overrides['monto'] ?? $this->monto,
            'estado'          => $overrides['estado'] ?? $this->estado,
        ];

        return new self($data);
    }
}
