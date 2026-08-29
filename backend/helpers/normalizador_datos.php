<?php

/**
 * Reduce espacios consecutivos y convierte valores vacios en null.
 */
function limpiarEspacios($valor): ?string
{
    $texto = preg_replace('/\s+/u', ' ', trim((string) $valor));

    return $texto === '' ? null : $texto;
}

/**
 * Normaliza texto libre sin alterar mayusculas, acentos ni signos del usuario.
 */
function normalizarTextoGeneral($valor): ?string
{
    return limpiarEspacios($valor);
}

/**
 * Convierte la primera letra de cada palabra a mayuscula conservando acentos.
 */
function normalizarTextoTitulo($valor): ?string
{
    $texto = limpiarEspacios($valor);
    if ($texto === null) {
        return null;
    }

    $texto = function_exists('mb_strtolower')
        ? mb_strtolower($texto, 'UTF-8')
        : strtolower($texto);
    $conectores = ['a', 'al', 'de', 'del', 'el', 'en', 'la', 'las', 'los', 'y'];
    $partes = preg_split('/(\s+)/u', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
    $primeraPalabra = true;

    foreach ($partes as &$parte) {
        if (preg_match('/\S/u', $parte) !== 1) {
            continue;
        }

        $esConector = in_array($parte, $conectores, true);
        if (!$primeraPalabra && $esConector) {
            continue;
        }

        $parte = preg_replace_callback('/(^|[-\/&])(\p{L})/u', static function (array $coincidencia): string {
            $letra = function_exists('mb_strtoupper')
                ? mb_strtoupper($coincidencia[2], 'UTF-8')
                : strtoupper($coincidencia[2]);

            return $coincidencia[1] . $letra;
        }, $parte);
        $primeraPalabra = false;
    }
    unset($parte);

    return implode('', $partes);
}

/**
 * Normaliza nombres de empresa y conserva abreviaturas societarias comunes.
 */
function normalizarRazonSocial($valor): ?string
{
    $texto = normalizarTextoTitulo($valor);
    if ($texto === null) {
        return null;
    }

    $abreviaturas = [
        '/\bS\.?\s*A\.?\s+DE\s+C\.?\s*V\.?(?=\s|$)/ui' => 'S.A. de C.V.',
        '/\bS\.?\s+DE\s+R\.?\s*L\.?\s+DE\s+C\.?\s*V\.?(?=\s|$)/ui' => 'S. de R.L. de C.V.',
        '/\bS\.?\s*A\.?\s*P\.?\s*I\.?(?=\s|$)/ui' => 'S.A.P.I.',
        '/\bS\.?\s*A\.?(?=\s|$)/ui' => 'S.A.',
        '/\bC\.?\s*V\.?(?=\s|$)/ui' => 'C.V.'
    ];

    return preg_replace(array_keys($abreviaturas), array_values($abreviaturas), $texto);
}

/**
 * Normaliza nombres de personas sin inventar acentos que no vengan capturados.
 */
function normalizarNombrePersona($valor): ?string
{
    return normalizarTextoTitulo($valor);
}

/**
 * Normaliza texto de direcciones para una lectura consistente.
 */
function normalizarDireccionTexto($valor): ?string
{
    return normalizarTextoTitulo($valor);
}

/**
 * Quita espacios y convierte un RFC a mayusculas.
 */
function normalizarRFC($valor): ?string
{
    $texto = limpiarEspacios($valor);
    if ($texto === null) {
        return null;
    }

    $texto = preg_replace('/\s+/u', '', $texto);

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($texto, 'UTF-8')
        : strtoupper($texto);
}

/**
 * Normaliza un correo sin validar ni alterar su estructura.
 */
function normalizarCorreo($valor): ?string
{
    $texto = limpiarEspacios($valor);

    return $texto === null
        ? null
        : (function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto));
}

/**
 * Convierte un telefono mexicano a diez digitos con formato ### ### ####.
 */
function normalizarTelefonoMX($valor): ?string
{
    $texto = limpiarEspacios($valor);
    if ($texto === null) {
        return null;
    }

    $digitos = preg_replace('/\D+/u', '', $texto);
    if (strlen($digitos) === 12 && strncmp($digitos, '52', 2) === 0) {
        $digitos = substr($digitos, 2);
    }

    if (strlen($digitos) !== 10) {
        return null;
    }

    return substr($digitos, 0, 3) . ' ' . substr($digitos, 3, 3) . ' ' . substr($digitos, 6, 4);
}
