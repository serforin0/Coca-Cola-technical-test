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
// CREAR PEDIDO
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    $cliente_id = (int)$_POST['cliente_id'];
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];

    // Validar que los IDs existan
    $cliente = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
    $cliente->execute([$cliente_id]);
    if (!$cliente->fetch()) {
        $mensaje = "<p style='color:red;'>Cliente no válido.</p>";
    } else {
        // Obtener producto y stock
        $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            $mensaje = "<p style='color:red;'>Producto no válido.</p>";
        } elseif ($cantidad <= 0) {
            $mensaje = "<p style='color:red;'>La cantidad debe ser mayor a 0.</p>";
        } elseif ($cantidad > $producto['stock']) {
            $mensaje = "<p style='color:red;'>Stock insuficiente para {$producto['nombre']}. Disponible: {$producto['stock']}.</p>";
        } else {
            // Calcular total
            $total = $producto['precio'] * $cantidad;

            try {
                $pdo->beginTransaction();

                // Insertar pedido
                $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, total) VALUES (?, ?)");
                $stmt->execute([$cliente_id, $total]);
                $pedido_id = $pdo->lastInsertId();

                // Insertar detalle
                $stmt = $pdo->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmt->execute([$pedido_id, $producto_id, $cantidad, $producto['precio']]);

                // Actualizar stock
                $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$cantidad, $producto_id]);

                $pdo->commit();
                $mensaje = "<p style='color:green;'>Pedido creado correctamente. ID: $pedido_id</p>";
            } catch (Exception $e) {
                $pdo->rollback();
                $mensaje = "<p style='color:red;'>Error al crear el pedido: " . $e->getMessage() . "</p>";
            }
        }
    }
}

// ----------------------------
// LISTAR CLIENTES Y PRODUCTOS
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
    <title>Crear Pedido</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; }
        select, input { padding: 6px; width: 250px; }
        button { padding: 8px 16px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { color: red; }
        .success { color: green; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <nav>
  <a href="productos.php">Productos</a> |
  <a href="clientes.php">Clientes</a> |
  <a href="pedidos.php">Pedidos</a>
</nav>

<h1>Crear Nuevo Pedido</h1>

<?php if ($mensaje): ?>
    <?= $mensaje ?>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <label for="cliente_id">Cliente:</label>
        <select name="cliente_id" id="cliente_id" required>
            <option value="">-- Seleccione un cliente --</option>
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="producto_id">Producto:</label>
        <select name="producto_id" id="producto_id" required onchange="actualizarStock()">
            <option value="">-- Seleccione un producto --</option>
            <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" data-precio="<?= $p['precio'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?> - Stock: <?= $p['stock'] ?> - Precio: $<?= number_format($p['precio'], 2) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="cantidad">Cantidad:</label>
        <input type="number" name="cantidad" id="cantidad" min="1" value="1" required>

        <button type="submit" name="crear_pedido" value="1">Crear Pedido</button>
    </form>
</div>

<!-- Lista de pedidos recientes (opcional) -->
<h2>Pedidos Recientes</h2>
<?php
$stmt = $pdo->query("
    SELECT p.id, c.nombre AS cliente, pr.nombre AS producto, dp.cantidad, p.total, p.estado, p.fecha_creacion
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN detalle_pedido dp ON p.id = dp.pedido_id
    JOIN productos pr ON dp.producto_id = pr.id
    ORDER BY p.fecha_creacion DESC
    LIMIT 10
");
$pedidos = $stmt->fetchAll();
?>

<?php if ($pedidos): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cant.</th>
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
                    <td><?= htmlspecialchars($p['producto']) ?></td>
                    <td><?= $p['cantidad'] ?></td>
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

<script>
function actualizarStock() {
    const select = document.getElementById('producto_id');
    const option = select.options[select.selectedIndex];
    const stock = option.getAttribute('data-stock');
    const cantidadInput = document.getElementById('cantidad');
    if (stock) {
        cantidadInput.max = stock;
        cantidadInput.placeholder = 'Máx: ' + stock;
    }
}
</script>

</body>
</html>