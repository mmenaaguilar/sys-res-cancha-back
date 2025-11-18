INSERT INTO TipoDeporte (nombre, descripcion) VALUES
('Fútbol 5', 'Cancha de grass sintético o cemento para equipos de cinco jugadores.'),
('Fútbol 7', 'Cancha de grass natural o sintético para equipos de siete jugadores.'),
('Básquetbol', 'Cancha con tablero y aro para juegos de dos o cinco jugadores por equipo.'),
('Vóley', 'Cancha para la práctica de voleibol.'),
('Tenis', 'Cancha de arcilla o cemento para la práctica de tenis.'),
('Fútbol 11', 'Cancha de fútbol estándar (cancha grande) para equipos completos.'),
('Pádel', 'Cancha cerrada y acristalada para el deporte de pádel.');

INSERT INTO Roles (rol_id, nombre) VALUES
(1, 'cliente'),
(2, 'gestor_cancha'),
(3, 'admin_sistema');


-- ===========================
-- INSERTAR DEPARTAMENTO
-- ===========================

INSERT INTO Departamento (nombre)
VALUES ('Tacna');

-- ===========================
-- INSERTAR PROVINCIAS DE TACNA
-- ===========================

INSERT INTO Provincia (departamento_id, nombre) VALUES
(1, 'Tacna'),
(1, 'Tarata'),
(1, 'Jorge Basadre'),
(1, 'Candarave');

-- ===========================
-- INSERTAR DISTRITOS DE TACNA
-- ===========================

-- Provincia Tacna (ID 1)
INSERT INTO Distrito (provincia_id, nombre) VALUES
(1, 'Tacna'),
(1, 'Alto de la Alianza'),
(1, 'Calana'),
(1, 'Ciudad Nueva'),
(1, 'Inclán'),
(1, 'Pachía'),
(1, 'Palca'),
(1, 'Pocollay'),
(1, 'Sama'),
(1, 'Coronel Gregorio Albarracín Lanchipa');

-- Provincia Tarata (ID 2)
INSERT INTO Distrito (provincia_id, nombre) VALUES
(2, 'Tarata'),
(2, 'Heroes Albarracín'),
(2, 'Estique'),
(2, 'Estique-Pampa'),
(2, 'Sitajara'),
(2, 'Susapaya'),
(2, 'Tarucachi'),
(2, 'Ticaco');

-- Provincia Jorge Basadre (ID 3)
INSERT INTO Distrito (provincia_id, nombre) VALUES
(3, 'Locumba'),
(3, 'Ite'),
(3, 'Ilabaya');

-- Provincia Candarave (ID 4)
INSERT INTO Distrito (provincia_id, nombre) VALUES
(4, 'Candarave'),
(4, 'Cairani'),
(4, 'Camilaca'),
(4, 'Curibaya'),
(4, 'Huanuara'),
(4, 'Quilahuani');

-- ===========================
-- INSERTAR COMPLEJOS DEPORTIVOS EJEMPLO (TACNA)
-- ===========================

INSERT INTO ComplejoDeportivo (
    nombre, departamento_id, provincia_id, distrito_id, direccion_detalle,
    url_imagen, url_map, descripcion, estado
) VALUES
('Complejo Deportivo La Bombonera', 1, 1, 10, 'Av. La Cultura – Gregorio Albarracín', NULL, NULL, 'Cancha sintética con iluminación.', 'activo'),

('Complejo Deportivo El Triunfo', 1, 1, 1, 'Av. Leguía – Tacna Centro', NULL, NULL, 'Cancha techada y grass sintético.', 'activo'),

('Complejo Deportivo Ciudad Nueva', 1, 1, 4, 'Av. Circunvalación – Ciudad Nueva', NULL, NULL, 'Complejo con canchas múltiples.', 'activo');

INSERT INTO Usuarios (nombre, telefono, correo, contrasena, estado) VALUES
('Juan Pérez (Cliente)', '991234567', 'juan.perez@example.com', 'password_hash_cliente_1', 'activo'),
('María García (Gestora)', '998765432', 'maria.garcia@gestor.com', 'password_hash_gestor_2', 'activo'),
('Pedro Sánchez (Admin)', '990001112', 'pedro.admin@sistema.com', 'password_hash_admin_3', 'activo');