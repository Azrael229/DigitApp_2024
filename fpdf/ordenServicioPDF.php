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

    // Ajusta un texto completo al ancho disponible y conserva los saltos escritos por el usuario.
    public function wrappedLines(float $width, string $value): array
    {
        $text = $this->pdfText(str_replace("\r", '', trim($value)));
        $availableWidth = max(1.0, $width - 2 * $this->cMargin);
        $lines = [];
        foreach (explode("\n", $text) as $paragraph) {
            if (trim($paragraph) === '') {
                $lines[] = '';
                continue;
            }
            $current = '';
            foreach (preg_split('/\s+/', trim($paragraph)) ?: [] as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                if ($this->GetStringWidth($candidate) <= $availableWidth) {
                    $current = $candidate;
                    continue;
                }
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                while ($this->GetStringWidth($word) > $availableWidth) {
                    $chunk = '';
                    while ($word !== '' && $this->GetStringWidth($chunk . $word[0]) <= $availableWidth) {
                        $chunk .= $word[0];
                        $word = substr($word, 1);
                    }
                    $lines[] = $chunk !== '' ? $chunk : substr($word, 0, 1);
                    if ($chunk === '') {
                        $word = substr($word, 1);
                    }
                }
                $current = $word;
            }
            if ($current !== '') {
                $lines[] = $current;
            }
        }
        return $lines ?: [''];
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

    // Formatea magnitudes metrológicas sin comas y conserva la unidad maestra del equipo.
    public function measurement($value, string $unit): string
    {
        if ($value === null || $value === '' || (float) $value <= 0) {
            return '—';
        }
        $formatted = rtrim(rtrim(number_format((float) $value, 6, '.', ' '), '0'), '.');
        return trim($formatted . ' ' . $unit);
    }

    // Dibuja una etiqueta y su valor completo dentro de una celda informativa.
    private function equipmentField(float $x, float $y, float $width, float $height, string $label, string $value): void
    {
        $this->SetDrawColor(...self::BORDE);
        $this->SetFillColor(255, 255, 255);
        $this->Rect($x, $y, $width, $height, 'DF');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 6.8);
        $this->SetXY($x + 2, $y + 1.2);
        $this->Cell($width - 4, 3, $this->pdfText($label), 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 8.1);
        $this->SetXY($x + 2, $y + 4.2);
        $this->MultiCell($width - 4, 3.7, $this->pdfText($value !== '' ? $value : '—'), 0, 'L');
    }

    // Presenta un equipo como ficha horizontal legible y evita la compresión de diez columnas.
    public function equipmentCard(float $y, int $number, array $equipment): float
    {
        $x = 14.0;
        $width = 188.0;
        $headerHeight = 10.0;
        $rowHeight = 11.0;
        $metroHeight = 8.0;
        $notesHeight = 7.0;
        $numberWidth = 27.0;

        $this->SetDrawColor(...self::AZUL);
        $this->SetFillColor(...self::AZUL);
        $this->Rect($x, $y, $numberWidth, $headerHeight, 'DF');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.2);
        $this->SetXY($x + 2, $y + 2.5);
        $this->Cell($numberWidth - 4, 5, $this->pdfText(sprintf('EQUIPO %02d', $number)), 0, 0, 'L');

        $this->SetFillColor(220, 230, 238);
        $this->Rect($x + $numberWidth, $y, $width - $numberWidth, $headerHeight, 'DF');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY($x + $numberWidth + 2, $y + 1.5);
        $this->MultiCell($width - $numberWidth - 4, 3.7, $this->pdfText((string) ($equipment['descripcion'] ?: 'Equipo')), 0, 'L');

        $column = $width / 3;
        $rowOneY = $y + $headerHeight;
        $this->equipmentField($x, $rowOneY, $column, $rowHeight, 'Marca', (string) $equipment['marca']);
        $this->equipmentField($x + $column, $rowOneY, $column, $rowHeight, 'Modelo', (string) $equipment['modelo']);
        $this->equipmentField($x + 2 * $column, $rowOneY, $column, $rowHeight, 'Número de serie', (string) $equipment['numero_serie']);

        $rowTwoY = $rowOneY + $rowHeight;
        $this->equipmentField($x, $rowTwoY, $width / 2, $rowHeight, 'Identificación', (string) $equipment['identificacion']);
        $this->equipmentField($x + $width / 2, $rowTwoY, $width / 2, $rowHeight, 'Ubicación', (string) $equipment['ubicacion']);

        $metroY = $rowTwoY + $rowHeight;
        $unit = trim((string) $equipment['unidad']);
        $metroValues = [
            'Max: ' . $this->measurement($equipment['capacidad_maxima'], $unit),
            'd: ' . $this->measurement($equipment['division_real'], $unit),
            'e: ' . $this->measurement($equipment['division_verificacion'], $unit),
            'Clase: ' . ((string) $equipment['clase_exactitud'] ?: '—'),
        ];
        $metroWidth = $width / 4;
        $this->SetFillColor(231, 238, 244);
        $this->SetFont('Arial', 'B', 8.0);
        foreach ($metroValues as $index => $value) {
            $cellX = $x + $index * $metroWidth;
            $this->Rect($cellX, $metroY, $metroWidth, $metroHeight, 'DF');
            $this->SetXY($cellX + 2, $metroY + 2);
            $this->Cell($metroWidth - 4, 4, $this->pdfText($value), 0, 0, 'L');
        }

        $notesY = $metroY + $metroHeight;
        $this->SetFillColor(255, 255, 255);
        $this->Rect($x, $notesY, $width, $notesHeight, 'DF');
        $this->SetTextColor(102, 116, 130);
        $this->SetFont('Arial', '', 6.7);
        $this->SetXY($x + 2, $notesY + 1.5);
        $this->Cell($width - 4, 4, $this->pdfText('Corrección o anotación en campo:'), 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);

        return $headerHeight + 2 * $rowHeight + $metroHeight + $notesHeight;
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
$pdf->Cell(188, 5, $pdf->pdfText('Fecha de emisión: ' . date('d/m/Y', strtotime((string) $orden['fecha_generacion']))), 0, 0, 'L');

