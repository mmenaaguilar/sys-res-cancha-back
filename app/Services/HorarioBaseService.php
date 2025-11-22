<?php

namespace App\Services;

use App\Repositories\HorarioBaseRepository;
use Exception;

class HorarioBaseService
{
    private HorarioBaseRepository $repository;

    public function __construct()
    {
        $this->repository = new HorarioBaseRepository();
    }

    private function validateData(array $data)
    {
        if (empty($data['cancha_id'])) {
            throw new Exception("El campo cancha_id es requerido.");
        }
        if (empty($data['dia_semana'])) {
            throw new Exception("El día de semana es requerido.");
        }
        if (empty($data['hora_inicio']) || empty($data['hora_fin'])) {
            throw new Exception("La hora de inicio y fin son requeridas.");
        }
        if (!isset($data['monto'])) {
            throw new Exception("El monto es requerido.");
        }
    }

    /**
     * 🔹 Formato de paginación común
     */
    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1; // Si no hay datos, hay 1 página vacía.

        // Asegurar que la página actual no exceda el total de páginas
        $page = min($page, (int)$totalPages);

        // Evaluar presencia de página siguiente y anterior
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        return [
            'total'       => $total,
            'per_page'    => $limit,
            'current_page' => $page,
            'last_page'   => (int)$totalPages,

            // Nueva info
            'next_page'   => $hasNextPage,
            'prev_page'   => $hasPrevPage,

            'data'        => $result['data']
        ];
    }

    /**
     * Listado paginado con filtros.
     */
    public function getPaginated(array $data): array
    {
        $canchaId = (int)($data['cancha_id'] ?? 0);
        if ($canchaId <= 0) {
            throw new Exception("El cancha_id es obligatorio para listar.");
        }

        $diaSemana = $data['dia_semana'] ?? null;

        $page = max(1, (int)($data['page'] ?? 1));
        $limit = max(1, (int)($data['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $result = $this->repository->getPaginated($limit, $offset, $canchaId, $diaSemana);

        // 🔥 Ahora se utiliza el nuevo formatter
        return $this->formatPaginationResponse($result, $page, $limit);
    }

    public function create(array $data): int
    {
        $this->validateData($data);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->repository->getById($id)) {
            throw new Exception("HorarioBase no encontrado.");
        }

        $this->validateData($data);
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        if (!$this->repository->getById($id)) {
            throw new Exception("HorarioBase no encontrado.");
        }
        return $this->repository->delete($id);
    }
}
