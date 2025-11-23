<?php

namespace App\Services;

use App\Repositories\ComplejoDeportivoFavoritoRepository;
use Exception;

class ComplejoDeportivoFavoritoService
{
    private ComplejoDeportivoFavoritoRepository $repository;

    public function __construct()
    {
        $this->repository = new ComplejoDeportivoFavoritoRepository();
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

    public function create(array $data): int
    {
        if (empty($data['usuario_id']) || empty($data['complejo_id'])) {
            throw new Exception("usuario_id y complejo_id son obligatorios.");
        }
        $data['estado'] = $data['estado'] ?? 'activo';
        return $this->repository->create($data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->getById($id);
    }

    public function listByUsuario(?int $usuarioId, ?string $searchTerm, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $result = $this->repository->listByUsuarioPaginated($usuarioId, $searchTerm, $limit, $offset);
        return $this->formatPaginationResponse($result, $page, $limit);
    }
}
