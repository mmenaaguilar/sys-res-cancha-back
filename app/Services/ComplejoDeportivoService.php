<?php

namespace App\Services;

use App\Repositories\ComplejoDeportivoRepository;
use App\Repositories\ContactoRepository;
use Exception;

class ComplejoDeportivoService
{
    private ComplejoDeportivoRepository $repository;
    private ContactoRepository $contactoRepo;

    public function __construct()
    {
        $this->repository = new ComplejoDeportivoRepository();
        $this->contactoRepo = new ContactoRepository();
    }

    private function formatPaginationResponse(array $result, int $page, int $limit): array
    {
        $total = $result['total'];

        // Calcular total de páginas
        $totalPages = $limit > 0 ? ceil($total / $limit) : 0;
        if ($total == 0) $totalPages = 1; // Si no hay datos, hay 1 página vacía.

        // Asegurar que la página actual no es mayor al total de páginas
        $page = min($page, (int)$totalPages);

        // Calcular next_page y prev_page
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int)$totalPages,

            // ¡Nuevos campos booleanos!
            'next_page' => $hasNextPage,
            'prev_page' => $hasPrevPage,

            'data' => $result['data']
        ];
    }
    public function getAll(?int $usaurioId = null, ?string $searchTerm, int $page, int $limit): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $searchTerm = trim($searchTerm ?? '');
        $result = $this->repository->getAll($usaurioId,
            $searchTerm,
            $limit,
            $offset);
        return $this->formatPaginationResponse($result, $page, $limit);
    }

    public function getById(int $id): array
    {
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");
        $complejo['contactos'] = $this->contactoRepo->getActiveByComplejoId($id);
        return $complejo;
    }

 public function create(array $data, ?array $file = null): int
    {
        if (empty($data['nombre'])) {
            throw new Exception("El nombre del complejo es obligatorio.");
        }
        $data['estado'] = $data['estado'] ?? 'activo';

        // ✅ LÓGICA DE CREATE: Procesar archivo si viene
        if ($file && isset($file['tmp_name'])) {
            $data['url_imagen'] = $this->handleImage($file); // Guarda la URL que genera handleImage
        } else {
             $data['url_imagen'] = null; // Si no viene archivo, es null
        }
        
        return $this->repository->create($data);
    }

    // ✅ CORRECCIÓN CLAVE AQUÍ: Lógica para mantener la URL
    public function update(int $id, array $data, ?array $file = null): bool
    {
        $complejo = $this->repository->getById($id);
        if (!$complejo) throw new Exception("Complejo no encontrado.");

        $this->validate($data);
        
        // 1. Si se sube nueva imagen (ARCHIVO en $_FILES), procesar
        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            // Manejar subida (y opcionalmente eliminar la anterior)
            $data['url_imagen'] = $this->handleImage($file);
        } else {
            // 2. Si NO se sube archivo nuevo: MANTENER LA URL ANTERIOR DE LA BD
            $data['url_imagen'] = $complejo['url_imagen'];
        }

        // Si el frontend envía 'url_imagen' (como texto) y no hay 'file', aquí PHP usará
        // $data['url_imagen'] = $complejo['url_imagen'] (el valor viejo de la BD). 
        
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

        return 'public/uploads/complejos/' . $filename;
    }

    public function getUbicacionesDisponibles(): array
    {
        return $this->repository->getDistritosConComplejos();
    }
}
