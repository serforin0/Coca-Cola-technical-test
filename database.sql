-- database.sql
CREATE DATABASE IF NOT EXISTS bebidas_db;
USE bebidas_db;

-- Tabla de productos
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0
);

-- Datos de prueba
INSERT INTO productos (nombre, precio, stock) VALUES
('Kola Real 355ml', 20.00, 100),
('Kola Real 500ml', 25.00, 80),
('Cielo Agua 600ml', 15.00, 120),
('Sporade 500ml', 30.00, 60);

-- Tabla de clientes
CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  correo VARCHAR(100),
  telefono VARCHAR(20)
);

INSERT INTO clientes (nombre, correo, telefono) VALUES
('Juan Pérez', 'juanp@example.com', '8095551234'),
('Ana López', 'ana.lopez@example.com', '8294445678');


CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Datos de prueba para ventas
INSERT INTO ventas (producto_id, cantidad) VALUES
(1, 5),
(2, 3),
(3, 10),
(1, 2);


SELECT 
    v.id AS venta_id,
    p.nombre AS producto,
    v.cantidad,
    v.fecha
FROM ventas v
INNER JOIN productos p ON v.producto_id = p.id;