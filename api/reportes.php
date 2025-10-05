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

// Función para ejecutar un stored procedure y devolver resultados
function ejecutarSP($pdo, $nombre, $params = []) {
    $placeholders = str_repeat('?,', count($params));
    $placeholders = rtrim($placeholders, ',');
    $sql = "CALL $nombre(" . ($placeholders ?: '') . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Avanzados</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }
        h1, h2 { color: #2c3e50; }
        .reporte { background: white; padding: 15px; margin: 20px 0; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #ecf0f1; }
        .parametros { margin: 10px 0; padding: 10px; background: #f1f8ff; border-radius: 4px; }
        input, button { padding: 6px; margin-right: 8px; }
    </style>
</head>
<body>

<h1>📊 Reportes Avanzados</h1>

<!-- Reporte 1: Productos más vendidos (con rango de fechas) -->
<div class="reporte">
    <h2>1. Productos más vendidos (por rango de fechas)</h2>
    <form method="GET" action="">
        <div class="parametros">
            <label>Desde: <input type="date" name="inicio" value="<?= $_GET['inicio'] ?? date('Y-m-01') ?>"></label>
            <label>Hasta: <input type="date" name="fin" value="<?= $_GET['fin'] ?? date('Y-m-d') ?>"></label>
            <button type="submit" name="reporte" value="ventas_rango">Generar</button>
        </div>
    </form>

    <?php if (isset($_GET['reporte']) && $_GET['reporte'] === 'ventas_rango'): ?>
        <?php
        $inicio = $_GET['inicio'] ?? date('Y-m-01');
        $fin = $_GET['fin'] ?? date('Y-m-d');
        $result = ejecutarSP($pdo, 'sp_productos_mas_vendidos', [$inicio, $fin]);
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Unidades Vendidas</th>
                    <th>Ingresos ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php foreach ($result as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['nombre']) ?></td>
                            <td><?= $r['unidades_vendidas'] ?></td>
                            <td><?= number_format($r['ingresos'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No hay ventas en este rango.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Reporte 2: Stock bajo -->
<div class="reporte">
    <h2>2. Productos con stock bajo</h2>
    <form method="GET">
        <div class="parametros">
            <label>Umbral de stock: <input type="number" name="umbral" value="<?= $_GET['umbral'] ?? 20 ?>" min="0"></label>
            <button type="submit" name="reporte" value="stock_bajo">Ver productos</button>
        </div>
    </form>

    <?php if (isset($_GET['reporte']) && $_GET['reporte'] === 'stock_bajo'): ?>
        <?php
        $umbral = (int)($_GET['umbral'] ?? 20);
        $result = ejecutarSP($pdo, 'sp_productos_stock_bajo', [$umbral]);
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $r): ?>
                    <tr style="<?= $r['stock'] == 0 ? 'background:#ffebee;' : '' ?>">
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                        <td>$<?= number_format($r['precio'], 2) ?></td>
                        <td><?= $r['stock'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Reporte 3: Resumen por cliente (usando vista) -->
<div class="reporte">
    <h2>3. Resumen de clientes (vista precalculada)</h2>
    <?php
    $stmt = $pdo->query("SELECT * FROM vista_pedidos_por_cliente ORDER BY total_gastado DESC");
    $clientes = $stmt->fetchAll();
    ?>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Pedidos</th>
                <th>Total Gastado ($)</th>
                <th>Última Compra</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['cliente']) ?></td>
                    <td><?= $c['total_pedidos'] ?></td>
                    <td><?= number_format($c['total_gastado'], 2) ?></td>
                    <td><?= $c['ultima_compra'] ?: 'Nunca' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Reporte 4: Ventas por estado -->
<div class="reporte">
    <h2>4. Ventas por estado del pedido</h2>
    <?php
    $result = ejecutarSP($pdo, 'sp_ventas_por_estado');
    ?>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th>Pedidos</th>
                <th>Total Ventas ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result as $r): ?>
                <tr>
                    <td><?= ucfirst($r['estado']) ?></td>
                    <td><?= $r['cantidad_pedidos'] ?></td>
                    <td><?= number_format($r['total_ventas'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>