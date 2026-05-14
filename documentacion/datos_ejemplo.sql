-- ============================================================
-- DATOS DE EJEMPLO - FitStock
-- ============================================================

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
('Cinta de correr', 'Cinta de correr electrica profesional', 'Piso 1', 'operativo', 'maquina', 'CIN-001', '2026-05-11'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-002', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-003', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-004', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-005', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-006', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-007', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-008', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-009', '2026-03-10'),
('Cinta de correr', 'Cinta de correr electrica profesional', NULL,      'operativo', 'maquina', 'CIN-010', '2026-03-10'),
('Pulsador pecho',  'Maquina de pulsador para pectorales',    NULL,      'operativo', 'maquina', 'PUL-001', '2026-05-01'),
('Pulsador hombro', 'Maquina de pulsador para hombros',       NULL,      'operativo', 'maquina', 'PUL-002', '2026-05-01'),
('Pulsador pierna', 'Maquina de pulsador para piernas',       NULL,      'operativo', 'maquina', 'PUL-003', '2026-05-01');

-- Productos en stock
INSERT INTO productos_stock (nombre_prod, descripcion, cant_actual, stock_minimo, precio) VALUES
('Barrita proteica chocolate', NULL, 68, 10, 2.50),
('Barrita proteica vainilla',  NULL, 40, 10, 2.50),
('Creatina monohidrato 300g',  NULL, 11,  3, 25.00),
('Whey protein chocolate 1kg', NULL,  8,  2, 40.00),
('BCAA 200 capsulas',          NULL, 19,  5, 20.00),
('Whey protein vainilla 1kg',  NULL,  3,  2, 40.00),
('Pre-entreno 300g',           NULL,  5,  3, 30.00),
('Proteina',                   NULL, 24,  8, 32.00);

-- Incidencias
INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc, fecha_resolucion) VALUES
(26, 1, 'ROTO', 'media', 'resuelta', '2026-05-11 12:54:03');
