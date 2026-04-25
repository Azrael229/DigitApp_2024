<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function responderJson(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson([
            'ok' => false,
            'message' => 'Método no permitido',
        ], 405);
    }

    // Validar archivo recibido
    if (!isset($_FILES['constancia_pdf'])) {
        responderJson([
            'ok' => false,
            'message' => 'No se recibió el archivo PDF.',
        ], 400);
    }

    $archivo = $_FILES['constancia_pdf'];

    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        responderJson([
            'ok' => false,
            'message' => 'Error al recibir el archivo PDF.',
        ], 400);
    }

    if (!isset($archivo['size']) || $archivo['size'] > 5 * 1024 * 1024) {
        responderJson([
            'ok' => false,
            'message' => 'El archivo excede el tamaño máximo permitido de 5 MB.',
        ], 400);
    }

    $nombreArchivo = isset($archivo['name']) ? (string) $archivo['name'] : '';
    $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        responderJson([
            'ok' => false,
            'message' => 'El archivo debe tener extensión .pdf',
        ], 400);
    }

    $rutaTemporalPdf = isset($archivo['tmp_name']) ? (string) $archivo['tmp_name'] : '';

    if ($rutaTemporalPdf === '' || !is_uploaded_file($rutaTemporalPdf)) {
        responderJson([
            'ok' => false,
            'message' => 'No se recibió un archivo temporal válido.',
        ], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $rutaTemporalPdf) : '';

    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mime !== 'application/pdf') {
        responderJson([
            'ok' => false,
            'message' => 'El archivo recibido no es un PDF válido.',
        ], 400);
    }

    responderJson([
        'ok' => false,
        'message' => 'La lectura directa por parser PDF fue deshabilitada. El procesamiento de constancia fiscal se implementará con el nuevo método.',
        'fase' => 'pendiente_nuevo_metodo',
    ], 200);
} catch (Throwable $error) {
    responderJson([
        'ok' => false,
        'message' => 'Ocurrió un error al validar la constancia fiscal.',
    ], 500);
}
