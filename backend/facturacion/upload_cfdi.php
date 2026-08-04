<?php
header('Content-Type: application/json');

$uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'global_xml' . DIRECTORY_SEPARATOR;
$allowedExtensions = ['xml', 'pdf'];

// recepcion del archivo
if (!isset($_FILES['cfdi_file'])) {
    echo json_encode([
        'ok' => false,
        'message' => 'No se recibio ningun archivo.'
    ]);
    exit;
}

$file = $_FILES['cfdi_file'];

if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'ok' => false,
        'message' => 'No se pudo recibir el archivo.'
    ]);
    exit;
}

$originalName = basename((string) $file['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// validacion de extension
if (!in_array($extension, $allowedExtensions, true)) {
    echo json_encode([
        'ok' => false,
        'message' => 'Extension no permitida.'
    ]);
    exit;
}

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

$targetPath = $uploadDirectory . $originalName;

if (file_exists($targetPath)) {
    echo json_encode([
        'ok' => false,
        'message' => 'El archivo ya existe en la carpeta de destino.'
    ]);
    exit;
}

// guardado en carpeta global_xml
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode([
        'ok' => false,
        'message' => 'No se pudo guardar el archivo.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Archivo guardado correctamente.'
]);
