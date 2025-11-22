<?php

namespace App\Services;

use App\Repositories\ComplejoDeportivoRepository;
use Exception;

class ComplejoDeportivoService
{
    private ComplejoDeportivoRepository $repository;

    public function __construct()
    {
        $this->repository = new ComplejoDeportivoRepository();
    }

    public function getAll(?int $complejoId = null): array
    {
        return $this->repository->getAll($complejoId);
    }


    public function getById(int $id): array
    {
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");
        return $complejo;
    }

    public function create(array $data): int
    {
        $this->validate($data);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->repository->getById($id)) {
            throw new Exception("Complejo no encontrado.");
        }
        $this->validate($data);
        return $this->repository->update($id, $data);
    }

    public function changeStatus(int $id): array
    {
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");

        $nuevoEstado = $complejo['estado'] === 'activo' ? 'inactivo' : 'activo';

        if ($this->repository->changeStatus($id, $nuevoEstado)) {
            return [
                'complejo_id' => $id,
                'nuevo_estado' => $nuevoEstado
            ];
        }

        throw new Exception("Error al cambiar el estado del complejo.");
    }

    public function delete(int $id): bool
    {
        if (!$this->repository->getById($id)) {
            throw new Exception("Complejo no encontrado.");
        }
        return $this->repository->delete($id);
    }

    private function validate(array &$data): void
    {
        if (empty($data['nombre'])) {
            throw new Exception("El nombre del complejo es requerido.");
        }
        if (empty($data['estado'])) {
            $data['estado'] = 'activo';
        }
    }
}
