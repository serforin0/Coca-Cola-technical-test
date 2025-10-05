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


-- 5. Consulta: Productos nunca vendidos
SELECT p.id, p.nombre, p.stock
FROM productos p
LEFT JOIN detalle_pedido dp ON p.id = dp.producto_id
WHERE dp.producto_id IS NULL;

-- Índice en productos por nombre (búsquedas)
CREATE INDEX idx_productos_nombre ON productos(nombre);

-- Índice en clientes por email (único y frecuente)
CREATE UNIQUE INDEX idx_clientes_email ON clientes(email);

-- Índice en pedidos por cliente y fecha (reportes)
CREATE INDEX idx_pedidos_cliente_fecha ON pedidos(cliente_id, fecha_creacion);

-- Índice en detalle_pedido por producto (ventas por producto)
CREATE INDEX idx_detalle_producto ON detalle_pedido(producto_id);

-- Índice compuesto para reportes de ventas en rango de fechas
CREATE INDEX idx_pedidos_fecha_estado ON pedidos(fecha_creacion, estado);

-- Permite crear un pedido con múltiples productos directamente desde la BD (útil para APIs o integraciones).
DELIMITER $$
CREATE PROCEDURE sp_generar_pedido_completo(
    IN p_cliente_id INT,
    IN p_productos JSON -- formato: [{"id":1,"cantidad":2}, {"id":3,"cantidad":1}]
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_producto_id INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(10,2);
    DECLARE v_stock INT;
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE pedido_id INT;

    DECLARE cur CURSOR FOR 
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(item, '$.id')),
            JSON_UNQUOTE(JSON_EXTRACT(item, '$.cantidad'))
        FROM JSON_TABLE(p_productos, '$[*]' COLUMNS (item JSON PATH '$')) AS jt;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Validar cliente
    IF NOT EXISTS (SELECT 1 FROM clientes WHERE id = p_cliente_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cliente no existe';
    END IF;

    -- Validar stock de todos los productos
    BLOCK_VALIDACION: BEGIN
        DECLARE i INT DEFAULT 0;
        DECLARE total_items INT DEFAULT JSON_LENGTH(p_productos);
        WHILE i < total_items DO
            SET v_producto_id = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', i, '].id')));
            SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', i, '].cantidad')));

            SELECT precio, stock INTO v_precio, v_stock
            FROM productos WHERE id = v_producto_id;

            IF v_stock IS NULL THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no existe';
            END IF;
            IF v_cantidad > v_stock THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente';
            END IF;
            SET v_total = v_total + (v_precio * v_cantidad);
            SET i = i + 1;
        END WHILE;
    END BLOCK_VALIDACION;

    -- Crear pedido
    INSERT INTO pedidos (cliente_id, total) VALUES (p_cliente_id, v_total);
    SET pedido_id = LAST_INSERT_ID();

    -- Insertar detalles y descontar stock
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_producto_id, v_cantidad;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SELECT precio INTO v_precio FROM productos WHERE id = v_producto_id;
        INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario)
        VALUES (pedido_id, v_producto_id, v_cantidad, v_precio);

        UPDATE productos SET stock = stock - v_cantidad WHERE id = v_producto_id;
    END LOOP;
    CLOSE cur;

    SELECT pedido_id AS nuevo_pedido_id;
END$$
DELIMITER ;

-- sp_reporte_ventas_diarias
-- Genera un reporte diario de ventas totales y cantidad de pedidos por día.

DELIMITER $$
CREATE PROCEDURE sp_reporte_ventas_diarias(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT 
        DATE(p.fecha_creacion) AS dia,
        COUNT(p.id) AS pedidos,
        SUM(p.total) AS ingresos,
        AVG(p.total) AS ticket_promedio
    FROM pedidos p
    WHERE p.fecha_creacion BETWEEN p_fecha_inicio AND p_fecha_fin
      AND p.estado IN ('pagado', 'entregado')
    GROUP BY DATE(p.fecha_creacion)
    ORDER BY dia;
END$$
DELIMITER ;

-- Evitar stock negativo
ALTER TABLE productos 
ADD CONSTRAINT chk_stock_no_negativo 
CHECK (stock >= 0);

-- Evitar precio negativo
ALTER TABLE productos 
ADD CONSTRAINT chk_precio_positivo 
CHECK (precio >= 0);

-- Evitar cantidad negativa en pedidos
ALTER TABLE detalle_pedido 
ADD CONSTRAINT chk_cantidad_positiva 
CHECK (cantidad > 0);

-- Email con formato básico (opcional, pero útil)
ALTER TABLE clientes 
ADD CONSTRAINT chk_email_formato 
CHECK (email LIKE '%@%.%');

-- vista_ventas_completas
-- Muestra solo los pedidos que han sido pagados o entregados, excluyendo los pendientes o cancelados.

CREATE OR REPLACE VIEW vista_ventas_completas AS
SELECT 
    p.id AS pedido_id,
    p.fecha_creacion,
    p.estado,
    p.total,
    c.nombre AS cliente,
    c.email,
    pr.nombre AS producto,
    dp.cantidad,
    dp.precio_unitario,
    (dp.cantidad * dp.precio_unitario) AS subtotal
FROM pedidos p
JOIN clientes c ON p.cliente_id = c.id
JOIN detalle_pedido dp ON p.id = dp.pedido_id
JOIN productos pr ON dp.producto_id = pr.id;

-- vista_stock_critico
-- Muestra productos con stock menor o igual a 20 unidades.

CREATE OR REPLACE VIEW vista_stock_critico AS
SELECT id, nombre, precio, stock
FROM productos
WHERE stock <= 20
ORDER BY stock ASC;