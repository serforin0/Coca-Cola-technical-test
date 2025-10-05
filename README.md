# Coca-Cola Technical Test – Sistema de Gestión de Pedidos de Bebidas

Aplicación web completa para la gestión de **productos**, **clientes** y **pedidos** de bebidas, desarrollada como parte del test técnico de Coca-Cola.

## 🎯 Funcionalidades implementadas

### 📦 Productos
- CRUD visual completo (`productos.php`)
- Búsqueda por nombre
- Gestión de stock en tiempo real
- Validación de datos (precio ≥ 0, stock ≥ 0)

### 👥 Clientes
- CRUD visual completo (`clientes.php`)
- Campos: nombre, email, teléfono, dirección
- Validación de campos obligatorios

### 🛒 Pedidos
- Creación de pedidos con **múltiples productos**
- Validación automática de **stock disponible por producto**
- Cálculo automático del **total del pedido**
- Descuento inmediato del stock al confirmar
- Soporte para estados: pendiente, pagado, entregado, cancelado

### 🔍 Detalle de pedido
- Vista detallada por pedido (`pedido_detalle.php?id=123`)
- Posibilidad de **cambiar el estado** del pedido desde la interfaz

### 📊 Reportes Avanzados (`reporte.php`)
- **Procedimientos almacenados (Stored Procedures)** para:
  - Productos más vendidos en un rango de fechas
  - Productos con stock bajo (con umbral configurable)
  - Ventas por estado
  - Productos nunca vendidos
- **Vistas SQL** optimizadas para resúmenes
- Filtros interactivos por fecha y estado

### 🔒 Seguridad y buenas prácticas
- Uso de **PDO con consultas preparadas** (evita inyección SQL)
- Redirección tras operaciones (previene reenvíos)
- Estructura relacional normalizada (1:N y N:M)
- Código limpio, modular y mantenible

## ⚙️ Optimizaciones en la base de datos
- Índices en campos críticos (`email`, `nombre`, `fecha_creacion`)
- Restricciones `CHECK` para evitar datos inválidos (stock negativo, etc.)
- Procedimientos almacenados avanzados para lógica de negocio
- Vistas precalculadas para reportes rápidos

## 🛠️ Requisitos

- Servidor local con **PHP 7.4+** (XAMPP, WAMP, etc.)
- **MySQL 8+ / MariaDB 10.2+**
- Navegador web moderno

## 🚀 Instalación

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/Coca-Cola-technical-test.git