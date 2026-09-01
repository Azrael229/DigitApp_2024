<?php

require_once __DIR__ . '/../helpers/normalizador_datos.php';
require __DIR__ . '/../../config/conexion.php';

function responderErrorContacto(string $mensaje, int $estado = 422): void
{
    http_response_code($estado);
    echo $mensaje;
    exit;
}

function obtenerIdOpcional($valor): ?int
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $id = filter_var($valor, FILTER_VALIDATE_INT);

    return $id === false || $id < 1 ? null : $id;
}

function normalizarTelefonoObligatorio($valor): ?string
{
    $original = limpiarEspacios($valor);
    if ($original === null || $original === '.' || $original === '0') {
        return null;
    }

    return normalizarTelefonoMX($original);
}

function redirigirContacto(?int $empresaContexto, ?int $empresaRetorno, bool $esNuevo, array $empresas): void
{
    if ($esNuevo && $empresaContexto !== null && in_array($empresaContexto, $empresas, true)) {
        header('Location: ../../paginas/ver_empresa.php?id=' . $empresaContexto);
        exit;
    }

    if ($empresaRetorno !== null && in_array($empresaRetorno, $empresas, true)) {
        header('Location: ../../paginas/ver_empresa.php?id=' . $empresaRetorno);
        exit;
    }

    header('Location: ../../paginas/contactos.php');
    exit;
}

$idContactoEntrada = $_POST['contacto_id'] ?? null;
$idContacto = obtenerIdOpcional($idContactoEntrada);
$esNuevo = $idContacto === null;
$empresaContexto = $esNuevo ? obtenerIdOpcional($_POST['empresa_contexto'] ?? null) : null;
$empresaRetorno = obtenerIdOpcional($_POST['return_empresa_id'] ?? null);
$nombre = normalizarNombrePersona($_POST['contacto_nombre'] ?? null);
$telefono = normalizarTelefonoObligatorio($_POST['contacto_cel'] ?? null);
$correoOriginal = limpiarEspacios($_POST['contacto_email'] ?? null);
$correo = $correoOriginal === null || $correoOriginal === '.' ? null : normalizarCorreo($correoOriginal);
$recibioDepartamento = array_key_exists('id_departamento', $_POST);
$recibioPuesto = array_key_exists('puesto', $_POST);
$puesto = $recibioPuesto ? limpiarEspacios($_POST['puesto']) : null;

if ($idContactoEntrada !== null && $idContactoEntrada !== '' && $idContacto === null) {
    mysqli_close($conexion);
    responderErrorContacto('El identificador del contacto no es válido.');
}

if ($nombre === null) {
    mysqli_close($conexion);
    responderErrorContacto('El nombre del contacto es obligatorio.');
}

if ($telefono === null) {
    mysqli_close($conexion);
    responderErrorContacto('El teléfono es obligatorio y debe contener exactamente 10 dígitos nacionales.');
}

if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
    mysqli_close($conexion);
    responderErrorContacto('El correo electrónico no es válido.');
}

$idDepartamento = null;
if ($recibioDepartamento && $_POST['id_departamento'] !== '') {
    $idDepartamento = obtenerIdOpcional($_POST['id_departamento']);
    if ($idDepartamento === null) {
        mysqli_close($conexion);
        responderErrorContacto('El departamento no es válido.');
    }

    $consultaDepartamento = $conexion->prepare(
        'SELECT id FROM catalogo_departamentos WHERE id = ? AND activo = 1 LIMIT 1'
    );
    $consultaDepartamento->bind_param('i', $idDepartamento);
    $consultaDepartamento->execute();
    $departamentoValido = $consultaDepartamento->get_result()->fetch_assoc();
    $consultaDepartamento->close();

    if ($departamentoValido === null) {
        mysqli_close($conexion);
        responderErrorContacto('El departamento seleccionado no existe o está inactivo.');
    }
}

$usarRelacionesExplicitas = array_key_exists('empresas', $_POST) || array_key_exists('empresas_presentes', $_POST);
$empresas = [];

