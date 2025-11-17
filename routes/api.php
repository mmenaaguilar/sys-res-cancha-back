<?php
// routes/api.php

// Definición de una ruta de prueba: GET /api/status
// Esto verifica si el router y el controller están funcionando.

// Asume que la variable $router ya está disponible (instanciada en index.php)

$router->post('/api/register', 'AuthController@register');
$router->post('/api/login', 'AuthController@login');

$router->get('/api/tipo-deporte/combo', 'TipoDeporteController@combo');
$router->get('/api/ubigeo/search', 'UbigeoController@search');

$router->get('/api/complejos/search', 'ComplejoDeportivoController@search');