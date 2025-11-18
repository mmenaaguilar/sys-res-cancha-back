<?php
// app/Core/Router.php

namespace App\Core;

class Router
{
    protected array $routes = [];

    /**
     * Registra una ruta GET.
     * @param string $uri La URL de la ruta (ej. '/api/canchas/{id}').
     * @param string $controller Acción del controlador (ej. 'CanchaController@mostrar').
     */
    public function get(string $uri, string $controller): void
    {
        $this->routes['GET'][$uri] = $controller;
    }

    /**
     * Registra una ruta POST.
     */
    public function post(string $uri, string $controller): void
    {
        $this->routes['POST'][$uri] = $controller;
    }

    // --- MÉTODOS AÑADIDOS PARA SOPORTAR EL CRUD ---

    /**
     * Registra una ruta PUT (para actualizaciones).
     */
    public function put(string $uri, string $controller): void
    {
        $this->routes['PUT'][$uri] = $controller;
    }

    /**
     * Registra una ruta DELETE (para eliminaciones).
     */
    public function delete(string $uri, string $controller): void
    {
        $this->routes['DELETE'][$uri] = $controller;
    }
    
    // ----------------------------------------------

    /**
     * Despacha la petición a su destino.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Comprobar si existe el método y la URI
        if (!isset($this->routes[$method])) {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        foreach ($this->routes[$method] as $routeUri => $action) {
            // Convertir la ruta a una expresión regular para manejar parámetros {id}
            // NOTA: Se debe escapar el separador de patrón, que es '#'.
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $routeUri);

            if (preg_match("#^$pattern$#", $uri, $matches)) {

                // Extraer el Controller y el método
                list($controllerName, $methodName) = explode('@', $action);

                // Añadir el namespace App\Controllers\
                $controllerClass = "App\\Controllers\\" . $controllerName;

                if (!class_exists($controllerClass)) {
                    http_response_code(500);
                    echo json_encode(['error' => "Controller class not found: $controllerClass"]);
                    return;
                }

                $controller = new $controllerClass();

                // Llamar al método del controlador, pasando los parámetros capturados
                $params = array_slice($matches, 1);

                call_user_func_array([$controller, $methodName], $params);
                return;
            }
        }

        // Si no se encontró ninguna ruta
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
}
