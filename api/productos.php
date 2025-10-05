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
// ELIMINAR producto
// ----------------------------
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    if ($stmt->execute([$id])) {
        $mensaje = "<p style='color:green;'>Producto eliminado correctamente.</p>";
    } else {
        $mensaje = "<p style='color:red;'>Error al eliminar el producto.</p>";
    }
    header("Location: productos.php?mensaje=" . urlencode($mensaje) . (isset($_GET['busqueda']) ? "&busqueda=" . urlencode($_GET['busqueda']) : ''));
    exit;
}

// ----------------------------
// ACTUALIZAR producto
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'])) {
    $id = (int)$_POST['editar_id'];
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];

    if ($nombre && $precio >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $precio, $stock, $id])) {
            $mensaje = "<p style='color:green;'>Producto actualizado correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al actualizar el producto.</p>";
        }
    } else {
        $mensaje = "<p style='color:red;'>Datos inválidos.</p>";
    }
    header("Location: productos.php?mensaje=" . urlencode($mensaje) . (isset($_GET['busqueda']) ? "&busqueda=" . urlencode($_GET['busqueda']) : ''));
    exit;
}

// ----------------------------
// CREAR nuevo producto
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];

    if ($nombre && $precio >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio, stock) VALUES (?, ?, ?)");
        if ($stmt->execute([$nombre, $precio, $stock])) {
            $mensaje = "<p style='color:green;'>Producto creado correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al crear el producto.</p>";
        }
    } else {
        $mensaje = "<p style='color:red;'>Datos inválidos.</p>";
    }
    header("Location: productos.php?mensaje=" . urlencode($mensaje) . (isset($_GET['busqueda']) ? "&busqueda=" . urlencode($_GET['busqueda']) : ''));
    exit;
}

// ----------------------------
// OBTENER producto para editar
// ----------------------------
$editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ----------------------------
// BUSCAR productos
// ----------------------------
$busqueda = $_GET['busqueda'] ?? '';
if ($busqueda !== '') {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre LIKE ? ORDER BY id");
    $stmt->execute(['%' . $busqueda . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY id");
}
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Productos - Coca-Cola Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .form-container { background: #f9f9f9; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
        input[type="text"], input[type="number"] { width: 200px; padding: 5px; }
        button { padding: 6px 12px; margin: 5px 0; }
        .search-box { margin: 10px 0; }
        .search-box input { width: 300px; padding: 6px; }
    </style>
</head>
<body>

<nav>
  <a href="productos.php">Productos</a> |
  <a href="clientes.php">Clientes</a> |
  <a href="pedidos.php">Pedidos</a> |
  <a href="reporte.php">Reportes</a>
</nav>

<h1>Gestión de Productos</h1>

<?php if ($mensaje): ?>
    <?= $mensaje ?>
<?php endif; ?>

<!-- Buscador -->
<div class="search-box">
    <form method="GET">
        <label>
            Buscar por nombre:
            <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej: Kola Real">
        </label>
        <button type="submit">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a href="productos.php">Ver todos</a>
        <?php endif; ?>
    </form>
</div>

<!-- Formulario de Creación o Edición -->
<div class="form-container">
    <h2><?= $editar ? 'Editar Producto' : 'Agregar Nuevo Producto' ?></h2>
    <form method="POST">
        <?php if ($editar): ?>
            <input type="hidden" name="editar_id" value="<?= htmlspecialchars($editar['id']) ?>">
        <?php endif; ?>

        <label>Nombre:<br>
            <input type="text" name="nombre" value="<?= $editar ? htmlspecialchars($editar['nombre']) : '' ?>" required>
        </label><br><br>

        <label>Precio:<br>
            <input type="number" step="0.01" name="precio" value="<?= $editar ? htmlspecialchars($editar['precio']) : '' ?>" min="0" required>
        </label><br><br>

        <label>Stock:<br>
            <input type="number" name="stock" value="<?= $editar ? htmlspecialchars($editar['stock']) : '' ?>" min="0" required>
        </label><br><br>

        <button type="submit" name="crear" value="1"><?= $editar ? 'Actualizar' : 'Crear Producto' ?></button>
        <?php if ($editar): ?>
            <a href="productos.php<?= $busqueda ? '?busqueda=' . urlencode($busqueda) : '' ?>">Cancelar edición</a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista de Productos -->
<h2>Lista de Productos <?= $busqueda ? "(Resultados para: " . htmlspecialchars($busqueda) . ")" : "" ?></h2>
<?php if ($productos): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['id']) ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= number_format($p['precio'], 2) ?></td>
                    <td><?= htmlspecialchars($p['stock']) ?></td>
                    <td>
                        <a href="?editar=<?= $p['id'] ?><?= $busqueda ? '&busqueda=' . urlencode($busqueda) : '' ?>">✏️ Editar</a> |
                        <a href="?eliminar=<?= $p['id'] ?><?= $busqueda ? '&busqueda=' . urlencode($busqueda) : '' ?>" onclick="return confirm('¿Eliminar este producto?')" style="color:red;">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No se encontraron productos.</p>
<?php endif; ?>

</body>
</html>