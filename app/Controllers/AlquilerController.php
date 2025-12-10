<?php

namespace App\Controllers;

use App\Patterns\AlquilerFacade;                 
use App\Patterns\Reserva\HorarioBaseComposite;  
use App\Core\Helpers\ApiHelper;

class AlquilerController extends ApiHelper
{
    private AlquilerFacade $facade;

    public function __construct()
    {
        $this->facade = new AlquilerFacade();
    }

    public function buscarComplejosDisponiblesPorDistrito()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $facade = new AlquilerFacade();
        $resultado = $facade->buscarComplejosDisponiblesPorDistrito($data);

        header('Content-Type: application/json');
        echo json_encode($resultado);
    }

        public function validarDisponibilidad()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $canchaId = $data['cancha_id'] ?? 0;
        $fecha = $data['fecha'] ?? date('Y-m-d');

        if (!$canchaId) {
            $this->sendError(new \Exception("Falta ID de cancha"));
            return;
        }

        try {
            $grilla = $this->facade->validarDisponibilidad($canchaId, $fecha);
            
            $this->sendResponse([
                'fecha' => $fecha,
                'slots' => $grilla
            ]);
        } catch (\Exception $e) {
            $this->sendError($e);
        }
    }
}
