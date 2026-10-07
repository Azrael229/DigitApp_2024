<?php

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');
require_once __DIR__ . '/../helpers/normalizador_datos.php';

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_verify_csrf($_POST['csrf'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $nombre = limpiarEspacios($_POST['nombre'] ?? null);
    if ($nombre === null || mb_strlen($nombre, 'UTF-8') > 100) {
        http_response_code(422);
        echo json_encode(['error' => 'Escribe un departamento válido de hasta 100 caracteres.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $guardar = $conexion->prepare(
        'INSERT INTO catalogo_departamentos (nombre, activo, fecha_creacion, fecha_actualizacion)
         VALUES (?, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE activo = 1, fecha_actualizacion = NOW(), id = LAST_INSERT_ID(id)'
    );
    $guardar->bind_param('s', $nombre);
    $guardar->execute();
    $id = (int) $conexion->insert_id;
    $guardar->close();
    $consulta = $conexion->prepare('SELECT id, nombre FROM catalogo_departamentos WHERE id = ? LIMIT 1');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $departamento = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    echo json_encode(['departamento' => $departamento], JSON_UNESCAPED_UNICODE);
    mysqli_close($conexion);
    exit;
}

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
