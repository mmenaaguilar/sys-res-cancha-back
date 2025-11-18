<?php
// app/Services/ServicioPorDeporteService.php

namespace App\Services;

use App\Repositories\ServicioPorDeporteRepository;
use Exception;

class ServicioPorDeporteService
{
    private ServicioPorDeporteRepository $servicioPorDeporteRepository;
    // Asumo que tienes un ServicioService para verificar si existe el servicio
    // private ServicioService $servicioService; 

    public function __construct()
    {
        $this->servicioPorDeporteRepository = new ServicioPorDeporteRepository();
        // $this->servicioService = new ServicioService(); 
    }

    // --- Helper de Paginación ---
    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1; // Si no hay datos, hay 1 página vacía.

        // Asegurar que la página actual no es mayor al total de páginas
        $page = min($page, (int)$totalPages);

        // Calcular next_page y prev_page
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,

            // ¡Nuevos campos booleanos!
            'next_page' => $hasNextPage,
            'prev_page' => $hasPrevPage,

            'data' => $result['data']
        ];
    }

    // --- CREATE (Asignación) ---
    public function createAsignacion(array $data): int
    {
        if (empty($data['servicio_id']) || empty($data['tipo_deporte_id'])) {
            throw new Exception("IDs de servicio y tipo de deporte son requeridos.", 400);
        }
        // NOTA: Se asume que la verificación de existencia del Servicio y TipoDeporte se hace aquí o en el controlador.
        return $this->servicioPorDeporteRepository->create($data);
    }

    // --- UPDATE (Edición) ---
    public function updateAsignacion(int $id, array $data): bool
    {
        if (!$this->servicioPorDeporteRepository->getById($id)) {
            throw new Exception("Asignación no encontrada.", 404);
        }

        $dataToUpdate = [];
        $isUpdated = false;

        // 1. Validar y capturar 'estado'
        if (isset($data['estado'])) {
            if (!in_array($data['estado'], ['activo', 'inactivo'])) {
                throw new Exception("El campo 'estado' debe ser 'activo' o 'inactivo'.", 400);
            }
            $dataToUpdate['estado'] = $data['estado'];
            $isUpdated = true;
        }

        // 2. Validar y capturar 'tipo_deporte_id'
        if (isset($data['tipo_deporte_id'])) {
            if (!is_numeric($data['tipo_deporte_id']) || (int)$data['tipo_deporte_id'] <= 0) {
                throw new Exception("El 'tipo_deporte_id' es inválido.", 400);
            }
            // Opcional: Validar que el nuevo tipo_deporte_id existe en la tabla TipoDeporte
            $dataToUpdate['tipo_deporte_id'] = (int)$data['tipo_deporte_id'];
            $isUpdated = true;
        }

        if (!$isUpdated) {
            throw new Exception("Se requiere al menos 'estado' o 'tipo_deporte_id' para actualizar.", 400);
        }

        return $this->servicioPorDeporteRepository->update($id, $dataToUpdate);
    }

    // --- READ (Listado Paginado filtrado por servicio_id) ---
    public function getDeportesPaginatedByServicio(?int $servicioId, int $page, int $limit): array
    {
        if ($servicioId === null || $servicioId <= 0) {
            throw new Exception("El ID del servicio es requerido para listar las asignaciones de deportes.", 400);
        }
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.", 400);
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $result = $this->servicioPorDeporteRepository->getDeportesPaginatedByServicio($servicioId, $limit, $offset);

        return $this->formatPaginationResponse($result, $page, $limit);
    }

    // --- CHANGE STATUS ---
    public function changeServicioPorDeportetatus(int $id): bool
    {
        if (!$this->servicioPorDeporteRepository->getById($id)) {
            throw new Exception("Servicio no encontrado.", 404);
        }
        return $this->servicioPorDeporteRepository->changeStatus($id);
    }

    // --- DELETE (Desasignación por ID de la tabla de relación) ---
    public function deleteAsignacion(int $id): bool
    {
        $deleted = $this->servicioPorDeporteRepository->delete($id);
        if (!$deleted) {
            throw new Exception("Asignación de deporte no encontrada o ya eliminada.", 404);
        }
        return true;
    }
}
