<?php
declare(strict_types=1);

function limpiarTextoEmpresaAI($valor): string
{
    if (is_string($valor)) {
        $texto = $valor;
    } elseif (is_scalar($valor)) {
        $texto = (string) $valor;
    } elseif (is_object($valor) && method_exists($valor, '__toString')) {
        $texto = (string) $valor;
    } else {
        $texto = '';
    }

    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
    $texto = trim((string) $texto);
    $texto = preg_replace('/\s+/u', ' ', $texto);

    return trim((string) $texto);
}

function normalizarClaveEmpresaAI(string $clave): string
{
    $clave = trim($clave);
    if ($clave === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $clave = mb_strtolower($clave, 'UTF-8');
    } else {
        $clave = strtolower($clave);
    }

    $clave = strtr($clave, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ]);

    $clave = preg_replace('/[^a-z0-9]+/u', '_', $clave);
    $clave = trim((string) $clave, '_');

    return $clave;
}

function obtenerValorPorClaveFlexibleEmpresaAI(array $fuente, string $alias): string
{
    if (array_key_exists($alias, $fuente)) {
        $valor = limpiarTextoEmpresaAI($fuente[$alias]);
        if ($valor !== '') {
            return $valor;
        }
    }

    $aliasNormalizado = normalizarClaveEmpresaAI($alias);
    if ($aliasNormalizado === '') {
        return '';
    }

    foreach ($fuente as $clave => $valor) {
        if (normalizarClaveEmpresaAI((string) $clave) === $aliasNormalizado) {
            $texto = limpiarTextoEmpresaAI($valor);
            if ($texto !== '') {
                return $texto;
            }
        }
    }

    return '';
}

function obtenerValorAliasEmpresaAI(array $principal, array $fallback, array $aliases): string
{
    foreach ($aliases as $alias) {
        $valor = obtenerValorPorClaveFlexibleEmpresaAI($principal, (string) $alias);
        if ($valor !== '') {
            return $valor;
        }
    }

    foreach ($aliases as $alias) {
        $valor = obtenerValorPorClaveFlexibleEmpresaAI($fallback, (string) $alias);
        if ($valor !== '') {
            return $valor;
        }
    }

    return '';
}

function normalizarMayusculasEmpresaAI($valor): string
{
    $texto = limpiarTextoEmpresaAI($valor);
    if ($texto === '') {
        return '';
    }

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($texto, 'UTF-8')
        : strtoupper($texto);
}

function normalizarDomicilioEmpresaAI($valor): string
{
    $texto = limpiarTextoEmpresaAI($valor);
    if ($texto === '') {
        return '';
    }

    $soloLetras = preg_replace('/[^[:alpha:]]/u', '', $texto);
    $estaMayusculas = $soloLetras !== '' && $soloLetras === normalizarMayusculasEmpresaAI($soloLetras);

    if ($estaMayusculas) {
        if (function_exists('mb_convert_case')) {
            $texto = mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        } else {
            $texto = ucwords(strtolower($texto));
        }
    }

    $reemplazos = [
        '/\bAv\b\.?/iu' => 'Av.',
        '/\bBlvd\b\.?/iu' => 'Blvd.',
        '/\bCarr\b\.?/iu' => 'Carr.',
        '/\bNo\b\.?/iu' => 'No.',
        '/\bInt\b\.?/iu' => 'Int.',
        '/\bCol\b\.?/iu' => 'Col.',
    ];

    foreach ($reemplazos as $patron => $salida) {
        $texto = preg_replace($patron, $salida, (string) $texto);
    }

    return limpiarTextoEmpresaAI($texto);
}

function validarRFCEmpresaAI($rfc): bool
{
    $valor = normalizarMayusculasEmpresaAI($rfc);
    $valor = preg_replace('/[\s\-]+/', '', $valor);
    return (bool) preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', (string) $valor);
}

function normalizarRFCEmpresaAI($rfc): string
{
    $valor = normalizarMayusculasEmpresaAI($rfc);
    $valor = preg_replace('/[\s\-]+/', '', $valor);
    return validarRFCEmpresaAI($valor) ? (string) $valor : '';
}

function validarCodigoPostalEmpresaAI($cp): bool
{
    $valor = limpiarTextoEmpresaAI($cp);
    return (bool) preg_match('/^[0-9]{5}$/', $valor);
}

