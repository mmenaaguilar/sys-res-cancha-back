<?php
// app/Services/UsuarioService.php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use Exception;

class UsuarioService
{
    private UsuarioRepository $usuarioRepository;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
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

    public function getUsuariosPaginated(int $page, int $limit): array
    {
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.");
        }
        if ($page <= 0) {
            $page = 1;
        }

        $offset = ($page - 1) * $limit;

        $result = $this->usuarioRepository->getUsuariosPaginated($limit, $offset);

        $total = $result['total'];
        $totalPages = ceil($total / $limit);

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,
            'data' => $result['data']
        ];
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
}
