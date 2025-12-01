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

        // ------------------------------------------------------------------------------------
        // CORRECCIÓN CRÍTICA PARA EL HOSTING (Render, Docker)
        // ------------------------------------------------------------------------------------
        // Prioridad 1: Si se detecta que ya hay variables cruciales (DB_HOST) inyectadas
        // por el entorno (Render), usamos directamente esas variables inyectadas.
        // El ConfigLoader NO HACE 'return', sino que omite la lectura del archivo .env local
        // y deja que el resto del código use las variables inyectadas por Render.
        if (getenv("DB_HOST")) {
            // Ya que Render inyecta las variables, salimos de la función sin leer el archivo .env,
            // pero el código PHP ya tiene acceso a ellas vía $_ENV o getenv().
            return;
        }

        // ------------------------------------------------------------------------------------
        // Prioridad 2: Si NO hay variables del sistema (ejecución local), intenta cargar el .env
        // ------------------------------------------------------------------------------------
        if (!file_exists($path)) {
            // Si el archivo .env no existe y no hay variables de hosting, lanzamos un error o simplemente salimos.
            return;
        }

        // 3. Cargar el archivo .env si no hay variables del sistema.
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentarios
            if (str_starts_with(trim($line), "#")) {
                continue;
            }

            // Procesar la línea
            @list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!empty($name) && !empty($value)) {
                // Limpiar comillas si existen
                $value = trim($value, '"');
                $value = trim($value, "'");

                // Asignar al entorno de PHP para que sea accesible
                putenv(sprintf("%s=%s", $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
