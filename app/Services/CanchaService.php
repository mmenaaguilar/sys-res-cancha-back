<?php
// app/Services/CanchaService.php

namespace App\Services;

use App\Repositories\CanchaRepository;
use Exception;

class CanchaService
{
    private CanchaRepository $canchaRepository;

    public function __construct()
    {
        $this->canchaRepository = new CanchaRepository();
    }

    /**
     * Valida los datos de la cancha.
     */
    private function validateCanchaData(array $data, bool $isUpdate = false): void
    {
        if (empty($data['complejo_id']) || !is_numeric($data['complejo_id'])) {
            throw new Exception("El ID del complejo es requerido.");
        }
        if (empty($data['tipo_deporte_id']) || !is_numeric($data['tipo_deporte_id'])) {
            throw new Exception("El tipo de deporte es requerido.");
        }
        if (empty($data['nombre'])) {
            throw new Exception("El nombre de la cancha es requerido.");
        }

        if (!empty($data['estado']) && !in_array($data['estado'], ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
    }

    /**
     * Obtiene canchas activas por complejo.
     */
    public function getByComplejo(int $complejoId): array
    {
        if ($complejoId <= 0) {
            throw new Exception("ID de complejo inválido.");
        }
        return $this->canchaRepository->getByComplejo($complejoId);
    }

    /**
     * Crea una nueva cancha.
     */
    public function createCancha(array $data): int
    {
        $data['estado'] = $data['estado'] ?? 'activo';
        $this->validateCanchaData($data);
        return $this->canchaRepository->create($data);
    }

    /**
     * Actualiza una cancha existente.
     */
    public function updateCancha(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de cancha inválido.");
        }

        $this->validateCanchaData($data, true);

        if (!$this->canchaRepository->getById($id)) {
            throw new Exception("Cancha no encontrada.");
        }

        return $this->canchaRepository->update($id, $data);
    }

    /**
     * Elimina físicamente una cancha.
     */
    public function deleteCancha(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de cancha inválido.");
        }
        return $this->canchaRepository->delete($id);
    }

    /**
     * Cambia el estado de la cancha.
     */
    public function changeStatus(int $id): array
    {
        if ($id <= 0) {
            throw new Exception("ID de cancha inválido.");
        }

        $cancha = $this->canchaRepository->getById($id);
        if (!$cancha) {
            throw new Exception("Cancha no encontrada.");
        }

        $nuevoEstado = ($cancha['estado'] === 'activo') ? 'inactivo' : 'activo';

        if ($this->canchaRepository->changeStatus($id, $nuevoEstado)) {
            return ['cancha_id' => $id, 'nuevo_estado' => $nuevoEstado];
        }

        throw new Exception("Error al cambiar el estado de la cancha.");
    }
}
