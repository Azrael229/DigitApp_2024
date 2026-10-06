<?php
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');
require(__DIR__ . "/../../config/conexion.php");

function valorPostDireccion(string $campo): ?string
{
    $valor = trim((string) ($_POST[$campo] ?? ''));

    return $valor === '' ? null : $valor;
}

function redirigirEmpresaDireccion(int $empresaId, string $parametro = ''): void
{
    $sufijo = $parametro === '' ? '' : '&' . $parametro;
    header('Location: ../../paginas/ver_empresa.php?id=' . $empresaId . $sufijo);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$empresaId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT);
$direccionId = filter_var($_POST['direccion_id'] ?? null, FILTER_VALIDATE_INT);
$version = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$tipoDireccion = valorPostDireccion('tipo_direccion');
$calle = valorPostDireccion('calle');

if ($empresaId === false || $empresaId === null || !in_array($tipoDireccion, ['fiscal', 'entrega'], true) || $calle === null) {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$consultaEmpresa = $conexion->prepare('SELECT id_e FROM empresas WHERE id_e = ? LIMIT 1');
$consultaEmpresa->bind_param('i', $empresaId);
$consultaEmpresa->execute();
$empresaExiste = $consultaEmpresa->get_result()->num_rows === 1;
$consultaEmpresa->close();

if (!$empresaExiste) {
    mysqli_close($conexion);
    header('Location: ../../paginas/empresas.php');
    exit;
}

$esPrincipal = isset($_POST['es_principal']) ? 1 : 0;
$campos = [
    valorPostDireccion('alias') ?? ucfirst($tipoDireccion),
    $calle,
    valorPostDireccion('numero_exterior'),
    valorPostDireccion('numero_interior'),
    valorPostDireccion('colonia'),
    valorPostDireccion('localidad'),
    valorPostDireccion('municipio'),
    valorPostDireccion('ciudad'),
    valorPostDireccion('estado'),
    valorPostDireccion('codigo_postal'),
    valorPostDireccion('pais') ?? 'México',
    valorPostDireccion('entre_calles'),
    valorPostDireccion('referencia'),
    valorPostDireccion('enlace_maps'),
];

if ($direccionId !== false && $direccionId !== null) {
    $consultaDireccion = $conexion->prepare(
        'UPDATE empresa_direcciones SET
            tipo_direccion = ?, alias = ?, es_principal = ?, calle = ?,
            numero_exterior = ?, numero_interior = ?, colonia = ?, localidad = ?,
            municipio = ?, ciudad = ?, estado = ?, codigo_postal = ?, pais = ?,
            entre_calles = ?, referencia = ?, enlace_maps = ?, version = version + 1
        WHERE id = ? AND empresa_id = ? AND version = ?'
    );
    $tiposActualizacion = 'ssi' . str_repeat('s', 13) . 'iii';
    $consultaDireccion->bind_param(
        $tiposActualizacion,
        $tipoDireccion,
        $campos[0],
        $esPrincipal,
        $campos[1],
        $campos[2],
        $campos[3],
        $campos[4],
        $campos[5],
        $campos[6],
        $campos[7],
        $campos[8],
        $campos[9],
        $campos[10],
        $campos[11],
        $campos[12],
        $campos[13],
        $direccionId,
        $empresaId,
        $version
    );
} else {
    $consultaDireccion = $conexion->prepare(
        'INSERT INTO empresa_direcciones (
            empresa_id, tipo_direccion, alias, es_principal, calle,
            numero_exterior, numero_interior, colonia, localidad, municipio,
            ciudad, estado, codigo_postal, pais, entre_calles, referencia, enlace_maps
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $consultaDireccion->bind_param(
        'ississsssssssssss',
        $empresaId,
        $tipoDireccion,
        $campos[0],
        $esPrincipal,
        $campos[1],
        $campos[2],
        $campos[3],
        $campos[4],
        $campos[5],
        $campos[6],
        $campos[7],
        $campos[8],
        $campos[9],
        $campos[10],
        $campos[11],
        $campos[12],
        $campos[13]
    );
}

try {
    $consultaDireccion->execute();
    if ($direccionId !== false && $direccionId !== null && $consultaDireccion->affected_rows !== 1) {
        throw new RuntimeException('Otra edición modificó esta dirección. Recarga la página.');
    }
    $consultaDireccion->close();
    mysqli_close($conexion);
    redirigirEmpresaDireccion($empresaId, $direccionId !== false && $direccionId !== null ? 'direccion_actualizada=1' : 'direccion_guardada=1');
} catch (Throwable $error) {
    $consultaDireccion->close();
    mysqli_close($conexion);
    redirigirEmpresaDireccion($empresaId, 'error=' . rawurlencode($error->getMessage()));
}
?>
