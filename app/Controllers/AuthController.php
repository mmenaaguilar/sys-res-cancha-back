<?php
// app/Controllers/AuthController.php

namespace App\Controllers;

use App\Services\AuthService;
use Exception;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Maneja la solicitud POST /api/login
     */
    public function login()
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        $correo = $input['correo'] ?? null;
        $contrasena = $input['contrasena'] ?? null;

        if (!$correo || !$contrasena) {
            http_response_code(400); 
            echo json_encode(['error' => 'Correo y contraseña son requeridos.']);
            return;
        }

        $result = $this->authService->login($correo, $contrasena);

        if ($result) {
            http_response_code(200);
            echo json_encode([
                'message' => 'Login exitoso.',
                'data' => $result
            ]);
        } else {
            http_response_code(401); 
            echo json_encode(['error' => 'Credenciales inválidas.']);
        }
    }


    /**
     * Maneja la solicitud POST /api/register
     */
    public function register()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        // Validación de campos requeridos
        $nombre = $input['nombre'] ?? null;
        $correo = $input['correo'] ?? null;
        $contrasena = $input['contrasena'] ?? null;
        $telefono = $input['telefono'] ?? null;

        if (!$nombre || !$correo || !$contrasena) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre, correo y contrasenia son requeridos para el registro.']);
            return;
        }

        try {
            $result = $this->authService->register([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena' => $contrasena,
                'telefono' => $telefono
            ]);

            http_response_code(201); 
            echo json_encode([
                'message' => 'Usuario registrado exitosamente. Rol "Deportista" asignado.',
                'data' => $result
            ]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                http_response_code(409); 
                echo json_encode(['error' => 'El correo electrónico ya está registrado.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error interno del servidor durante el registro.', 'detail' => $e->getMessage()]);
            }
        }
    }
}
