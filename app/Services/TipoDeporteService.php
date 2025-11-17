<?php
// app/Services/TipoDeporteService.php

namespace App\Services;

use App\Repositories\TipoDeporteRepository;
use Exception;

class TipoDeporteService
{
    private TipoDeporteRepository $tipoDeporteRepository;

    public function __construct()
    {
        // Instancia el Repositorio
        $this->tipoDeporteRepository = new TipoDeporteRepository();
    }

    /**
     * Obtiene la lista de tipos de deporte en formato 'value'/'label'
     * para ser consumida por el frontend (combo box).
     * @return array
     */
    public function getComboList(): array
    {
        // Se podría agregar lógica de caché o filtros aquí si fuera necesario
        return $this->tipoDeporteRepository->getAllForCombo();
    }
}
