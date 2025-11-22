-- ================================
-- UBIGEO
-- ================================
INSERT INTO Departamento (nombre) VALUES ('Lima');
INSERT INTO Provincia (departamento_id, nombre) VALUES (1, 'Lima');
INSERT INTO Distrito (provincia_id, nombre) VALUES (1, 'Miraflores');

-- ================================
-- TIPO DE DEPORTE
-- ================================
INSERT INTO TipoDeporte (nombre, descripcion) VALUES
('Fútbol', 'Deporte de equipo'),
('Vóley', 'Deporte en equipo');

-- ================================
-- ROLES
-- ================================
INSERT INTO Roles (nombre) VALUES
('admin'),
('cliente'),
('encargado');

-- ================================
-- USUARIOS
-- ================================
INSERT INTO Usuarios (nombre, telefono, correo, contrasena, estado) VALUES
('Administrador', '999999999', 'admin@demo.com', '123456', 'activo'),
('Cliente Prueba', '988888888', 'cliente@demo.com', 'abc123', 'activo'),
('Encargado 1', '977777777', 'encargado@demo.com', 'enc2025', 'activo');

-- ================================
-- COMPLEJO DEPORTIVO
-- ================================
INSERT INTO ComplejoDeportivo 
(nombre, departamento_id, provincia_id, distrito_id, direccion_detalle, url_imagen, url_map, descripcion)
VALUES 
('Complejo Deportivo Los Olivos', 1, 1, 1,
 'Av. Principal 123 - Miraflores',
 'https://demo.com/complejo1.jpg',
 'https://maps.google.com/demo',
 'Complejo principal');

-- ================================
-- CANCHAS
-- ================================
INSERT INTO Cancha (complejo_id, tipo_deporte_id, nombre, url_imagen, descripcion)
VALUES
(1, 1, 'Cancha de Fútbol 7', 'https://demo.com/cancha1.jpg', 'Pasto sintético profesional'),
(1, 2, 'Cancha de Vóley Arena', 'https://demo.com/cancha2.jpg', 'Arena fina especial');

-- ================================
-- ASIGNAR ROLES A USUARIOS
-- ================================
INSERT INTO UsuarioRol (usuario_id, rol_id, complejo_id)
VALUES
(1, 1, NULL),
(2, 2, NULL),
(3, 3, 1);

-- ================================
-- CONTACTOS DEL COMPLEJO
-- ================================
INSERT INTO Contactos (complejo_id, tipo, valor_contacto)
VALUES
(1, 'WhatsApp', '999999999'),
(1, 'Correo', 'contacto@complejo1.com');

-- ================================
-- SERVICIOS DEL COMPLEJO
-- ================================
INSERT INTO Servicios (complejo_id, nombre, descripcion, monto)
VALUES
(1, 'Alquiler de balones', 'Balón de fútbol o vóley', 10.00),
(1, 'Iluminación', 'Luz completa del campo', 15.00);

-- ================================
-- HORARIO BASE
-- ================================
INSERT INTO HorarioBase (cancha_id, dia_semana, hora_inicio, hora_fin, monto)
VALUES
(1, 'Lunes', '08:00', '09:00', 80.00),
(1, 'Lunes', '09:00', '10:00', 80.00),
(2, 'Martes', '10:00', '11:00', 60.00);

-- ================================
-- SERVICIOS POR HORARIO
-- ================================
INSERT INTO ServicioPorHorario (servicio_id, horarioBase_id)
VALUES
(1, 1),
(2, 1),
(1, 2);

-- ================================
-- HORARIO ESPECIAL
-- ================================
INSERT INTO HorarioEspecial (cancha_id, fecha, hora_inicio, hora_fin, monto, estado, descripcion)
VALUES
(1, '2025-12-25', '08:00', '10:00', 120.00, 'bloqueado', 'Navidad');

-- ================================
-- MÉTODOS DE PAGO
-- ================================
INSERT INTO MetodoPago (nombre) VALUES
('Efectivo'),
('Yape'),
('Transferencia');

-- ================================
-- RESERVA
-- ================================
INSERT INTO Reserva (cancha_id, usuario_id, fecha, hora_inicio, hora_fin, metodo_pago_id, total_pago, estado)
VALUES
(1, 2, '2025-12-20', '08:00', '09:00', 1, 80.00, 'confirmada');

-- ================================
-- CALIFICACIONES
-- ================================
INSERT INTO ComplejoCalificaciones (usuario_id, complejo_id, puntuacion)
VALUES
(2, 1, 5);

-- ================================
-- FAVORITOS
-- ================================
INSERT INTO CanchaFavoritos (usuario_id, cancha_id)
VALUES
(2, 1);

-- ================================
-- POLÍTICA DE CANCELACIÓN
-- ================================
INSERT INTO PoliticaCancelacion (complejo_id, horas_limite, estrategia_temprana)
VALUES
(1, 4, 'CreditoCompleto');

-- ================================
-- CRÉDITOS DE USUARIO
-- ================================
INSERT INTO CreditoUsuario (usuario_id, monto, origen_reserva_id, estado)
VALUES
(2, 15.00, 1, 'activo');