function normalizarCodigoPostalEmpresaAI($cp): string
{
    $valor = limpiarTextoEmpresaAI($cp);
    $digitos = preg_replace('/\D+/', '', $valor);
    return validarCodigoPostalEmpresaAI($digitos) ? (string) $digitos : '';
}

function validarCorreoEmpresaAI($correo): bool
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

function normalizarCorreoEmpresaAI($correo): string
{
    $valor = limpiarTextoEmpresaAI($correo);
    if ($valor === '') {
        return '';
    }

    $valor = function_exists('mb_strtolower')
        ? mb_strtolower($valor, 'UTF-8')
        : strtolower($valor);

    return validarCorreoEmpresaAI($valor) ? $valor : '';
}

function normalizarTelefonoEmpresaAI($telefono): string
{
    $valor = limpiarTextoEmpresaAI($telefono);
    if ($valor === '') {
        return '';
    }

    $valor = preg_replace('/[^0-9\+\-\(\)\s]/', '', $valor);
    $valor = limpiarTextoEmpresaAI($valor);
    $digitos = preg_replace('/\D+/', '', $valor);

    return strlen((string) $digitos) >= 7 ? $valor : '';
}

function normalizarRegimenCapitalEmpresaAI(string $valor): string
{
    $valorLimpio = limpiarTextoEmpresaAI($valor);
    if ($valorLimpio === '') {
        return '';
    }

    $comparacion = normalizarMayusculasEmpresaAI($valorLimpio);
    $comparacion = strtr($comparacion, [
        'Á' => 'A',
        'É' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ú' => 'U',
        'Ü' => 'U',
    ]);
    $comparacion = limpiarTextoEmpresaAI($comparacion);

    $mapa = [
        'SOCIEDAD ANONIMA DE CAPITAL VARIABLE' => 'S.A. de C.V.',
        'SOCIEDAD ANONIMA' => 'S.A.',
        'SOCIEDAD DE RESPONSABILIDAD LIMITADA DE CAPITAL VARIABLE' => 'S. de R.L. de C.V.',
        'SOCIEDAD DE RESPONSABILIDAD LIMITADA' => 'S. de R.L.',
        'SOCIEDAD CIVIL' => 'S.C.',
        'ASOCIACION CIVIL' => 'A.C.',
        'SOCIEDAD POR ACCIONES SIMPLIFICADA' => 'S.A.S.',
        'PERSONA FISICA' => 'Persona física',
    ];

    return $mapa[$comparacion] ?? $valorLimpio;
}

