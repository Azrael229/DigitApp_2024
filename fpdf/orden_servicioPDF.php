<?php
require('fpdf.php');

/*
|--------------------------------------------------------------------------
| Configuracion inicial
|--------------------------------------------------------------------------
| Plantilla PDF para Orden de Servicio.
| Por ahora usa datos dummy para validar maquetacion y estilo visual.
| Queda lista para reemplazar despues por datos reales de Render_OS.php.
|--------------------------------------------------------------------------
*/

function pdfText($text)
{
    return utf8_decode((string) $text);
}

/*
|--------------------------------------------------------------------------
| Datos dummy
|--------------------------------------------------------------------------
| Estructura basada en los campos visibles en Render_OS.php, con algunos
| complementos visuales para maquetar el PDF completo.
|--------------------------------------------------------------------------
*/
$orden = [
    'folio_os' => 'OS-2026-0018',
    'fecha_emision' => '2026-04-21',
    'fecha_servicio' => '2026-04-23',
    'empresa_nombre' => 'INDUSTRIAS DEL BAJIO S.A. DE C.V.',
    'direccion_entrega' => 'Av. de la Industria 245, Parque Industrial Benito Juarez, Queretaro, Qro.',
    'ubicacion' => 'Planta 2 - Area de embarques',
    'contacto_nombre' => 'Ing. Laura Hernandez',
    'telefono_contacto' => '442 555 0187',
    'correo_contacto' => 'laura.hernandez@industriasbajio.com',
    'departamento' => 'Mantenimiento',
    'ciudad' => 'Queretaro, Qro.',
    'estado' => 'Programada',
    'nombre_proyecto' => 'Mantenimiento preventivo de equipos de pesaje',
    'descripcion' => 'Realizar inspeccion general, limpieza, ajuste, revision funcional y verificacion operativa de los equipos de pesaje ubicados en planta. Se debe dejar registro del estado de cada equipo y notificar cualquier hallazgo que requiera refacciones o servicio correctivo.',
    'observaciones' => 'El cliente solicita ejecutar los trabajos en horario matutino y coordinar el ingreso del tecnico con el responsable de seguridad industrial. Es importante validar suministro electrico, nivelacion y condiciones del area antes de iniciar.',
    'comentarios_tecnicos' => 'Se recomienda revisar conectores, cajas suma y estado del cableado expuesto. En equipos con desviaciones mayores a tolerancia se debe documentar el resultado y proponer accion correctiva.',
    'alcance_servicio' => 'Incluye inspeccion visual, revision funcional, ajuste menor, limpieza y entrega de reporte basico de servicio por equipo atendido.',
    'condiciones_generales' => 'No incluye refacciones, modificaciones estructurales ni calibracion acreditada, salvo que se especifique por escrito en una orden complementaria.',
    'tecnico_responsable' => 'Israel Navarrete Aguilar',
    'puesto_tecnico' => 'Tecnico especialista en sistemas de pesaje',
    'cliente_conformidad' => 'Nombre y firma de conformidad del cliente',
    'notas_adicionales' => 'Cualquier trabajo adicional detectado durante la visita debera ser autorizado por el cliente antes de su ejecucion.',
];

$equipos = [
    [
        'no' => 1,
        'tipo_bascula' => 'Bascula de plataforma',
        'marca' => 'Rhino',
        'modelo' => 'BAR-1500',
        'capacidad' => '1 500 kg',
        'division' => '0.2 kg',
        'serie' => 'RH-1500-AX22',
        'ubicacion' => 'Anden 1',
        'observaciones' => 'Presenta desgaste ligero en cable de celda.',
    ],
    [
        'no' => 2,
        'tipo_bascula' => 'Bascula camionera',
        'marca' => 'Mettler Toledo',
        'modelo' => 'BridgeMont',
        'capacidad' => '60 000 kg',
        'division' => '10 kg',
        'serie' => 'MT-TRK-8841',
        'ubicacion' => 'Acceso principal',
        'observaciones' => 'Se requiere revisar nivelacion del indicador.',
    ],
    [
        'no' => 3,
        'tipo_bascula' => 'Bascula de mesa',
        'marca' => 'Torrey',
        'modelo' => 'EQB-100',
        'capacidad' => '100 kg',
        'division' => '0.01 kg',
        'serie' => 'TR-100-5512',
        'ubicacion' => 'Laboratorio',
        'observaciones' => 'Equipo en buenas condiciones.',
    ],
    [
        'no' => 4,
        'tipo_bascula' => 'Bascula colgante',
        'marca' => 'Keli',
        'modelo' => 'OCS-XZ',
        'capacidad' => '500 kg',
        'division' => '0.1 kg',
        'serie' => 'KL-500-9021',
        'ubicacion' => 'Almacen B',
        'observaciones' => 'Validar bateria recargable.',
    ],
    [
        'no' => 5,
        'tipo_bascula' => 'Indicador con plataforma',
        'marca' => 'Avery Weigh-Tronix',
        'modelo' => 'ZM201',
        'capacidad' => '2 000 kg',
        'division' => '0.5 kg',
        'serie' => 'AV-201-3348',
        'ubicacion' => 'Produccion linea 3',
        'observaciones' => 'Se detecta suciedad en caja suma.',
    ],
    [
        'no' => 6,
        'tipo_bascula' => 'Bascula de recibo',
        'marca' => 'Braunker',
        'modelo' => 'BP-300',
        'capacidad' => '300 kg',
        'division' => '0.05 kg',
        'serie' => 'BK-300-7710',
        'ubicacion' => 'Area de recibo',
        'observaciones' => 'Sin novedad.',
    ],
];

