<?php
// app/Services/UbigeoService.php

namespace App\Services;

use App\Repositories\UbigeoRepository;
use Exception;

class UbigeoService
{
    private UbigeoRepository $ubigeoRepository;

    public function __construct()
    {
        $this->ubigeoRepository = new UbigeoRepository();
    }

    /**
     * Busca distritos basándose en la jerarquía inferida de los componentes de búsqueda.
     * @param array $components Array de [Departamento, Provincia, Distrito]
     * @param string $level Nivel inferido ('departamento', 'provincia', 'distrito')
     * @return array
     */
    public function findDistritosByHierarchy(array $components, string $level): array
    {
        // El controlador ya validó el término mínimo, aquí solo orquestamos
        try {
            // Llama al Repositorio para la construcción dinámica del WHERE
            return $this->ubigeoRepository->searchDistritosByHierarchy($components, $level);
        } catch (Exception $e) {
            throw new Exception("Fallo en la búsqueda jerárquica de Ubigeo. Por favor, intente más tarde.");
        }
    }
}
