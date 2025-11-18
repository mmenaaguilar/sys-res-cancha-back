-- ##################################################################
-- SCRIPT DE CREACIÓN DE BASE DE DATOS: sistema_reservas_canchas
-- ##################################################################

-- 1. ELIMINAR BASE DE DATOS EXISTENTE (SOLO PARA DESARROLLO)
DROP DATABASE IF EXISTS sistema_reservas_canchas;

-- 2. CREAR BASE DE DATOS
CREATE DATABASE IF NOT EXISTS sistema_reservas_canchas 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. USAR LA BASE DE DATOS
USE sistema_reservas_canchas;

-- ##################################################################
-- TABLAS DE UBIGEO (RECOMENDADO PARA BUSCADORES Y DIRECCIONES)
-- ##################################################################

CREATE TABLE Departamento (
    departamento_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE Provincia (
    provincia_id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    UNIQUE KEY uk_provincia (departamento_id, nombre),
    FOREIGN KEY (departamento_id) REFERENCES Departamento(departamento_id)
);

CREATE TABLE Distrito (
    distrito_id INT AUTO_INCREMENT PRIMARY KEY,
    provincia_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    UNIQUE KEY uk_distrito (provincia_id, nombre),
    FOREIGN KEY (provincia_id) REFERENCES Provincia(provincia_id)
);

-- ##################################################################
-- TABLAS DE CONFIGURACIÓN MAESTRA
-- ##################################################################

CREATE TABLE TipoDeporte (
    tipo_deporte_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

-- ##################################################################
-- TABLAS DE USUARIOS Y ROLES
-- ##################################################################

CREATE TABLE Usuarios (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    telefono VARCHAR(255),
    correo VARCHAR(255) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

CREATE TABLE Roles (
    rol_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

-- ##################################################################
-- TABLAS DE ESTRUCTURA Y GESTIÓN DE CANCHAS
-- ##################################################################

CREATE TABLE ComplejoDeportivo (
    complejo_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,

    -- NUEVA UBICACIÓN ESTRUCTURADA
    departamento_id INT,
    provincia_id INT,
    distrito_id INT,
    direccion_detalle VARCHAR(255), -- calle, número, referencia

    url_imagen VARCHAR(500),
    url_map VARCHAR(500),
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',

    FOREIGN KEY (departamento_id) REFERENCES Departamento(departamento_id),
    FOREIGN KEY (provincia_id) REFERENCES Provincia(provincia_id),
    FOREIGN KEY (distrito_id) REFERENCES Distrito(distrito_id)
);

CREATE TABLE Cancha (
    cancha_id INT AUTO_INCREMENT PRIMARY KEY,
    complejo_id INT NOT NULL,
    tipo_deporte_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    url_imagen VARCHAR(500),
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_deporte_id) REFERENCES TipoDeporte(tipo_deporte_id)
);

CREATE TABLE UsuarioRol (
    usuarioRol_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    rol_id INT NOT NULL,
    complejo_id INT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    UNIQUE KEY idx_unicidad_compuesta (usuario_id, rol_id, complejo_id),

    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (rol_id) REFERENCES Roles(rol_id) ON DELETE CASCADE,
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE
);

CREATE TABLE Contactos (
    contacto_id INT AUTO_INCREMENT PRIMARY KEY,
    complejo_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    valor_contacto VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE
);

CREATE TABLE Servicios (
    servicio_id INT AUTO_INCREMENT PRIMARY KEY,
    complejo_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    monto DECIMAL(10,2) NOT NULL,
    is_obligatorio BOOLEAN DEFAULT FALSE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE
);

CREATE TABLE ServicioPorDeporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    tipo_deporte_id INT NOT NULL,
    FOREIGN KEY (servicio_id) REFERENCES Servicios(servicio_id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_deporte_id) REFERENCES TipoDeporte(tipo_deporte_id)
);

-- ##################################################################
-- HORARIOS Y PRECIOS
-- ##################################################################

CREATE TABLE HorarioBase (
    horario_base_id INT AUTO_INCREMENT PRIMARY KEY,
    cancha_id INT NOT NULL,
    dia_semana ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cancha_id) REFERENCES Cancha(cancha_id) ON DELETE CASCADE
);

CREATE TABLE HorarioEspecial (
    horario_especial_id INT AUTO_INCREMENT PRIMARY KEY,
    cancha_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    monto DECIMAL(10,2),
    estado ENUM('disponible','bloqueado','mantenimiento'),
    descripcion VARCHAR(255),
    FOREIGN KEY (cancha_id) REFERENCES Cancha(cancha_id) ON DELETE CASCADE
);

-- ##################################################################
-- RESERVAS Y PAGOS
-- ##################################################################

CREATE TABLE MetodoPago (
    metodo_pago_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Reserva (
    reserva_id INT AUTO_INCREMENT PRIMARY KEY,
    cancha_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    metodo_pago_id INT,
    total_pago DECIMAL(10,2) DEFAULT 0.00,
    estado ENUM('pendiente','pagado','cancelado','finalizado') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cancha_id) REFERENCES Cancha(cancha_id),
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id),
    FOREIGN KEY (metodo_pago_id) REFERENCES MetodoPago(metodo_pago_id)
);

CREATE TABLE ReservaServicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    servicio_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (reserva_id) REFERENCES Reserva(reserva_id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES Servicios(servicio_id)
);

-- ##################################################################
-- CALIFICACIONES, FAVORITOS y POLÍTICAS
-- ##################################################################

CREATE TABLE ComplejoCalificaciones (
    calificacion_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    complejo_id INT NOT NULL,
    puntuacion TINYINT CHECK (puntuacion BETWEEN 1 AND 5),
    fecha_calificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (usuario_id, complejo_id),
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE
);

CREATE TABLE CanchaFavoritos (
    favorito_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cancha_id INT NOT NULL,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (usuario_id, cancha_id),
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (cancha_id) REFERENCES Cancha(cancha_id) ON DELETE CASCADE
);

CREATE TABLE PoliticaCancelacion (
    politica_id INT AUTO_INCREMENT PRIMARY KEY,
    complejo_id INT NOT NULL,
    horas_limite INT NOT NULL,
    estrategia_temprana ENUM('CreditoCompleto','ReembolsoFisico') NOT NULL UNIQUE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (complejo_id) REFERENCES ComplejoDeportivo(complejo_id) ON DELETE CASCADE
);

CREATE TABLE CreditoUsuario (
    credito_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_otorgado DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATE,
    origen_reserva_id INT,
    estado ENUM('activo','usado','expirado') DEFAULT 'activo',
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(usuario_id) ON DELETE CASCADE,
    FOREIGN KEY (origen_reserva_id) REFERENCES Reserva(reserva_id) ON DELETE SET NULL
);
