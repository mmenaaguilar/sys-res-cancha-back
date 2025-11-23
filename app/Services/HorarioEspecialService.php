<?php

namespace App\Services;

use App\Repositories\HorarioEspecialRepository;
use Exception;

class HorarioEspecialService
{
    private HorarioEspecialRepository $repo;

    public function __construct()
    {
        $this->repo = new HorarioEspecialRepository();
    }

    /**
     * Validación de datos
     */
    private function validate(array $data)
    {
        if (empty($data['cancha_id']) || !is_numeric($data['cancha_id'])) {
            throw new Exception("El ID de cancha es requerido.");
        }
        if (empty($data['fecha'])) {
            throw new Exception("La fecha es requerida.");
        }
        if (empty($data['hora_inicio']) || empty($data['hora_fin'])) {
            throw new Exception("Las horas de inicio y fin son requeridas.");
        }
    }

    /**
     * Formato paginado
     */
    private function formatPagination(array $result, int $page, int $limit): array
    {
        $total = $result['total'];
        $totalPages = max(1, ceil($total / $limit));

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages,
            'next_page' => $page < $totalPages,
            'prev_page' => $page > 1,
            'data' => $result['data']
        ];
    }

    /**
     * LIST paginado + búsqueda
     */
    public function getPaginated(?int $canchaId, ?string $search, ?string $fecha, int $page, int $limit): array
    {
        if ($canchaId === null || $canchaId <= 0) {
            throw new Exception("El ID de cancha es requerido.");
        }

        $offset = ($page - 1) * $limit;
        $search = trim($search ?? '');

        $result = $this->repo->getPaginated(
            $limit,
            $offset,
            $canchaId,
            $fecha,
            $search
        );

        return $this->formatPagination($result, $page, $limit);
    }

    /**
     * CREATE
     */
    public function create(array $data): int
    {
        $this->validate($data);
        $data['estado'] = $data['estado'] ?? 'disponible';

        return $this->repo->create($data);
    }

    /**
     * UPDATE
     */
    public function update(int $id, array $data): bool
    {
        if (!$this->repo->getById($id)) {
            throw new Exception("Horario especial no encontrado.");
        }

        $this->validate($data);

        return $this->repo->update($id, $data);
    }

    /**
     * DELETE
     */
    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * CHANGE STATUS
     */
    public function changeStatus(int $id): array
    {
        if ($id <= 0) {
            throw new Exception("ID de contacto inválido.");
        }
        $item = $this->repo->getById($id);
        if (!$item) {
            throw new Exception("Horario especial no encontrado.");
        }

        $nuevoEstado = ($item['estado'] === 'activo') ? 'inactivo' : 'activo';

        if ($this->repo->changeStatus($id, $nuevoEstado)) {
            return ['contacto_id' => $id, 'nuevo_estado' => $nuevoEstado];
        }

        throw new Exception("Error al cambiar el estado del contacto.");
    }
}
