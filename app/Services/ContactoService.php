<?php
// app/Services/ContactoService.php

namespace App\Services;

use App\Repositories\ContactoRepository;
use Exception;

class ContactoService
{
    private ContactoRepository $contactoRepository;

    public function __construct()
    {
        $this->contactoRepository = new ContactoRepository();
    }

    /**
     * Helper para validar los datos del contacto.
     */
    private function validateContactData(array $data, bool $isUpdate = false): void
    {
        if (empty($data['complejo_id']) || !is_numeric($data['complejo_id'])) {
            throw new Exception("El ID del complejo es requerido.");
        }
        if (empty($data['tipo'])) {
            throw new Exception("El tipo de contacto es requerido.");
        }
        if (empty($data['valor_contacto'])) {
            throw new Exception("El valor del contacto es requerido.");
        }

        if (!empty($data['estado']) && !in_array($data['estado'], ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
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
    // --- CRUD OPERACIONES ---

    public function getContactosPaginatedByComplejo(?int $complejoId, ?string $searchTerm, int $page, int $limit): array
    {
        if ($complejoId === null || $complejoId <= 0) {
            throw new Exception("El ID del complejo es requerido para listar servicios.", 400);
        }
        if ($limit <= 0) {
            throw new Exception("El límite de resultados debe ser un número positivo.");
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $searchTerm = trim($searchTerm ?? '');

        $result = $this->contactoRepository->getContactosPaginatedByFilters(
            $complejoId,
            $searchTerm,
            $limit,
            $offset
        );

        return $this->formatPaginationResponse($result, $page, $limit);
    }

    public function createContact(array $data): int
    {
        $data['estado'] = $data['estado'] ?? 'activo';
        $this->validateContactData($data);
        return $this->contactoRepository->create($data);
    }

    public function updateContact(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de contacto inválido para la actualización.");
        }
        $this->validateContactData($data, true);

        if (!$this->contactoRepository->getById($id)) {
            throw new Exception("Contacto no encontrado.");
        }

        return $this->contactoRepository->update($id, $data);
    }

    public function deleteContact(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de contacto inválido para la eliminación.");
        }
        return $this->contactoRepository->delete($id);
    }

    public function changeStatus(int $id): array
    {
        if ($id <= 0) {
            throw new Exception("ID de contacto inválido.");
        }

        $contacto = $this->contactoRepository->getById($id);
        if (!$contacto) {
            throw new Exception("Contacto no encontrado.");
        }

        $nuevoEstado = ($contacto['estado'] === 'activo') ? 'inactivo' : 'activo';

        if ($this->contactoRepository->changeStatus($id, $nuevoEstado)) {
            return ['contacto_id' => $id, 'nuevo_estado' => $nuevoEstado];
        }

        throw new Exception("Error al cambiar el estado del contacto.");
    }
}
