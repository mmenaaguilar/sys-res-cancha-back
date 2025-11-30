<?php
// public/index.php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); 
}
// 1. Cargar el autocargador de Composer (librerías y clases App\)
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ConfigLoader;
use App\Core\Router;

// 2. Cargar las variables del .env (Host, DB_USER, etc.)
// La raíz del proyecto es un nivel arriba de public/
ConfigLoader::load(dirname(__DIR__));

// 3. Crear el Router
$router = new Router();

// 4. Cargar las definiciones de Rutas (el mapa)
require_once __DIR__ . '/../routes/api.php';

// 5. Despachar la petición (iniciar la aplicación)
$router->dispatch();
