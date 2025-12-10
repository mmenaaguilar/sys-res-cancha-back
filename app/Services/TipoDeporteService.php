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
        $this->tipoDeporteRepository = new TipoDeporteRepository();
    }

    /**
     * Obtiene la lista de tipos de deporte en formato 'value'/'label'
     * para ser consumida por el frontend (combo box).
     * @return array
     */
    public function getComboList(): array
    {
        return $this->tipoDeporteRepository->getAllForCombo();
    }
}