if ($usarRelacionesExplicitas) {
    $empresasEntrada = is_array($_POST['empresas']) ? $_POST['empresas'] : [$_POST['empresas']];
    foreach ($empresasEntrada as $empresaEntrada) {
        if ($empresaEntrada === null || $empresaEntrada === '') {
            continue;
        }
        $empresaId = obtenerIdOpcional($empresaEntrada);
        if ($empresaId === null) {
            mysqli_close($conexion);
            responderErrorContacto('Una empresa asociada no es válida.');
        }
        $empresas[$empresaId] = $empresaId;
    }
} elseif (array_key_exists('contacto_nombre_empresa', $_POST)) {
    $empresaHistoricaEntrada = $_POST['contacto_nombre_empresa'];
    $empresaHistorica = obtenerIdOpcional($empresaHistoricaEntrada);
    if ($empresaHistoricaEntrada !== '' && $empresaHistorica === null) {
        mysqli_close($conexion);
        responderErrorContacto('La empresa seleccionada no es válida.');
    }

    // El formulario legado usa 6 como "Sin Empresa Asociada". Nunca se crea esa relación.
    if ($empresaHistorica !== null && $empresaHistorica !== 6) {
        $empresas[$empresaHistorica] = $empresaHistorica;
    }
}

$empresas = array_values($empresas);
$empresaPrincipal = null;
if ($empresas !== []) {
    $empresaPrincipalEntrada = $_POST['empresa_principal'] ?? null;
    $empresaPrincipal = obtenerIdOpcional($empresaPrincipalEntrada);
    if ($empresaPrincipalEntrada !== null && $empresaPrincipalEntrada !== '' && $empresaPrincipal === null) {
        mysqli_close($conexion);
        responderErrorContacto('La empresa principal no es válida.');
    }
    $empresaPrincipal ??= $empresas[0];
    if (!in_array($empresaPrincipal, $empresas, true)) {
        mysqli_close($conexion);
        responderErrorContacto('La empresa principal debe estar incluida en las empresas asociadas.');
    }

    $consultaEmpresa = $conexion->prepare('SELECT id_e FROM empresas WHERE id_e = ? LIMIT 1');
    foreach ($empresas as $empresaId) {
        $consultaEmpresa->bind_param('i', $empresaId);
        $consultaEmpresa->execute();
        if ($consultaEmpresa->get_result()->fetch_assoc() === null) {
            $consultaEmpresa->close();
            mysqli_close($conexion);
            responderErrorContacto('Una empresa asociada no existe.');
        }
    }
    $consultaEmpresa->close();
}

$activoSolicitado = null;
if (array_key_exists('activo', $_POST) && $_POST['activo'] !== '') {
    $activoSolicitado = filter_var($_POST['activo'], FILTER_VALIDATE_INT);
    if ($activoSolicitado === false || !in_array($activoSolicitado, [0, 1], true)) {
        mysqli_close($conexion);
        responderErrorContacto('El estado del contacto no es válido.');
    }
}

if (!$conexion->begin_transaction()) {
    mysqli_close($conexion);
    responderErrorContacto('No fue posible iniciar la transacción.');
}

