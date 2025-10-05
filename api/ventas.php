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

// Obtener parámetros de filtro
$estado_filtro = $_GET['estado'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// Construir consulta dinámica
$sql = "
    SELECT 
        p.id AS pedido_id,
        c.nombre AS cliente,
        GROUP_CONCAT(pr.nombre, ' (x', dp.cantidad, ')') AS productos,
        p.total,
        p.estado,
        p.fecha_creacion
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN detalle_pedido dp ON p.id = dp.pedido_id
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE 1=1
";

$params = [];

if ($estado_filtro && in_array($estado_filtro, ['pendiente', 'pagado', 'entregado', 'cancelado'])) {
    $sql .= " AND p.estado = ?";
    $params[] = $estado_filtro;
}

if ($fecha_inicio) {
    $sql .= " AND p.fecha_creacion >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $sql .= " AND p.fecha_creacion <= ?";
    $params[] = $fecha_fin . ' 23:59:59';
}

$sql .= " GROUP BY p.id ORDER BY p.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas / Pedidos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .filters { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        label { margin-right: 10px; }
        select, input { padding: 5px; margin-right: 10px; }
        button { padding: 6px 12px; background: #007BFF; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .estado-pendiente { color: #ff9800; }
        .estado-pagado { color: #4caf50; }
        .estado-entregado { color: #2196f3; }
        .estado-cancelado { color: #f44336; }
    </style>
</head>
<body>

<h1>Reporte de Ventas / Pedidos</h1>

<!-- Filtros -->
<div class="filters">
    <form method="GET">
        <label>Estado:</label>
        <select name="estado">
            <option value="">Todos</option>
            <option value="pendiente" <?= $estado_filtro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="pagado" <?= $estado_filtro === 'pagado' ? 'selected' : '' ?>>Pagado</option>
            <option value="entregado" <?= $estado_filtro === 'entregado' ? 'selected' : '' ?>>Entregado</option>
            <option value="cancelado" <?= $estado_filtro === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>

        <label>Desde:</label>
        <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio ?? '') ?>">

        <label>Hasta:</label>
        <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin ?? '') ?>">

        <button type="submit">Filtrar</button>
        <a href="ventas.php" style="margin-left: 10px;">Limpiar</a>
    </form>
</div>

<!-- Lista de pedidos -->
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
                    <td><?= $p['pedido_id'] ?></td>
                    <td><?= htmlspecialchars($p['cliente']) ?></td>
                    <td><?= htmlspecialchars($p['productos']) ?></td>
                    <td>$<?= number_format($p['total'], 2) ?></td>
                    <td class="estado-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></td>
                    <td><?= $p['fecha_creacion'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay pedidos que coincidan con los filtros.</p>
<?php endif; ?>

<!-- Enlace al formulario de pedidos -->
<p><a href="pedidos.php" style="display: inline-block; margin-top: 20px; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">+ Crear Nuevo Pedido</a></p>

</body>
</html>