<?php
// app/Services/ServicioService.php

namespace App\Services;

use App\Repositories\ServicioRepository;
use Exception;

class ServicioService
{
    private ServicioRepository $servicioRepository;

    public function __construct()
    {
        $this->servicioRepository = new ServicioRepository();
    }

    // --- Helper de Paginación ---
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

    // --- CREATE ---
    public function createServicio(array $data): int
    {
        if (empty($data['complejo_id']) || empty($data['nombre']) || empty($data['monto']) || $data['monto'] <= 0) {
            throw new Exception("Datos de servicio incompletos o inválidos (complejo_id, nombre, monto).", 400);
        }
        return $this->servicioRepository->create($data);
    }

    // --- READ (Listado Paginado con Filtros) ---
    public function getServiciosPaginatedByFilters(?int $complejoId, ?string $searchTerm, int $page, int $limit): array
    {
        if ($complejoId === null || $complejoId <= 0) {
            throw new Exception("El ID del complejo es requerido para listar servicios.", 400);
        }
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.", 400);
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $searchTerm = trim($searchTerm ?? '');

        $result = $this->servicioRepository->getServiciosPaginatedByFilters($complejoId, $searchTerm, $limit, $offset);

        return $this->formatPaginationResponse($result, $page, $limit);
    }

    // --- UPDATE ---
    public function updateServicio(int $id, array $data): bool
    {
        if (!$this->servicioRepository->getById($id)) {
            throw new Exception("Servicio no encontrado.", 404);
        }
        if (empty($data['nombre']) || empty($data['monto']) || $data['monto'] <= 0) {
            throw new Exception("Nombre o monto de servicio inválido.", 400);
        }
        return $this->servicioRepository->update($id, $data);
    }

    // --- CHANGE STATUS ---
    public function changeServicioStatus(int $id): bool
    {
        if (!$this->servicioRepository->getById($id)) {
            throw new Exception("Servicio no encontrado.", 404);
        }
        return $this->servicioRepository->changeStatus($id);
    }

    // --- DELETE ---
    public function deleteServicio(int $id): bool
    {
        if (!$this->servicioRepository->getById($id)) {
            throw new Exception("Servicio no encontrado.", 404);
        }
        return $this->servicioRepository->delete($id);
    }
}