function normalizarDatosEmpresaAI(array $datos): array
{
    $empresaDefaults = [
        'rfc' => '',
        'razon_social' => '',
        'nombre_comercial_sugerido' => '',
        'regimen_fiscal' => '',
        'regimen_capital_detectado' => '',
        'regimen_capital' => '',
        'estatus_fiscal' => '',
        'fecha_inicio_operaciones' => '',
    ];

    $direccionDefaults = [
        'codigo_postal' => '',
        'calle' => '',
        'numero_exterior' => '',
        'numero_interior' => '',
        'colonia' => '',
        'localidad' => '',
        'municipio' => '',
        'estado' => '',
        'pais' => '',
    ];

    $contactoDefaults = [
        'nombre_contacto' => '',
        'telefono' => '',
        'correo' => '',
        'sitio_web' => '',
    ];

    $clasificacionDefaults = [
        'rol' => '',
        'estatus' => '',
        'mercado' => '',
        'giro' => '',
    ];

    $empresaRaw = is_array($datos['empresa'] ?? null) ? $datos['empresa'] : [];
    $direccionRaw = is_array($datos['direccion_fiscal'] ?? null) ? $datos['direccion_fiscal'] : [];
    $contactoRaw = is_array($datos['contacto'] ?? null) ? $datos['contacto'] : [];
    $clasificacionRaw = is_array($datos['clasificacion_sugerida'] ?? null) ? $datos['clasificacion_sugerida'] : [];

    $empresa = array_merge($empresaDefaults, $empresaRaw);
    $direccion = array_merge($direccionDefaults, $direccionRaw);
    $contacto = array_merge($contactoDefaults, $contactoRaw);
    $clasificacion = array_merge($clasificacionDefaults, $clasificacionRaw);

    $empresa['rfc'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['rfc']);
    $empresa['razon_social'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['razon_social', 'razón_social', 'denominacion_social', 'denominación_social', 'denominacion', 'denominación', 'nombre_razon_social']);
    $empresa['nombre_comercial_sugerido'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['nombre_comercial_sugerido', 'nombre_comercial', 'empresa', 'nombre_empresa']);

    $regimenFiscalDetectado = obtenerValorAliasEmpresaAI(
        $empresa,
        $datos,
        ['regimen_fiscal', 'régimen_fiscal', 'regimen_fiscal_descripcion', 'régimen_fiscal_descripción', 'regimen_fiscal_texto']
    );
    $regimenFiscalCodigo = obtenerValorAliasEmpresaAI($empresa, $datos, ['regimen_fiscal_codigo', 'régimen_fiscal_código']);
    if ($regimenFiscalDetectado === '' && $regimenFiscalCodigo !== '') {
        $regimenFiscalDetectado = $regimenFiscalCodigo;
    } elseif ($regimenFiscalDetectado !== '' && $regimenFiscalCodigo !== '' && stripos($regimenFiscalDetectado, $regimenFiscalCodigo) !== 0) {
        $regimenFiscalDetectado = $regimenFiscalCodigo . ' - ' . $regimenFiscalDetectado;
    }
    $empresa['regimen_fiscal'] = $regimenFiscalDetectado;
    $empresa['regimen_capital_detectado'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['regimen_capital_detectado', 'régimen_capital_detectado', 'regimen_capital_texto', 'régimen_capital_texto', 'tipo_sociedad']);
    $empresa['regimen_capital'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['regimen_capital', 'régimen_capital']);
    $empresa['estatus_fiscal'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['estatus_fiscal', 'situacion_fiscal', 'status_fiscal']);
    $empresa['fecha_inicio_operaciones'] = obtenerValorAliasEmpresaAI($empresa, $datos, ['fecha_inicio_operaciones', 'fecha_inicio_de_operaciones', 'inicio_operaciones']);

    $direccion['codigo_postal'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['codigo_postal', 'código_postal', 'cp']);
    $direccion['calle'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['calle', 'direccion', 'domicilio']);
    $direccion['numero_exterior'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['numero_exterior', 'num_exterior', 'no_exterior']);
    $direccion['numero_interior'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['numero_interior', 'num_interior', 'no_interior']);
    $direccion['colonia'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['colonia']);
    $direccion['localidad'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['localidad', 'ciudad']);
    $direccion['municipio'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['municipio', 'delegacion']);
    $direccion['estado'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['estado', 'entidad_federativa', 'entidad_federación']);
    $direccion['pais'] = obtenerValorAliasEmpresaAI($direccion, $datos, ['pais', 'país']);

    $contacto['nombre_contacto'] = obtenerValorAliasEmpresaAI($contacto, $datos, ['nombre_contacto', 'contacto']);
    $contacto['telefono'] = obtenerValorAliasEmpresaAI($contacto, $datos, ['telefono', 'telefono_principal', 'celular']);
    $contacto['correo'] = obtenerValorAliasEmpresaAI($contacto, $datos, ['correo', 'email', 'email_principal']);
    $contacto['sitio_web'] = obtenerValorAliasEmpresaAI($contacto, $datos, ['sitio_web', 'sitio', 'pagina_web', 'página_web', 'web']);

    $clasificacion['rol'] = obtenerValorAliasEmpresaAI($clasificacion, $datos, ['rol']);
    $clasificacion['estatus'] = obtenerValorAliasEmpresaAI($clasificacion, $datos, ['estatus']);
    $clasificacion['mercado'] = obtenerValorAliasEmpresaAI($clasificacion, $datos, ['mercado']);
    $clasificacion['giro'] = obtenerValorAliasEmpresaAI($clasificacion, $datos, ['giro', 'giro_mercantil', 'actividad_economica']);

    $rfcNormalizado = normalizarRFCEmpresaAI($empresa['rfc']);
    $regimenCapitalDetectado = normalizarMayusculasEmpresaAI($empresa['regimen_capital_detectado']);
    $regimenCapitalFuente = $regimenCapitalDetectado !== '' ? $regimenCapitalDetectado : limpiarTextoEmpresaAI($empresa['regimen_capital']);
    $regimenCapitalNormalizado = normalizarRegimenCapitalEmpresaAI($regimenCapitalFuente);

    $cpOriginal = limpiarTextoEmpresaAI($direccion['codigo_postal']);
    $cpNormalizado = normalizarCodigoPostalEmpresaAI($cpOriginal);
    $correoOriginal = limpiarTextoEmpresaAI($contacto['correo']);
    $correoNormalizado = normalizarCorreoEmpresaAI($correoOriginal);
    $telefonoOriginal = limpiarTextoEmpresaAI($contacto['telefono']);
    $telefonoNormalizado = normalizarTelefonoEmpresaAI($telefonoOriginal);

    $advertencias = is_array($datos['advertencias'] ?? null) ? $datos['advertencias'] : [];
    $advertenciasLimpias = [];
    foreach ($advertencias as $advertencia) {
        $texto = limpiarTextoEmpresaAI($advertencia);
        if ($texto !== '') {
            $advertenciasLimpias[] = $texto;
        }
    }

    if ($cpOriginal !== '' && $cpNormalizado === '') {
        $advertenciasLimpias[] = 'El código postal detectado no tiene formato válido.';
    }
    if ($correoOriginal !== '' && $correoNormalizado === '') {
        $advertenciasLimpias[] = 'El correo detectado no tiene formato válido.';
    }
    if ($telefonoOriginal !== '' && $telefonoNormalizado === '') {
        $advertenciasLimpias[] = 'El teléfono detectado no tiene formato válido.';
    }

    $paisNormalizado = normalizarDomicilioEmpresaAI($direccion['pais']);
    if ($paisNormalizado === '' && $rfcNormalizado !== '') {
        $paisNormalizado = 'México';
    }

    $confianza = (int) ($datos['confianza_global'] ?? 0);
    $confianza = max(0, min(100, $confianza));

    $filtrarArrayTexto = static function ($valores): array {
        $salida = [];
        if (!is_array($valores)) {
            return $salida;
        }
        foreach ($valores as $valor) {
            $texto = limpiarTextoEmpresaAI($valor);
            if ($texto !== '') {
                $salida[] = $texto;
            }
        }
        return $salida;
    };

    return [
        'tipo_documento_detectado' => limpiarTextoEmpresaAI($datos['tipo_documento_detectado'] ?? 'desconocido'),
        'confianza_global' => $confianza,
        'empresa' => [
            'rfc' => $rfcNormalizado,
            'razon_social' => normalizarMayusculasEmpresaAI($empresa['razon_social']),
            'nombre_comercial_sugerido' => limpiarTextoEmpresaAI($empresa['nombre_comercial_sugerido']),
            'regimen_fiscal' => normalizarMayusculasEmpresaAI($empresa['regimen_fiscal']),
            'regimen_capital_detectado' => $regimenCapitalDetectado,
            'regimen_capital' => $regimenCapitalNormalizado,
            'estatus_fiscal' => normalizarMayusculasEmpresaAI($empresa['estatus_fiscal']),
            'fecha_inicio_operaciones' => limpiarTextoEmpresaAI($empresa['fecha_inicio_operaciones']),
        ],
        'direccion_fiscal' => [
            'codigo_postal' => $cpNormalizado,
            'calle' => normalizarDomicilioEmpresaAI($direccion['calle']),
            'numero_exterior' => limpiarTextoEmpresaAI($direccion['numero_exterior']),
            'numero_interior' => limpiarTextoEmpresaAI($direccion['numero_interior']),
            'colonia' => normalizarDomicilioEmpresaAI($direccion['colonia']),
            'localidad' => normalizarDomicilioEmpresaAI($direccion['localidad']),
            'municipio' => normalizarDomicilioEmpresaAI($direccion['municipio']),
            'estado' => normalizarDomicilioEmpresaAI($direccion['estado']),
            'pais' => $paisNormalizado,
        ],
        'contacto' => [
            'nombre_contacto' => limpiarTextoEmpresaAI($contacto['nombre_contacto']),
            'telefono' => $telefonoNormalizado,
            'correo' => $correoNormalizado,
            'sitio_web' => limpiarTextoEmpresaAI($contacto['sitio_web']),
        ],
        'clasificacion_sugerida' => [
            'rol' => limpiarTextoEmpresaAI($clasificacion['rol']),
            'estatus' => limpiarTextoEmpresaAI($clasificacion['estatus']),
            'mercado' => limpiarTextoEmpresaAI($clasificacion['mercado']),
            'giro' => limpiarTextoEmpresaAI($clasificacion['giro']),
        ],
        'campos_detectados' => $filtrarArrayTexto($datos['campos_detectados'] ?? []),
        'campos_no_detectados' => $filtrarArrayTexto($datos['campos_no_detectados'] ?? []),
        'advertencias' => $advertenciasLimpias,
    ];
}

