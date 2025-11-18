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

    // --- VALIDACIÓN HELPER ---
    private function validateServiceData(array &$data): void
    {
        if (empty($data['complejo_id']) || !is_numeric($data['complejo_id'])) {
            throw new Exception("El ID del complejo es requerido.");
        }
        if (empty($data['tipo_deporte_id']) || !is_numeric($data['tipo_deporte_id'])) {
            throw new Exception("El ID del tipo de deporte es requerido.");
        }
        if (empty($data['nombre'])) {
            throw new Exception("El nombre del servicio es requerido.");
        }
        if (empty($data['monto']) || !is_numeric($data['monto']) || $data['monto'] < 0) {
            throw new Exception("El monto debe ser un valor numérico positivo.");
        }

        // Sanitización y defaults
        $data['is_obligatorio'] = $data['is_obligatorio'] ?? false;
        $data['estado'] = $data['estado'] ?? 'activo';

        if (!in_array($data['estado'], ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
    }

    // --- READ ---
    public function getServiciosByFilters(array $data): array
    {
        $complejoId = (int) ($data['complejo_id'] ?? 0);
        $tipoDeporteId = isset($data['tipo_deporte_id']) ? (int) $data['tipo_deporte_id'] : null;

        if ($complejoId <= 0) {
            throw new Exception("ID de complejo inválido.");
        }

        try {
            return $this->servicioRepository->listByFilters($complejoId, $tipoDeporteId);
        } catch (Exception $e) {
            throw new Exception("Error al listar servicios: " . $e->getMessage());
        }
    }

    // --- CREATE ---
    public function createService(array $data): int
    {
        $this->validateServiceData($data);
        return $this->servicioRepository->create($data);
    }

    // --- UPDATE ---
    public function updateService(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de servicio inválido para la actualización.");
        }
        $this->validateServiceData($data);

        if (!$this->servicioRepository->getById($id)) {
            throw new Exception("Servicio no encontrado.");
        }

        return $this->servicioRepository->update($id, $data);
    }

    // --- DELETE ---
    public function deleteService(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de servicio inválido para la eliminación.");
        }
        return $this->servicioRepository->delete($id);
    }

    // --- CHANGE STATUS ---
    public function changeStatus(int $id): array
    {
        if ($id <= 0) {
            throw new Exception("ID de servicio inválido.");
        }

        $servicio = $this->servicioRepository->getById($id);
        if (!$servicio) {
            throw new Exception("Servicio no encontrado.");
        }

        $nuevoEstado = ($servicio['estado'] === 'activo') ? 'inactivo' : 'activo';

        if ($this->servicioRepository->changeStatus($id, $nuevoEstado)) {
            return ['servicio_id' => $id, 'nuevo_estado' => $nuevoEstado];
        }

        throw new Exception("Error al cambiar el estado del servicio.");
    }
}
