-- ============================================================
-- FitStock - Base de datos del gimnasio
-- ============================================================
-- Elimina la base de datos si existe (para refrescar desde cero)
DROP DATABASE IF EXISTS fitstock;

CREATE DATABASE fitstock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitstock;

-- ============================================================
-- TABLA: usuarios
-- Almacena los usuarios del sistema con sus roles
-- ============================================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','entrenador','cliente') NOT NULL DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: material
-- Almacena maquinas (tipo=maquina) y material prestable (tipo=prestable)
-- ============================================================
CREATE TABLE material (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    nombre_equipo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('operativo','averiado','mantenimiento') NOT NULL DEFAULT 'operativo',
    tipo ENUM('maquina','prestable') NOT NULL DEFAULT 'prestable',
    qr_identificador VARCHAR(100) UNIQUE,
    ultima_rev DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: prestamos
-- Registra los prestamos de material prestable a usuarios
-- ============================================================
CREATE TABLE prestamos (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_material INT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion DATE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_material) REFERENCES material(id_material) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos_stock
-- Gestiona el inventario de suplementos y productos vendibles
-- ============================================================
CREATE TABLE productos_stock (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_prod VARCHAR(100) NOT NULL,
    cant_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: incidencias
-- Registra incidencias reportadas sobre maquinas
-- ============================================================
CREATE TABLE incidencias (
    id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_material INT,
    id_user_rep INT,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    estado_inc ENUM('abierta','en_proceso','resuelta') NOT NULL DEFAULT 'abierta',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_material) REFERENCES material(id_material) ON DELETE SET NULL,
    FOREIGN KEY (id_user_rep) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: accesos_registro
-- Registro de entradas de usuarios al gimnasio
-- ============================================================
CREATE TABLE accesos_registro (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    fecha_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- Usuario de base de datos para la aplicacion
-- ============================================================
CREATE USER IF NOT EXISTS fitstock IDENTIFIED BY 'fitstock';
GRANT ALL ON fitstock.* TO fitstock;
FLUSH PRIVILEGES;

-- ============================================================
-- DATOS DE EJEMPLO: Usuarios
-- (password para todos: "password")
-- ============================================================
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin FitStock', 'admin@fitstock.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Carlos Garcia', 'carlos@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'entrenador'),
('Ana Martinez', 'ana@correo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente');

-- ============================================================
-- DATOS DE EJEMPLO: Material prestable (mancuernas, pesas, balones)
-- Aparecen en la pestana PRESTAMOS
-- ============================================================
INSERT INTO material (nombre_equipo, descripcion, estado, tipo, qr_identificador, ultima_rev) VALUES
('Mancuerna 5kg',  'Mancuerna de 5kg para ejercicios de brazo',     'operativo', 'prestable', 'MAN-001', '2026-01-15'),
('Mancuerna 5kg',  'Mancuerna de 5kg para ejercicios de brazo',     'operativo', 'prestable', 'MAN-002', '2026-01-15'),
('Mancuerna 8kg',  'Mancuerna de 8kg para ejercicios de brazo',     'operativo', 'prestable', 'MAN-003', '2026-01-15'),
('Mancuerna 8kg',  'Mancuerna de 8kg para ejercicios de brazo',     'operativo', 'prestable', 'MAN-004', '2026-01-15'),
('Mancuerna 10kg', 'Mancuerna de 10kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-005', '2026-01-15'),
('Mancuerna 10kg', 'Mancuerna de 10kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-006', '2026-01-15'),
('Mancuerna 12kg', 'Mancuerna de 12kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-007', '2026-01-15'),
('Mancuerna 12kg', 'Mancuerna de 12kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-008', '2026-01-15'),
('Mancuerna 15kg', 'Mancuerna de 15kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-009', '2026-01-15'),
('Mancuerna 15kg', 'Mancuerna de 15kg para ejercicios de brazo',    'operativo', 'prestable', 'MAN-010', '2026-01-15'),
('Pesa 10kg',      'Disco de pesa de 10kg',                         'operativo', 'prestable', 'PES-001', '2026-02-01'),
('Pesa 10kg',      'Disco de pesa de 10kg',                         'operativo', 'prestable', 'PES-002', '2026-02-01'),
('Pesa 15kg',      'Disco de pesa de 15kg',                         'operativo', 'prestable', 'PES-003', '2026-02-01'),
('Pesa 15kg',      'Disco de pesa de 15kg',                         'operativo', 'prestable', 'PES-004', '2026-02-01'),
('Pesa 20kg',      'Disco de pesa de 20kg',                         'operativo', 'prestable', 'PES-005', '2026-02-01'),
('Pesa 20kg',      'Disco de pesa de 20kg',                         'operativo', 'prestable', 'PES-006', '2026-02-01'),
('Pesa 25kg',      'Disco de pesa de 25kg',                         'operativo', 'prestable', 'PES-007', '2026-02-01'),
('Pesa 25kg',      'Disco de pesa de 25kg',                         'operativo', 'prestable', 'PES-008', '2026-02-01'),
('Pesa 30kg',      'Disco de pesa de 30kg',                         'operativo', 'prestable', 'PES-009', '2026-02-01'),
('Pesa 30kg',      'Disco de pesa de 30kg',                         'operativo', 'prestable', 'PES-010', '2026-02-01'),
('Balon 5kg',      'Balon medicinal lastrado de 5kg',               'operativo', 'prestable', 'BAL-001', '2026-04-05'),
('Balon 8kg',      'Balon medicinal lastrado de 8kg',               'operativo', 'prestable', 'BAL-002', '2026-04-05'),
('Balon 10kg',     'Balon medicinal lastrado de 10kg',              'operativo', 'prestable', 'BAL-003', '2026-04-05'),
('Balon 12kg',     'Balon medicinal lastrado de 12kg',              'operativo', 'prestable', 'BAL-004', '2026-04-05'),
('Balon 15kg',     'Balon medicinal lastrado de 15kg',              'operativo', 'prestable', 'BAL-005', '2026-04-05');

-- ============================================================
-- DATOS DE EJEMPLO: Maquinas (cintas, pulsadores)
-- Aparecen en la pestana MAQUINAS y en el selector de INCIDENCIAS
-- ============================================================
INSERT INTO material (nombre_equipo, descripcion, estado, tipo, qr_identificador, ultima_rev) VALUES
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-001', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-002', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-003', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-004', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-005', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-006', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-007', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-008', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-009', '2026-03-10'),
('Cinta de correr',   'Cinta de correr electrica profesional',       'operativo', 'maquina', 'CIN-010', '2026-03-10'),
('Pulsador pecho',    'Maquina de pulsador para pectorales',         'operativo', 'maquina', 'PUL-001', '2026-05-01'),
('Pulsador hombro',   'Maquina de pulsador para hombros',            'operativo', 'maquina', 'PUL-002', '2026-05-01'),
('Pulsador pierna',   'Maquina de pulsador para piernas',            'operativo', 'maquina', 'PUL-003', '2026-05-01');

-- ============================================================
-- DATOS DE EJEMPLO: Productos en stock (suplementos)
-- Aparecen en la pestana PRODUCTOS
-- ============================================================
INSERT INTO productos_stock (nombre_prod, cant_actual, stock_minimo, precio) VALUES
('Barrita proteica chocolate',  50, 10, 2.50),
('Barrita proteica vainilla',   45, 10, 2.50),
('Bebida energetica naranja',   30,  5, 3.00),
('Bebida energetica limon',     25,  5, 3.00),
('Creatina monohidrato 300g',   15,  3, 25.00),
('Creatina monohidrato 500g',   10,  2, 35.00),
('Whey protein chocolate 1kg',   8,  2, 40.00),
('Whey protein vainilla 1kg',    6,  2, 40.00),
('Pre-entreno 300g',            12,  3, 30.00),
('BCAA 200 capsulas',           20,  5, 20.00);