function validarDatosNormalizadosEmpresaAI(array $datos): array
{
    $warnings = [];

    $empresa = is_array($datos['empresa'] ?? null) ? $datos['empresa'] : [];
    $direccion = is_array($datos['direccion_fiscal'] ?? null) ? $datos['direccion_fiscal'] : [];
    $contacto = is_array($datos['contacto'] ?? null) ? $datos['contacto'] : [];
    $camposDetectados = is_array($datos['campos_detectados'] ?? null) ? $datos['campos_detectados'] : [];
    $camposDetectadosNormalizados = array_map(static function ($v) {
        return strtolower(limpiarTextoEmpresaAI($v));
    }, $camposDetectados);

    if (($empresa['rfc'] ?? '') === '') {
        $warnings[] = 'No se detectó RFC válido.';
    }

    if (($empresa['razon_social'] ?? '') === '') {
        $warnings[] = 'No se detectó razón social.';
    }

    if (in_array('codigo_postal', $camposDetectadosNormalizados, true) && ($direccion['codigo_postal'] ?? '') === '') {
        $warnings[] = 'El código postal detectado no tiene formato válido.';
    }

    if (in_array('correo', $camposDetectadosNormalizados, true) && ($contacto['correo'] ?? '') === '') {
        $warnings[] = 'El correo detectado no tiene formato válido.';
    }

    if (in_array('telefono', $camposDetectadosNormalizados, true) && ($contacto['telefono'] ?? '') === '') {
        $warnings[] = 'El teléfono detectado no tiene formato válido.';
    }

    $regDetectado = limpiarTextoEmpresaAI($empresa['regimen_capital_detectado'] ?? '');
    $regNormalizado = limpiarTextoEmpresaAI($empresa['regimen_capital'] ?? '');
    if ($regDetectado !== '' && $regNormalizado === $regDetectado) {
        $candidato = normalizarRegimenCapitalEmpresaAI($regDetectado);
        if ($candidato === $regDetectado) {
            $warnings[] = 'El régimen capital no pudo normalizarse automáticamente.';
        }
    }

    $confianza = (int) ($datos['confianza_global'] ?? 0);
    if ($confianza < 60) {
        $warnings[] = 'La confianza global de extracción es baja. Revisa los datos antes de guardar.';
    }

    $ok = !(($empresa['rfc'] ?? '') === '' || ($empresa['razon_social'] ?? '') === '');

    return [
        'ok' => $ok,
        'warnings' => $warnings,
    ];
}

