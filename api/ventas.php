<?php
$stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);
$stock_actual = $stmt->fetchColumn();

if ($cantidad > $stock_actual) {
    $error = "Stock insuficiente.";
} else {
    
    $pdo->prepare("INSERT INTO ventas (producto_id, cantidad) VALUES (?, ?)")
        ->execute([$producto_id, $cantidad]);

   
    $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
        ->execute([$cantidad, $producto_id]);
}

?>