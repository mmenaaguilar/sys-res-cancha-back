<?php

namespace App\Controllers;

use App\Patterns\AlquilerFacade;                 // <-- Ruta ajustada
use App\Patterns\Reserva\HorarioBaseComposite;  // <-- Mantiene el namespace del composite
use App\Core\Helpers\ApiHelper;

class AlquilerController extends ApiHelper
{
    private AlquilerFacade $facade;

    public function __construct()
    {
        // Inyectamos el composite dentro del Facade
        $this->facade = new AlquilerFacade();
    }

    public function validarDisponibilidad()
    {
        // Obtener el JSON enviado desde el frontend
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        // Llamar al Facade
        $response = $this->facade->validarDisponibilidad($data);

        // Devolver respuesta en JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    public function buscarComplejosDisponiblesPorDistrito()
    {
        // Obtener los datos enviados en el body POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Crear el facade y llamar al método
        $facade = new AlquilerFacade();
        $resultado = $facade->buscarComplejosDisponiblesPorDistrito($data);

        // Devolver JSON
        header('Content-Type: application/json');
        echo json_encode($resultado);
    }

        public function verAgenda()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $canchaId = $data['cancha_id'] ?? 0;
        $fecha = $data['fecha'] ?? date('Y-m-d');

        if (!$canchaId) {
            $this->sendError(new \Exception("Falta ID de cancha"));
            return;
        }

        try {
            $grilla = $this->facade->obtenerGrillaPorCancha($canchaId, $fecha);
            
            // Enviamos info extra de la cancha también para el header
            // (Opcional si ya la tienes, pero útil)
            
            $this->sendResponse([
                'fecha' => $fecha,
                'slots' => $grilla
            ]);
        } catch (\Exception $e) {
            $this->sendError($e);
        }
    }
}
