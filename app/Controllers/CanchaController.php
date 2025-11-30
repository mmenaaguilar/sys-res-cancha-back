<?php
namespace App\Controllers;

use App\Services\CanchaService; // Asegúrate que tu Service use el nuevo Repo
use Exception;

class CanchaController
{
    private $canchaService;

    public function __construct()
    {
        // Asumimos que CanchaService ya instancia CanchaRepository
        $this->canchaService = new CanchaService();
    }

    // Helper para CORS y JSON
    private function jsonResponse($data, $code = 200) {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    public function listByComplejoPaginated()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Content-Type: application/json");

        $input = json_decode(file_get_contents("php://input"), true);
        $complejoId = $input['complejo_id'] ?? null;
        
        // Validación estricta de ID
        if (!$complejoId) {
            echo json_encode(['data' => [], 'total' => 0]);
            return;
        }

        try {
            // Parámetros opcionales
            $tipoDeporte = $input['tipo_deporte_id'] ?? null;
            $search = $input['searchTerm'] ?? '';
            $page = $input['page'] ?? 1;
            $limit = $input['limit'] ?? 10;

            // Llamada al servicio
            $result = $this->canchaService->getByComplejoPaginated($complejoId, $tipoDeporte, $search, $page, $limit);
            
            echo json_encode($result); // Devuelve { data: [...], total: N }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['complejo_id']) || empty($input['nombre'])) {
            $this->jsonResponse(['error' => 'Faltan datos'], 400);
        }

        try {
            $id = $this->canchaService->createCancha($input);
            $this->jsonResponse(['message' => 'Cancha creada', 'id' => $id], 201);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function update($id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, OPTIONS");
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') return;

        $input = json_decode(file_get_contents("php://input"), true);

        try {
            $this->canchaService->updateCancha($id, $input);
            $this->jsonResponse(['message' => 'Actualizado']);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function changeStatus($id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, OPTIONS");
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') return;

        try {
            $res = $this->canchaService->changeStatus($id);
            $this->jsonResponse($res);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: DELETE, OPTIONS");
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

        try {
            $this->canchaService->deleteCancha($id);
            $this->jsonResponse(['message' => 'Eliminado']);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}