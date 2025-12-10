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

    public function getDepartamentos(): array
    {
        return $this->ubigeoRepository->getAllDepartamentos();
    }

    public function getProvincias(int $depId): array
    {
        return $this->ubigeoRepository->getProvinciasByDepartamentoId($depId);
    }

    public function getDistritos(int $provId): array
    {
        return $this->ubigeoRepository->getDistritosByProvinciaId($provId);
    }
    
    public function findDistritosByHierarchy(array $components, string $level): array
    {
        try {
            return $this->ubigeoRepository->searchDistritosByHierarchy($components, $level);
        } catch (Exception $e) {
            throw new Exception("Fallo en la búsqueda jerárquica de Ubigeo. Por favor, intente más tarde.");
        }
    }
}
