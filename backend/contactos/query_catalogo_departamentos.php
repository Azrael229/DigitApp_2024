<?php

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$consultaCatalogo = $conexion->prepare(
    'SELECT id, nombre
     FROM catalogo_departamentos
     WHERE activo = 1
     ORDER BY nombre ASC'
);
$consultaCatalogo->execute();
$departamentos = $consultaCatalogo->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaCatalogo->close();

echo json_encode($departamentos, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
