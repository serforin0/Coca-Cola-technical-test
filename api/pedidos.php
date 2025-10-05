<?php
session_start();

$host = 'localhost';
$dbname = 'bebidas_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = '';

// ----------------------------
// CREAR PEDIDO CON MÚLTIPLES PRODUCTOS
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $productos = $_POST['productos'] ?? [];

    // Validar cliente
    $cliente = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
    $cliente->execute([$cliente_id]);
    if (!$cliente->fetch()) {
        $mensaje = "<p style='color:red;'>Cliente no válido.</p>";
    } elseif (empty($productos)) {
        $mensaje = "<p style='color:red;'>Debe agregar al menos un producto.</p>";
    } else {
        // Validar todos los productos y calcular total
        $detalles = [];
        $total_general = 0;
        $errores = [];

        foreach ($productos as $item) {
            $producto_id = (int)($item['id'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 0);

            if ($producto_id <= 0 || $cantidad <= 0) continue;

            $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $errores[] = "Producto inválido (ID: $producto_id).";
            } elseif ($cantidad > $producto['stock']) {
                $errores[] = "Stock insuficiente para '{$producto['nombre']}'. Disponible: {$producto['stock']}.";
            } else {
                $subtotal = $producto['precio'] * $cantidad;
                $total_general += $subtotal;
                $detalles[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];
            }
        }

        if (!empty($errores)) {
            $mensaje = "<p style='color:red;'>" . implode("<br>", $errores) . "</p>";
        } elseif (empty($detalles)) {
            $mensaje = "<p style='color:red;'>No hay productos válidos para registrar.</p>";
        } else {
            // Todo OK → crear pedido
            try {
                $pdo->beginTransaction();

                // Insertar pedido
                $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, total) VALUES (?, ?)");
                $stmt->execute([$cliente_id, $total_general]);
                $pedido_id = $pdo->lastInsertId();

                // Insertar cada detalle y descontar stock
                foreach ($detalles as $d) {
                    $p = $d['producto'];
                    // Detalle
                    $stmt = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$pedido_id, $p['id'], $d['cantidad'], $p['precio']]);

                    // Descontar stock
                    $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$d['cantidad'], $p['id']]);
                }

                $pdo->commit();
                $mensaje = "<p style='color:green;'>Pedido creado correctamente. ID: $pedido_id</p>";
            } catch (Exception $e) {
                $pdo->rollback();
                $mensaje = "<p style='color:red;'>Error al crear el pedido: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
}

// ----------------------------
// CARGAR DATOS PARA EL FORMULARIO
// ----------------------------
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT id, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre")->fetchAll();

if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Pedido - Múltiples Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; }
        select, input { padding: 6px; }
        .producto-item { margin: 10px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 8px 16px; margin: 5px; cursor: pointer; }
        .btn-primary { background: #28a745; color: white; border: none; }
        .btn-danger { background: #dc3545; color: white; border: none; }
        .btn-secondary { background: #6c757d; color: white; border: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>

<nav>
  <a href="productos.php">Productos</a> |
  <a href="clientes.php">Clientes</a> |
  <a href="pedidos.php">Pedidos</a> |
  <a href="reporte.php">Reportes</a>
</nav>

<h1>Crear Nuevo Pedido (Múltiples Productos)</h1>

<?php if ($mensaje): ?>
    <?= $mensaje ?>
<?php endif; ?>

<div class="form-container">
    <form method="POST" id="form-pedido">
        <label for="cliente_id">Cliente:</label>
        <select name="cliente_id" id="cliente_id" required>
            <option value="">-- Seleccione un cliente --</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <h3>Productos</h3>
        <div id="lista-productos">
            <!-- Los productos se agregarán aquí con JS -->
        </div>

        <button type="button" id="btn-agregar-producto" class="btn-secondary">+ Agregar Producto</button>
        <br><br>
        <button type="submit" name="crear_pedido" value="1" class="btn-primary">Crear Pedido</button>
    </form>
</div>

<!-- Plantilla oculta para clonar -->
<script type="text/template" id="plantilla-producto">
    <div class="producto-item">
        <select name="productos[__INDEX__][id]" required onchange="actualizarStock(this)">
            <option value="">-- Seleccione --</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" data-precio="<?= $p['precio'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?> - Stock: <?= $p['stock'] ?> - Precio: $<?= number_format($p['precio'], 2) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="productos[__INDEX__][cantidad]" min="1" value="1" required style="width: 80px; margin-left: 10px;">
        <button type="button" class="btn-danger" onclick="eliminarProducto(this)">Eliminar</button>
    </div>
</script>

<script>
let contador = 0;

function agregarProducto() {
    const contenedor = document.getElementById('lista-productos');
    const plantilla = document.getElementById('plantilla-producto').innerHTML;
    const html = plantilla.replace(/__INDEX__/g, contador++);
    contenedor.insertAdjacentHTML('beforeend', html);
}

function eliminarProducto(boton) {
    boton.closest('.producto-item').remove();
}

function actualizarStock(select) {
    const option = select.options[select.selectedIndex];
    const stock = option.getAttribute('data-stock');
    const cantidadInput = select.parentNode.querySelector('input[type="number"]');
    if (stock) {
        cantidadInput.max = stock;
        cantidadInput.title = `Máximo: ${stock} unidades`;
    }
}

// Agregar primer producto al cargar
document.addEventListener('DOMContentLoaded', () => {
    agregarProducto();
    document.getElementById('btn-agregar-producto').addEventListener('click', agregarProducto);
});
</script>

<!-- Lista de pedidos recientes -->
<h2>Pedidos Recientes</h2>
<?php
$stmt = $pdo->query("
    SELECT p.id, c.nombre AS cliente, 
           GROUP_CONCAT(pr.nombre, ' (x', dp.cantidad, ')') AS productos,
           p.total, p.estado, p.fecha_creacion
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN detalle_pedido dp ON p.id = dp.pedido_id
    JOIN productos pr ON dp.producto_id = pr.id
    GROUP BY p.id
    ORDER BY p.fecha_creacion DESC
    LIMIT 5
");
$pedidos = $stmt->fetchAll();
?>

<?php if ($pedidos): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Productos</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['cliente']) ?></td>
                    <td><?= htmlspecialchars($p['productos']) ?></td>
                    <td>$<?= number_format($p['total'], 2) ?></td>
                    <td><?= htmlspecialchars($p['estado']) ?></td>
                    <td><?= $p['fecha_creacion'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay pedidos registrados.</p>
<?php endif; ?>

</body>
</html>