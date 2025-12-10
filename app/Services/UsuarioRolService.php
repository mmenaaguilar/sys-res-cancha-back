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

    public function invitarGestor(string $email, int $complejoId, int $rolId): int
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo no es válido.", 400);
        }

        $user = $this->usuarioRolRepository->findUserByEmail($email);
        if (!$user) {
            throw new Exception("No se encontró ningún usuario registrado con el correo: $email", 404);
        }

        return $this->createUsuarioRol([
            'usuario_id' => $user['usuario_id'],
            'complejo_id' => $complejoId,
            'rol_id' => $rolId,
            'estado' => 'activo'
        ]);

        if ($complejoId <= 0) {
            throw new Exception("Error: ID de complejo inválido.");
        }

        return $this->createUsuarioRol([
            'usuario_id' => $user['usuario_id'],
            'complejo_id' => $complejoId,
            'rol_id' => $rolId,
            'estado' => 'activo'
        ]);
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

        $estado = strtolower($data['estado'] ?? 'activo');
        if (!in_array($estado, ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
        $data['estado'] = $estado;
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
