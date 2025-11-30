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
$router->post('/api/horario-base/clone', 'HorarioBaseController@cloneByDia');

// Rutas para la gestión de Usuarios (MOVIDAS/AÑADIDAS AQUÍ)
$router->post('/api/usuarios/list', 'UsuarioController@getUsuariosPaginated');
$router->put('/api/usuarios/{id}', 'UsuarioController@update');
$router->post('/api/usuarios/creditos', 'UsuarioController@getCreditos');

// RUTA PARA VALIDAR DISPONIBILIDAD
$router->post('/api/alquiler/validar-disponibilidad', 'AlquilerController@validarDisponibilidad');
// Listar complejos disponibles por distrito
$router->post('/api/alquiler/buscar-complejos-disponibles', 'AlquilerController@buscarComplejosDisponiblesPorDistrito');

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
$router->post('/api/complejos/{id}', 'ComplejoDeportivoController@update'); 
$router->put('/api/complejos/status/{id}', 'ComplejoDeportivoController@changeStatus');
$router->delete('/api/complejos/{id}', 'ComplejoDeportivoController@delete');

// Rutas para la gestión de horari oespecial
$router->post('/api/horario-especial/list', 'HorarioEspecialController@list');
$router->post('/api/horario-especial', 'HorarioEspecialController@create');
$router->put('/api/horario-especial/{id}', 'HorarioEspecialController@update');
$router->delete('/api/horario-especial/{id}', 'HorarioEspecialController@delete');
$router->put('/api/horario-especial/status/{id}', 'HorarioEspecialController@changeStatus');


// Rutas para la gestión de Reservas
$router->post('/api/reserva/list', 'ReservaController@listReservas');
$router->post('/api/reserva-detalle/list', 'ReservaController@listReservaDetalle');
$router->post('/api/reserva', 'ReservaController@crear');           
$router->put('/api/reserva/cancelar/{id}', 'ReservaController@cancelar');

// Rutas para la gestión de canchas fav
$router->post('/api/favoritos', 'ComplejoDeportivoFavoritoController@create');
$router->delete('/api/favoritos/{id}', 'ComplejoDeportivoFavoritoController@delete');
$router->post('/api/favoritos/list', 'ComplejoDeportivoFavoritoController@listByUsuario');

// GESTIÓN DE UBIGEO 
$router->get('/api/ubigeo/departamentos', 'UbigeoController@getDepartamentos');
$router->get('/api/ubigeo/provincias/{id}', 'UbigeoController@getProvincias');
$router->get('/api/ubigeo/distritos/{id}', 'UbigeoController@getDistritos');
// Ruta auxiliar para obtener el nombre completo de una ubicación por su distrito ID
$router->get('/api/ubigeo/detalle/{distrito_id}', 'UbigeoController@getDetalleUbicacion');

$router->post('/api/gestores/list', 'UsuarioRolController@list');
$router->post('/api/gestores/invite', 'UsuarioRolController@invite');
$router->delete('/api/gestores/{id}', 'UsuarioRolController@delete');

