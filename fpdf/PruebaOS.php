<?php
require('fpdf.php');

/*
|--------------------------------------------------------------------------
| Datos dummy
|--------------------------------------------------------------------------
| Plantilla visual de prueba para una Orden de Servicio.
*/
$orden = [
    'folio_os' => 'OS-2026-0041',
    'estado' => 'En proceso',
    'fecha_emision' => '2026-04-21',
    'fecha_servicio' => '2026-04-23',
    'tipo_servicio' => 'Mantenimiento preventivo y verificacion funcional',
    'tecnico_asignado' => 'Israel Navarrete Aguilar',
    'empresa' => 'Alimentos Industriales del Centro, S.A. de C.V.',
    'razon_social' => 'AIC Operaciones Industriales del Centro',
    'direccion' => 'Av. del Parque 128, Nave C, Parque Industrial Queretaro, Santa Rosa Jauregui, Queretaro, Qro.',
    'contacto' => 'Ing. Fernanda Lopez',
    'telefono' => '442 555 0198',
    'correo' => 'fernanda.lopez@aic-industrial.com',
    'departamento' => 'Mantenimiento y Produccion',
    'ubicacion' => 'Planta 2 - Andenes y laboratorio de empaque',
    'observaciones_cliente' => 'Solicitan iniciar actividades antes de las 09:00 y entregar resumen ejecutivo al cierre de la visita.',
    'descripcion_servicio' => 'Se realizara mantenimiento preventivo, inspeccion visual, ajuste funcional, limpieza general y verificacion operativa de los equipos de pesaje instalados en planta. El objetivo es asegurar condiciones de trabajo estables, detectar desviaciones tempranas y dejar documentados los hallazgos para una futura planeacion correctiva si se requiere.',
    'observaciones_tecnicas' => 'Durante la visita se debe revisar estado mecanico, cableado, celdas de carga, indicador, sellos, alimentacion electrica y fijaciones. Cualquier componente con desgaste o comportamiento irregular debe registrarse con detalle.',
    'hallazgos' => 'Se detecto deriva ligera en uno de los equipos de recibo y acumulacion de suciedad en caja suma de la plataforma principal. Ningun equipo se reporta fuera de servicio al momento de generar esta orden.',
    'recomendaciones' => 'Programar una revision correctiva menor para la plataforma de recibo y sustituir conectores expuestos en el equipo del area de embarques durante la siguiente visita.',
    'pendientes' => 'Validar autorizacion del cliente para servicio correctivo complementario y confirmar ventana operativa para segunda intervencion.',
];

$servicom = [
    'nombre' => 'SERVICOM Basculas Digitales',
    'subtitulo' => 'Servicios de precision a sistemas de pesaje',
    'direccion' => 'Cerro El Gavilan 302-12, Ex Hacienda Santana, 76116, Queretaro, Qro.',
    'correo' => 'contacto@servicombasculas.com.mx',
    'sitio' => 'servicombasculas.com.mx',
    'telefono' => '442 216 8452',
    'eslogan' => 'Mantenimiento, calibracion y soporte tecnico especializado',
];

$equipos = [
    [
        'tipo' => 'Bascula de plataforma',
        'marca' => 'Rhino',
        'modelo' => 'BAR-1500',
        'capacidad' => '1,500 kg',
        'division' => '0.2 kg',
        'serie' => 'RH-AX22-1500',
        'ubicacion' => 'Anden 1',
        'observaciones' => 'Desgaste moderado en cable de celda de carga.',
    ],
    [
        'tipo' => 'Indicador con plataforma',
        'marca' => 'Avery Weigh-Tronix',
        'modelo' => 'ZM201',
        'capacidad' => '2,000 kg',
        'division' => '0.5 kg',
        'serie' => 'AWT-201-3348',
        'ubicacion' => 'Linea 3',
        'observaciones' => 'Revisar nivelacion del conjunto.',
    ],
    [
        'tipo' => 'Bascula de mesa',
        'marca' => 'Torrey',
        'modelo' => 'EQB-100',
        'capacidad' => '100 kg',
        'division' => '0.01 kg',
        'serie' => 'TR-100-5512',
        'ubicacion' => 'Laboratorio',
        'observaciones' => 'Equipo estable, solo limpieza preventiva.',
    ],
    [
        'tipo' => 'Bascula camionera',
        'marca' => 'Mettler Toledo',
        'modelo' => 'BridgeMont',
        'capacidad' => '60,000 kg',
        'division' => '10 kg',
        'serie' => 'MT-TRK-8841',
        'ubicacion' => 'Acceso principal',
        'observaciones' => 'Verificar conexion del indicador y drenajes.',
    ],
    [
        'tipo' => 'Bascula colgante',
        'marca' => 'Keli',
        'modelo' => 'OCS-XZ',
        'capacidad' => '500 kg',
        'division' => '0.1 kg',
        'serie' => 'KL-500-9021',
        'ubicacion' => 'Almacen B',
        'observaciones' => 'Validar bateria recargable y gancho.',
    ],
];

