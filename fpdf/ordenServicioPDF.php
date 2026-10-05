<?php
declare(strict_types=1);

require __DIR__ . '/../backend/ordenes_servicio/common.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/fpdf.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(422);
    exit('La orden de servicio no es válida.');
}
$conexion->set_charset('utf8mb4');
try {
    $orden = os_get($conexion, $id);
} catch (OutOfBoundsException $error) {
    http_response_code(404);
    exit('La orden de servicio no existe.');
} finally {
    $conexion->close();
}

final class OrdenServicioPDF extends FPDF
{
    private array $orden;
    private const AZUL = [18, 56, 94];
    public const ROJO = [174, 57, 50];
    private const GRIS = [242, 244, 246];
    private const BORDE = [205, 213, 221];
    private const TEXTO = [24, 50, 75];

    public function __construct(array $orden)
    {
        parent::__construct('P', 'mm', 'letter');
        $this->orden = $orden;
        $this->SetMargins(14, 27, 14);
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
        $this->SetTitle($this->pdfText('Orden de servicio ' . $orden['numero_servicio']));
        $this->SetAuthor($this->pdfText('SERVICOM Básculas Digitales'));
    }

    // Convierte contenido UTF-8 para las fuentes base utilizadas por FPDF.
    public function pdfText(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT', $value);
        return $converted === false ? $value : $converted;
    }

    // Acorta textos dentro de celdas compactas sin alterar el registro completo.
    public function compact(string $value, int $limit): string
    {
        $clean = trim((string) preg_replace('/[ \t]+/', ' ', str_replace("\r", '', $value)));
        if (mb_strlen($clean, 'UTF-8') <= $limit) {
            return $clean;
        }
        return rtrim(mb_substr($clean, 0, max(1, $limit - 1), 'UTF-8')) . '…';
    }

