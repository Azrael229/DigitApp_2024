<?php
declare(strict_types=1);

function cargarConfigOpenAI(): array
{
    //$configPath = __DIR__ . '/../config/openai_config.php';
    $configPath = dirname(__DIR__, 2) . '/config_privado/openai_config.php';

    $config = [
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model_empresa' => getenv('OPENAI_MODEL_EMPRESA') ?: 'gpt-4.1-mini',
        'timeout_seconds' => 60,
    ];

    if (is_file($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            $config = array_merge($config, $loaded);
        }
    }

    $config['api_key'] = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
    $config['model_empresa'] = isset($config['model_empresa']) && trim((string) $config['model_empresa']) !== ''
        ? trim((string) $config['model_empresa'])
        : 'gpt-4.1-mini';

    $timeout = $config['timeout_seconds'] ?? 60;
    $config['timeout_seconds'] = is_numeric($timeout) ? (int) $timeout : 60;

    return $config;
}

function validarConfigOpenAI(array $config): array
{
    $apiKey = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
    if ($apiKey === '') {
        return [
            'ok' => false,
            'message' => 'API key de OpenAI no configurada.',
        ];
    }

    $modelo = isset($config['model_empresa']) ? trim((string) $config['model_empresa']) : '';
    if ($modelo === '') {
        return [
            'ok' => false,
            'message' => 'Modelo de OpenAI no configurado.',
        ];
    }

    if (!isset($config['timeout_seconds']) || !is_numeric($config['timeout_seconds'])) {
        return [
            'ok' => false,
            'message' => 'Timeout de OpenAI no valido.',
        ];
    }

    return [
        'ok' => true,
        'message' => 'Configuracion de OpenAI lista.',
    ];
}

function obtenerHeadersOpenAI(string $apiKey): array
{
    return [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];
}

function obtenerModeloEmpresaOpenAI(array $config): string
{
    $modelo = isset($config['model_empresa']) ? trim((string) $config['model_empresa']) : '';
    return $modelo !== '' ? $modelo : 'gpt-4.1-mini';
}

function obtenerMensajeErrorOpenAI(string $responseBody, int $httpCode): string
{
    $mensaje = 'Error de OpenAI (HTTP ' . $httpCode . ').';

    if ($responseBody !== '') {
        $decoded = json_decode($responseBody, true);
        if (is_array($decoded) && isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
            $mensaje = trim($decoded['error']['message']);
        }
    }

    if ($mensaje === '') {
        $mensaje = 'No se pudo completar la operacion con OpenAI.';
    }

    return $mensaje;
}

