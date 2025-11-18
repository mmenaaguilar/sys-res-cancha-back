<?php
// app/Services/PoliticaService.php

namespace App\Services;

use App\Repositories\PoliticaRepository;
use Exception;

class PoliticaService
{
    private PoliticaRepository $politicaRepository;

    public function __construct()
    {
        $this->politicaRepository = new PoliticaRepository();
    }

    // --- VALIDACIÓN HELPER ---
    private function validatePolicyData(array &$data): void
    {
        $validTemprana = ['CreditoCompleto', 'ReembolsoFisico'];

        if (empty($data['complejo_id']) || !is_numeric($data['complejo_id'])) {
            throw new Exception("El ID del complejo es requerido y debe ser numérico.");
        }
        if (empty($data['horas_limite']) || !is_numeric($data['horas_limite']) || $data['horas_limite'] < 0) {
            throw new Exception("Las horas límite deben ser un número positivo.");
        }
        if (empty($data['estrategia_temprana']) || !in_array($data['estrategia_temprana'], $validTemprana)) {
            throw new Exception("Estrategia temprana inválida. Debe ser 'CreditoCompleto' o 'ReembolsoFisico'.");
        }

        $data['estado'] = $data['estado'] ?? 'activo';
        if (!in_array($data['estado'], ['activo', 'inactivo'])) {
            throw new Exception("El estado debe ser 'activo' o 'inactivo'.");
        }
    }

    // --- READ (LIST) ---
    public function listPoliciesByComplejo(int $complejoId): array
    {
        if ($complejoId <= 0) {
            throw new Exception("ID de complejo inválido.");
        }
        return $this->politicaRepository->listByComplejoId($complejoId);
    }

    // --- CREATE ---
    public function createPolicy(array $data): int
    {
        $this->validatePolicyData($data);
        return $this->politicaRepository->create($data);
    }

    // --- UPDATE ---
    public function updatePolicy(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de política inválido para la actualización.");
        }
        $this->validatePolicyData($data);

        if (!$this->politicaRepository->getById($id)) {
            throw new Exception("Política no encontrada.");
        }

        return $this->politicaRepository->update($id, $data);
    }

    // --- DELETE ---
    public function deletePolicy(int $id): bool
    {
        if ($id <= 0) {
            throw new Exception("ID de política inválido para la eliminación.");
        }
        return $this->politicaRepository->delete($id);
    }

    // --- CHANGE STATUS ---
    public function toggleStatus(int $id): array
    {
        if ($id <= 0) {
            throw new Exception("ID de política inválido.");
        }

        $politica = $this->politicaRepository->getById($id);
        if (!$politica) {
            throw new Exception("Política no encontrada.");
        }

        $nuevoEstado = ($politica['estado'] === 'activo') ? 'inactivo' : 'activo';

        if ($this->politicaRepository->changeStatus($id, $nuevoEstado)) {
            return ['politica_id' => $id, 'nuevo_estado' => $nuevoEstado];
        }

        throw new Exception("Error al cambiar el estado de la política.");
    }
}
