# Coca-Cola Technical Test – Sistema de Gestión de Pedidos de Bebidas

Aplicación web completa para la gestión de **productos**, **clientes** y **pedidos** de bebidas, desarrollada como parte del test técnico de Coca-Cola.

## 🎯 Funcionalidades implementadas

### 📦 Productos
- CRUD visual completo (`productos.php`)
- Gestión de stock en tiempo real
- Validación de datos (precio ≥ 0, stock ≥ 0)

### 👥 Clientes
- CRUD visual completo (`clientes.php`)
- Campos: nombre, email, teléfono, dirección
- Validación de campos obligatorios

### 🛒 Pedidos
- Creación de pedidos con selección de cliente y producto
- Validación automática de **stock disponible**
- Cálculo automático del **total del pedido**
- Descuento inmediato del stock al confirmar el pedido
- Soporte para estado del pedido (pendiente, pagado, entregado, cancelado)

### 📊 Reportes Avanzados (`reporte.php`)
- **Procedimientos almacenados (Stored Procedures)** para:
  - Productos más vendidos en un rango de fechas
  - Productos con stock bajo (con umbral configurable)
  - Resumen de ventas por estado
- **Vistas SQL** para resumen de clientes y actividad
- Filtros interactivos por fecha y estado
- Interfaz clara y optimizada para toma de decisiones

### 🔒 Seguridad y buenas prácticas
- Uso de **PDO con consultas preparadas** (evita inyección SQL)
- Validación de entradas y redirección tras operaciones (previene reenvíos)
- Estructura de base de datos relacional normalizada (1:N y N:M)
- Código limpio, modular y mantenible

## 🛠️ Requisitos

- Servidor local con **PHP 7.4+** (XAMPP, WAMP, etc.)
- **MySQL / MariaDB**
- Navegador web moderno

## 🚀 Instalación

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/Coca-Cola-technical-test.git