function subirArchivoOpenAI(array $archivoValidado, array $config): array
{
    $tmpName = isset($archivoValidado['tmp_name']) ? (string) $archivoValidado['tmp_name'] : '';
    $nombreArchivo = isset($archivoValidado['nombre_archivo']) ? (string) $archivoValidado['nombre_archivo'] : '';
    $mime = isset($archivoValidado['mime']) ? (string) $archivoValidado['mime'] : '';
    $apiKey = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
    $timeout = isset($config['timeout_seconds']) && is_numeric($config['timeout_seconds'])
        ? (int) $config['timeout_seconds']
        : 60;

    if ($apiKey === '' || $tmpName === '' || $nombreArchivo === '' || $mime === '') {
        throw new RuntimeException('No se pudo subir el archivo a OpenAI para procesamiento.');
    }

    if (!is_file($tmpName) || !function_exists('curl_init')) {
        throw new RuntimeException('No se pudo subir el archivo a OpenAI para procesamiento.');
    }

    $curlFile = new CURLFile($tmpName, $mime, $nombreArchivo);

    $ch = curl_init('https://api.openai.com/v1/files');
    if ($ch === false) {
        throw new RuntimeException('No se pudo subir el archivo a OpenAI para procesamiento.');
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'purpose' => 'user_data',
        'file' => $curlFile,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    curl_close($ch);

    if ($curlErrNo !== 0 || !is_string($responseBody)) {
        throw new RuntimeException('No se pudo subir el archivo a OpenAI para procesamiento.');
    }

    $decoded = json_decode($responseBody, true);
    $fileId = is_array($decoded) && isset($decoded['id']) && is_string($decoded['id'])
        ? trim($decoded['id'])
        : '';

    if ($httpCode < 200 || $httpCode >= 300 || $fileId === '') {
        $mensaje = obtenerMensajeErrorOpenAI($responseBody, $httpCode);
        throw new RuntimeException($mensaje !== '' ? $mensaje : 'No se pudo subir el archivo a OpenAI para procesamiento.');
    }

    return [
        'ok' => true,
        'file_id' => $fileId,
        'filename' => $nombreArchivo,
    ];
}

function eliminarArchivoOpenAI(string $fileId, array $config): array
{
    $fileId = trim($fileId);
    $apiKey = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
    $timeout = isset($config['timeout_seconds']) && is_numeric($config['timeout_seconds'])
        ? (int) $config['timeout_seconds']
        : 60;

    if ($fileId === '' || $apiKey === '' || !function_exists('curl_init')) {
        return [
            'ok' => false,
            'message' => 'No se pudo eliminar el archivo temporal de OpenAI.',
        ];
    }

    $ch = curl_init('https://api.openai.com/v1/files/' . rawurlencode($fileId));
    if ($ch === false) {
        return [
            'ok' => false,
            'message' => 'No se pudo eliminar el archivo temporal de OpenAI.',
        ];
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    curl_close($ch);

    if ($curlErrNo !== 0 || !is_string($responseBody)) {
        return [
            'ok' => false,
            'message' => 'No se pudo eliminar el archivo temporal de OpenAI.',
        ];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'ok' => true,
        ];
    }

    return [
        'ok' => false,
        'message' => 'No se pudo eliminar el archivo temporal de OpenAI.',
    ];
}

function extraerTextoRespuestaOpenAI(array $respuesta): string
{
    if (isset($respuesta['output_text']) && is_string($respuesta['output_text'])) {
        $texto = trim($respuesta['output_text']);
        if ($texto !== '') {
            return $texto;
        }
    }

    $partes = [];
    if (isset($respuesta['output']) && is_array($respuesta['output'])) {
        foreach ($respuesta['output'] as $item) {
            if (!is_array($item) || !isset($item['content']) || !is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $contenido) {
                if (!is_array($contenido)) {
                    continue;
                }

                if (isset($contenido['type'], $contenido['text']) && $contenido['type'] === 'output_text' && is_string($contenido['text'])) {
                    $texto = trim($contenido['text']);
                    if ($texto !== '') {
                        $partes[] = $texto;
                    }
                    continue;
                }

                if (isset($contenido['text']) && is_string($contenido['text'])) {
                    $texto = trim($contenido['text']);
                    if ($texto !== '') {
                        $partes[] = $texto;
                    }
                }
            }
        }
    }

    $salida = trim(implode("\n", $partes));
    if ($salida === '') {
        throw new RuntimeException('No se encontró texto de salida en la respuesta de OpenAI.');
    }

    return $salida;
}

function llamarResponsesEmpresaAI(array $contenidoOpenAI, array $config, array $formatoRespuesta, string $instruccionesSistema): array
{
    $apiKey = isset($config['api_key']) ? trim((string) $config['api_key']) : '';
    $modelo = obtenerModeloEmpresaOpenAI($config);
    $timeout = isset($config['timeout_seconds']) && is_numeric($config['timeout_seconds'])
        ? (int) $config['timeout_seconds']
        : 60;

    if ($apiKey === '' || $modelo === '') {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    $format = isset($formatoRespuesta['format']) && is_array($formatoRespuesta['format'])
        ? $formatoRespuesta['format']
        : [];

    $payload = [
        'model' => $modelo,
        'instructions' => $instruccionesSistema,
        'input' => [
            [
                'role' => 'user',
                'content' => $contenidoOpenAI,
            ],
        ],
        'text' => [
            'format' => $format,
        ],
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (!is_string($jsonPayload)) {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    if ($ch === false) {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, max(1, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

    $responseBody = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    curl_close($ch);

    if ($curlErrNo !== 0 || !is_string($responseBody)) {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    $respuesta = json_decode($responseBody, true);
    if (!is_array($respuesta)) {
        throw new RuntimeException('No se pudo extraer información con OpenAI.');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $mensaje = obtenerMensajeErrorOpenAI($responseBody, $httpCode);
        throw new RuntimeException($mensaje !== '' ? $mensaje : 'No se pudo extraer información con OpenAI.');
    }

    $textoSalida = extraerTextoRespuestaOpenAI($respuesta);
    $datos = json_decode($textoSalida, true);
    if (!is_array($datos)) {
        throw new InvalidArgumentException('OpenAI no devolvió un JSON válido.');
    }

    return [
        'ok' => true,
        'raw_response_id' => isset($respuesta['id']) && is_string($respuesta['id']) ? $respuesta['id'] : '',
        'datos' => $datos,
        'usage' => $respuesta['usage'] ?? null,
    ];
}
