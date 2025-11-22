<?php

namespace App\Controllers;

use App\Patterns\AlquilerFacade;                 // <-- Ruta ajustada
use App\Patterns\Reserva\HorarioBaseComposite;  // <-- Mantiene el namespace del composite

class AlquilerController
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
}
