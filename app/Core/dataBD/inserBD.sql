-- ##################################################################
-- SCRIPTS DE INSERCIÓN DE DATOS FALTANTES (Corregido)
-- ##################################################################

-- ================================
-- UBIGEO
-- ================================
INSERT INTO Departamento (nombre) VALUES ('Lima'); -- ID 1
INSERT INTO Provincia (departamento_id, nombre) VALUES (1, 'Lima'); -- ID 1
INSERT INTO Distrito (provincia_id, nombre) VALUES (1, 'Miraflores'); -- ID 1
INSERT INTO Provincia (departamento_id, nombre) VALUES (1, 'Callao'); -- ID 2
INSERT INTO Distrito (provincia_id, nombre) VALUES (2, 'Bellavista'); -- ID 2

-- ================================
-- TIPO DE DEPORTE
-- ================================
INSERT INTO TipoDeporte (nombre, descripcion) VALUES
('Básquet', 'Deporte en cancha dura con canastas'),
('Tenis', 'Deporte individual o dobles en cancha de arcilla o cemento');

-- ================================
-- ROLES
-- ================================
INSERT INTO Roles (nombre) VALUES ('super_admin');

-- ================================
-- USUARIOS
-- ================================
INSERT INTO Usuarios (nombre, telefono, correo, contrasena, estado) VALUES
('Mario Pérez', '966554433', 'mario.perez@user.com', 'perez123', 'activo'),
('Luis Gómez', '966111222', 'luis.gomez@user.com', 'luis123', 'activo'),
('Ana Torres', '966333444', 'ana.torres@user.com', 'ana123', 'activo');

-- ================================
-- COMPLEJO DEPORTIVO
-- ================================
INSERT INTO ComplejoDeportivo 
(nombre, departamento_id, provincia_id, distrito_id, direccion_detalle, url_imagen, url_map, descripcion)
VALUES 
('Canchas del Sol', 1, 2, 2,
 'Calle Los Girasoles 456 - Bellavista',
 'https://demo.com/complejo2.jpg',
 'http://googleusercontent.com/maps.google.com/1',
 'Espacio deportivo para tenis y básquet');

-- ================================
-- CANCHAS
-- ================================
INSERT INTO Cancha (complejo_id, tipo_deporte_id, nombre, descripcion)
VALUES
(1, 1, 'Cancha de Fútbol 11 (Principal)', 'Pasto natural'),
(1, 2, 'Cancha de Básquet Techada', 'Piso de parqué'),
(1, 2, 'Cancha de Básquet Exterior', 'Piso sintético'),
(1, 1, 'Cancha de Tenis (Arcilla)', 'Cancha de arcilla reglamentaria');

-- ================================
-- ASIGNAR ROLES A USUARIOS
-- ================================
INSERT INTO UsuarioRol (usuario_id, rol_id, complejo_id)
VALUES
(1, 1, 1);

-- ================================
-- CONTACTOS DEL COMPLEJO
-- ================================
INSERT INTO Contactos (complejo_id, tipo, valor_contacto)
VALUES
(1, 'Teléfono Fijo', '(01) 321 0000');

-- ================================
-- SERVICIOS DEL COMPLEJO
-- ================================
INSERT INTO Servicios (complejo_id, nombre, descripcion, monto)
VALUES
(1, 'Toallas y duchas', 'Uso de vestuarios y toallas', 5.00),
(1, 'Raquetas y pelotas', 'Alquiler de equipo de tenis', 12.50);

-- ================================
-- HORARIO BASE
-- ================================
INSERT INTO HorarioBase (cancha_id, dia_semana, hora_inicio, hora_fin, monto)
VALUES
(2, 'Miércoles', '18:00', '19:30', 95.00),
(2, 'Jueves', '19:30', '21:00', 105.00);

-- ================================
-- SERVICIOS POR HORARIO
-- ================================
INSERT INTO ServicioPorHorario (servicio_id, horarioBase_id, is_obligatorio)
VALUES
(1, 1, TRUE);

-- ================================
-- HORARIO ESPECIAL
-- ================================
INSERT INTO HorarioEspecial (cancha_id, fecha, hora_inicio, hora_fin, monto, estado_horario, descripcion)
VALUES
(1, '2025-11-28', '08:00', '12:00', NULL, 'mantenimiento', 'Mantenimiento de piso'),
(2, '2025-12-31', '14:00', '16:00', 70.00, 'disponible', 'Tarifa especial por Fin de Año');

-- ================================
-- MÉTODOS DE PAGO
-- ================================
INSERT INTO MetodoPago (nombre) VALUES
('Tarjeta de Crédito'),
('Pago con Crédiots');

-- ================================
-- RESERVAS
-- ================================
INSERT INTO Reserva (usuario_id, metodo_pago_id, total_pago, estado, izipay_token, izipay_estado, fecha_pago)
VALUES
(2, 1, 80.00, 'confirmada', NULL, 'pagado', '2025-11-20 10:00:00'), -- Reserva 1
(3, 2, 150.00, 'pendiente_pago', 'TKN_12345', 'iniciado', NULL),       -- Reserva 2
(2, 1, 95.00, 'cancelado', NULL, 'fallido', '2025-11-21 11:00:00');    -- Reserva 3

-- ================================
-- RESERVA DETALLE
-- ================================
INSERT INTO ReservaDetalle (reserva_id, cancha_id, fecha, hora_inicio, hora_fin, precio)
VALUES
(1, 1, '2025-12-20', '08:00', '09:00', 80.00),
(2, 2, '2025-12-05', '18:00', '19:30', 95.00),
(2, 2, '2025-12-05', '19:30', '21:00', 55.00),
(3, 3, '2025-12-10', '10:00', '11:00', 95.00);

-- ================================
-- FAVORITOS
-- ================================
INSERT INTO ComplejoDeportivoFavoritos (usuario_id, complejo_id)
VALUES
(3, 1);

-- ================================
-- POLÍTICA DE CANCELACIÓN
-- ================================
INSERT INTO PoliticaCancelacion (complejo_id, horas_limite, estrategia_temprana)
VALUES
(1, 6, 'ReembolsoFisico');

-- ================================
-- CRÉDITOS DE USUARIO
-- ================================
INSERT INTO CreditoUsuario (usuario_id, monto, fecha_expiracion, origen_reserva_id, estado)
VALUES
(3, 50.00, '2026-06-30', NULL, 'activo'),
(2, 95.00, NULL, 3, 'activo'); -- Crédito por cancelación de Reserva 3
