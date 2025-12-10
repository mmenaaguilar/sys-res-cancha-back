<?php
// app/Services/ServicioPorHorarioService.php

namespace App\Services;

use App\Repositories\ServicioPorHorarioRepository; 
use Exception;

class ServicioPorHorarioService
{
    private ServicioPorHorarioRepository $servicioPorHorarioRepository; 

    public function __construct()
    {
        $this->servicioPorHorarioRepository = new ServicioPorHorarioRepository(); 
    }

    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1;
        $page = min($page, (int)$totalPages);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,
            'next_page' => $hasNextPage,
            'prev_page' => $hasPrevPage,
            'data' => $result['data']
        ];
    }

    // --- CREATE (Asignación) ---
    public function createAsignacion(array $data): int
    {
        if (empty($data['servicio_id']) || empty($data['horarioBase_id'])) {
            throw new Exception("IDs de servicio y horario base son requeridos.", 400);
        }
        return $this->servicioPorHorarioRepository->create($data);
    }

    // --- UPDATE (Edición) ---
    public function updateAsignacion(int $id, array $data): bool
    {
        if (!$this->servicioPorHorarioRepository->getById($id)) {
            throw new Exception("Asignación no encontrada.", 404);
        }

        $dataToUpdate = [];
        $isUpdated = false;

        if (isset($data['estado'])) {
            if (!in_array($data['estado'], ['activo', 'inactivo'])) {
                throw new Exception("El campo 'estado' debe ser 'activo' o 'inactivo'.", 400);
            }
            $dataToUpdate['estado'] = $data['estado'];
            $isUpdated = true;
        }

        if (isset($data['horarioBase_id'])) {
            if (!is_numeric($data['horarioBase_id']) || (int)$data['horarioBase_id'] <= 0) {
                throw new Exception("El 'horarioBase_id' es inválido.", 400); 
            }
            $dataToUpdate['horarioBase_id'] = (int)$data['horarioBase_id'];
            $isUpdated = true;
        }

        if (isset($data['is_obligatorio'])) {
            $dataToUpdate['is_obligatorio'] = ((bool)$data['is_obligatorio']) ? 1 : 0;
            $isUpdated = true;
        }

        if (!$isUpdated) {
            throw new Exception("Se requiere al menos 'estado' o 'horarioBase_id' para actualizar.", 400); // Cambio de mensaje
        }

        return $this->servicioPorHorarioRepository->update($id, $dataToUpdate);
    }

    // --- READ (Listado Paginado filtrado por servicio_id) ---
    public function getHorariosPaginatedByServicio(?int $servicioId, int $page, int $limit): array
    {
        if ($servicioId === null || $servicioId <= 0) {
            throw new Exception("El ID del servicio es requerido para listar las asignaciones de horarios.", 400);
        }
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.", 400);
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $result = $this->servicioPorHorarioRepository->getHorariosPaginatedByServicio($servicioId, $limit, $offset);

        return $this->formatPaginationResponse($result, $page, $limit);
    }

    // --- CHANGE STATUS ---
    public function changeServicioPorHorarioStatus(int $id): bool
    {
        if (!$this->servicioPorHorarioRepository->getById($id)) {
            throw new Exception("Asignación de Horario no encontrada.", 404);
        }
        return $this->servicioPorHorarioRepository->changeStatus($id);
    }

    // --- DELETE (Desasignación por ID de la tabla de relación) ---
    public function deleteAsignacion(int $id): bool
    {
        $deleted = $this->servicioPorHorarioRepository->delete($id);
        if (!$deleted) {
            throw new Exception("Asignación de horario no encontrada o ya eliminada.", 404);
        }
        return true;
    }
}
