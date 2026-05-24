-- FitStock - Esquema de base de datos para importar en phpMyAdmin
-- Copia este contenido y pégalo en la pestaña SQL de phpMyAdmin
-- Asegúrate de tener seleccionada la base de datos antes de importar

-- TABLA: usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','entrenador','cliente') NOT NULL DEFAULT 'cliente',
    forzar_cambio_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABLA: material
CREATE TABLE material (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    nombre_equipo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ubicacion VARCHAR(255) DEFAULT NULL,
    estado ENUM('operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja') NOT NULL DEFAULT 'operativo',
    tipo ENUM('maquina','prestable') NOT NULL DEFAULT 'prestable',
    id_tag_material VARCHAR(100) UNIQUE,
    ultima_rev DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABLA: prestamos
CREATE TABLE prestamos (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_material INT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion DATE,
    estado VARCHAR(30) DEFAULT 'activo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_material) REFERENCES material(id_material) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABLA: productos_stock
CREATE TABLE productos_stock (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_prod VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cant_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABLA: compras
CREATE TABLE compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos_stock(id_producto) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- TABLA: incidencias
CREATE TABLE incidencias (
    id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_material INT,
    id_user_rep INT,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    estado_inc ENUM('abierta','en_proceso','resuelta') NOT NULL DEFAULT 'abierta',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME DEFAULT NULL,
    FOREIGN KEY (id_material) REFERENCES material(id_material) ON DELETE SET NULL,
    FOREIGN KEY (id_user_rep) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- DATOS DE EJEMPLO

-- Usuarios (password para todos: "password")
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin FitStock', 'admin@fitstock.com', '$2y$12$EhlqrmMIGzBggJmnWwA5WO5m//kgIcCnp/Hh.JdGP8OJLFTf2K.d.', 'admin'),
('Carlos Garcia', 'carlos@gym.com', '$2y$12$EhlqrmMIGzBggJmnWwA5WO5m//kgIcCnp/Hh.JdGP8OJLFTf2K.d.', 'entrenador'),
('ilsa', 'ilsa@fitstock.com', '$2y$12$7lOqymM3DmBf91DYg5ELBuld63Pi7hym1jkUM44RL7Wu4a/70WqJ2', 'cliente'),
('marta', 'marta@fitstock.com', '$2y$12$EOvv5koWLTsHua43cbDF0e4L6FbOG2d/45piyAOpyEBuiD5TKAYjm', 'entrenador');

-- Material prestable
INSERT INTO material (nombre_equipo, descripcion, estado, tipo, id_tag_material, ultima_rev) VALUES
('Mancuerna 5kg',  'Mancuerna de 5kg para ejercicios de brazo',  'operativo', 'prestable', 'MAN-001', '2026-01-15'),
('Mancuerna 5kg',  'Mancuerna de 5kg para ejercicios de brazo',  'operativo', 'prestable', 'MAN-002', '2026-01-15'),
('Mancuerna 8kg',  'Mancuerna de 8kg para ejercicios de brazo',  'operativo', 'prestable', 'MAN-003', '2026-01-15'),
('Mancuerna 8kg',  'Mancuerna de 8kg para ejercicios de brazo',  'operativo', 'prestable', 'MAN-004', '2026-01-15'),
('Mancuerna 10kg', 'Mancuerna de 10kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-005', '2026-01-15'),
('Mancuerna 10kg', 'Mancuerna de 10kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-006', '2026-01-15'),
('Mancuerna 12kg', 'Mancuerna de 12kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-007', '2026-01-15'),
('Mancuerna 12kg', 'Mancuerna de 12kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-008', '2026-01-15'),
('Mancuerna 15kg', 'Mancuerna de 15kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-009', '2026-01-15'),
('Mancuerna 15kg', 'Mancuerna de 15kg para ejercicios de brazo', 'operativo', 'prestable', 'MAN-010', '2026-01-15'),
('Pesa 10kg',      'Disco de pesa de 10kg',                      'operativo', 'prestable', 'PES-001', '2026-02-01'),
('Pesa 10kg',      'Disco de pesa de 10kg',                      'operativo', 'prestable', 'PES-002', '2026-02-01'),
('Pesa 15kg',      'Disco de pesa de 15kg',                      'operativo', 'prestable', 'PES-003', '2026-02-01'),
('Pesa 15kg',      'Disco de pesa de 15kg',                      'operativo', 'prestable', 'PES-004', '2026-02-01'),
('Pesa 20kg',      'Disco de pesa de 20kg',                      'operativo', 'prestable', 'PES-005', '2026-02-01'),
('Pesa 20kg',      'Disco de pesa de 20kg',                      'operativo', 'prestable', 'PES-006', '2026-02-01'),
('Pesa 25kg',      'Disco de pesa de 25kg',                      'operativo', 'prestable', 'PES-007', '2026-02-01'),
('Pesa 25kg',      'Disco de pesa de 25kg',                      'operativo', 'prestable', 'PES-008', '2026-02-01'),
('Pesa 30kg',      'Disco de pesa de 30kg',                      'operativo', 'prestable', 'PES-009', '2026-02-01'),
('Pesa 30kg',      'Disco de pesa de 30kg',                      'operativo', 'prestable', 'PES-010', '2026-02-01'),
('Balon 5kg',      'Balon medicinal lastrado de 5kg',            'operativo', 'prestable', 'BAL-001', '2026-04-05'),
('Balon 8kg',      'Balon medicinal lastrado de 8kg',            'operativo', 'prestable', 'BAL-002', '2026-04-05'),
('Balon 12kg',     'Balon medicinal lastrado de 12kg',           'operativo', 'prestable', 'BAL-004', '2026-04-05'),
('Balon 15kg',     'Balon medicinal lastrado de 15kg',           'operativo', 'prestable', 'BAL-005', '2026-04-05');

-- Maquinas
INSERT INTO material (nombre_equipo, descripcion, ubicacion, estado, tipo, id_tag_material, ultima_rev) VALUES
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1 - Zona Cardio', 'operativo', 'maquina', 'CIN-001', '2026-05-11'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1 - Zona Cardio', 'operativo', 'maquina', 'CIN-002', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1 - Zona Cardio', 'operativo', 'maquina', 'CIN-003', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1 - Zona Cardio', 'operativo', 'maquina', 'CIN-004', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1 - Zona Cardio', 'operativo', 'maquina', 'CIN-005', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 2 - Zona Cardio', 'operativo', 'maquina', 'CIN-006', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 2 - Zona Cardio', 'operativo', 'maquina', 'CIN-007', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 2 - Zona Cardio', 'operativo', 'maquina', 'CIN-008', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 2 - Zona Cardio', 'operativo', 'maquina', 'CIN-009', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 2 - Zona Cardio', 'operativo', 'maquina', 'CIN-010', '2026-03-10'),
('Pulsador pecho',  'Maquina de pulsador para pectorales',    'Piso 1 - Zona Fuerza', 'operativo', 'maquina', 'PUL-001', '2026-05-01'),
('Pulsador hombro', 'Maquina de pulsador para hombros',       'Piso 1 - Zona Fuerza', 'operativo', 'maquina', 'PUL-002', '2026-05-01'),
('Pulsador pierna', 'Maquina de pulsador para piernas',       'Piso 1 - Zona Fuerza', 'operativo', 'maquina', 'PUL-003', '2026-05-01');

-- Items adicionales creados durante pruebas
INSERT INTO material (nombre_equipo, descripcion, ubicacion, estado, tipo, id_tag_material, ultima_rev) VALUES
('Cinta de correr', 'Cinta en ventana', NULL, 'operativo', 'maquina', NULL, '2026-05-11');
INSERT INTO material (nombre_equipo, descripcion, estado, tipo, id_tag_material) VALUES
('Balon 10kg', 'Balon medicinal lastrado de 10kg', 'operativo', 'prestable', 'BAL10-001');

-- Productos en stock
INSERT INTO productos_stock (nombre_prod, descripcion, cant_actual, stock_minimo, precio) VALUES
('Barrita proteica chocolate', 'Deliciosa barrita con 20g de proteína y cobertura de chocolate. Ideal para después del entrenamiento.', 68, 10, 2.50),
('Barrita proteica vainilla',  'Barrita suave con 20g de proteína sabor vainilla. Perfecta como snack post-entreno.', 40, 10, 2.50),
('Creatina monohidrato 300g',  'Creatina monohidrato micronizada en polvo. Mejora la fuerza y el rendimiento en ejercicios de alta intensidad.', 11,  3, 25.00),
('Whey protein chocolate 1kg', 'Proteína de suero de leche sabor chocolate. Aporta 24g de proteína por toma para la recuperación muscular.',  8,  2, 40.00),
('BCAA 200 capsulas',          'Aminoácidos ramificados (BCAA 2:1:1) en cápsulas. Ayudan a reducir la fatiga y mejorar la recuperación.', 19,  5, 20.00),
('Whey protein vainilla 1kg',  'Proteína de suero de leche sabor vainilla. 24g de proteína por toma para la recuperación muscular.',  3,  2, 40.00),
('Pre-entreno 300g',           'Pre-entreno con cafeína y beta-alanina para maximizar la energía, el enfoque y el rendimiento.',  5,  3, 30.00),
('Proteina',                   'Proteína concentrada de suero de leche. 22g de proteína por toma. Ideal para el día a día.', 24,  8, 32.00);

-- Incidencias
INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc, fecha_resolucion) VALUES
(26, 1, 'ROTO', 'media', 'resuelta', '2026-05-11 12:54:03');

-- Préstamos de ejemplo
INSERT INTO prestamos (id_usuario, id_material, fecha_inicio, fecha_devolucion, estado) VALUES
(2, 1, '2026-05-01 10:00:00', '2026-05-05', 'devuelto'),
(3, 2, '2026-05-10 12:00:00', '2026-05-15', 'devuelto'),
(3, 3, '2026-05-14 09:00:00', NULL, 'activo'),
(2, 5, '2026-05-16 11:00:00', NULL, 'pendiente');

-- Compras de ejemplo
INSERT INTO compras (id_usuario, id_producto, cantidad, precio_unitario, total, fecha_compra) VALUES
(3, 1, 2, 2.50, 5.00, '2026-05-02 14:30:00'),
(3, 3, 1, 25.00, 25.00, '2026-05-05 10:00:00'),
(2, 1, 3, 2.50, 7.50, '2026-05-08 16:00:00'),
(3, 4, 1, 40.00, 40.00, '2026-05-10 09:00:00'),
(2, 5, 2, 20.00, 40.00, '2026-05-12 11:00:00');