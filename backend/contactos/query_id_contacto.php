<?php
$id = filter_var(trim(file_get_contents('php://input')), FILTER_VALIDATE_INT);

require (__DIR__ . "/../../config/conexion.php");

header('Content-Type: application/json; charset=utf-8');

if ($id === false || $id < 1) {
     http_response_code(422);
     echo json_encode(['error' => 'Contacto no valido']);
     mysqli_close($conexion);
     exit;
}

$consultaContacto = $conexion->prepare(
     'SELECT contactos.*, empresas.empresa, empresas.dir_entrega, empresas.id_e AS empresa_id
      FROM contactos
      INNER JOIN empresas ON contactos.id_empresa = empresas.id_e
      WHERE contactos.id = ?
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

$consultaDirecciones = $conexion->prepare(
     'SELECT id, tipo_direccion, alias, es_principal, calle, numero_exterior,
             numero_interior, colonia, localidad, municipio, ciudad, estado,
             codigo_postal, pais, entre_calles, referencia, direccion_original
      FROM empresa_direcciones
      WHERE empresa_id = ?
      ORDER BY tipo_direccion, es_principal DESC, id ASC'
);
$consultaDirecciones->bind_param('i', $contacto['empresa_id']);
$consultaDirecciones->execute();
$direcciones = $consultaDirecciones->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaDirecciones->close();

$contacto['direcciones'] = $direcciones;
echo json_encode($contacto, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
