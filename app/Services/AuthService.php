<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Repositories\AuthRepository;
use Exception;

class AuthService
{
    private AuthRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new AuthRepository();
    }

    // ==========================================================
    // Lógica para el LOGIN (RF-1)
    // ==========================================================
    public function login(string $correo, string $contrasena): ?array
    {
        // 1. Buscar el usuario por correo
        $user = $this->userRepository->findByCorreo($correo);

        if (!$user) {
            return null; // Credencial inválida
        }

        // 2. Verificar la contraseña encriptada (Hash)
        if (password_verify($contrasena, $user['contrasena'])) {

            // 3. Autenticación exitosa: generar token y limpiar datos
            unset($user['contrasena']); // ¡Eliminar el hash antes de devolver!

            // Generación de Token (Simulado, usar librería JWT en producción)
            $token = 'JWT_' . hash('sha256', $user['correo'] . time() . getenv('APP_KEY'));

            return [
                'user' => $user,
                'token' => $token,
                'expires_in' => 3600
            ];
        }

        return null; // Credencial inválida
    }

    // ==========================================================
    // Lógica para el REGISTER (RF-2)
    // ==========================================================
    public function register(array $data): array
    {
        // 1. Encriptar la contraseña antes de guardarla
        if (!isset($data['contrasena'])) {
            throw new Exception("La contraseña es obligatoria.");
        }
        $data['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);

        // 2. Crear el usuario y asignar el rol (manejo de transacciones en el Repository)
        $usuarioId = $this->userRepository->create($data);

        // 3. Retornar datos básicos
        return [
            'usuario_id' => $usuarioId,
            'correo' => $data['correo'],
            'rol_asignado' => 'Deportista',
        ];
    }
}