try {
    if ($idContacto === null) {
        $activo = $activoSolicitado ?? 1;
        $guardarContacto = $conexion->prepare(
            'INSERT INTO contactos
                (nombre, celular, correo, id_departamento, puesto, activo, fecha_creacion, fecha_actualizacion)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $guardarContacto->bind_param('sssisi', $nombre, $telefono, $correo, $idDepartamento, $puesto, $activo);
        if (!$guardarContacto->execute()) {
            throw new RuntimeException('No fue posible crear el contacto.');
        }
        $idContacto = $conexion->insert_id;
        $guardarContacto->close();
    } else {
        $consultaActual = $conexion->prepare(
            'SELECT activo, id_departamento, puesto FROM contactos WHERE id = ? LIMIT 1'
        );
        $consultaActual->bind_param('i', $idContacto);
        $consultaActual->execute();
        $contactoActual = $consultaActual->get_result()->fetch_assoc();
        $consultaActual->close();

        if ($contactoActual === null) {
            throw new RuntimeException('El contacto no existe.');
        }

        $activo = $activoSolicitado ?? (int) $contactoActual['activo'];
        if (!$recibioDepartamento) {
            $idDepartamento = $contactoActual['id_departamento'] === null
                ? null
                : (int) $contactoActual['id_departamento'];
        }
        if (!$recibioPuesto) {
            $puesto = $contactoActual['puesto'];
        }
        $guardarContacto = $conexion->prepare(
            'UPDATE contactos
             SET nombre = ?, celular = ?, correo = ?, id_departamento = ?, puesto = ?, activo = ?,
                 fecha_actualizacion = NOW()
             WHERE id = ?'
        );
        $guardarContacto->bind_param('sssisii', $nombre, $telefono, $correo, $idDepartamento, $puesto, $activo, $idContacto);
        if (!$guardarContacto->execute()) {
            throw new RuntimeException('No fue posible actualizar el contacto.');
        }
        $guardarContacto->close();
    }

    if ($usarRelacionesExplicitas) {
        $desactivarRelaciones = $conexion->prepare(
            'UPDATE empresa_contactos
             SET activo = 0, es_principal = 0, fecha_actualizacion = NOW()
             WHERE id_contacto = ? AND activo = 1'
        );
        $desactivarRelaciones->bind_param('i', $idContacto);
        if (!$desactivarRelaciones->execute()) {
            throw new RuntimeException('No fue posible actualizar las relaciones empresariales.');
        }
        $desactivarRelaciones->close();
    }

    if ($empresas !== []) {
        $guardarRelacion = $conexion->prepare(
            'INSERT INTO empresa_contactos
                (id_empresa, id_contacto, activo, es_principal, fecha_creacion, fecha_actualizacion)
             VALUES (?, ?, 1, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE activo = 1, fecha_actualizacion = NOW()'
        );
        foreach ($empresas as $empresaId) {
            $guardarRelacion->bind_param('ii', $empresaId, $idContacto);
            if (!$guardarRelacion->execute()) {
                throw new RuntimeException('No fue posible guardar una relación empresarial.');
            }
        }
        $guardarRelacion->close();

        if ($usarRelacionesExplicitas) {
            $definirPrincipal = $conexion->prepare(
                'UPDATE empresa_contactos
                 SET es_principal = CASE WHEN id_empresa = ? THEN 1 ELSE 0 END,
                     fecha_actualizacion = NOW()
                 WHERE id_contacto = ? AND activo = 1'
            );
            $definirPrincipal->bind_param('ii', $empresaPrincipal, $idContacto);
            if (!$definirPrincipal->execute()) {
                throw new RuntimeException('No fue posible definir la empresa principal.');
            }
            $definirPrincipal->close();
        } else {
            $consultaPrincipal = $conexion->prepare(
                'SELECT id FROM empresa_contactos WHERE id_contacto = ? AND activo = 1 AND es_principal = 1 LIMIT 1'
            );
            $consultaPrincipal->bind_param('i', $idContacto);
            $consultaPrincipal->execute();
            $tienePrincipal = $consultaPrincipal->get_result()->fetch_assoc() !== null;
            $consultaPrincipal->close();

            if (!$tienePrincipal) {
                $definirPrincipal = $conexion->prepare(
                    'UPDATE empresa_contactos
                     SET es_principal = 1, fecha_actualizacion = NOW()
                     WHERE id_contacto = ? AND id_empresa = ? AND activo = 1'
                );
                $definirPrincipal->bind_param('ii', $idContacto, $empresaPrincipal);
                if (!$definirPrincipal->execute()) {
                    throw new RuntimeException('No fue posible definir la empresa principal.');
                }
                $definirPrincipal->close();
            }
        }
    }

    if (!$conexion->commit()) {
        throw new RuntimeException('No fue posible confirmar la transacción.');
    }
} catch (Throwable $error) {
    $conexion->rollback();
    mysqli_close($conexion);
    responderErrorContacto($error->getMessage(), 409);
}

mysqli_close($conexion);
redirigirContacto($empresaContexto, $empresaRetorno, $esNuevo, $empresas);
?>
