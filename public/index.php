<?php
// Reportar errores para depuración (solo en entorno DEV)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// CORRECCIÓN CRÍTICA: Cargar el Autoload de Composer
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

// =========================================================================
// 3. INSTARCIAR EL ROUTER Y HACERLO GLOBAL (¡MOVIDO ARRIBA!)
// =========================================================================
$router = new Router();
// Se añade global para asegurar la visibilidad en el archivo de rutas
global $router;

// =========================================================================
// 4. Cargar las rutas de la API (DEBE IR DESPUÉS DE LA INSTANCIACIÓN)
// Esta era la línea 28 que estaba fallando: ahora se ejecuta con $router ya creado.
// =========================================================================
require BASE_PATH . '/routes/api.php';

// 5. Procesar la solicitud entrante
$router->dispatch();
