<?php
// Reportar errores para depuración (solo en entorno DEV)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// CORRECCIÓN CRÍTICA: Cargar el Autoload de Composer
// La clase App\Core\ConfigLoader no se encuentra si no se carga primero.
// =========================================================================
require __DIR__ . '/../vendor/autoload.php';

use App\Core\ConfigLoader;
use App\Core\Database;
use App\Core\Router;


// Definir la ruta base del proyecto
define('BASE_PATH', dirname(__DIR__));

// 1. Cargar la configuración de entorno (variables DB_*)
ConfigLoader::load(BASE_PATH);

// 2. Inicializar la conexión a la base de datos (si es necesario)
Database::getConnection();

// 3. Cargar las rutas de la API
require BASE_PATH . '/routes/api.php';

// 4. Procesar la solicitud entrante
$router = new Router();
$router->dispatch();