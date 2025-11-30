-- ##################################################################
-- SCRIPTS DE INSERCIÓN DE DATOS FALTANTES (Corregido)
-- ##################################################################

-- ================================
-- UBIGEO
-- ================================
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE Distrito;
TRUNCATE TABLE Provincia;
TRUNCATE TABLE Departamento;

-- Reiniciar contadores
ALTER TABLE Departamento AUTO_INCREMENT = 1;
ALTER TABLE Provincia AUTO_INCREMENT = 1;
ALTER TABLE Distrito AUTO_INCREMENT = 1;

INSERT INTO Departamento (departamento_id, nombre) VALUES 
(1, 'Arequipa'),
(2, 'Lima'),
(3, 'Tacna');

-- ----------------------------------------------------------
-- 2. PROVINCIAS
-- ----------------------------------------------------------

-- AREQUIPA (ID 1)
INSERT INTO Provincia (provincia_id, departamento_id, nombre) VALUES 
(1, 1, 'Arequipa'),
(2, 1, 'Camaná'),
(3, 1, 'Caravelí'),
(4, 1, 'Castilla'),
(5, 1, 'Caylloma'),
(6, 1, 'Condesuyos'),
(7, 1, 'Islay'),
(8, 1, 'La Unión');

-- LIMA (ID 2)
INSERT INTO Provincia (provincia_id, departamento_id, nombre) VALUES 
(9, 2, 'Lima'),
(10, 2, 'Barranca'),
(11, 2, 'Cajatambo'),
(12, 2, 'Canta'),
(13, 2, 'Cañete'),
(14, 2, 'Huaral'),
(15, 2, 'Huarochirí'),
(16, 2, 'Huaura'),
(17, 2, 'Oyón'),
(18, 2, 'Yauyos');

-- TACNA (ID 3)
INSERT INTO Provincia (provincia_id, departamento_id, nombre) VALUES 
(19, 3, 'Tacna'),
(20, 3, 'Candarave'),
(21, 3, 'Jorge Basadre'),
(22, 3, 'Tarata');

-- ----------------------------------------------------------
-- 3. DISTRITOS (Selección Completa de Capitales y Principales)
-- ----------------------------------------------------------

-- === AREQUIPA: Arequipa (Prov 1) ===
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(1, 'Arequipa'), (1, 'Alto Selva Alegre'), (1, 'Cayma'), (1, 'Cerro Colorado'), 
(1, 'Characato'), (1, 'Chiguata'), (1, 'Jacobo Hunter'), (1, 'La Joya'), 
(1, 'Mariano Melgar'), (1, 'Miraflores'), (1, 'Mollebaya'), (1, 'Paucarpata'), 
(1, 'Pocsi'), (1, 'Polobaya'), (1, 'Quequeña'), (1, 'Sabandia'), (1, 'Sachaca'), 
(1, 'San Juan de Siguas'), (1, 'San Juan de Tarucani'), (1, 'Santa Isabel de Siguas'), 
(1, 'Santa Rita de Siguas'), (1, 'Socabaya'), (1, 'Tiabaya'), (1, 'Uchumayo'), 
(1, 'Vitor'), (1, 'Yanahuara'), (1, 'Yarabamba'), (1, 'Yura'), 
(1, 'José Luis Bustamante y Rivero');

-- Arequipa: Camaná (Prov 2)
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(2, 'Camaná'), (2, 'José María Quimper'), (2, 'Mariano Nicolás Valcárcel'), (2, 'Mariscal Cáceres'), 
(2, 'Nicolás de Piérola'), (2, 'Ocoña'), (2, 'Quilca'), (2, 'Samuel Pastor');

-- Arequipa: Islay (Prov 7)
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(7, 'Mollendo'), (7, 'Cocachacra'), (7, 'Dean Valdivia'), (7, 'Islay'), (7, 'Mejía'), (7, 'Punta de Bombón');

-- Arequipa: Caylloma (Prov 5) - Chivay
INSERT INTO Distrito (provincia_id, nombre) VALUES (5, 'Chivay'), (5, 'Achoma'), (5, 'Cabanaconde'), (5, 'Majes');


