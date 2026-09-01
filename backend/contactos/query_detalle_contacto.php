<?php

$id = filter_var(trim(file_get_contents('php://input')), FILTER_VALIDATE_INT);

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($id === false || $id < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'Contacto no válido']);
    mysqli_close($conexion);
    exit;
}

$consultaContacto = $conexion->prepare(
    'SELECT c.id, c.nombre, c.celular, c.correo, c.activo, c.fecha_creacion,
            c.fecha_actualizacion, c.id_departamento, COALESCE(d.nombre, c.depto) AS departamento,
            c.puesto
     FROM contactos AS c
     LEFT JOIN catalogo_departamentos AS d ON d.id = c.id_departamento
     WHERE c.id = ?
     LIMIT 1'
);
$consultaContacto->bind_param('i', $id);
$consultaContacto->execute();
$contacto = $consultaContacto->get_result()->fetch_assoc();
$consultaContacto->close();

if ($contacto === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Contacto no encontrado']);
    mysqli_close($conexion);
    exit;
}

$consultaEmpresas = $conexion->prepare(
    'SELECT ec.id_empresa, e.empresa, ec.es_principal, ec.activo
     FROM empresa_contactos AS ec
     INNER JOIN empresas AS e ON e.id_e = ec.id_empresa
     WHERE ec.id_contacto = ? AND ec.activo = 1
     ORDER BY ec.es_principal DESC, e.empresa ASC, ec.id ASC'
);
$consultaEmpresas->bind_param('i', $id);
$consultaEmpresas->execute();
$contacto['empresas'] = $consultaEmpresas->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaEmpresas->close();

echo json_encode($contacto, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
