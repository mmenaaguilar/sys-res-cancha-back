<?php
// routes/api.php

// Definición de una ruta de prueba: GET /api/status
// Esto verifica si el router y el controller están funcionando.

// Asume que la variable $router ya está disponible (instanciada en index.php)

$router->post('/api/register', 'AuthController@register');
$router->post('/api/login', 'AuthController@login');

$router->get('/api/tipo-deporte/combo', 'TipoDeporteController@combo');
$router->get('/api/ubigeo/search', 'UbigeoController@search');
$router->post('/api/complejos/search-available', 'ComplejoDeportivoController@searchAvailable');

// Rutas para la gestión de Contactos
$router->post('/api/contactos/list', 'ContactoController@listByComplejo');
$router->post('/api/contactos', 'ContactoController@create');
$router->put('/api/contactos/{id}', 'ContactoController@update');
$router->put('/api/contactos/status/{id}', 'ContactoController@changeStatus');
$router->delete('/api/contactos/{id}', 'ContactoController@delete'); // Eliminación física