<?php
namespace App\Controllers;
use App\Services\CanchaService;
use App\Repositories\CanchaRepository;
use App\Core\Helpers\ApiHelper;
use Exception;

class CanchaController extends ApiHelper
{
    private $canchaService;
    private CanchaRepository $repository;

    public function __construct()
    {

        $this->canchaService = new CanchaService();
        $this->repository = new CanchaRepository();
    }


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

        if (!$complejoId) {
            echo json_encode(['data' => [], 'total' => 0]);
            return;
        }

        try {
            $tipoDeporte = $input['tipo_deporte_id'] ?? null;
            $search = $input['searchTerm'] ?? '';
            $page = $input['page'] ?? 1;
            $limit = $input['limit'] ?? 10;


            $result = $this->canchaService->getByComplejoPaginated($complejoId, $tipoDeporte, $search, $page, $limit);
            
            echo json_encode($result); 

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

    public function show(int $id)
    {
        try {
            $cancha = $this->repository->getByIdWithDetails($id);
            
            if (!$cancha) {
                $this->sendError("Cancha no encontrada", 404);
                return;
            }

            $this->sendResponse($cancha);
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
}