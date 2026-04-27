<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/openai_client.php';
require_once __DIR__ . '/../includes/empresa_ai_payload.php';
require_once __DIR__ . '/../includes/empresa_ai_schema.php';
require_once __DIR__ . '/../includes/empresa_ai_prompt.php';
require_once __DIR__ . '/../includes/empresa_ai_normalizadores.php';

function responderJson(array $data, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function detectarTipoEntrada(string $extension, string $mime = ''): string
{
    $extension = strtolower($extension);

    if ($extension === 'pdf') {
        return 'pdf';
    }
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return 'imagen';
    }
    if ($extension === 'txt') {
        return 'texto';
    }
    if ($extension === 'xml') {
        return 'xml';
    }
    if ($extension === 'json') {
        return 'json';
    }
    if (in_array($extension, ['doc', 'docx', 'rtf'], true)) {
        return 'documento';
    }
    if ($extension === 'csv') {
        return 'csv';
    }
    if (str_starts_with($mime, 'image/')) {
        return 'imagen';
    }
    return 'desconocido';
}

function limpiarWarnings(array $warnings): array
{
    $salida = [];
    foreach ($warnings as $warning) {
        $texto = trim((string) $warning);
        if ($texto !== '') {
            $salida[] = $texto;
        }
    }
    return array_values(array_unique($salida));
}