function pdfText($text)
{
    return utf8_decode((string) $text);
}

class PruebaOSPDF extends FPDF
{
    public $orden = [];
    public $servicom = [];
    public $logoPath = '../imgs/Logo2.png';
    private $accent = [221, 126, 32];
    private $accentSoft = [247, 233, 217];
    private $navy = [24, 47, 73];
    private $slate = [78, 92, 110];
    private $line = [220, 226, 233];
    private $softPanel = [246, 248, 251];
    private $tableWidths = [10, 29, 18, 18, 18, 14, 24, 22, 43];
    private $tableAligns = ['C', 'L', 'L', 'L', 'C', 'C', 'L', 'L', 'L'];

    public function Header()
    {
        $this->drawHeaderBar();
    }

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetDrawColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->Line(10, $this->GetY(), 206, $this->GetY());
        $this->Line(10, $this->GetY() + 1, 206, $this->GetY() + 1);

        $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
        $this->SetFont('Arial', '', 8);
        $this->SetXY(12, $this->GetY() + 4);
        $this->Cell(120, 4, pdfText(($this->servicom['nombre'] ?? '') . ' | ' . ($this->servicom['correo'] ?? '')), 0, 0, 'L');
        $this->Cell(74, 4, pdfText('Pagina ') . $this->PageNo() . ' de {nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    private function drawHeaderBar()
    {
        $this->SetFillColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->Rect(10, 10, 196, 28, 'F');

        if (is_file($this->logoPath)) {
            $this->Image($this->logoPath, 140, 7, 62, 0);
        }

        $this->SetTextColor(255, 255, 255);
        $this->SetXY(14, 13);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(100, 6, pdfText('ORDEN DE SERVICIO'), 0, 1, 'L');

        $this->SetX(14);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(100, 5, pdfText($this->servicom['nombre'] ?? ''), 0, 1, 'L');

        $this->SetX(14);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(100, 4, pdfText($this->servicom['subtitulo'] ?? ''), 0, 1, 'L');

        $this->SetX(14);
        $this->Cell(100, 4, pdfText($this->servicom['eslogan'] ?? ''), 0, 1, 'L');

        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor($this->accentSoft[0], $this->accentSoft[1], $this->accentSoft[2]);
        $this->RoundedPanel(10, 40, 196, 13, 2.8, true);

        $this->SetXY(14, 43.5);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(26, 4, pdfText('FOLIO'), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(34, 4, pdfText($this->orden['folio_os'] ?? ''), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(28, 4, pdfText('EMISION'), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(28, 4, pdfText($this->orden['fecha_emision'] ?? ''), 0, 0, 'L');

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(30, 4, pdfText('SERVICIO'), 0, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(25, 4, pdfText($this->orden['fecha_servicio'] ?? ''), 0, 0, 'L');

        $this->drawStateBadge(170, 42.7, 28, 7.2, $this->orden['estado'] ?? '');
    }

    public function RoundedPanel($x, $y, $w, $h, $radius = 2, $fill = false)
    {
        $style = $fill ? 'FD' : 'D';
        $this->RoundedRect($x, $y, $w, $h, $radius, $style);
    }

    public function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style === 'F') {
            $op = 'f';
        } elseif ($style === 'FD' || $style === 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }

        $myArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $myArc, $yc - $r, $xc + $r, $yc - $r * $myArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $myArc, $xc + $r * $myArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $myArc, $yc + $r, $xc - $r, $yc + $r * $myArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $myArc, $xc - $r * $myArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    public function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }

    public function drawStateBadge($x, $y, $w, $h, $text)
    {
        $state = strtolower((string) $text);
        if (str_contains($state, 'proceso')) {
            $fill = [235, 244, 255];
            $textColor = [29, 78, 216];
        } elseif (str_contains($state, 'pendiente')) {
            $fill = [255, 244, 226];
            $textColor = [180, 83, 9];
        } elseif (str_contains($state, 'program')) {
            $fill = [232, 250, 240];
            $textColor = [5, 122, 85];
        } else {
            $fill = [239, 242, 246];
            $textColor = [70, 85, 103];
        }

        $this->SetFillColor($fill[0], $fill[1], $fill[2]);
        $this->SetDrawColor($fill[0], $fill[1], $fill[2]);
        $this->RoundedPanel($x, $y, $w, $h, 2.5, true);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->SetXY($x, $y + 1.5);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($w, 4, pdfText(strtoupper((string) $text)), 0, 0, 'C');
        $this->SetTextColor(0, 0, 0);
    }

    public function sectionTitle($title, $subtitle = '')
    {
        $this->ensureSpace(16);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->SetFillColor($this->softPanel[0], $this->softPanel[1], $this->softPanel[2]);
        $this->RoundedPanel(10, $this->GetY(), 196, 11, 2.5, true);

        $this->SetXY(14, $this->GetY() + 2.3);
        $this->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(100, 4, pdfText($title), 0, 0, 'L');

        if ($subtitle !== '') {
            $this->SetFont('Arial', '', 7);
            $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
            $this->Cell(88, 4, pdfText($subtitle), 0, 0, 'R');
        }

        $this->SetTextColor(0, 0, 0);
        $this->Ln(13);
    }

    public function ensureSpace($height)
    {
        if ($this->GetY() + $height > 246) {
            $this->AddPage();
            $this->SetY(58);
        }
    }

    public function drawSummaryCards()
    {
        $this->ensureSpace(26);
        $cards = [
            ['Cliente', $this->orden['empresa'] ?? ''],
            ['Contacto', $this->orden['contacto'] ?? ''],
            ['Telefono', $this->orden['telefono'] ?? ''],
            ['Tecnico', $this->orden['tecnico_asignado'] ?? ''],
        ];

        $x = 10;
        $y = $this->GetY();
        $w = 46.5;
        $h = 22;

        foreach ($cards as $card) {
            $this->SetFillColor(255, 255, 255);
            $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
            $this->RoundedPanel($x, $y, $w, $h, 2.8, true);

            $this->SetXY($x + 3, $y + 3);
            $this->SetFont('Arial', 'B', 7);
            $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
            $this->Cell($w - 6, 3.5, pdfText($card[0]), 0, 1, 'L');

            $this->SetXY($x + 3, $y + 8);
            $this->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
            $this->SetFont('Arial', 'B', 9);
            $this->MultiCell($w - 6, 4, pdfText($card[1]), 0, 'L');
            $x += $w + 2;
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetY($y + $h + 4);
    }

    public function drawClientAndServiceOverview()
    {
        $this->sectionTitle('RESUMEN GENERAL', 'Vista recomendada para revision ejecutiva');
        $this->drawSummaryCards();

        $this->ensureSpace(56);
        $topY = $this->GetY();

        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->RoundedPanel(10, $topY, 96, 52, 3, true);
        $this->RoundedPanel(110, $topY, 96, 52, 3, true);

        $this->SetXY(14, $topY + 4);
        $this->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(40, 4, pdfText('Datos del cliente'), 0, 1, 'L');

        $this->drawKeyValueBlock(14, $topY + 11, 18, 'Empresa', $this->orden['empresa'] ?? '', 70);
        $this->drawKeyValueBlock(14, $topY + 18, 18, 'Razon social', $this->orden['razon_social'] ?? '', 70);
        $this->drawKeyValueBlockMulti(14, $topY + 25, 18, 'Direccion', $this->orden['direccion'] ?? '', 70);
        $this->drawKeyValueBlock(14, $topY + 39, 18, 'Correo', $this->orden['correo'] ?? '', 70);
        $this->drawKeyValueBlock(14, $topY + 46, 18, 'Telefono', $this->orden['telefono'] ?? '', 70);

        $this->SetXY(114, $topY + 4);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(50, 4, pdfText('Control operativo'), 0, 1, 'L');

        $this->drawKeyValueBlock(114, $topY + 11, 18, 'Contacto', $this->orden['contacto'] ?? '', 70);
        $this->drawKeyValueBlock(114, $topY + 18, 18, 'Depto', $this->orden['departamento'] ?? '', 70);
        $this->drawKeyValueBlock(114, $topY + 25, 18, 'Tecnico', $this->orden['tecnico_asignado'] ?? '', 70);
        $this->drawKeyValueBlock(114, $topY + 32, 18, 'Servicio', $this->orden['tipo_servicio'] ?? '', 70);
        $this->drawKeyValueBlockMulti(114, $topY + 39, 18, 'Ubicacion', $this->orden['ubicacion'] ?? '', 70);

        $this->SetY($topY + 58);
    }

    public function drawKeyValueBlock($x, $y, $labelW, $label, $value, $valueW)
    {
        $this->SetXY($x, $y);
        $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($labelW, 4, pdfText($label), 0, 0, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 7.5);
        $this->Cell($valueW, 4, pdfText($value), 0, 0, 'L');
    }

    public function drawKeyValueBlockMulti($x, $y, $labelW, $label, $value, $valueW)
    {
        $this->SetXY($x, $y);
        $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
        $this->SetFont('Arial', 'B', 7);
        $this->Cell($labelW, 3.8, pdfText($label), 0, 0, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 7.5);
        $this->SetXY($x + $labelW, $y);
        $this->MultiCell($valueW, 3.8, pdfText($value), 0, 'L');
    }

    public function drawNarrativePanel($title, $content, $height = 18)
    {
        $boxHeight = max($height, ($this->NbLines(188, pdfText($content)) * 4) + 8);
        $this->ensureSpace($boxHeight + 10);

        $y = $this->GetY();
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->RoundedPanel(10, $y, 196, $boxHeight, 3, true);

        $this->SetXY(14, $y + 4);
        $this->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->SetFont('Arial', 'B', 8.5);
        $this->Cell(188, 4, pdfText($title), 0, 1, 'L');

        $this->SetXY(14, $y + 10);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->MultiCell(188, 4.2, pdfText($content), 0, 'J');

        $this->SetY($y + $boxHeight + 4);
    }

    public function drawTechnicalGrid()
    {
        $this->sectionTitle('OBSERVACIONES TECNICAS', 'Hallazgos y seguimiento');
        $items = [
            ['Comentarios del tecnico', $this->orden['observaciones_tecnicas'] ?? ''],
            ['Hallazgos', $this->orden['hallazgos'] ?? ''],
            ['Recomendaciones', $this->orden['recomendaciones'] ?? ''],
            ['Pendientes', $this->orden['pendientes'] ?? ''],
        ];

        $pairIndex = 0;
        while ($pairIndex < count($items)) {
            $left = $items[$pairIndex];
            $right = $items[$pairIndex + 1] ?? null;

            $leftHeight = max(26, ($this->NbLines(86, pdfText($left[1])) * 4) + 12);
            $rightHeight = $right ? max(26, ($this->NbLines(86, pdfText($right[1])) * 4) + 12) : 26;
            $rowHeight = max($leftHeight, $rightHeight);

            $this->ensureSpace($rowHeight + 6);
            $y = $this->GetY();

            $this->drawMiniPanel(10, $y, 96, $rowHeight, $left[0], $left[1]);
            if ($right) {
                $this->drawMiniPanel(110, $y, 96, $rowHeight, $right[0], $right[1]);
            }

            $this->SetY($y + $rowHeight + 4);
            $pairIndex += 2;
        }
    }

    public function drawMiniPanel($x, $y, $w, $h, $title, $content)
    {
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->RoundedPanel($x, $y, $w, $h, 3, true);

        $this->SetFillColor($this->accentSoft[0], $this->accentSoft[1], $this->accentSoft[2]);
        $this->RoundedPanel($x + 3, $y + 3, $w - 6, 7, 2.2, true);

        $this->SetXY($x + 6, $y + 5);
        $this->SetTextColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($w - 12, 3, pdfText($title), 0, 0, 'L');

        $this->SetXY($x + 6, $y + 14);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
        $this->MultiCell($w - 12, 4.2, pdfText($content), 0, 'J');
    }

    public function drawEquiposHeader()
    {
        $headers = ['No.', 'Tipo bascula', 'Marca', 'Modelo', 'Capacidad', 'Div.', 'Serie / ID', 'Ubicacion', 'Observaciones'];
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor($this->navy[0], $this->navy[1], $this->navy[2]);
        $this->SetTextColor(255, 255, 255);

        foreach ($headers as $i => $header) {
            $this->Cell($this->tableWidths[$i], 8, pdfText($header), 0, 0, 'C', true);
        }

        $this->SetTextColor(0, 0, 0);
        $this->Ln();
    }

    public function drawEquipos(array $equipos)
    {
        $this->sectionTitle('EQUIPOS / BASCULAS', 'Detalle de activos a intervenir');
        $this->drawEquiposHeader();

        foreach ($equipos as $index => $equipo) {
            $row = [
                $index + 1,
                $equipo['tipo'] ?? '',
                $equipo['marca'] ?? '',
                $equipo['modelo'] ?? '',
                $equipo['capacidad'] ?? '',
                $equipo['division'] ?? '',
                $equipo['serie'] ?? '',
                $equipo['ubicacion'] ?? '',
                $equipo['observaciones'] ?? '',
            ];

            $height = $this->calculateRowHeight($row);
            if ($this->GetY() + $height > 240) {
                $this->AddPage();
                $this->SetY(58);
                $this->sectionTitle('EQUIPOS / BASCULAS', 'Continuacion');
                $this->drawEquiposHeader();
            }

            $fill = ($index % 2 === 0) ? [255, 255, 255] : [249, 250, 252];
            $this->drawRow($row, $height, $fill);
        }

        $this->Ln(4);
    }

    public function calculateRowHeight(array $row)
    {
        $lines = 1;
        foreach ($row as $i => $text) {
            $cellLines = $this->NbLines($this->tableWidths[$i] - 2, pdfText($text));
            if ($cellLines > $lines) {
                $lines = $cellLines;
            }
        }
        return max(9, ($lines * 3.7) + 2);
    }

    public function drawRow(array $row, $height, array $fill)
    {
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetFont('Arial', '', 7.2);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->SetFillColor($fill[0], $fill[1], $fill[2]);

        foreach ($row as $i => $text) {
            $w = $this->tableWidths[$i];
            $this->Rect($x, $y, $w, $height, 'FD');
            $this->SetXY($x + 1, $y + 1.2);
            $this->MultiCell($w - 2, 3.5, pdfText($text), 0, $this->tableAligns[$i]);
            $x += $w;
            $this->SetXY($x, $y);
        }

        $this->SetXY(10, $y + $height);
    }

    public function drawClosure()
    {
        $this->sectionTitle('CIERRE OPERATIVO', 'Validacion y conformidad');
        $this->ensureSpace(40);

        $y = $this->GetY();
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor($this->line[0], $this->line[1], $this->line[2]);
        $this->RoundedPanel(10, $y, 196, 34, 3, true);

        $this->Line(22, $y + 23, 90, $y + 23);
        $this->Line(126, $y + 23, 194, $y + 23);

        $this->SetXY(22, $y + 25);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(68, 4, pdfText($this->orden['tecnico_asignado'] ?? ''), 0, 0, 'C');
        $this->Cell(36, 4, '', 0, 0, 'C');
        $this->Cell(68, 4, pdfText($this->orden['contacto'] ?? ''), 0, 1, 'C');

        $this->SetX(22);
        $this->SetTextColor($this->slate[0], $this->slate[1], $this->slate[2]);
        $this->SetFont('Arial', '', 7.5);
        $this->Cell(68, 4, pdfText('Tecnico responsable'), 0, 0, 'C');
        $this->Cell(36, 4, '', 0, 0, 'C');
        $this->Cell(68, 4, pdfText('Cliente / responsable'), 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);

        $this->SetXY(16, $y + 6);
        $this->SetFont('Arial', '', 7.5);
        $this->Cell(174, 4, pdfText('Documento de prueba visual para revision administrativa, tecnica e impresion desde navegador.'), 0, 0, 'L');
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
| Render del PDF
|--------------------------------------------------------------------------
*/
$pdf = new PruebaOSPDF('P', 'mm', 'letter');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 22);
$pdf->orden = $orden;
$pdf->servicom = $servicom;
$pdf->AddPage();
$pdf->SetY(58);

// Vista ejecutiva principal
$pdf->drawClientAndServiceOverview();

// Descripcion amplia del servicio
$pdf->sectionTitle('DESCRIPCION DEL SERVICIO', 'Bloque principal de trabajo');
$pdf->drawNarrativePanel('Alcance del trabajo', $orden['descripcion_servicio'] ?? '', 24);
$pdf->drawNarrativePanel('Observaciones del cliente', $orden['observaciones_cliente'] ?? '', 16);

// Comentarios tecnicos
$pdf->drawTechnicalGrid();

// Equipos
$pdf->drawEquipos($equipos);

// Cierre
$pdf->drawClosure();

$pdf->Output('I', 'PRUEBA_OS_' . ($orden['folio_os'] ?? 'DEMO') . '.pdf');
?>
