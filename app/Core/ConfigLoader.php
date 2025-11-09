<?php
// app/Core/ConfigLoader.php

namespace App\Core;

class ConfigLoader
{
    public static function load(string $path): void
    {
        try {
            // Usamos createUnsafeImmutable para ser más tolerantes con archivos
            // que puedan tener problemas de codificación o terminación de línea (CRLF).
            $dotenv = \Dotenv\Dotenv::createUnsafeImmutable($path);

            // Carga las variables y las hace accesibles vía getenv() y $_ENV
            $dotenv->load();

            // Opcional: Ejecutar comprobaciones de seguridad básicas
            $dotenv->required(['APP_ENV', 'APP_KEY', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'])->notEmpty();
        } catch (\Exception $e) {
            // Esto evita que la aplicación se inicie si falta una variable crucial
            die("FATAL ERROR: No se pudieron cargar o verificar las variables de entorno: " . $e->getMessage());
        }
    }
}