function sanitizarMensajeErrorControlado(string $mensaje): string
{
    $texto = trim($mensaje);
    if ($texto === '') {
        return 'Mensaje controlado del error';
    }

    $texto = preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i', 'Bearer ***', $texto);
    $texto = preg_replace('/sk-[A-Za-z0-9\-_]+/i', 'sk-***', $texto);
    $texto = preg_replace('/api[_-]?key[^,;:\n]*/i', 'api_key ***', $texto);

    if (strlen($texto) > 240) {
        $texto = substr($texto, 0, 240) . '...';
    }

    return $texto;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responderJson([
            'ok' => false,
            'message' => 'Metodo no permitido',
        ], 405);
    }

    $extensionesPermitidas = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'txt',
        'xml',
        'json',
        'doc',
        'docx',
        'rtf',
        'csv',
    ];

    $mimesPermitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'text/plain',
        'text/xml',
        'application/xml',
        'application/json',
        'text/csv',
        'application/csv',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/rtf',
        'text/rtf',
    ];

    $extensionesOctetStreamPermitidas = ['doc', 'docx', 'rtf', 'csv'];
    $tamanioMaximoBytes = 5 * 1024 * 1024;
    $warnings = [];

    $textoUsuario = isset($_POST['texto_empresa_ai']) ? trim((string) $_POST['texto_empresa_ai']) : '';
    $tieneTexto = $textoUsuario !== '';
    $longitudTexto = function_exists('mb_strlen') ? mb_strlen($textoUsuario) : strlen($textoUsuario);

    $archivo = $_FILES['archivo_empresa_ai'] ?? null;
    $hayInputArchivo = is_array($archivo) && isset($archivo['error']) && ((int) $archivo['error'] !== UPLOAD_ERR_NO_FILE);

    if (!$hayInputArchivo && !$tieneTexto) {
        responderJson([
            'ok' => false,
            'message' => 'Carga un archivo o pega texto para detectar datos de empresa.',
        ], 422);
    }

    $archivoValido = null;

    if ($hayInputArchivo) {
        $errorArchivo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorArchivo !== UPLOAD_ERR_OK) {
            responderJson([
                'ok' => false,
                'message' => 'No se pudo recibir el archivo.',
            ], 422);
        }

        $nombreArchivo = basename((string) ($archivo['name'] ?? ''));
        $tamanioArchivo = (int) ($archivo['size'] ?? 0);
        $rutaTemporal = (string) ($archivo['tmp_name'] ?? '');

        if ($nombreArchivo === '' || $rutaTemporal === '') {
            responderJson([
                'ok' => false,
                'message' => 'No se pudo recibir el archivo.',
            ], 422);
        }

        if ($tamanioArchivo <= 0) {
            responderJson([
                'ok' => false,
                'message' => 'El archivo recibido no es valido.',
            ], 422);
        }

        if ($tamanioArchivo > $tamanioMaximoBytes) {
            responderJson([
                'ok' => false,
                'message' => 'El archivo supera el tamano maximo permitido de 5 MB.',
            ], 422);
        }

        $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionesPermitidas, true)) {
            responderJson([
                'ok' => false,
                'message' => 'Archivo no permitido.',
            ], 422);
        }

        if (!is_uploaded_file($rutaTemporal)) {
            responderJson([
                'ok' => false,
                'message' => 'No se pudo validar el archivo recibido.',
            ], 422);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            responderJson([
                'ok' => false,
                'message' => 'No se pudo validar el tipo de archivo.',
            ], 500);
        }

        $mime = (string) finfo_file($finfo, $rutaTemporal);
        finfo_close($finfo);
        $mime = strtolower(trim($mime));

        if ($mime === '') {
            responderJson([
                'ok' => false,
                'message' => 'No se pudo validar el tipo de archivo.',
            ], 422);
        }

        if (!in_array($mime, $mimesPermitidos, true)) {
            $permitirOctetStream = $mime === 'application/octet-stream'
                && in_array($extension, $extensionesOctetStreamPermitidas, true);

            if ($permitirOctetStream) {
                $warnings[] = 'Se detecto MIME generico (application/octet-stream) para un documento permitido.';
            } else {
                responderJson([
                    'ok' => false,
                    'message' => 'El tipo MIME del archivo no esta permitido.',
                ], 422);
            }
        }

        $archivoValido = [
            'tmp_name' => $rutaTemporal,
            'nombre_archivo' => $nombreArchivo,
            'extension' => $extension,
            'mime' => $mime,
            'tipo_detectado' => detectarTipoEntrada($extension, $mime),
            'tamanio_bytes' => $tamanioArchivo,
        ];
    }

    if ($tieneTexto && $longitudTexto < 10) {
        if ($archivoValido === null) {
            responderJson([
                'ok' => false,
                'message' => 'El texto ingresado es demasiado corto para detectar datos de empresa.',
            ], 422);
        }

        $warnings[] = 'El texto recibido fue ignorado por tener menos de 10 caracteres.';
        $tieneTexto = false;
        $textoUsuario = '';
    }

    if ($archivoValido === null && !$tieneTexto) {
        responderJson([
            'ok' => false,
            'message' => 'Carga un archivo o pega texto para detectar datos de empresa.',
        ], 422);
    }

    $entrada = [
        'tiene_archivo' => $archivoValido !== null,
        'tiene_texto' => $tieneTexto,
    ];

    if ($archivoValido !== null) {
        $entrada['nombre_archivo'] = $archivoValido['nombre_archivo'];
        $entrada['extension'] = $archivoValido['extension'];
        $entrada['mime'] = $archivoValido['mime'];
        $entrada['tipo_detectado'] = $archivoValido['tipo_detectado'];
        $entrada['tamanio_bytes'] = $archivoValido['tamanio_bytes'];
    } else {
        $entrada['tipo_detectado'] = 'texto';
    }

    if ($tieneTexto) {
        $entrada['longitud_texto'] = function_exists('mb_strlen') ? mb_strlen($textoUsuario) : strlen($textoUsuario);
    }

    $configOpenAI = cargarConfigOpenAI();
    $validacionOpenAI = validarConfigOpenAI($configOpenAI);
    if (!$validacionOpenAI['ok']) {
        responderJson([
            'ok' => false,
            'fase' => 'config_openai_pendiente',
            'message' => 'OpenAI no esta configurado. Configura la API key en el servidor para continuar.',
            'warnings' => limpiarWarnings($warnings),
        ], 200);
    }

    $fileId = '';
    $archivoTemporalEliminado = $archivoValido === null;
    $seIntentoResponses = false;
    $responseId = '';

    try {
        $archivoOpenAIParaPayload = null;

        if ($archivoValido !== null) {
            $resultadoSubida = subirArchivoOpenAI($archivoValido, $configOpenAI);
            $fileId = (string) ($resultadoSubida['file_id'] ?? '');
            if ($fileId === '') {
                throw new RuntimeException('No se pudo subir el archivo a OpenAI para procesamiento.');
            }

            $archivoOpenAIParaPayload = [
                'file_id' => $fileId,
                'nombre_archivo' => $archivoValido['nombre_archivo'],
                'tipo_detectado' => $archivoValido['tipo_detectado'],
            ];
        }

        $contenidoOpenAI = prepararContenidoOpenAIDesdeFileId($archivoOpenAIParaPayload, $textoUsuario);
        $formatoRespuesta = obtenerFormatoRespuestaEmpresaAI();
        $instruccionesSistema = obtenerInstruccionesSistemaEmpresaAI();

        $seIntentoResponses = true;
        $resultadoRespuesta = llamarResponsesEmpresaAI(
            $contenidoOpenAI,
            $configOpenAI,
            $formatoRespuesta,
            $instruccionesSistema
        );

        $responseId = (string) ($resultadoRespuesta['raw_response_id'] ?? '');
        $datosOpenAI = is_array($resultadoRespuesta['datos'] ?? null) ? $resultadoRespuesta['datos'] : [];

        $validacionEstructura = validarEstructuraEmpresaAI($datosOpenAI);
        if (!$validacionEstructura['ok']) {
            $warnings = array_merge($warnings, $validacionEstructura['warnings']);
        }

        $preparacionFrontend = prepararDatosEmpresaAIParaFrontend($datosOpenAI);
        $warnings = array_merge($warnings, $preparacionFrontend['warnings'] ?? []);

        if ($fileId !== '') {
            $resultadoEliminacion = eliminarArchivoOpenAI($fileId, $configOpenAI);
            if (!empty($resultadoEliminacion['ok'])) {
                $archivoTemporalEliminado = true;
            } else {
                $archivoTemporalEliminado = false;
                $warnings[] = 'No se pudo confirmar la eliminacion del archivo temporal en OpenAI.';
            }
        }

        $warnings = limpiarWarnings($warnings);
        $datosNormalizados = is_array($preparacionFrontend['datos'] ?? null) ? $preparacionFrontend['datos'] : [];

        if (!empty($preparacionFrontend['ok'])) {
            $respuestaExitosa = [
                'ok' => true,
                'fase' => 'datos_empresa_extraidos',
                'message' => 'Datos detectados correctamente. Revisa la informacion antes de guardar.',
                'datos' => $datosNormalizados,
                'warnings' => $warnings,
                'openai' => [
                    'response_id' => $responseId,
                    'archivo_temporal_eliminado' => $archivoTemporalEliminado,
                ],
            ];

            responderJson($respuestaExitosa, 200);
        }

        $respuestaParcial = [
            'ok' => false,
            'fase' => 'datos_empresa_parciales',
            'message' => 'Se detectaron datos parciales. Revisa la informacion antes de guardar.',
            'datos' => $datosNormalizados,
            'warnings' => $warnings,
            'openai' => [
                'response_id' => $responseId,
                'archivo_temporal_eliminado' => $archivoTemporalEliminado,
            ],
        ];

        responderJson($respuestaParcial, 200);
    } catch (InvalidArgumentException $e) {
        if ($fileId !== '') {
            $resultadoEliminacion = eliminarArchivoOpenAI($fileId, $configOpenAI);
            if (!empty($resultadoEliminacion['ok'])) {
                $archivoTemporalEliminado = true;
            } else {
                $archivoTemporalEliminado = false;
                $warnings[] = 'No se pudo confirmar la eliminacion del archivo temporal en OpenAI.';
            }
        }

        responderJson([
            'ok' => false,
            'fase' => 'error_json_openai',
            'message' => 'OpenAI no devolvio un JSON valido.',
            'warnings' => limpiarWarnings(array_merge($warnings, [sanitizarMensajeErrorControlado($e->getMessage())])),
            'openai' => [
                'archivo_temporal_eliminado' => $archivoTemporalEliminado,
            ],
        ], 200);
    } catch (Throwable $e) {
        if ($fileId !== '') {
            $resultadoEliminacion = eliminarArchivoOpenAI($fileId, $configOpenAI);
            if (!empty($resultadoEliminacion['ok'])) {
                $archivoTemporalEliminado = true;
            } else {
                $archivoTemporalEliminado = false;
                $warnings[] = 'No se pudo confirmar la eliminacion del archivo temporal en OpenAI.';
            }
        }

        $faseError = $seIntentoResponses ? 'error_openai_responses' : 'error_openai_file_upload';
        $messageError = $seIntentoResponses
            ? 'No se pudo extraer informacion con OpenAI.'
            : 'No se pudo subir el archivo a OpenAI para procesamiento.';

        responderJson([
            'ok' => false,
            'fase' => $faseError,
            'message' => $messageError,
            'warnings' => limpiarWarnings(array_merge($warnings, [sanitizarMensajeErrorControlado($e->getMessage())])),
            'openai' => [
                'archivo_temporal_eliminado' => $archivoTemporalEliminado,
            ],
        ], 200);
    }
} catch (Throwable $e) {
    responderJson([
        'ok' => false,
        'message' => 'Ocurrio un error al procesar la entrada.',
    ], 500);
}
