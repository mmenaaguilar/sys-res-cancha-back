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

    public function create(array $data, ?array $file = null): int
    {
        $this->validate($data);
        $data['url_imagen'] = $this->handleImage($file);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data, ?array $file = null): bool
    {
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");

        $this->validate($data);

        // Si se sube nueva imagen, reemplaza
        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            // Opcional: eliminar imagen anterior
            if (!empty($complejo['url_imagen'])) {
                $oldPath = __DIR__ . '/../../public' . $complejo['url_imagen'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $data['url_imagen'] = $this->handleImage($file);
        } else {
            $data['url_imagen'] = $complejo['url_imagen'];
        }

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
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");

        // Opcional: eliminar imagen
        if (!empty($complejo['url_imagen'])) {
            $path = __DIR__ . '/../../public' . $complejo['url_imagen'];
            if (file_exists($path)) unlink($path);
        }

        return $this->repository->delete($id);
    }

    private function validate(array &$data): void
    {
        if (empty($data['nombre'])) throw new Exception("El nombre del complejo es requerido.");
        if (empty($data['estado'])) $data['estado'] = 'activo';
    }

    private function handleImage(array $file): ?string
    {
        if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'complejo_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../public/uploads/complejos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Error al subir la imagen del complejo.");
        }

        return '/uploads/complejos/' . $filename;
    }
}