// Datos fiscales y de entrega se distinguen en columnas independientes.
$fiscalBody = implode("\n", [
    'Razón social: ' . trim((string) ($orden['fiscal_razon_social'] ?? '')),
    'RFC: ' . trim((string) ($orden['fiscal_rfc'] ?? '')),
    'Dirección fiscal: ' . trim((string) ($orden['fiscal_direccion'] ?? '')),
]);
$role = trim(implode(' · ', array_filter([
    $orden['entrega_puesto_mostrado'] ?? $orden['entrega_puesto'] ?? '',
    $orden['entrega_departamento_mostrado'] ?? $orden['entrega_departamento'] ?? '',
])));
$deliveryBody = implode("\n", [
    'Contacto: ' . trim((string) ($orden['entrega_contacto'] ?? '')),
    'Puesto / departamento: ' . $role,
    'Teléfono: ' . trim((string) ($orden['entrega_telefono'] ?? '')),
    'Correo: ' . trim((string) ($orden['entrega_correo'] ?? '')),
    'Dirección entrega:',
    trim((string) ($orden['entrega_direccion_alias'] ?? '')),
    trim((string) ($orden['entrega_direccion'] ?? '')),
]);
$pdf->infoBox(14, 48, 92, 42, 'DATOS FISCALES DEL CLIENTE', $pdf->compact($fiscalBody, 360), [18, 56, 94]);
$pdf->infoBox(110, 48, 92, 42, 'ENTREGA Y ATENCIÓN DEL SERVICIO', $pdf->compact($deliveryBody, 360), OrdenServicioPDF::ROJO);

