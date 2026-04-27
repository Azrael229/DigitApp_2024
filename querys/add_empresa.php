<?php

require_once("conexion.php");
require_once("add_empresa_direcciones.php");

// Validar metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no permitido");
}

// Validar conexion
if (!isset($conexion) || !$conexion) {
    die("Error de conexion a la base de datos.");
}

// Recibir datos de empresa
$empresa = trim($_POST["empresa"] ?? ($_POST["nombre_comercial"] ?? ""));
$razonSocial = trim($_POST["razon_social"] ?? "");
$rfc = strtoupper(trim($_POST["rfc"] ?? ""));
$rol = trim($_POST["rol"] ?? "");
$regimenFiscalCodigo = trim($_POST["regimen_fiscal_codigo"] ?? ($_POST["regimen_fiscal"] ?? ""));
$regimenFiscalDescripcion = trim($_POST["regimen_fiscal_descripcion"] ?? "");
$regimenCapital = trim($_POST["regimen_capital"] ?? "");
$tipoPersona = trim($_POST["tipo_persona"] ?? "");
$giroMercantil = trim($_POST["giro_mercantil"] ?? "");
$mercado = trim($_POST["mercado"] ?? "");
$telefonoPrincipal = trim($_POST["telefono_principal"] ?? "");
$emailPrincipal = trim($_POST["email_principal"] ?? "");
$paginaWeb = trim($_POST["pagina_web"] ?? ($_POST["sitio_web"] ?? ""));
$estatus = trim($_POST["estatus"] ?? "");
$origenRegistro = trim($_POST["origen_registro"] ?? "");
$observaciones = trim($_POST["observaciones"] ?? "");

// Validar obligatorios
if ($empresa === "" || $rol === "") {
    die("Faltan datos obligatorios: empresa y rol.");
}

// Recibir datos de direccion fiscal
$datosDireccionFiscal = [
    "tipo_direccion" => "fiscal",
    "alias" => trim($_POST["alias"] ?? "Fiscal"),
    "calle" => trim($_POST["calle"] ?? ""),
    "numero_exterior" => trim($_POST["numero_exterior"] ?? ""),
    "numero_interior" => trim($_POST["numero_interior"] ?? ""),
    "colonia" => trim($_POST["colonia"] ?? ""),
    "localidad" => trim($_POST["localidad"] ?? ""),
    "municipio" => trim($_POST["municipio"] ?? ""),
    "ciudad" => trim($_POST["ciudad"] ?? ""),
    "estado" => trim($_POST["estado"] ?? ""),
    "codigo_postal" => trim($_POST["codigo_postal"] ?? ""),
    "pais" => trim($_POST["pais"] ?? "Mexico"),
    "enlace_maps" => trim($_POST["enlace_maps"] ?? ""),
    "referencia" => trim($_POST["referencia"] ?? ""),
    "entre_calles" => trim($_POST["entre_calles"] ?? ""),
    "es_principal" => 1,
    "activa" => 1
];

// Insertar empresa
$sqlEmpresa = "INSERT INTO empresas (
    empresa,
    razon_social,
    rfc,
    rol,
    regimen_fiscal_codigo,
    regimen_fiscal_descripcion,
    regimen_capital,
    tipo_persona,
    giro_mercantil,
    mercado,
    telefono_principal,
    email_principal,
    pagina_web,
    estatus,
    origen_registro,
    observaciones,
    created_at,
    updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

$stmtEmpresa = mysqli_prepare($conexion, $sqlEmpresa);

if (!$stmtEmpresa) {
    die("Error al preparar la consulta de empresa: " . mysqli_error($conexion));
}

mysqli_stmt_bind_param(
    $stmtEmpresa,
    "ssssssssssssssss",
    $empresa,
    $razonSocial,
    $rfc,
    $rol,
    $regimenFiscalCodigo,
    $regimenFiscalDescripcion,
    $regimenCapital,
    $tipoPersona,
    $giroMercantil,
    $mercado,
    $telefonoPrincipal,
    $emailPrincipal,
    $paginaWeb,
    $estatus,
    $origenRegistro,
    $observaciones
);

$insertEmpresa = mysqli_stmt_execute($stmtEmpresa);

if (!$insertEmpresa) {
    die("Error al guardar la empresa: " . mysqli_error($conexion));
}

mysqli_stmt_close($stmtEmpresa);

// Obtener ID de empresa creada
$idEmpresa = mysqli_insert_id($conexion);

if (!$idEmpresa) {
    die("No fue posible obtener el ID de la empresa creada.");
}

// Insertar direccion fiscal
$direccionGuardada = guardarDireccionFiscalEmpresa($conexion, $idEmpresa, $datosDireccionFiscal);

if (!$direccionGuardada) {
    die("La empresa se guardo, pero ocurrio un error al guardar la direccion fiscal.");
}

// Redireccionar al detalle
header("Location: ../Render_detalle_empresa.php?id_empresa=" . $idEmpresa . "&guardado=1");
exit;
