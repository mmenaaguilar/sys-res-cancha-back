<?php
// routes/api.php


$router->post('/api/register', 'AuthController@register');
$router->post('/api/login', 'AuthController@login');

// Rutas para listados (Combos)
$router->get('/api/roles/combos', 'UsuarioRolController@getRolesCombo');
$router->get('/api/tipo-deporte/combo', 'TipoDeporteController@combo');
$router->get('/api/ubigeo/search', 'UbigeoController@search');
// $router->post('/api/complejos/search-available', 'ComplejoDeportivoController@searchAvailable');

// Rutas para la gestión de Contactos
$router->post('/api/contactos/list', 'ContactoController@listByComplejo');
$router->post('/api/contactos', 'ContactoController@create');
$router->put('/api/contactos/{id}', 'ContactoController@update');
$router->put('/api/contactos/status/{id}', 'ContactoController@changeStatus');
$router->delete('/api/contactos/{id}', 'ContactoController@delete');

// Rutas para la gestión de Servicios
$router->post('/api/servicios/list', 'ServicioController@listByFilters');
$router->post('/api/servicios', 'ServicioController@create');
$router->put('/api/servicios/{id}', 'ServicioController@update');
$router->put('/api/servicios/status/{id}', 'ServicioController@changeStatus');
$router->delete('/api/servicios/{id}', 'ServicioController@delete');

// Rutas para la gestión de ServicioPorHorario
$router->post('/api/servicio-horarios/list', 'ServicioPorHorarioController@listByFilters');
$router->post('/api/servicio-horarios', 'ServicioPorHorarioController@create');
$router->put('/api/servicio-horarios/{id}', 'ServicioPorHorarioController@update');
$router->put('/api/servicio-horarios/status/{id}', 'ServicioPorHorarioController@changeStatus');
$router->delete('/api/servicio-horarios/{id}', 'ServicioPorHorarioController@delete');

// Rutas para la gestión de Políticas de Cancelación
$router->post('/api/politicas/list', 'PoliticaController@getByComplejo');
$router->post('/api/politicas', 'PoliticaController@create');
$router->put('/api/politicas/{id}', 'PoliticaController@update');
$router->put('/api/politicas/status/{id}', 'PoliticaController@changeStatus');
$router->delete('/api/politicas/{id}', 'PoliticaController@delete');

// Rutas para la gestión de Asignación de Roles (UsuarioRol)
$router->post('/api/usuario-roles', 'UsuarioRolController@create');
$router->post('/api/usuario-roles/list', 'UsuarioRolController@listUsuarioRoles');
$router->put('/api/usuario-roles/{id}', 'UsuarioRolController@update');
$router->put('/api/usuario-roles/status/{id}', 'UsuarioRolController@changeStatus'); 
$router->delete('/api/usuario-roles/{id}', 'UsuarioRolController@delete');

// RUTAS PARA LA GESTIÓN DE HORARIO BASE
$router->post('/api/horario-base/list', 'HorarioBaseController@getPaginated');
$router->post('/api/horario-base', 'HorarioBaseController@create');
$router->put('/api/horario-base/{id}', 'HorarioBaseController@update');
$router->put('/api/horario-base/status/{id}', 'HorarioBaseController@changeStatus');
$router->delete('/api/horario-base/{id}', 'HorarioBaseController@delete');

// Rutas para la gestión de Usuarios (MOVIDAS/AÑADIDAS AQUÍ)
$router->post('/api/usuarios/list', 'UsuarioController@getUsuariosPaginated');
$router->put('/api/usuarios/{id}', 'UsuarioController@update');

// RUTA PARA VALIDAR DISPONIBILIDAD
$router->post('/api/alquiler/validar-disponibilidad', 'AlquilerController@validarDisponibilidad');

// Rutas para la gestión de Canchas
$router->post('/api/canchas/list', 'CanchaController@listByComplejoPaginated');
$router->post('/api/canchas', 'CanchaController@create');                   
$router->put('/api/canchas/{id}', 'CanchaController@update');               
$router->put('/api/canchas/status/{id}', 'CanchaController@changeStatus');  
$router->delete('/api/canchas/{id}', 'CanchaController@delete');

// Rutas para la gestión de ComplejoDeportivo
$router->post('/api/complejos/list', 'ComplejoDeportivoController@getComplejo');
$router->post('/api/complejos', 'ComplejoDeportivoController@create');
$router->put('/api/complejos/{id}', 'ComplejoDeportivoController@update');
$router->put('/api/complejos/status/{id}', 'ComplejoDeportivoController@changeStatus');
$router->delete('/api/complejos/{id}', 'ComplejoDeportivoController@delete');