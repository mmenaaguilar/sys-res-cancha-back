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
    // Lógica para el LOGIN (RF-1)
    public function login(string $correo, string $contrasena): ?array
    {
        $user = $this->userRepository->findByCorreo($correo);

        if (!$user) {
            return null;
        }

        if (password_verify($contrasena, $user['contrasena'])) {

            unset($user['contrasena']); 

            $roles = $this->userRepository->getRolesByUserId($user['usuario_id']);


            $token = 'JWT_' . hash('sha256', $user['correo'] . time() . getenv('APP_KEY'));

            return [
                'user' => $user,
                'token' => $token,
                'expires_in' => 3600,
                'roles' => $roles
            ];
        }

        return null;
    }

    // Lógica para el REGISTER (RF-2)
    public function register(array $data): array
    {

        if (!isset($data['contrasena'])) {
            throw new Exception("La contraseña es obligatoria.");
        }
        $data['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);

        $usuarioId = $this->userRepository->create($data);

        return [
            'usuario_id' => $usuarioId,
            'correo' => $data['correo'],
            'rol_asignado' => 'Deportista',
            'roles' => []
        ];
    }
}
