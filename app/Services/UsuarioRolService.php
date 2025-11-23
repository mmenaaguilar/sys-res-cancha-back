<?php
// app/Services/UsuarioRolService.php

namespace App\Services;

use App\Repositories\UsuarioRolRepository;
use Exception;

class UsuarioRolService
{
    private UsuarioRolRepository $usuarioRolRepository;

    public function __construct()
    {
        $this->usuarioRolRepository = new UsuarioRolRepository();
    }

    private function validateUsuarioRolData(array &$data): void
    {
        if (empty($data['usuario_id']) || !is_numeric($data['usuario_id'])) {
            throw new Exception("El ID del usuario es requerido y debe ser numérico.");
        }
        if (empty($data['rol_id']) || !is_numeric($data['rol_id'])) {
            throw new Exception("El ID del rol es requerido y debe ser numérico.");
        }

        if (isset($data['complejo_id'])) {
            if (!is_numeric($data['complejo_id']) || $data['complejo_id'] <= 0) {
                $data['complejo_id'] = null;
            } else {
                $data['complejo_id'] = (int) $data['complejo_id'];
            }
        } else {
            $data['complejo_id'] = null;
        }

        // Manejo del estado (si no viene, por defecto es 'activo' para create)
        $estado = strtolower($data['estado'] ?? 'activo');
        if (!in_array($estado, ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
        $data['estado'] = $estado;
    }
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

    public function getUsuarioRolesPaginated(?int $complejoId, ?string $searchTerm, int $page, int $limit): array
    {
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.");
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $result = $this->usuarioRolRepository->getUsuarioRolesPaginatedByComplejo($complejoId, $searchTerm, $limit, $offset);

        return $this->formatPaginationResponse($result, $page, $limit);
    }


    public function createUsuarioRol(array $data): int
    {
        $this->validateUsuarioRolData($data);
        return $this->usuarioRolRepository->create($data);
    }

    public function updateUsuarioRol(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de asignación (UsuarioRol) inválido para la actualización.");
        }
        $this->validateUsuarioRolData($data);

        if (!$this->usuarioRolRepository->getById($id)) {
            throw new Exception("Asignación de rol no encontrada.");
        }

        return $this->usuarioRolRepository->update($id, $data);
    }

    public function changeUsuarioRolStatus(int $id): bool
    {
        $current = $this->usuarioRolRepository->getById($id);
        if (!$current) {
            throw new Exception("Asignación de rol no encontrada.", 404);
        }

        $newStatus = ($current['estado'] === 'activo') ? 'inactivo' : 'activo';

        return $this->usuarioRolRepository->changeStatus($id, $newStatus);
    }

    public function deleteUsuarioRol(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de asignación (UsuarioRol) inválido para la eliminación.");
        }
        return $this->usuarioRolRepository->delete($id);
    }
}
