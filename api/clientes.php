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
// ELIMINAR cliente
// ----------------------------
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    if ($stmt->execute([$id])) {
        $mensaje = "<p style='color:green;'>Cliente eliminado correctamente.</p>";
    } else {
        $mensaje = "<p style='color:red;'>Error al eliminar el cliente.</p>";
    }
    header("Location: clientes.php?mensaje=" . urlencode($mensaje));
    exit;
}

// ----------------------------
// ACTUALIZAR cliente
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'])) {
    $id = (int)$_POST['editar_id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    if ($nombre && $email) {
        $stmt = $pdo->prepare("UPDATE clientes SET nombre = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?");
        if ($stmt->execute([$nombre, $email, $telefono, $direccion, $id])) {
            $mensaje = "<p style='color:green;'>Cliente actualizado correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al actualizar el cliente.</p>";
        }
    } else {
        $mensaje = "<p style='color:red;'>Nombre y email son obligatorios.</p>";
    }
    header("Location: clientes.php?mensaje=" . urlencode($mensaje));
    exit;
}

// ----------------------------
// CREAR nuevo cliente
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    if ($nombre && $email) {
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, direccion) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nombre, $email, $telefono, $direccion])) {
            $mensaje = "<p style='color:green;'>Cliente creado correctamente.</p>";
        } else {
            $mensaje = "<p style='color:red;'>Error al crear el cliente.</p>";
        }
    } else {
        $mensaje = "<p style='color:red;'>Nombre y email son obligatorios.</p>";
    }
    header("Location: clientes.php?mensaje=" . urlencode($mensaje));
    exit;
}

// ----------------------------
// OBTENER cliente para editar
// ----------------------------
$editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ----------------------------
// LISTAR clientes
// ----------------------------
$stmt = $pdo->query("SELECT * FROM clientes ORDER BY id");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['mensaje'])) {
    $mensaje = urldecode($_GET['mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .form-container { background: #f9f9f9; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
        input[type="text"], input[type="email"], textarea { width: 250px; padding: 5px; }
        button { padding: 6px 12px; margin: 5px 0; }
    </style>
</head>
<body>

<nav>
  <a href="productos.php">Productos</a> |
  <a href="clientes.php">Clientes</a> |
  <a href="pedidos.php">Pedidos</a>
</nav>

<h1>Gestión de Clientes</h1>

<?php if ($mensaje): ?>
    <?= $mensaje ?>
<?php endif; ?>

<!-- Formulario de Creación o Edición -->
<div class="form-container">
    <h2><?= $editar ? 'Editar Cliente' : 'Agregar Nuevo Cliente' ?></h2>
    <form method="POST">
        <?php if ($editar): ?>
            <input type="hidden" name="editar_id" value="<?= htmlspecialchars($editar['id']) ?>">
        <?php endif; ?>

        <label>Nombre:<br>
            <input type="text" name="nombre" value="<?= $editar ? htmlspecialchars($editar['nombre']) : '' ?>" required>
        </label><br><br>

        <label>Email:<br>
            <input type="email" name="email" value="<?= $editar ? htmlspecialchars($editar['email']) : '' ?>" required>
        </label><br><br>

        <label>Teléfono:<br>
            <input type="text" name="telefono" value="<?= $editar ? htmlspecialchars($editar['telefono']) : '' ?>">
        </label><br><br>

        <label>Dirección:<br>
<textarea name="direccion" rows="2"><?= $editar ? htmlspecialchars($editar['direccion'] ?? '') : '' ?></textarea>
        <button type="submit" name="crear" value="1"><?= $editar ? 'Actualizar' : 'Crear Cliente' ?></button>
        <?php if ($editar): ?>
            <a href="clientes.php">Cancelar edición</a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista de Clientes -->
<h2>Lista de Clientes</h2>
<?php if ($clientes): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['id']) ?></td>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['telefono']) ?></td>
                    <td><?= htmlspecialchars($c['direccion']) ?></td>
                    <td>
                        <a href="?editar=<?= $c['id'] ?>">✏️ Editar</a> |
                        <a href="?eliminar=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar este cliente?')" style="color:red;">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No hay clientes registrados.</p>
<?php endif; ?>

</body>
</html>