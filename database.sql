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
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(255)
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

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'pagado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

CREATE TABLE detalle_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);



USE bebidas_db;

-- 1. Vista: Resumen de pedidos por cliente
CREATE OR REPLACE VIEW vista_pedidos_por_cliente AS
SELECT 
    c.id AS cliente_id,
    c.nombre AS cliente,
    COUNT(p.id) AS total_pedidos,
    COALESCE(SUM(p.total), 0) AS total_gastado,
    MAX(p.fecha_creacion) AS ultima_compra
FROM clientes c
LEFT JOIN pedidos p ON c.id = p.cliente_id
GROUP BY c.id, c.nombre;

-- 2. Procedimiento: Productos más vendidos en un rango de fechas
DELIMITER $$
CREATE PROCEDURE sp_productos_mas_vendidos(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        pr.id,
        pr.nombre,
        SUM(dp.cantidad) AS unidades_vendidas,
        SUM(dp.cantidad * dp.precio_unitario) AS ingresos
    FROM detalle_pedido dp
    JOIN pedidos p ON dp.pedido_id = p.id
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE DATE(p.fecha_creacion) BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY pr.id, pr.nombre
    ORDER BY unidades_vendidas DESC;
END$$
DELIMITER ;

-- 3. Procedimiento: Stock bajo (menos de 20 unidades)
DELIMITER $$
CREATE PROCEDURE sp_productos_stock_bajo(IN p_umbral INT)
BEGIN
    SELECT 
        id,
        nombre,
        precio,
        stock
    FROM productos
    WHERE stock <= p_umbral
    ORDER BY stock ASC;
END$$
DELIMITER ;

-- 4. Procedimiento: Ventas por estado (resumen)
DELIMITER $$
CREATE PROCEDURE sp_ventas_por_estado()
BEGIN
    SELECT 
        estado,
        COUNT(*) AS cantidad_pedidos,
        COALESCE(SUM(total), 0) AS total_ventas
    FROM pedidos
    GROUP BY estado
    ORDER BY FIELD(estado, 'pendiente', 'pagado', 'entregado', 'cancelado');
END$$
DELIMITER ;