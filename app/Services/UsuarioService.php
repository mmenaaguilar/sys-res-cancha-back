<?php
// app/Services/UsuarioService.php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Repositories\CreditoUsuarioRepository;
use Exception;

class UsuarioService
{
    private UsuarioRepository $usuarioRepository;
    private CreditoUsuarioRepository $creditoRepo;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
        $this->creditoRepo = new CreditoUsuarioRepository();
    }

    /**
     * Valida los campos que pueden ser editados (nombre, telefono, correo).
     */
    private function validateUpdateData(array $data): void
    {
        if (empty($data['nombre'])) {
            throw new Exception("El nombre es requerido.");
        }
        if (empty($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electrónico es requerido y debe ser válido.");
        }
        // Telefono puede ser opcional (NULL), pero si se envía debe ser un string.
        if (!isset($data['telefono'])) {
            $data['telefono'] = null;
        }
    }
    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];

        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1;

        $page = min($page, (int)$totalPages);

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,
            'next_page' => $page < $totalPages,
            'prev_page' => $page > 1,
            'data' => $result['data']
        ];
    }

    public function getUsuariosPaginated(?string $searchTerm, int $page, int $limit): array
    {
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.");
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $searchTerm = trim($searchTerm ?? '');

        $result = $this->usuarioRepository->getUsuariosPaginatedByFilters(
            $searchTerm,
            $limit,
            $offset
        );

        return $this->formatPaginationResponse($result, $page, $limit);
    }
    /**
     * Procesa la actualización de un usuario.
     */
    public function updateUsuario(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de usuario inválido.", 400);
        }

        // 1. Validar que el usuario existe antes de intentar la actualización
        if (!$this->usuarioRepository->getById($id)) {
            throw new Exception("Usuario no encontrado.", 404);
        }

        // 2. Validar los datos de entrada
        $this->validateUpdateData($data);

        // 3. Ejecutar la actualización
        return $this->usuarioRepository->update($id, $data);
    }
    public function getCreditosByUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            throw new Exception("ID de usuario inválido.");
        }

        return $this->creditoRepo->getCreditosByUsuario($usuarioId);
    }
}
