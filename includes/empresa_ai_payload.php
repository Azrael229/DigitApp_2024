<?php
declare(strict_types=1);

function obtenerInstruccionExtraccionEmpresa(): string
{
    return 'Analiza la informacion proporcionada y extrae datos de empresa. Busca RFC, razon social, nombre comercial sugerido, regimen fiscal, direccion fiscal, codigo postal, contacto, telefono, correo, sitio web, mercado y giro. No inventes datos. Si un dato no aparece, dejalo vacio. En la siguiente fase se solicitara respuesta JSON estructurada.';
}

function prepararInputTextoEmpresaAI(string $textoUsuario): ?array
{
    $textoUsuario = trim($textoUsuario);
    if ($textoUsuario === '') {
        return null;
    }

    return [
        'type' => 'input_text',
        'text' => $textoUsuario,
    ];
}

function prepararInputArchivoEmpresaAI(string $fileId, string $nombreArchivo, string $tipoDetectado): array
{
    $fileId = trim($fileId);
    $nombreArchivo = trim($nombreArchivo);
    $tipoDetectado = trim(strtolower($tipoDetectado));

    if ($tipoDetectado === 'imagen') {
        return [
            'type' => 'input_image',
            'file_id' => $fileId,
            'detail' => 'auto',
        ];
    }

    if (in_array($tipoDetectado, ['pdf', 'texto', 'xml', 'json', 'documento', 'csv'], true)) {
        return [
            'type' => 'input_file',
            'file_id' => $fileId,
        ];
    }

    throw new Exception('Tipo de archivo no soportado para preparar input de OpenAI.');
}

function prepararContenidoOpenAIDesdeFileId(?array $archivoOpenAI, string $textoUsuario): array
{
    $contenido = [];
    $contenido[] = [
        'type' => 'input_text',
        'text' => obtenerInstruccionExtraccionEmpresa(),
    ];

    $inputTexto = prepararInputTextoEmpresaAI($textoUsuario);
    if ($inputTexto !== null) {
        $contenido[] = $inputTexto;
    }

    if ($archivoOpenAI !== null) {
        $contenido[] = prepararInputArchivoEmpresaAI(
            (string) ($archivoOpenAI['file_id'] ?? ''),
            (string) ($archivoOpenAI['nombre_archivo'] ?? ''),
            (string) ($archivoOpenAI['tipo_detectado'] ?? '')
        );
    }

    return $contenido;
}

function resumirContenidoOpenAI(array $contenidoOpenAI): array
{
    $tipos = [];
    $usaFileId = false;
    $tieneArchivoOpenAI = false;

    foreach ($contenidoOpenAI as $item) {
        if (!is_array($item)) {
            continue;
        }

        $tipo = isset($item['type']) ? (string) $item['type'] : 'desconocido';
        $tipos[] = $tipo;

        if (isset($item['file_id']) && trim((string) $item['file_id']) !== '') {
            $usaFileId = true;
            $tieneArchivoOpenAI = true;
        }
    }

    return [
        'items_preparados' => count($tipos),
        'tipos' => $tipos,
        'usa_file_id' => $usaFileId,
        'tiene_archivo_openai' => $tieneArchivoOpenAI,
    ];
}