// Las instrucciones crecen con el texto y continúan en páginas nuevas sin omitir información.
$pdf->SetFont('Arial', '', 7.2);
$instructionLines = $pdf->wrappedLines(184, (string) ($orden['instrucciones'] ?: 'Sin instrucciones adicionales.'));
$instructionSectionY = 94.0;
do {
    $pdf->section($instructionSectionY, $instructionSectionY === 94.0
        ? 'INSTRUCCIONES PARA EJECUTAR EL SERVICIO'
        : 'INSTRUCCIONES PARA EJECUTAR EL SERVICIO · CONTINUACIÓN');
    $instructionBoxY = $instructionSectionY + 6;
    $maxLines = max(1, (int) floor((250 - $instructionBoxY - 4) / 4));
    $pageLines = array_splice($instructionLines, 0, $maxLines);
    $instructionHeight = max(20.0, 4.0 + count($pageLines) * 4.0);
    $pdf->SetDrawColor(205, 213, 221);
    $pdf->SetFillColor(242, 244, 246);
    $pdf->Rect(14, $instructionBoxY, 188, $instructionHeight, 'DF');
    $pdf->SetTextColor(24, 50, 75);
    $pdf->SetFont('Arial', '', 7.2);
    $lineY = $instructionBoxY + 2;
    foreach ($pageLines as $line) {
        $pdf->SetXY(16, $lineY);
        $pdf->Cell(184, 4, $line, 0, 0, 'L');
        $lineY += 4;
    }
    if ($instructionLines) {
        $pdf->AddPage();
        $instructionSectionY = 29.0;
    }
} while ($instructionLines);

// Las fichas aprovechan el espacio restante sin superar cuatro equipos por página.
$equipmentSectionY = $instructionBoxY + $instructionHeight + 4;
if ($equipmentSectionY + 6 + 47 > 250) {
    $pdf->AddPage();
    $equipmentSectionY = 29.0;
}
$pdf->section($equipmentSectionY, 'EQUIPOS INCLUIDOS EN EL SERVICIO');
$equipmentY = $equipmentSectionY + 9;
if (!$orden['equipos']) {
    $pdf->SetXY(14, $equipmentY);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(188, 12, $pdf->pdfText('Sin equipos registrados.'), 1, 1, 'C');
} else {
    $equipmentOnPage = 0;
    foreach ($orden['equipos'] as $index => $equipment) {
        if ($equipmentOnPage >= 4 || $equipmentY + 47 > 250) {
            $pdf->AddPage();
            $pdf->section(29, 'EQUIPOS INCLUIDOS EN EL SERVICIO · CONTINUACIÓN');
            $equipmentY = 38.0;
            $equipmentOnPage = 0;
        }
        $equipmentY += $pdf->equipmentCard($equipmentY, $index + 1, $equipment) + 4;
        $equipmentOnPage++;
    }
}

// Reserva una página final con un área de observaciones 50% mayor y firmas independientes.
$pdf->AddPage();
$observationsY = 65.0;
$pdf->section($observationsY, 'OBSERVACIONES Y ANOTACIONES MANUALES');
$pdf->SetDrawColor(130, 145, 160);
$pdf->Rect(14, $observationsY + 6, 188, 79.5);
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(102, 116, 130);
$pdf->SetXY(16, $observationsY + 8);
$pdf->Cell(184, 4, $pdf->pdfText('Espacio para anotaciones del técnico o del cliente durante la ejecución y entrega.'), 0, 0, 'L');

$pdf->SetTextColor(24, 50, 75);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetXY(14, 163);
$pdf->Cell(88, 5, $pdf->pdfText('ACEPTACIÓN DEL CLIENTE'), 0, 0, 'C');
$pdf->Cell(12, 5, '', 0, 0);
$pdf->Cell(88, 5, $pdf->pdfText('PERSONAL QUE ENTREGA EL TRABAJO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 7);
$pdf->SetXY(14, 190);
$pdf->Cell(88, 4, '________________________________________', 0, 0, 'C');
$pdf->Cell(12, 4, '', 0, 0);
$pdf->Cell(88, 4, '________________________________________', 0, 1, 'C');
$pdf->SetXY(14, 195);
$pdf->Cell(88, 4, $pdf->pdfText('Nombre y firma'), 0, 0, 'C');
$pdf->Cell(12, 4, '', 0, 0);
$pdf->Cell(88, 4, $pdf->pdfText('Nombre y firma'), 0, 0, 'C');
$pdf->SetXY(14, 202);
$pdf->Cell(88, 4, $pdf->pdfText('Fecha de ejecución: ____ / ____ / ______'), 0, 0, 'C');

$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $orden['numero_servicio']) . '.pdf';
$pdf->Output('I', $filename);
