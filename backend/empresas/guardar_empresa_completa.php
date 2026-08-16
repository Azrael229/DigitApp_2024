<?php

require(__DIR__ . "/../../config/conexion.php");

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirFormulario('error=Solicitud%20no%20valida');
}

$empresa = valorPost('empresa');
$rol = valorPost('rol');
$estatus = valorPost('estatus');

if ($empresa === null || $rol === null || $estatus === null) {
    redirigirFormulario('error=Empresa%2C%20rol%20y%20estatus%20son%20obligatorios');
}

$camposEmpresa = [
    $empresa,
    valorPost('razon_social'),
    valorPost('rfc'),
    $rol,
    valorPost('actividad_economica'),
    valorPost('regimen_fiscal_codigo'),
    valorPost('regimen_fiscal_descripcion'),
    valorPost('regimen_capital'),
    valorPost('tipo_persona'),
    valorPost('giro_mercantil'),
    valorPost('mercado'),
    valorPost('telefono_principal'),
    valorPost('email_principal'),
    valorPost('pagina_web'),
    $estatus,
    valorPost('origen_registro') ?? 'manual',
    valorPost('observaciones'),
];

$camposDireccion = [
    valorPost('alias') ?? 'Fiscal',
    valorPost('calle'),
    valorPost('numero_exterior'),
    valorPost('numero_interior'),
    valorPost('colonia'),
    valorPost('localidad'),
    valorPost('municipio'),
    valorPost('ciudad'),
    valorPost('estado'),
    valorPost('codigo_postal'),
    valorPost('pais') ?? 'Mexico',
    valorPost('entre_calles'),
    valorPost('referencia'),
    valorPost('enlace_maps'),
];

mysqli_begin_transaction($conexion);

try {
    $consultaEmpresa = $conexion->prepare(
        'INSERT INTO empresas (
            empresa, razon_social, rfc, rol, actividad_economica,
            regimen_fiscal_codigo, regimen_fiscal_descripcion, regimen_capital,
            tipo_persona, giro_mercantil, mercado, telefono_principal,
            email_principal, pagina_web, estatus, origen_registro, observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $consultaEmpresa->bind_param(
        'sssssssssssssssss',
        ...$camposEmpresa
    );
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

    mysqli_commit($conexion);
    mysqli_close($conexion);
    redirigirFormulario('guardado=1');
} catch (Throwable $error) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    redirigirFormulario('error=No%20fue%20posible%20guardar%20la%20empresa');
}
