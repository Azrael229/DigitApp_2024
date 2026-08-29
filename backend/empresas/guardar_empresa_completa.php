<?php

require(__DIR__ . "/../../config/conexion.php");
require_once(__DIR__ . "/../helpers/normalizador_datos.php");

function valorPost(string $campo): ?string
{
    $valor = trim((string) ($_POST[$campo] ?? ''));

    return $valor === '' ? null : $valor;
}

function redirigirFormulario(string $parametro): void
{
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
$rol = valorPost('rol');
$estatus = valorPost('estatus');
$telefonoEntrada = valorPost('telefono_principal');
$telefono = $telefonoEntrada === null ? null : normalizarTelefonoMX($telefonoEntrada);

if ($empresa === null || $rol === null || $estatus === null) {
    redirigirFormulario('error=Empresa%2C%20rol%20y%20estatus%20son%20obligatorios');
}

if ($telefonoEntrada !== null && $telefono === null) {
    redirigirFormulario('error=' . rawurlencode('El teléfono debe contener exactamente 10 dígitos nacionales'));
}

$camposEmpresa = [
    normalizarRazonSocial($empresa),
    normalizarRazonSocial(valorPost('razon_social')),
    normalizarRFC(valorPost('rfc')),
    $rol,
    normalizarTextoGeneral(valorPost('actividad_economica')),
    normalizarTextoGeneral(valorPost('regimen_fiscal_codigo')),
    normalizarTextoGeneral(valorPost('regimen_fiscal_descripcion')),
    normalizarTextoGeneral(valorPost('regimen_capital')),
    normalizarTextoGeneral(valorPost('tipo_persona')),
    normalizarTextoGeneral(valorPost('giro_mercantil')),
    normalizarTextoGeneral(valorPost('mercado')),
    $telefono,
    normalizarCorreo(valorPost('email_principal')),
    normalizarTextoGeneral(valorPost('pagina_web')),
    $estatus,
    normalizarTextoGeneral(valorPost('origen_registro')) ?? 'manual',
    normalizarTextoGeneral(valorPost('observaciones')),
];

$camposDireccion = [
    normalizarDireccionTexto(valorPost('alias')) ?? 'Fiscal',
    normalizarDireccionTexto(valorPost('calle')),
    normalizarTextoGeneral(valorPost('numero_exterior')),
    normalizarTextoGeneral(valorPost('numero_interior')),
    normalizarDireccionTexto(valorPost('colonia')),
    normalizarDireccionTexto(valorPost('localidad')),
    normalizarDireccionTexto(valorPost('municipio')),
    normalizarDireccionTexto(valorPost('ciudad')),
    normalizarDireccionTexto(valorPost('estado')),
    normalizarTextoGeneral(valorPost('codigo_postal')),
    normalizarDireccionTexto(valorPost('pais')) ?? 'Mexico',
    normalizarDireccionTexto(valorPost('entre_calles')),
    normalizarDireccionTexto(valorPost('referencia')),
    normalizarTextoGeneral(valorPost('enlace_maps')),
];

mysqli_begin_transaction($conexion);

try {
    if ($esEdicion) {
        $consultaEmpresa = $conexion->prepare(
            'UPDATE empresas SET
                empresa = ?, razon_social = ?, rfc = ?, rol = ?, actividad_economica = ?,
                regimen_fiscal_codigo = ?, regimen_fiscal_descripcion = ?, regimen_capital = ?,
                tipo_persona = ?, giro_mercantil = ?, mercado = ?, telefono_principal = ?,
                email_principal = ?, pagina_web = ?, estatus = ?, origen_registro = ?, observaciones = ?
            WHERE id_e = ?'
        );
        $tiposActualizacion = str_repeat('s', 17) . 'i';
        $camposActualizacion = $camposEmpresa;
        $camposActualizacion[] = $empresaId;
        $consultaEmpresa->bind_param($tiposActualizacion, ...$camposActualizacion);
        $consultaEmpresa->execute();
        $consultaEmpresa->close();
    } else {
        $consultaEmpresa = $conexion->prepare(
            'INSERT INTO empresas (
                empresa, razon_social, rfc, rol, actividad_economica,
                regimen_fiscal_codigo, regimen_fiscal_descripcion, regimen_capital,
                tipo_persona, giro_mercantil, mercado, telefono_principal,
                email_principal, pagina_web, estatus, origen_registro, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $consultaEmpresa->bind_param('sssssssssssssssss', ...$camposEmpresa);
        $consultaEmpresa->execute();
        $empresaId = $conexion->insert_id;
        $consultaEmpresa->close();

        $consultaDireccion = $conexion->prepare(
            'INSERT INTO empresa_direcciones (
                empresa_id, tipo_direccion, alias, es_principal, calle,
                numero_exterior, numero_interior, colonia, localidad, municipio,
                ciudad, estado, codigo_postal, pais, entre_calles, referencia, enlace_maps
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $tipoDireccion = 'fiscal';
        $esPrincipal = 1;
        $consultaDireccion->bind_param(
            'ississsssssssssss',
            $empresaId,
            $tipoDireccion,
            $camposDireccion[0],
            $esPrincipal,
            $camposDireccion[1],
            $camposDireccion[2],
            $camposDireccion[3],
            $camposDireccion[4],
            $camposDireccion[5],
            $camposDireccion[6],
            $camposDireccion[7],
            $camposDireccion[8],
            $camposDireccion[9],
            $camposDireccion[10],
            $camposDireccion[11],
            $camposDireccion[12],
            $camposDireccion[13]
        );
        $consultaDireccion->execute();
        $consultaDireccion->close();
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);
    if ($esEdicion) {
        redirigirVistaEmpresa($empresaId, 'empresa_actualizada=1');
    }
    redirigirFormulario('guardado=1');
} catch (Throwable $error) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    if ($esEdicion) {
        redirigirFormulario('id=' . $empresaId . '&error=No%20fue%20posible%20actualizar%20la%20empresa');
    }
    redirigirFormulario('error=No%20fue%20posible%20guardar%20la%20empresa');
}
