<?php

namespace App\Core;

/**
 * Clase para cargar variables de entorno desde un archivo .env o desde variables del sistema (como Render).
 */
class ConfigLoader
{
    /**
     * Carga variables de entorno. Prioriza las variables del sistema (Render).
     * @param string $dir Directorio donde se encuentra el archivo .env
     */
    public static function load(string $dir): void
    {
        $path = $dir . '/.env';

        // 1. Prioridad 1: Si ya existe una variable de entorno crucial (como DB_HOST), 
        // asumimos que el entorno de hosting (Render) ya inyectó todas las variables
        // y NO es necesario leer el archivo .env local. Esto previene errores de lectura.
        if (isset($_ENV['DB_HOST']) || getenv('DB_HOST')) {
            return;
        }

        // 2. Prioridad 2: Si no hay variables del sistema, intenta cargar el .env local 
        // (Esto es útil si lo ejecutas en tu PC).
        if (!file_exists($path)) {
            return;
        }

        // 3. Cargar el archivo .env si no hay variables del sistema.
        // Se añade '\' antes de las constantes para asegurar que se usan las globales de PHP.
        $lines = \file($path, \FILE_IGNORE_EMPTY_LINES | \FILE_SKIP_NEW_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue; // Ignorar comentarios
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
