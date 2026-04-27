<?php

// Guarda la direccion fiscal de una empresa
function guardarDireccionFiscalEmpresa($conexion, $idEmpresa, $datosDireccionFiscal)
{
    if (empty($idEmpresa)) {
        return false;
    }

    // Datos para tabla empresa_direcciones
    $tipoDireccion = "fiscal";
    $alias = $datosDireccionFiscal["alias"] ?? "Fiscal";
    $calle = $datosDireccionFiscal["calle"] ?? "";
    $numeroExterior = $datosDireccionFiscal["numero_exterior"] ?? "";
    $numeroInterior = $datosDireccionFiscal["numero_interior"] ?? "";
    $colonia = $datosDireccionFiscal["colonia"] ?? "";
    $localidad = $datosDireccionFiscal["localidad"] ?? "";
    $municipio = $datosDireccionFiscal["municipio"] ?? "";
    $ciudad = $datosDireccionFiscal["ciudad"] ?? "";
    $estado = $datosDireccionFiscal["estado"] ?? "";
    $codigoPostal = $datosDireccionFiscal["codigo_postal"] ?? "";
    $pais = $datosDireccionFiscal["pais"] ?? "Mexico";
    $enlaceMaps = $datosDireccionFiscal["enlace_maps"] ?? "";
    $referencia = $datosDireccionFiscal["referencia"] ?? "";
    $entreCalles = $datosDireccionFiscal["entre_calles"] ?? "";
    $esPrincipal = 1;
    $activa = 1;

    $sqlDireccion = "INSERT INTO empresa_direcciones (
        empresa_id,
        tipo_direccion,
        alias,
        calle,
        numero_exterior,
        numero_interior,
        colonia,
        localidad,
        municipio,
        ciudad,
        estado,
        codigo_postal,
        pais,
        enlace_maps,
        referencia,
        entre_calles,
        es_principal,
        activa,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmtDireccion = mysqli_prepare($conexion, $sqlDireccion);

    if (!$stmtDireccion) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmtDireccion,
        "isssssssssssssssii",
        $idEmpresa,
        $tipoDireccion,
        $alias,
        $calle,
        $numeroExterior,
        $numeroInterior,
        $colonia,
        $localidad,
        $municipio,
        $ciudad,
        $estado,
        $codigoPostal,
        $pais,
        $enlaceMaps,
        $referencia,
        $entreCalles,
        $esPrincipal,
        $activa
    );

    $direccionGuardada = mysqli_stmt_execute($stmtDireccion);
    mysqli_stmt_close($stmtDireccion);

    return $direccionGuardada;
}
