<?php
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('productos');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/../../config/conexion.php';

function redirigirEquipoEmpresa(int $empresaId, string $parametro): void
{
    header('Location: ../../paginas/ver_empresa.php?id=' . $empresaId . '&' . $parametro . '#equipos');
    exit;
}

function textoEquipo(string $campo): ?string
{
    $valor = trim((string) ($_POST[$campo] ?? ''));
    return $valor === '' ? null : $valor;
}

function idOpcionalEquipo($valor)
{
    if ($valor === null || $valor === '') {
        return null;
    }
    $id = filter_var($valor, FILTER_VALIDATE_INT);
    return $id === false || $id < 1 ? false : $id;
}

function decimalEquipo(string $campo, bool $obligatorio)
{
    $valor = trim((string) ($_POST[$campo] ?? ''));
    if ($valor === '') {
        return $obligatorio ? false : null;
    }
    if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,9})?$/', $valor) || (float) $valor <= 0) {
        return false;
    }
    return $valor;
}

function claseExactitudEquipo(string $capacidad, string $divisionVerificacion): string
{
    $divisiones = (float) $capacidad / (float) $divisionVerificacion;
    if ($divisiones <= 1000) {
        return 'Ordinaria';
    }
    if ($divisiones <= 10000) {
        return 'Media';
    }
    if ($divisiones <= 100000) {
        return 'Fina';
    }
    return 'Especial';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$empresaId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT);
$equipoId = filter_var($_POST['equipo_id'] ?? null, FILTER_VALIDATE_INT);
$version = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($empresaId === false || $empresaId === null || $empresaId < 1) {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$token = (string) ($_POST['csrf'] ?? '');
if (empty($_SESSION['empresa_equipos_csrf']) || !hash_equals($_SESSION['empresa_equipos_csrf'], $token)) {
    mysqli_close($conexion);
    redirigirEquipoEmpresa($empresaId, 'error_equipo=La%20sesión%20del%20formulario%20expiró');
}

$direccionId = idOpcionalEquipo($_POST['direccion_id'] ?? null);
$ubicacion = textoEquipo('ubicacion');
$descripcionId = idOpcionalEquipo($_POST['descripcion_id'] ?? null);
$marcaId = idOpcionalEquipo($_POST['marca_id'] ?? null);
$modelo = textoEquipo('modelo');
$identificacion = textoEquipo('identificacion');
$numeroSerie = textoEquipo('numero_serie');
$unidad = textoEquipo('unidad');
$capacidad = decimalEquipo('capacidad_maxima', true);
$divisionReal = decimalEquipo('division_real', true);
$divisionVerificacion = decimalEquipo('division_verificacion', false);
$estatus = textoEquipo('estatus') ?? 'activo';

if (
    $direccionId === false || $descripcionId === false || $marcaId === false
    || !in_array($unidad, ['kg', 'g'], true)
    || $capacidad === false || $divisionReal === false || $divisionVerificacion === false
    || !in_array($estatus, ['activo', 'fuera_servicio', 'inactivo'], true)
) {
    mysqli_close($conexion);
    redirigirEquipoEmpresa($empresaId, 'error_equipo=Revise%20los%20datos%20obligatorios%20del%20equipo');
}

$direccionValida = true;
if ($direccionId !== null) {
    $consultaDireccion = $conexion->prepare('SELECT id FROM empresa_direcciones WHERE id = ? AND empresa_id = ? LIMIT 1');
    $consultaDireccion->bind_param('ii', $direccionId, $empresaId);
    $consultaDireccion->execute();
    $direccionValida = $consultaDireccion->get_result()->fetch_assoc() !== null;
    $consultaDireccion->close();
}

$descripcionValida = true;
if ($descripcionId !== null) {
    $consultaDescripcion = $conexion->prepare(
        'SELECT id FROM catalogo_descripciones_equipo WHERE id = ? AND activo = 1 LIMIT 1'
    );
    $consultaDescripcion->bind_param('i', $descripcionId);
    $consultaDescripcion->execute();
    $descripcionValida = $consultaDescripcion->get_result()->fetch_assoc() !== null;
    $consultaDescripcion->close();
}

$marcaValida = true;
if ($marcaId !== null) {
    $consultaMarca = $conexion->prepare('SELECT id FROM catalogo_marcas_equipo WHERE id = ? AND activo = 1 LIMIT 1');
    $consultaMarca->bind_param('i', $marcaId);
    $consultaMarca->execute();
    $marcaValida = $consultaMarca->get_result()->fetch_assoc() !== null;
    $consultaMarca->close();
}

if (!$direccionValida || !$descripcionValida || !$marcaValida) {
    mysqli_close($conexion);
    redirigirEquipoEmpresa($empresaId, 'error_equipo=La%20dirección%2C%20descripción%20o%20marca%20no%20es%20válida');
}

$clase = $divisionVerificacion === null ? null : claseExactitudEquipo($capacidad, $divisionVerificacion);

try {
    if ($equipoId !== false && $equipoId !== null && $equipoId > 0) {
        $guardar = $conexion->prepare(
            'UPDATE empresa_equipos SET direccion_id = ?, ubicacion = ?, descripcion_id = ?, marca_id = ?,
                modelo = ?, identificacion = ?, numero_serie = ?, unidad = ?, capacidad_maxima = ?,
                division_real = ?, division_verificacion = ?, clase_exactitud = ?, estatus = ?,
                version = version + 1
             WHERE id = ? AND empresa_id = ? AND version = ?'
        );
        $guardar->bind_param(
            'isiisssssssssiii',
            $direccionId,
            $ubicacion,
            $descripcionId,
            $marcaId,
            $modelo,
            $identificacion,
            $numeroSerie,
            $unidad,
            $capacidad,
            $divisionReal,
            $divisionVerificacion,
            $clase,
            $estatus,
            $equipoId,
            $empresaId,
            $version
        );
        $guardar->execute();
        $guardado = $guardar->affected_rows === 1;
        $guardar->close();
        if (!$guardado) {
            throw new RuntimeException('Otra edición modificó este equipo. Recarga la página.');
        }
        $parametro = 'equipo_actualizado=1';
    } else {
        $guardar = $conexion->prepare(
            'INSERT INTO empresa_equipos
                (empresa_id, direccion_id, ubicacion, descripcion_id, marca_id, modelo, identificacion,
                 numero_serie, unidad, capacidad_maxima, division_real, division_verificacion,
                 clase_exactitud, estatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $guardar->bind_param(
            'iisiisssssssss',
            $empresaId,
            $direccionId,
            $ubicacion,
            $descripcionId,
            $marcaId,
            $modelo,
            $identificacion,
            $numeroSerie,
            $unidad,
            $capacidad,
            $divisionReal,
            $divisionVerificacion,
            $clase,
            $estatus
        );
        $guardar->execute();
        $guardar->close();
        $parametro = 'equipo_guardado=1';
    }
} catch (Throwable $error) {
    mysqli_close($conexion);
    redirigirEquipoEmpresa($empresaId, 'error_equipo=No%20fue%20posible%20guardar%20el%20equipo');
}

mysqli_close($conexion);
redirigirEquipoEmpresa($empresaId, $parametro);
?>