function prepararDatosEmpresaAIParaFrontend(array $datosOpenAI): array
{
    $datosNormalizados = normalizarDatosEmpresaAI($datosOpenAI);
    $validacion = validarDatosNormalizadosEmpresaAI($datosNormalizados);

    $advertencias = is_array($datosNormalizados['advertencias'] ?? null) ? $datosNormalizados['advertencias'] : [];
    $warnings = is_array($validacion['warnings'] ?? null) ? $validacion['warnings'] : [];
    $warningsFinales = array_values(array_unique(array_filter(array_map('limpiarTextoEmpresaAI', array_merge($advertencias, $warnings)))));

    return [
        'ok' => (bool) ($validacion['ok'] ?? false),
        'datos' => $datosNormalizados,
        'warnings' => $warningsFinales,
    ];
}

/*
Pruebas manuales de referencia (no ejecutar en produccion):

normalizarRegimenCapitalEmpresaAI('SOCIEDAD ANONIMA DE CAPITAL VARIABLE'); // S.A. de C.V.
normalizarRegimenCapitalEmpresaAI('SOCIEDAD ANÓNIMA DE CAPITAL VARIABLE'); // S.A. de C.V.
normalizarRegimenCapitalEmpresaAI('SOCIEDAD DE RESPONSABILIDAD LIMITADA DE CAPITAL VARIABLE'); // S. de R.L. de C.V.
normalizarRegimenCapitalEmpresaAI('ASOCIACION CIVIL'); // A.C.
normalizarRegimenCapitalEmpresaAI('PERSONA FISICA'); // Persona física

normalizarRFCEmpresaAI('abc010203xyz'); // ABC010203XYZ
normalizarRFCEmpresaAI('ABC 010203 XYZ'); // ABC010203XYZ

normalizarCodigoPostalEmpresaAI('76100'); // 76100
normalizarCodigoPostalEmpresaAI('CP 76100'); // 76100

normalizarCorreoEmpresaAI('CONTACTO@EMPRESA.COM'); // contacto@empresa.com
*/
