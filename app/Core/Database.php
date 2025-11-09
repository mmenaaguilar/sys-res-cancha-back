<?php
// app/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    // Usamos el patrón Singleton para asegurar solo una conexión a la BD
    private static ?PDO $pdoInstance = null;

    public static function getConnection(): PDO
    {
        if (self::$pdoInstance === null) {
            // Obtener credenciales del entorno (cargadas desde el .env)
            $host = getenv('DB_HOST');
            $db = getenv('DB_DATABASE');
            $user = getenv('DB_USERNAME');
            $pass = getenv('DB_PASSWORD');
            $port = getenv('DB_PORT') ?: '3306'; // Puerto por defecto

            // ----------------------------------------------------
            // BLOQUE DE DEPURACIÓN CRÍTICO
            // ----------------------------------------------------

            // Si el nombre de usuario está vacío, el .env no se cargó correctamente
            if (empty($user)) {
                die("❌ ERROR FATAL DE LECTURA DE .ENV. Revisa la línea 'DB_USERNAME' en tu archivo .env y el ConfigLoader.");
            }
            // ----------------------------------------------------
            // FIN DEL BLOQUE DE DEPURACIÓN
            // ----------------------------------------------------


            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            try {
                self::$pdoInstance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Manejo de errores
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch por array asociativo
                ]);
            } catch (PDOException $e) {
                // DETENER la aplicación si falla la conexión a la BD (crítico)
                die("Error de conexión a la base de datos. Por favor, revisa el archivo .env: " . $e->getMessage());
            }
        }

        return self::$pdoInstance;
    }
}
