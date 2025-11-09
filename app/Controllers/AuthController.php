<?php
// app/Controllers/AuthController.php

namespace App\Controllers;

use App\Services\AuthService;
use Exception; // Necesario para manejar la excepción lanzada desde el Service/Repository

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        // Instancia el Service, que a su vez instancia el Repository
        $this->authService = new AuthService();
    }

    /**
     * Maneja la solicitud POST /api/login
     */
    public function login()
    {
        // Asegurar que la respuesta sea JSON
        header('Content-Type: application/json');

        // 1. Leer los datos del cuerpo de la petición (POST JSON)
        $input = json_decode(file_get_contents('php://input'), true);

        $correo = $input['correo'] ?? null;
        $contrasena = $input['contrasena'] ?? null;

        // 2. Validación básica
        if (!$correo || !$contrasena) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Correo y contraseña son requeridos.']);
            return;
        }

        // 3. Llamar al Service (Lógica de Negocio)
        $result = $this->authService->login($correo, $contrasena);

        if ($result) {
            // Autenticación exitosa
            http_response_code(200);
            echo json_encode([
                'message' => 'Login exitoso.',
                'data' => $result
            ]);
        } else {
            // Fallo en la autenticación (Credenciales inválidas)
            http_response_code(401); // Unauthorized
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
        $telefono = $input['telefono'] ?? null; // Opcional

        if (!$nombre || !$correo || !$contrasena) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre, correo y contrasenia son requeridos para el registro.']);
            return;
        }

        try {
            // Llamar al Service para ejecutar el Registro (encriptación y doble inserción)
            $result = $this->authService->register([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena' => $contrasena,
                'telefono' => $telefono
            ]);

            http_response_code(201); // Created
            echo json_encode([
                'message' => 'Usuario registrado exitosamente. Rol "Deportista" asignado.',
                'data' => $result
            ]);
        } catch (Exception $e) {
            // Manejar errores de SQL (ej. correo duplicado)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'El correo electrónico ya está registrado.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error interno del servidor durante el registro.', 'detail' => $e->getMessage()]);
            }
        }
    }
}