/*
|--------------------------------------------------------------------------
| Clase PDF
|--------------------------------------------------------------------------
*/
class OrdenServicioPDF extends FPDF
{
    public $orden = [];
    public $logoPath = '../imgs/LogoMakr_3N5U8p-262x87.png';
    private $accentColor = [255, 182, 3];
    private $grayFill = [189, 189, 189];
    private $tableWidths = [10, 24, 18, 18, 18, 15, 28, 24, 41];
    private $tableAligns = ['C', 'L', 'L', 'L', 'C', 'C', 'L', 'L', 'L'];

    public function Header()
    {
        $this->drawCorporateHeader();
    }

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetDrawColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $this->Line(10, $this->GetY(), 206, $this->GetY());
        $this->Line(10, $this->GetY() + 1, 206, $this->GetY() + 1);

        $this->SetTextColor(90, 90, 90);
        $this->SetFont('Arial', '', 8);
        $this->SetXY(12, $this->GetY() + 3);
        $this->Cell(110, 4, pdfText('SERVICOM | contacto@servicombasculas.com.mx | servicombasculas.com.mx'), 0, 0, 'L');
        $this->Cell(84, 4, pdfText('Pagina ') . $this->PageNo() . ' de {nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    public function drawCorporateHeader()
    {
        $this->SetXY(10, 11);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(135, 5, pdfText('SERVICIOS DE PRECISION A SISTEMAS DE PESAJE'), 0, 0, 'C');

        $this->SetFont('Arial', 'I', 8);
        $this->SetXY(15, 17);
        $this->MultiCell(55, 4, pdfText('Cerro El Gavilan 302-12, Ex Hacienda Santana, 76116, Queretaro, Qro.'), 0, 'L');

        $this->SetXY(88, 17);
        $this->MultiCell(52, 4, pdfText('contacto@servicombasculas.com.mx   servicombasculas.com.mx'), 0, 'R');

        if (is_file($this->logoPath)) {
            $this->Image($this->logoPath, 140, 6, 70, 0);
        }

        $this->SetXY(157, 20);
        $this->SetTextColor(150, 150, 150);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(45, 6, pdfText('BASCULAS DIGITALES'), 0, 0, 'C');

        $this->SetXY(10, 27);
        $this->SetTextColor(100, 100, 100);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(196, 10, pdfText('ORDEN DE SERVICIO'), 0, 0, 'C');

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(12, 38);
        $this->Cell(34, 5, pdfText('FECHA DE EMISION:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(28, 5, pdfText($this->orden['fecha_emision'] ?? ''), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(32, 5, pdfText('FECHA SERVICIO:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(28, 5, pdfText($this->orden['fecha_servicio'] ?? ''), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(16, 5, pdfText('FOLIO:'), 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(46, 5, pdfText($this->orden['folio_os'] ?? ''), 0, 0, 'L');

        $this->SetDrawColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $this->Line(10, 45, 206, 45);
        $this->Line(10, 46, 206, 46);
    }

    public function drawSectionTitle($title, $y = null)
    {
        if ($y !== null) {
            $this->SetY($y);
        }

        $this->SetFillColor($this->grayFill[0], $this->grayFill[1], $this->grayFill[2]);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(196, 6, '   ' . pdfText($title), 0, 1, 'L', true);
        $this->Ln(2);
    }

    public function drawClientBlock()
    {
        $startY = $this->GetY();

        $this->SetFillColor($this->grayFill[0], $this->grayFill[1], $this->grayFill[2]);
        $this->SetFont('Arial', 'I', 8);
        $this->SetXY(10, $startY);
        $this->Cell(98, 6, '   ' . pdfText('DATOS DEL CLIENTE'), 0, 0, 'L', true);
        $this->Cell(98, 6, '   ' . pdfText('DATOS DEL CONTACTO'), 0, 1, 'L', true);

        $leftY = $startY + 7;
        $rightY = $startY + 7;

        $this->drawLabelValue(12, $leftY, 20, 'Empresa:', $this->orden['empresa_nombre'] ?? '', 74);
        $leftY += 5;
        $leftY = $this->drawLabelValueMulti(12, $leftY, 20, 'Direccion:', $this->orden['direccion_entrega'] ?? '', 74);
        $leftY += 1;
        $leftY = $this->drawLabelValueMulti(12, $leftY, 20, 'Ubicacion:', $this->orden['ubicacion'] ?? '', 74);
        $leftY += 1;
        $this->drawLabelValue(12, $leftY, 20, 'Ciudad:', $this->orden['ciudad'] ?? '', 74);

        $this->drawLabelValue(110, $rightY, 18, 'Nombre:', $this->orden['contacto_nombre'] ?? '', 74);
        $rightY += 5;
        $this->drawLabelValue(110, $rightY, 18, 'Telefono:', $this->orden['telefono_contacto'] ?? '', 74);
        $rightY += 5;
        $this->drawLabelValue(110, $rightY, 18, 'Correo:', $this->orden['correo_contacto'] ?? '', 74);
        $rightY += 5;
        $this->drawLabelValue(110, $rightY, 18, 'Depto:', $this->orden['departamento'] ?? '', 74);
        $rightY += 5;
        $this->drawLabelValue(110, $rightY, 18, 'Estado:', $this->orden['estado'] ?? '', 74);

        $finalY = max($leftY + 5, $rightY + 5);
        $this->SetY($finalY + 4);

        $this->SetDrawColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $this->Line(10, $this->GetY(), 206, $this->GetY());
        $this->Line(10, $this->GetY() + 1, 206, $this->GetY() + 1);
        $this->Ln(5);
    }

    public function drawServiceDescriptionBlock()
    {
        $this->drawSectionTitle('DETALLE GENERAL DEL SERVICIO');

        $this->drawTextPanel('Nombre del proyecto', $this->orden['nombre_proyecto'] ?? '');
        $this->drawTextPanel('Descripcion del trabajo', $this->orden['descripcion'] ?? '');
        $this->drawTextPanel('Observaciones', $this->orden['observaciones'] ?? '');
        $this->drawTextPanel('Comentarios tecnicos', $this->orden['comentarios_tecnicos'] ?? '');
        $this->drawTextPanel('Alcance del servicio', $this->orden['alcance_servicio'] ?? '');
        $this->drawTextPanel('Condiciones generales', $this->orden['condiciones_generales'] ?? '');
    }

    public function drawTextPanel($title, $content)
    {
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(196, 5, pdfText($title), 0, 1, 'L');
        $this->SetFont('Arial', '', 8);
        $currentY = $this->GetY();
        $heightEstimate = max(8, ceil($this->GetStringWidth(pdfText($content)) / 175) * 4 + 4);
        $this->checkPageSpace($heightEstimate + 6);
        $currentY = $this->GetY();
        $this->Rect(10, $currentY, 196, $heightEstimate);
        $this->SetXY(12, $currentY + 2);
        $this->MultiCell(192, 4, pdfText($content), 0, 'J');
        $this->Ln(2);
    }

    public function drawEquiposSection(array $equipos)
    {
        $this->checkPageSpace(20);
        $this->drawSectionTitle('EQUIPOS / BASCULAS PROGRAMADAS');
        $this->drawEquiposHeader();

        foreach ($equipos as $equipo) {
            $row = [
                $equipo['no'] ?? '',
                $equipo['tipo_bascula'] ?? '',
                $equipo['marca'] ?? '',
                $equipo['modelo'] ?? '',
                $equipo['capacidad'] ?? '',
                $equipo['division'] ?? '',
                $equipo['serie'] ?? '',
                $equipo['ubicacion'] ?? '',
                $equipo['observaciones'] ?? '',
            ];

            $rowHeight = $this->calculateRowHeight($row);
            $this->checkTablePageBreak($rowHeight);
            $this->drawTableRow($row, $rowHeight);
        }

        $this->Ln(4);
    }

    public function drawEquiposHeader()
    {
        $headers = ['No.', 'Tipo de bascula', 'Marca', 'Modelo', 'Capacidad', 'Division', 'ID / Serie', 'Ubicacion', 'Observaciones'];

        $this->SetFont('Arial', 'I', 7);
        $this->SetFillColor($this->grayFill[0], $this->grayFill[1], $this->grayFill[2]);
        $this->SetDrawColor(0, 0, 0);

        foreach ($headers as $index => $header) {
            $this->Cell($this->tableWidths[$index], 8, pdfText($header), 1, 0, 'C', true);
        }

        $this->Ln();
    }

    public function drawTableRow(array $row, $rowHeight)
    {
        $x = $this->GetX();
        $y = $this->GetY();

        $this->SetFont('Arial', '', 7);

        foreach ($row as $index => $text) {
            $width = $this->tableWidths[$index];
            $align = $this->tableAligns[$index];

            $this->Rect($x, $y, $width, $rowHeight);
            $this->SetXY($x + 1, $y + 1);
            $this->MultiCell($width - 2, 3.5, pdfText($text), 0, $align);
            $x += $width;
            $this->SetXY($x, $y);
        }

        $this->SetXY(10, $y + $rowHeight);
    }

    public function calculateRowHeight(array $row)
    {
        $maxLines = 1;

        foreach ($row as $index => $text) {
            $lines = $this->NbLines($this->tableWidths[$index] - 2, pdfText($text));
            if ($lines > $maxLines) {
                $maxLines = $lines;
            }
        }

        return max(8, ($maxLines * 3.5) + 2);
    }

    public function drawSignaturesBlock()
    {
        $this->checkPageSpace(40);
        $this->drawSectionTitle('FIRMAS Y CONFORMIDAD');

        $baseY = $this->GetY() + 10;

        $this->Line(20, $baseY, 90, $baseY);
        $this->Line(126, $baseY, 196, $baseY);

        $this->SetXY(20, $baseY + 2);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(70, 5, pdfText($this->orden['tecnico_responsable'] ?? ''), 0, 0, 'C');
        $this->Cell(36, 5, '', 0, 0, 'C');
        $this->Cell(70, 5, pdfText($this->orden['cliente_conformidad'] ?? ''), 0, 1, 'C');

        $this->SetX(20);
        $this->SetFont('Arial', '', 8);
        $this->Cell(70, 5, pdfText($this->orden['puesto_tecnico'] ?? ''), 0, 0, 'C');
        $this->Cell(36, 5, '', 0, 0, 'C');
        $this->Cell(70, 5, pdfText('Cliente / Responsable de recepcion'), 0, 1, 'C');

        $this->Ln(10);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(196, 5, pdfText('Notas adicionales'), 0, 1, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Rect(10, $this->GetY(), 196, 14);
        $this->SetXY(12, $this->GetY() + 2);
        $this->MultiCell(192, 4, pdfText($this->orden['notas_adicionales'] ?? ''), 0, 'J');
    }

    public function drawLabelValue($x, $y, $labelWidth, $label, $value, $valueWidth)
    {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($labelWidth, 5, pdfText($label), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell($valueWidth, 5, pdfText($value), 0, 0, 'L');
    }

    public function drawLabelValueMulti($x, $y, $labelWidth, $label, $value, $valueWidth)
    {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($labelWidth, 4, pdfText($label), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $valueX = $x + $labelWidth;
        $this->SetXY($valueX, $y);
        $this->MultiCell($valueWidth, 4, pdfText($value), 0, 'L');
        return $this->GetY();
    }

    public function checkPageSpace($requiredHeight)
    {
        if ($this->GetY() + $requiredHeight > 248) {
            $this->AddPage();
            $this->SetY(50);
        }
    }

    public function checkTablePageBreak($rowHeight)
    {
        if ($this->GetY() + $rowHeight > 240) {
            $this->AddPage();
            $this->SetY(50);
            $this->drawSectionTitle('EQUIPOS / BASCULAS PROGRAMADAS');
            $this->drawEquiposHeader();
        }
    }

    public function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string) $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

/*
|--------------------------------------------------------------------------
| Render del documento
|--------------------------------------------------------------------------
*/
$pdf = new OrdenServicioPDF('P', 'mm', 'letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 22);
$pdf->orden = $orden;
$pdf->AddPage();
$pdf->SetY(50);

// Bloque de cliente y contacto
$pdf->drawClientBlock();

// Bloques de descripcion general
$pdf->drawServiceDescriptionBlock();

// Tabla de equipos
$pdf->drawEquiposSection($equipos);

// Firmas y notas finales
$pdf->drawSignaturesBlock();

$pdf->Output('I', 'ORDEN_SERVICIO_' . ($orden['folio_os'] ?? 'DEMO') . '.pdf');
?>