    // Dibuja la identidad institucional, título y folio sin referencias comerciales.
    public function Header(): void
    {
        $this->SetFillColor(...self::ROJO);
        $this->Rect(0, 0, 216, 3, 'F');
        $logo = __DIR__ . '/../imgs/Logo_SERVICOM_texto_negro_sin_fondo.png';
        if (is_file($logo)) {
            $this->Image($logo, 14, 4.5, 49);
        }
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 15);
        $this->SetXY(106, 7);
        $this->Cell(96, 6, $this->pdfText('ORDEN DE SERVICIO'), 0, 2, 'R');
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(...self::ROJO);
        $this->Cell(96, 6, $this->pdfText((string) $this->orden['numero_servicio']), 0, 0, 'R');
        $this->SetDrawColor(...self::BORDE);
        $this->Line(14, 24, 202, 24);
    }

    // Mantiene la identificación documental y la paginación en el pie.
    public function Footer(): void
    {
        $this->SetFillColor(...self::ROJO);
        $this->Rect(0, 255, 216, 2, 'F');
        $this->SetFillColor(...self::AZUL);
        $this->Rect(0, 257, 216, 22, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', '', 7.3);
        $this->SetXY(14, 262);
        $this->Cell(122, 4, $this->pdfText('SERVICOM Básculas Digitales · servicombasculas.com.mx'), 0, 0, 'L');
        $this->Cell(66, 4, $this->pdfText('F-OS-01 · Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    // Presenta un bloque fijo con encabezado coloreado y contenido compacto.
    public function infoBox(float $x, float $y, float $width, float $height, string $title, string $body, array $color): void
    {
        $this->SetDrawColor(...self::BORDE);
        $this->SetFillColor(...self::GRIS);
        $this->Rect($x, $y, $width, $height, 'DF');
        $this->SetFillColor(...$color);
        $this->Rect($x, $y, $width, 7, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.2);
        $this->SetXY($x + 2, $y + 1.3);
        $this->Cell($width - 4, 4, $this->pdfText($title), 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 7.2);
        $this->SetXY($x + 2, $y + 9);
        $this->MultiCell($width - 4, 4, $this->pdfText($body), 0, 'L');
    }

    // Dibuja el encabezado de una sección compacta.
    public function section(float $y, string $title): void
    {
        $this->SetFillColor(...self::AZUL);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY(14, $y);
        $this->Cell(188, 6, $this->pdfText($title), 0, 0, 'L', true);
        $this->SetTextColor(...self::TEXTO);
    }
}

$types = OS_TIPOS;
$typeLabel = mb_strtoupper((string) ($types[$orden['tipo']] ?? $orden['tipo']), 'UTF-8');
if (!str_starts_with($typeLabel, 'SERVICIO')) {
    $typeLabel = 'SERVICIO DE ' . $typeLabel;
}
$pdf = new OrdenServicioPDF($orden);
$pdf->AddPage();

// Tipo de servicio claramente visible.
$pdf->SetFillColor(...OrdenServicioPDF::ROJO);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 11.5);
$pdf->SetXY(14, 29);
$pdf->Cell(188, 10, $pdf->pdfText($typeLabel), 0, 1, 'C', true);
$pdf->SetTextColor(24, 50, 75);
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(14, 41);
$pdf->Cell(188, 5, $pdf->pdfText('Fecha: ' . date('d/m/Y', strtotime((string) $orden['fecha_generacion']))), 0, 0, 'L');

// Datos fiscales y de entrega se distinguen en columnas independientes.
$fiscalBody = implode("\n", array_filter([
    $orden['fiscal_razon_social'],
    trim('RFC: ' . (string) $orden['fiscal_rfc']),
    $orden['fiscal_regimen'],
    $orden['fiscal_direccion'],
]));
$role = trim(implode(' · ', array_filter([
    $orden['entrega_puesto_mostrado'] ?? $orden['entrega_puesto'] ?? '',
    $orden['entrega_departamento_mostrado'] ?? $orden['entrega_departamento'] ?? '',
])));
$deliveryBody = implode("\n", array_filter([
    $orden['entrega_contacto'], $role,
    trim(implode(' · ', array_filter([$orden['entrega_telefono'], $orden['entrega_correo']]))),
    $orden['entrega_direccion'],
]));
$pdf->infoBox(14, 48, 92, 42, 'DATOS FISCALES DEL CLIENTE', $pdf->compact($fiscalBody, 360), [18, 56, 94]);
$pdf->infoBox(110, 48, 92, 42, 'ENTREGA Y ATENCIÓN DEL SERVICIO', $pdf->compact($deliveryBody, 360), OrdenServicioPDF::ROJO);

// Instrucciones operativas.
$pdf->section(94, 'INSTRUCCIONES PARA EJECUTAR EL SERVICIO');
$pdf->SetDrawColor(205, 213, 221);
$pdf->SetFillColor(242, 244, 246);
$pdf->Rect(14, 100, 188, 20, 'DF');
$pdf->SetTextColor(24, 50, 75);
$pdf->SetFont('Arial', '', 7.2);
$pdf->SetXY(16, 102);
$pdf->MultiCell(184, 4, $pdf->pdfText($pdf->compact((string) ($orden['instrucciones'] ?: 'Sin instrucciones adicionales.'), 520)), 0, 'L');

// Tabla de equipos con crecimiento dinámico y encabezados repetidos.
$pdf->section(124, 'EQUIPOS INCLUIDOS EN EL SERVICIO');
$widths = [34, 17, 21, 24, 22, 18, 14, 10, 10, 18];
$headers = ['Descripción', 'Marca', 'Modelo', 'Serie', 'Identificación', 'Ubicación', 'Max', 'd', 'e', 'Clase'];
$equipmentHeaderHeight = 8.0;
$equipmentRowHeight = 8.0;
$drawEquipmentHeader = static function (OrdenServicioPDF $document, float $y) use ($widths, $headers, $equipmentHeaderHeight): void {
    $document->SetFillColor(198, 209, 219);
    $document->SetTextColor(24, 50, 75);
    $document->SetFont('Arial', 'B', 6.6);
    $document->SetXY(14, $y);
    foreach ($headers as $index => $header) {
        $document->Cell($widths[$index], $equipmentHeaderHeight, $document->pdfText($header), 1, 0, 'C', true);
    }
    $document->Ln($equipmentHeaderHeight);
};
$drawEquipmentHeader($pdf, 130);
$pdf->SetFont('Arial', '', 6.6);
if (!$orden['equipos']) {
    $pdf->Cell(188, $equipmentRowHeight, $pdf->pdfText('Sin equipos registrados.'), 1, 1, 'C');
} else {
    foreach ($orden['equipos'] as $index => $equipment) {
        if ($pdf->GetY() + $equipmentRowHeight > 250) {
            $pdf->AddPage();
            $pdf->section(29, 'EQUIPOS INCLUIDOS EN EL SERVICIO · CONTINUACIÓN');
            $drawEquipmentHeader($pdf, 35);
            $pdf->SetFont('Arial', '', 6.6);
        }
        $capacity = (float) $equipment['capacidad_maxima'] > 0
            ? rtrim(rtrim(number_format((float) $equipment['capacidad_maxima'], 3, '.', ','), '0'), '.') . ' ' . $equipment['unidad']
            : '—';
        $divisionReal = (float) $equipment['division_real'] > 0
            ? rtrim(rtrim((string) $equipment['division_real'], '0'), '.')
            : '—';
        $divisionVerification = (float) $equipment['division_verificacion'] > 0
            ? rtrim(rtrim((string) $equipment['division_verificacion'], '0'), '.')
            : '—';
        $values = [
            $pdf->compact((string) $equipment['descripcion'], 29),
            $pdf->compact((string) $equipment['marca'], 14),
            $pdf->compact((string) $equipment['modelo'], 16),
            $pdf->compact((string) $equipment['numero_serie'], 16),
            $pdf->compact((string) $equipment['identificacion'], 18),
            $pdf->compact((string) $equipment['ubicacion'], 15),
            $pdf->compact($capacity, 12),
            $pdf->compact($divisionReal, 8),
            $pdf->compact($divisionVerification, 8),
            $pdf->compact((string) $equipment['clase_exactitud'], 14),
        ];
        foreach ($values as $column => $value) {
            $pdf->Cell($widths[$column], $equipmentRowHeight, $pdf->pdfText($value), 1, 0, $column >= 6 ? 'C' : 'L');
        }
        $pdf->Ln($equipmentRowHeight);
    }
}

// Reserva al final del documento una zona amplia para observaciones y firmas.
if ($pdf->GetY() > 154) {
    $pdf->AddPage();
}
$observationsY = max(160.0, $pdf->GetY() + 6);
$pdf->section($observationsY, 'OBSERVACIONES Y ANOTACIONES MANUALES');
$pdf->SetDrawColor(130, 145, 160);
$pdf->Rect(14, $observationsY + 6, 188, 219 - ($observationsY + 6));
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(102, 116, 130);
$pdf->SetXY(16, $observationsY + 8);
$pdf->Cell(184, 4, $pdf->pdfText('Espacio para anotaciones del técnico o del cliente durante la ejecución y entrega.'), 0, 0, 'L');

$pdf->SetTextColor(24, 50, 75);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetXY(14, 222);
$pdf->Cell(88, 5, $pdf->pdfText('ACEPTACIÓN DEL CLIENTE'), 0, 0, 'C');
$pdf->Cell(12, 5, '', 0, 0);
$pdf->Cell(88, 5, $pdf->pdfText('PERSONAL QUE ENTREGA EL TRABAJO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 7);
$pdf->SetXY(14, 239);
$pdf->Cell(88, 4, '________________________________________', 0, 0, 'C');
$pdf->Cell(12, 4, '', 0, 0);
$pdf->Cell(88, 4, '________________________________________', 0, 1, 'C');
$pdf->SetXY(14, 244);
$pdf->Cell(88, 4, $pdf->pdfText('Nombre y firma'), 0, 0, 'C');
$pdf->Cell(12, 4, '', 0, 0);
$pdf->Cell(88, 4, $pdf->pdfText('Nombre y firma'), 0, 0, 'C');

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $orden['numero_servicio']) . '.pdf';
$pdf->Output('I', $filename);
