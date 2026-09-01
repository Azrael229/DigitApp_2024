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
    'SELECT c.id, c.nombre, c.celular, c.correo, c.depto, c.id_empresa, c.id_departamento,
            COALESCE(d.nombre, c.depto) AS departamento, c.puesto, c.activo,
            c.fecha_creacion, c.fecha_actualizacion
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

$consultaRelaciones = $conexion->prepare(
    'SELECT ec.id, ec.id_empresa, ec.es_principal, e.empresa, e.dir_entrega
     FROM empresa_contactos AS ec
     INNER JOIN empresas AS e ON e.id_e = ec.id_empresa
     WHERE ec.id_contacto = ? AND ec.activo = 1
     ORDER BY ec.es_principal DESC, e.empresa ASC, ec.id ASC'
);
$consultaRelaciones->bind_param('i', $id);
$consultaRelaciones->execute();
$relaciones = $consultaRelaciones->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaRelaciones->close();

$principal = $relaciones[0] ?? null;
$direcciones = [];
if ($principal !== null) {
    $idEmpresaPrincipal = (int) $principal['id_empresa'];
    $consultaDirecciones = $conexion->prepare(
        'SELECT id, tipo_direccion, alias, es_principal, calle, numero_exterior,
                numero_interior, colonia, localidad, municipio, ciudad, estado,
                codigo_postal, pais, entre_calles, referencia, direccion_original
         FROM empresa_direcciones
         WHERE empresa_id = ?
         ORDER BY tipo_direccion, es_principal DESC, id ASC'
    );
    $consultaDirecciones->bind_param('i', $idEmpresaPrincipal);
    $consultaDirecciones->execute();
    $direcciones = $consultaDirecciones->get_result()->fetch_all(MYSQLI_ASSOC);
    $consultaDirecciones->close();
}

// Claves heredadas para Cotizaciones y la edicion actual.
$contacto['empresa'] = $principal['empresa'] ?? '';
$contacto['empresa_id'] = $principal === null ? null : (int) $principal['id_empresa'];
$contacto['dir_entrega'] = $principal['dir_entrega'] ?? '';
$contacto['direcciones'] = $direcciones;
$contacto['relaciones'] = $relaciones;
$contacto['depto_historico'] = $contacto['depto'];
$contacto['depto'] = $contacto['departamento'];

echo json_encode($contacto, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
