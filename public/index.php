<?php
// Reportar errores para depuración (solo en entorno DEV)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// CORRECCIÓN CRÍTICA 1: MANEJO DE CORS Y PETICIÓN PREFLIGHT (OPTIONS)
// ESTE CÓDIGO DEBE ESTAR AL INICIO PARA ASEGURAR QUE LAS PETICIONES OPTIONS
// TERMINEN RÁPIDAMENTE CON UN CÓDIGO 200 SIN EJECUTAR LA LÓGICA DE LA APP.
// =========================================================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Éxito para el Preflight
    exit();                // Detiene completamente la ejecución
}

// =========================================================================
// CORRECCIÓN CRÍTICA 2: Cargar el Autoload de Composer
// =========================================================================
require __DIR__ . '/../vendor/autoload.php';

use App\Core\ConfigLoader;
use App\Core\Database;
use App\Core\Router;


// Definir la ruta base del proyecto
define('BASE_PATH', dirname(__DIR__));

// 1. Cargar la configuración de entorno (variables DB_*)
// Nota: Verifica que App\Core\ConfigLoader.php tenga el namespace correcto.
ConfigLoader::load(BASE_PATH);

// 2. Inicializar la conexión a la base de datos (si es necesario)
Database::getConnection();

// =========================================================================
// 3. INSTARCIAR EL ROUTER Y HACERLO GLOBAL
// =JOA
// =========================================================================
$router = new Router();
// Se añade global para asegurar la visibilidad en el archivo de rutas
global $router;

// =========================================================================
// 4. Cargar las rutas de la API (DEBE IR DESPUÉS DE LA INSTANCIACIÓN)
// =========================================================================
require BASE_PATH . '/routes/api.php';

// 5. Procesar la solicitud entrante
$router->dispatch();
