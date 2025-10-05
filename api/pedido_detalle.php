<?php
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
// CAMBIAR ESTADO DEL PEDIDO
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $pedido_id = (int)$_POST['pedido_id'];
    $nuevo_estado = $_POST['nuevo_estado'];

    if (in_array($nuevo_estado, ['pendiente', 'pagado', 'entregado', 'cancelado'])) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        if ($stmt->execute([$nuevo_estado, $pedido_id])) {
            $mensaje = "<p style='color:green;'>Estado actualizado a: " . ucfirst($nuevo_estado) . ".</p>";
            // Recargar para ver el cambio
            header("Location: pedido_detalle.php?id=" . $pedido_id . "&mensaje=" . urlencode($mensaje));
            exit;
        } else {
            $mensaje = "<p style='color:red;'>Error al actualizar el estado.</p>";
        }
    } else {
        $mensaje = "<p style='color:red;'>Estado no válido.</p>";
    }
}

// ----------------------------
// OBTENER DETALLE DEL PEDIDO
// ----------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de pedido no válido.");
}

$pedido_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT p.id, p.total, p.estado, p.fecha_creacion,
           c.nombre AS cliente_nombre, c.email, c.telefono, c.direccion
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Obtener productos del pedido
$stmt = $pdo->prepare("
    SELECT pr.nombre, dp.cantidad, dp.precio_unitario,
           (dp.cantidad * dp.precio_unitario) AS subtotal
    FROM detalle_pedido dp
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = ?
    ORDER BY pr.nombre
");
$stmt->execute([$pedido_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido #<?= $pedido['id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { background: #f9f9f9; padding: 15px; margin: 15px 0; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .estado-pendiente { color: #ff9800; }
        .estado-pagado { color: #4caf50; }
        .estado-entregado { color: #2196f3; }
        .estado-cancelado { color: #f44336; }
        button { padding: 6px 12px; margin: 2px; cursor: pointer; }
        .btn-success { background: #28a745; color: white; border: none; }
        .btn-warning { background: #ffc107; color: black; border: none; }
        .btn-danger { background: #dc3545; color: white; border: none; }
    </style>
</head>
<body>

<nav>
  <a href="productos.php">Productos</a> |
  <a href="clientes.php">Clientes</a> |
  <a href="pedidos.php">Pedidos</a> |
  <a href="reporte.php">Reportes</a>
</nav>

<h1>Detalle del Pedido #<?= $pedido['id'] ?></h1>

<?php if ($mensaje): ?>
    <?= $mensaje ?>
<?php endif; ?>

<!-- Información del pedido -->
<div class="card">
    <h2>Información del Pedido</h2>
    <p><strong>Estado:</strong> <span class="estado-<?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span></p>
    <p><strong>Fecha:</strong> <?= $pedido['fecha_creacion'] ?></p>
    <p><strong>Total:</strong> $<?= number_format($pedido['total'], 2) ?></p>
</div>

<!-- Datos del cliente -->
<div class="card">
    <h2>Cliente</h2>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombre']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono']) ?></p>
    <p><strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion']) ?></p>
</div>

<!-- Productos del pedido -->
<div class="card">
    <h2>Productos</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= $p['cantidad'] ?></td>
                    <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format($p['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Cambiar estado -->
<div class="card">
    <h2>Cambiar Estado del Pedido</h2>
    <form method="POST">
        <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
        <select name="nuevo_estado" required>
            <option value="">-- Seleccione nuevo estado --</option>
            <option value="pendiente" <?= $pedido['estado'] === 'pendiente' ? 'disabled' : '' ?>>Pendiente</option>
            <option value="pagado" <?= $pedido['estado'] === 'pagado' ? 'disabled' : '' ?>>Pagado</option>
            <option value="entregado" <?= $pedido['estado'] === 'entregado' ? 'disabled' : '' ?>>Entregado</option>
            <option value="cancelado" <?= $pedido['estado'] === 'cancelado' ? 'disabled' : '' ?>>Cancelado</option>
        </select>
        <button type="submit" name="cambiar_estado" value="1" class="btn-warning">Actualizar Estado</button>
    </form>
</div>

</body>
</html>