<?php
// app/Services/ComplejoDeportivoService.php

namespace App\Services;

use App\Repositories\ComplejoDeportivoRepository;
use Exception;

class ComplejoDeportivoService
{
    private ComplejoDeportivoRepository $complejoDeportivoRepository;

    public function __construct()
    {
        $this->complejoDeportivoRepository = new ComplejoDeportivoRepository();
    }

    /**
     * Busca y obtiene complejos deportivos activos por su ubicación (Departamento, Provincia o Distrito).
     * @param string $term Término de búsqueda.
     * @return array
     */
    public function findComplejosByUbicacion(string $term): array
    {
        $term = trim($term);

        // Lógica de Negocio: Requerir un mínimo de 3 caracteres
        if (strlen($term) < 3) {
            // Devolvemos un array vacío o lanzamos una excepción si el término es muy corto
            return [];
        }

        try {
            // Llamar al Repositorio para obtener los resultados
            return $this->complejoDeportivoRepository->searchByUbicacion($term);
        } catch (Exception $e) {
            // Podrías loguear el error aquí
            throw new Exception("Fallo en la lógica de búsqueda del complejo: " . $e->getMessage());
        }
    }
}
