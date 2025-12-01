<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase Singleton para manejar la conexión a la base de datos (MySQL/MariaDB).
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtiene la única instancia de la conexión PDO.
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST');
            $database = getenv('DB_DATABASE');
            $username = getenv('DB_USERNAME');
            $password = getenv('DB_PASSWORD');

            // TiDB Serverless usa el puerto 4000
            $port = getenv('DB_PORT') ?: '4000';

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,

                // =========================================================================
                // OPCIONES CRÍTICAS PARA TI DB CLOUD (SSL)
                // Se fuerza el uso de certificados para cifrar la conexión, como exige TiDB.
                // =========================================================================
                PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',

                // Se fuerza la verificación del certificado del servidor.
                // Esto es fundamental para cumplir con los requisitos de seguridad de TiDB.
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ];

            try {
                // Registro de diagnóstico antes de intentar la conexión
                error_log("Intentando conectar a TiDB. DSN: " . $dsn);

                // Intentar la conexión con las opciones SSL
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // =========================================================================
                // DIAGNÓSTICO: Error de Conexión, ahora probablemente por SSL o Credenciales
                // =========================================================================
                $details = [
                    'message' => $e->getMessage(),
                    'host' => $host,
                    'dsn_attempted' => $dsn,
                    'error_type' => 'SSL/AUTH Failed'
                ];
                $error_message = "Error de conexión a la base de datos. Revise SSL/Credenciales. Detalles: " . json_encode($details);
                error_log($error_message);

                // Devuelve un error 500 para la API
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Error en el servidor', 'details' => $details]);
                exit; // Detiene la ejecución
            }
        }
        return self::$instance;
    }
}
