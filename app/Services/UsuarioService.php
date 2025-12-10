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

        if (!$this->usuarioRepository->getById($id)) {
            throw new Exception("Usuario no encontrado.", 404);
        }

        $this->validateUpdateData($data);

        return $this->usuarioRepository->update($id, $data);
    }
    public function getCreditosByUsuario(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            throw new Exception("ID de usuario inválido.");
        }

        return $this->creditoRepo->getCreditosByUsuario($usuarioId);
    }

public function cambiarContrasena(int $usuarioId, string $contrasenaActual, string $nuevaContrasena): void
{
    error_log("SERVICE - cambiarContrasena llamado con usuarioId: " . $usuarioId);
    if ($usuarioId <= 0) {
        throw new Exception("ID de usuario inválido.", 400);
    }

    if (!$this->usuarioRepository->getById($usuarioId)) {
        throw new Exception("Usuario no encontrado.", 404);
    }

    if (empty($contrasenaActual)) {
        throw new Exception("La contraseña actual es requerida.", 400);
    }

    if (empty($nuevaContrasena) || strlen($nuevaContrasena) < 8) {
        throw new Exception("La nueva contraseña debe tener al menos 8 caracteres.", 400);
    }

    $hashActual = $this->usuarioRepository->getContrasenaHash($usuarioId);
    if ($hashActual === null) {
        throw new Exception("No se pudo obtener la contraseña actual.", 500);
    }

    error_log("DEBUG - Contraseña actual proporcionada: " . ($contrasenaActual ? 'SÍ' : 'NO'));
    error_log("DEBUG - Hash actual en BD: " . $hashActual);
    error_log("DEBUG - password_verify resultado: " . (password_verify($contrasenaActual, $hashActual) ? 'VERDADERO' : 'FALSO'));

    if (!password_verify($contrasenaActual, $hashActual)) {
        throw new Exception("La contraseña actual es incorrecta.", 400);
    }

    if (password_verify($nuevaContrasena, $hashActual)) {
        throw new Exception("La nueva contraseña debe ser diferente a la actual.", 400);
    }

    $nuevoHash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
    error_log("DEBUG - Nuevo hash generado: " . $nuevoHash);
    
    $actualizado = $this->usuarioRepository->actualizarContrasena($usuarioId, $nuevoHash);
    
    if (!$actualizado) {
        throw new Exception("Error al actualizar la contraseña.", 500);
    }
    
    error_log("DEBUG - Contraseña actualizada exitosamente para usuario: " . $usuarioId);
}
}