-- === LIMA: Lima Metropolitana (Prov 9) ===
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(9, 'Cercado de Lima'), (9, 'Ancón'), (9, 'Ate'), (9, 'Barranco'), (9, 'Breña'), 
(9, 'Carabayllo'), (9, 'Chaclacayo'), (9, 'Chorrillos'), (9, 'Cieneguilla'), 
(9, 'Comas'), (9, 'El Agustino'), (9, 'Independencia'), (9, 'Jesús María'), 
(9, 'La Molina'), (9, 'La Victoria'), (9, 'Lince'), (9, 'Los Olivos'), 
(9, 'Lurigancho'), (9, 'Lurín'), (9, 'Magdalena del Mar'), (9, 'Miraflores'), 
(9, 'Pachacámac'), (9, 'Pucusana'), (9, 'Pueblo Libre'), (9, 'Puente Piedra'), 
(9, 'Punta Hermosa'), (9, 'Punta Negra'), (9, 'Rímac'), (9, 'San Bartolo'), 
(9, 'San Borja'), (9, 'San Isidro'), (9, 'San Juan de Lurigancho'), 
(9, 'San Juan de Miraflores'), (9, 'San Luis'), (9, 'San Martín de Porres'), 
(9, 'San Miguel'), (9, 'Santa Anita'), (9, 'Santa María del Mar'), 
(9, 'Santa Rosa'), (9, 'Santiago de Surco'), (9, 'Surquillo'), 
(9, 'Villa El Salvador'), (9, 'Villa María del Triunfo');

-- Lima: Cañete (Prov 13)
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(13, 'San Vicente de Cañete'), (13, 'Asia'), (13, 'Calango'), (13, 'Cerro Azul'), 
(13, 'Chilca'), (13, 'Coayllo'), (13, 'Imperial'), (13, 'Lunahuaná'), 
(13, 'Mala'), (13, 'Nuevo Imperial'), (13, 'Pacarán'), (13, 'Quilmaná'), 
(13, 'San Antonio'), (13, 'San Luis'), (13, 'Santa Cruz de Flores'), (13, 'Zúñiga');

-- Lima: Huaral (Prov 14)
INSERT INTO Distrito (provincia_id, nombre) VALUES (14, 'Huaral'), (14, 'Atavillos'), (14, 'Chancay'), (14, 'Aucallama');

-- Lima: Huaura (Prov 16)
INSERT INTO Distrito (provincia_id, nombre) VALUES (16, 'Huacho'), (16, 'Hualmay'), (16, 'Sayán'), (16, 'Huaura');


-- === TACNA: Tacna (Prov 19) ===
INSERT INTO Distrito (provincia_id, nombre) VALUES 
(19, 'Tacna'), (19, 'Alto de la Alianza'), (19, 'Calana'), (19, 'Ciudad Nueva'), 
(19, 'Inclán'), (19, 'Pachía'), (19, 'Palca'), (19, 'Pocollay'), (19, 'Sama'), 
(19, 'Coronel Gregorio Albarracín Lanchipa'), (19, 'La Yarada-Los Palos');

-- Tacna: Jorge Basadre (Prov 21)
INSERT INTO Distrito (provincia_id, nombre) VALUES (21, 'Locumba'), (21, 'Ilabaya'), (21, 'Ite');

-- Tacna: Tarata (Prov 22)
INSERT INTO Distrito (provincia_id, nombre) VALUES (22, 'Tarata'), (22, 'Susapaya'), (22, 'Ticaco'), (22, 'Tarucachi');

-- Tacna: Candarave (Prov 20)
INSERT INTO Distrito (provincia_id, nombre) VALUES (20, 'Candarave'), (20, 'Cairani'), (20, 'Curibaya');
SET FOREIGN_KEY_CHECKS = 1;



-- ================================
-- TIPO DE DEPORTE
-- ================================
INSERT INTO TipoDeporte (nombre, descripcion) VALUES
('Fútbol', 'Deporte de equipo que busca anotar goles en campo amplio.'),
('Básquet', 'Juego en equipo donde se anotan puntos encestando el balón.'),
('Tenis', 'Juego individual o en dobles usando raquetas y una red.'),
('Voley', 'Deporte en equipo que se juega pasando el balón sobre una red.');

-- ================================
-- ROLES
-- ================================
INSERT INTO Roles (rol_id, nombre) VALUES 
(1,'super_admin'),
(2,'gestor'),
(3,'deportista');

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
INSERT INTO Reserva (usuario_id, metodo_pago_id, total_pago, estado, fecha_pago)
VALUES
(2, 1, 80.00, 'confirmada', '2025-11-20 10:00:00'), -- Reserva 1
(3, 2, 150.00, 'pendiente_pago', '2025-11-20 10:00:00'),       -- Reserva 2
(2, 1, 95.00, 'cancelado', '2025-11-21 11:00:00');    -- Reserva 3

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
