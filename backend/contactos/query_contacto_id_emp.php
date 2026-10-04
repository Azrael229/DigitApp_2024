<?php

$idEmpresa = filter_var(trim(file_get_contents('php://input')), FILTER_VALIDATE_INT);

require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($idEmpresa === false || $idEmpresa < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'Empresa no válida', 'contactos' => []]);
    mysqli_close($conexion);
    exit;
}

$consultaContactos = $conexion->prepare(
    'SELECT c.id, c.nombre, c.celular, c.correo, c.depto, c.id_departamento,
            COALESCE(d.nombre, c.depto) AS departamento, c.puesto, c.activo, ec.es_principal
     FROM empresa_contactos AS ec
     INNER JOIN contactos AS c ON c.id = ec.id_contacto
     LEFT JOIN catalogo_departamentos AS d ON d.id = c.id_departamento
     WHERE ec.id_empresa = ? AND ec.activo = 1
     ORDER BY ec.es_principal DESC, c.nombre ASC, c.id ASC'
);
$consultaContactos->bind_param('i', $idEmpresa);
$consultaContactos->execute();
$contactos = $consultaContactos->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaContactos->close();

$consultaDirecciones = $conexion->prepare(
    'SELECT id, tipo_direccion, alias, es_principal, calle, numero_exterior,
            numero_interior, colonia, localidad, municipio, ciudad, estado,
            codigo_postal, pais, entre_calles, referencia, direccion_original
     FROM empresa_direcciones
     WHERE empresa_id = ?
     ORDER BY es_principal DESC, tipo_direccion ASC, id ASC'
);
$consultaDirecciones->bind_param('i', $idEmpresa);
$consultaDirecciones->execute();
$direcciones = $consultaDirecciones->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaDirecciones->close();

$consultaEquipos = $conexion->prepare(
    'SELECT ee.id, ee.direccion_id, ee.ubicacion, ee.modelo, ee.identificacion, ee.numero_serie,
            ee.unidad, ee.capacidad_maxima, ee.division_real,
            ee.division_verificacion, ee.clase_exactitud, ee.estatus,
            COALESCE(cd.nombre, \'\') AS descripcion,
            COALESCE(cm.nombre, \'\') AS marca
     FROM empresa_equipos AS ee
     LEFT JOIN catalogo_descripciones_equipo AS cd ON cd.id = ee.descripcion_id
     LEFT JOIN catalogo_marcas_equipo AS cm ON cm.id = ee.marca_id
     WHERE ee.empresa_id = ?
     ORDER BY ee.direccion_id ASC, cd.nombre ASC, ee.identificacion ASC, ee.id ASC'
);
$consultaEquipos->bind_param('i', $idEmpresa);
$consultaEquipos->execute();
$equipos = $consultaEquipos->get_result()->fetch_all(MYSQLI_ASSOC);
$consultaEquipos->close();

// Informe actual consume nombre/correo en el objeto raiz. Se conserva el primer
// contacto ordenado como compatibilidad y se expone el arreglo completo para el nuevo flujo.
$respuesta = $contactos[0] ?? [];
$respuesta['contactos'] = $contactos;
$respuesta['direcciones'] = $direcciones;
$respuesta['equipos'] = $equipos;

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

mysqli_close($conexion);
?>
