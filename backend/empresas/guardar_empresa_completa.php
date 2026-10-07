<?php

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');

require(__DIR__ . "/../../config/conexion.php");
require_once(__DIR__ . "/../helpers/normalizador_datos.php");

function valorPost(string $campo): ?string
{
    $valor = trim((string) ($_POST[$campo] ?? ''));

    return $valor === '' ? null : $valor;
}

function redirigirFormulario(string $parametro, ?int $contactoId = null): void
{
    if ($contactoId !== null) {
        $parametro .= '&contacto_id=' . $contactoId;
    }
    header('Location: ../../paginas/form_empresa.php?' . $parametro);
    exit;
}

function redirigirVistaEmpresa(int $empresaId, string $parametro): void
{
    header('Location: ../../paginas/ver_empresa.php?id=' . $empresaId . '&' . $parametro);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirFormulario('error=Solicitud%20no%20valida');
}

$empresa = valorPost('empresa');
$empresaId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT);
$esEdicion = $empresaId !== false && $empresaId !== null;
$contactoRetornoId = $esEdicion ? null : filter_var($_POST['contacto_retorno_id'] ?? null, FILTER_VALIDATE_INT);
if ($contactoRetornoId === false || $contactoRetornoId === null || $contactoRetornoId < 1) {
    $contactoRetornoId = null;
}
$version = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$rol = valorPost('rol');
$estatus = valorPost('estatus');
$telefonoEntrada = valorPost('telefono_principal');
$telefono = $telefonoEntrada === null ? null : normalizarTelefonoMX($telefonoEntrada);
$paginaWebEntrada = valorPost('pagina_web');
$paginaWeb = normalizarUrlWeb($paginaWebEntrada);

if ($empresa === null || $rol === null || $estatus === null) {
    redirigirFormulario('error=Empresa%2C%20rol%20y%20estatus%20son%20obligatorios', $contactoRetornoId);
}

if ($telefonoEntrada !== null && $telefono === null) {
    redirigirFormulario('error=' . rawurlencode('El teléfono debe contener exactamente 10 dígitos nacionales'), $contactoRetornoId);
}

if ($paginaWebEntrada !== null && ($paginaWeb === null || mb_strlen($paginaWeb, 'UTF-8') > 255)) {
    redirigirFormulario('error=' . rawurlencode('El sitio web no es válido. Usa una dirección HTTP o HTTPS.'), $contactoRetornoId);
}

if ($contactoRetornoId !== null) {
    $consultaContactoRetorno = $conexion->prepare('SELECT id FROM contactos WHERE id = ? AND activo = 1 LIMIT 1');
    $consultaContactoRetorno->bind_param('i', $contactoRetornoId);
    $consultaContactoRetorno->execute();
    $contactoValido = $consultaContactoRetorno->get_result()->fetch_assoc() !== null;
    $consultaContactoRetorno->close();
    if (!$contactoValido) {
        $contactoRetornoId = null;
    }
}

$camposEmpresa = [
    normalizarRazonSocial($empresa),
    normalizarRazonSocial(valorPost('razon_social')),
    normalizarRFC(valorPost('rfc')),
    $rol,
    normalizarTextoGeneral(valorPost('actividad_economica')),
    normalizarTextoGeneral(valorPost('regimen_capital')),
    normalizarTextoGeneral(valorPost('tipo_persona')),
    normalizarTextoGeneral(valorPost('giro_mercantil')),
    normalizarTextoGeneral(valorPost('mercado')),
    $telefono,
    normalizarCorreo(valorPost('email_principal')),
    $paginaWeb,
    $estatus,
];

mysqli_begin_transaction($conexion);

try {
    if ($esEdicion) {
        $consultaEmpresa = $conexion->prepare(
            'UPDATE empresas SET
                empresa = ?, razon_social = ?, rfc = ?, rol = ?, actividad_economica = ?,
                regimen_capital = ?, tipo_persona = ?, giro_mercantil = ?, mercado = ?,
                telefono_principal = ?, email_principal = ?, pagina_web = ?, estatus = ?,
                version = version + 1
            WHERE id_e = ? AND version = ?'
        );
        $tiposActualizacion = str_repeat('s', 13) . 'ii';
        $camposActualizacion = $camposEmpresa;
        $camposActualizacion[] = $empresaId;
        $camposActualizacion[] = $version;
        $consultaEmpresa->bind_param($tiposActualizacion, ...$camposActualizacion);
        $consultaEmpresa->execute();
        if ($consultaEmpresa->affected_rows !== 1) {
            throw new RuntimeException('Otra edición modificó esta empresa. Recarga la página antes de guardar.');
        }
        $consultaEmpresa->close();
    } else {
        $consultaEmpresa = $conexion->prepare(
            'INSERT INTO empresas (
                empresa, razon_social, rfc, rol, actividad_economica,
                regimen_capital, tipo_persona, giro_mercantil, mercado,
                telefono_principal, email_principal, pagina_web, estatus
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consultaEmpresa->bind_param('sssssssssssss', ...$camposEmpresa);
        $consultaEmpresa->execute();
        $empresaId = $conexion->insert_id;
        $consultaEmpresa->close();

        if ($contactoRetornoId !== null) {
            $consultaPrincipal = $conexion->prepare(
                'SELECT id FROM empresa_contactos WHERE id_contacto = ? AND activo = 1 AND es_principal = 1 LIMIT 1'
            );
            $consultaPrincipal->bind_param('i', $contactoRetornoId);
            $consultaPrincipal->execute();
            $esPrincipalContacto = $consultaPrincipal->get_result()->fetch_assoc() === null ? 1 : 0;
            $consultaPrincipal->close();
            $guardarRelacionContacto = $conexion->prepare(
                'INSERT INTO empresa_contactos
                    (id_empresa, id_contacto, activo, es_principal, fecha_creacion, fecha_actualizacion)
                 VALUES (?, ?, 1, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE activo = 1, es_principal = VALUES(es_principal), fecha_actualizacion = NOW()'
            );
            $guardarRelacionContacto->bind_param('iii', $empresaId, $contactoRetornoId, $esPrincipalContacto);
            $guardarRelacionContacto->execute();
            $guardarRelacionContacto->close();
        }
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);
    if ($esEdicion) {
        redirigirVistaEmpresa($empresaId, 'empresa_actualizada=1');
    }
    if ($contactoRetornoId !== null) {
        header('Location: ../../paginas/ver_contacto.php?id=' . $contactoRetornoId . '&empresa_relacionada=1');
        exit;
    }
    redirigirFormulario('guardado=1');
} catch (Throwable $error) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    if ($esEdicion) {
        redirigirFormulario('id=' . $empresaId . '&error=No%20fue%20posible%20actualizar%20la%20empresa');
    }
    redirigirFormulario('error=No%20fue%20posible%20guardar%20la%20empresa', $contactoRetornoId);
}
