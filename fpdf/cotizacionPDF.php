<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth/bootstrap.php';
auth_require_permission('comercial');

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../paginas/funciones.php';
require_once __DIR__ . '/../backend/helpers/cotizacion_terminos.php';
require_once __DIR__ . '/../backend/cotizaciones/common.php';
require __DIR__ . '/fpdf.php';

$cotizacionId = filter_var($_POST['cotizacion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($cotizacionId === false || $cotizacionId === null) {
    http_response_code(422);
    exit('La cotización no es válida.');
}

$conexion->set_charset('utf8mb4');
$cotizacion = cotizacionDetalle($conexion, (int) $cotizacionId);
if ($cotizacion === null) {
    $conexion->close();
    http_response_code(404);
    exit('La cotización no existe.');
}
if (empty($cotizacion['empresa_id']) || empty($cotizacion['contacto_id']) || empty($cotizacion['direccion_id'])
    || $cotizacion['empresa_actual'] === null || $cotizacion['contacto_actual'] === null
    || $cotizacion['direccion_actual_id'] === null) {
    $conexion->close();
    http_response_code(422);
    exit('La cotización debe vincularse con una empresa, contacto y dirección vigentes antes de generar nuevamente su PDF.');
}

$consulta = $conexion->prepare(
    'SELECT posicion, cantidad, unidad, descripcion, valor_unitario, importe
     FROM cotizacion_partidas WHERE cotizacion_id = ? ORDER BY posicion'
);
$consulta->bind_param('i', $cotizacionId);
$consulta->execute();
$partidas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();

$consulta = $conexion->prepare('SELECT posicion, texto FROM cotizacion_notas WHERE cotizacion_id = ? ORDER BY posicion');
$consulta->bind_param('i', $cotizacionId);
$consulta->execute();
$notas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();
$conexion->close();

if (!$partidas) {
    http_response_code(422);
    exit('La cotización no tiene partidas registradas.');
}

$terminos = cotizacionTerminosDesdeSnapshot($cotizacion['cot_terminos_snapshot'] ?? null);

final class CotizacionPDF extends FPDF
{
    private array $cotizacion;
    private array $terminos;
    private string $tipoPagina = 'partidas';
    private const AZUL = [18, 56, 94];
    private const ROJO = [214, 45, 45];
    private const ROJO_OSCURO = [183, 31, 36];
    private const GRIS_CLARO = [242, 244, 246];
    private const GRIS = [216, 222, 228];
    private const TEXTO = [24, 50, 75];
    private const MUTED = [102, 116, 130];

    public function __construct(array $cotizacion, array $terminos)
    {
        parent::__construct('P', 'mm', 'letter');
        $this->cotizacion = $cotizacion;
        $this->terminos = $terminos;
        $this->SetMargins(14, 10, 14);
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
        $this->SetTitle($this->texto('Cotización ' . (string) $cotizacion['cot_numero']));
        $this->SetAuthor($this->texto('SERVICOM Básculas Digitales'));
    }

    // Convierte UTF-8 a la codificación compatible con las fuentes base de FPDF.
    public function texto(string $valor): string
    {
        $convertido = iconv('UTF-8', 'windows-1252//TRANSLIT', $valor);
        return $convertido === false ? $valor : $convertido;
    }

    // Dibuja rectángulos redondeados para mantener el lenguaje visual institucional.
    public function rectanguloRedondeado(float $x, float $y, float $w, float $h, float $r, string $estilo = ''): void
    {
        $k = $this->k;
        $hp = $this->h;
        $op = $estilo === 'F' ? 'f' : (($estilo === 'FD' || $estilo === 'DF') ? 'B' : 'S');
        $myArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->arco($xc + $r * $myArc, $yc - $r, $xc + $r, $yc - $r * $myArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->arco($xc + $r, $yc + $r * $myArc, $xc + $r * $myArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->arco($xc - $r * $myArc, $yc + $r, $xc - $r, $yc + $r * $myArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->arco($xc - $r, $yc - $r * $myArc, $xc - $r * $myArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    // Cambia el encabezado según se trate de partidas o de la página final de términos.
    public function Header(): void
    {
        $this->SetFillColor(...self::ROJO_OSCURO);
        $this->Rect(0, 0, 58, 3.2, 'F');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(58, 0, 158, 3.2, 'F');
        if ($this->tipoPagina === 'terminos') {
            $this->encabezadoTerminos();
        } elseif ($this->PageNo() === 1) {
            $this->encabezadoPrincipal();
        } else {
            $this->encabezadoContinuacion();
        }
    }

    // Mantiene identidad, contacto, versión y paginación en todas las páginas.
    public function Footer(): void
    {
        $this->SetFillColor(...self::ROJO);
        $this->Rect(0, 250, 216, 1.8, 'F');
        $this->SetFillColor(...self::AZUL);
        $this->Rect(0, 251.8, 216, 27.6, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY(14, 258);
        $this->Cell(95, 4, $this->texto('SERVICOM Básculas Digitales'), 0, 0, 'L');
        $this->SetFont('Arial', '', 7.2);
        $this->SetXY(14, 265);
        $this->Cell(105, 4, 'contacto@servicombasculas.com.mx  |  servicombasculas.com.mx', 0, 0, 'L');
        $this->SetXY(116, 258);
        $this->Cell(86, 4, $this->texto('PRECISIÓN  -  SERVICIO  -  CONFIANZA'), 0, 0, 'R');
        $this->SetXY(116, 265);
        $this->Cell(86, 4, $this->texto('F-COT-01  |  Revisión 05  |  Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    // Calcula las líneas que ocupará un texto con la fuente seleccionada.
    public function numeroLineas(float $ancho, string $texto): int
    {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($ancho - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $texto);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                $i = $sep === -1 ? max($i, $j + 1) : $sep + 1;
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

    // Divide una descripción en líneas reales para continuarla entre páginas sin recortes.
    public function lineasTexto(float $ancho, string $texto): array
    {
        $limite = $ancho - 2 * $this->cMargin;
        $lineas = [];
        foreach (explode("\n", str_replace("\r", '', $texto)) as $parrafo) {
            $palabras = preg_split('/\s+/', trim($parrafo)) ?: [];
            $linea = '';
            foreach ($palabras as $palabra) {
                if ($palabra === '') {
                    continue;
                }
                $candidata = $linea === '' ? $palabra : $linea . ' ' . $palabra;
                if ($this->GetStringWidth($candidata) <= $limite) {
                    $linea = $candidata;
                    continue;
                }
                if ($linea !== '') {
                    $lineas[] = $linea;
                    $linea = '';
                }
                while ($this->GetStringWidth($palabra) > $limite) {
                    $fragmento = '';
                    while ($palabra !== '' && $this->GetStringWidth($fragmento . $palabra[0]) <= $limite) {
                        $fragmento .= $palabra[0];
                        $palabra = substr($palabra, 1);
                    }
                    $lineas[] = $fragmento;
                }
                $linea = $palabra;
            }
            $lineas[] = $linea;
        }
        return $lineas ?: [''];
    }

    // Imprime una partida completa y crea continuaciones cuando el contenido rebasa la página.
    public function partida(array $partida): void
    {
        $this->SetFont('Arial', '', 8);
        $descripcion = $this->texto((string) $partida['descripcion']);
        $lineas = $this->lineasTexto(82, $descripcion);
        $altoCompleto = max(10, count($lineas) * 4 + 3);
        if ($this->GetY() + $altoCompleto > 240 && $altoCompleto <= 194) {
            $this->tipoPagina = 'partidas';
            $this->AddPage();
        }
        $primerFragmento = true;
        while ($lineas) {
            $disponible = 240 - $this->GetY();
            $maximoLineas = (int) floor(($disponible - 3) / 4);
            if ($maximoLineas < 1) {
                $this->tipoPagina = 'partidas';
                $this->AddPage();
                continue;
            }
            $fragmento = array_splice($lineas, 0, $maximoLineas);
            $alto = max(10, count($fragmento) * 4 + 3);
            $x = 14;
            $y = $this->GetY();
            if (((int) $partida['posicion'] % 2) === 0) {
                $this->SetFillColor(...self::GRIS_CLARO);
                $this->Rect($x, $y, 188, $alto, 'F');
            }
            $this->SetTextColor(...self::TEXTO);
            if ($primerFragmento) {
                $this->SetXY($x, $y + 2.5);
                $this->Cell(18, 5, $this->formatoCantidad($partida['cantidad']), 0, 0, 'C');
                $this->Cell(28, 5, $this->texto((string) $partida['unidad']), 0, 0, 'L');
                $this->SetXY($x + 128, $y + 2.5);
                $this->Cell(30, 5, formatoMonedaMXN($partida['valor_unitario']), 0, 0, 'R');
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(30, 5, formatoMonedaMXN($partida['importe']), 0, 0, 'R');
                $this->SetFont('Arial', '', 8);
            } else {
                $this->SetFont('Arial', 'I', 7.2);
                $this->SetTextColor(...self::MUTED);
                $this->SetXY($x + 18, $y + 2.5);
                $this->Cell(28, 5, $this->texto('Continuación'), 0, 0, 'L');
                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(...self::TEXTO);
            }
            $lineaY = $y + 2.5;
            foreach ($fragmento as $linea) {
                $this->SetXY($x + 46, $lineaY);
                $this->Cell(82, 4, $linea, 0, 0, 'L');
                $lineaY += 4;
            }
            $this->SetDrawColor(...self::GRIS);
            $this->Line($x, $y + $alto, 202, $y + $alto);
            $this->SetY($y + $alto);
            $primerFragmento = false;
            if ($lineas) {
                $this->tipoPagina = 'partidas';
                $this->AddPage();
            }
        }
    }

    // Dibuja las condiciones comerciales y los importes al final de las partidas.
    public function resumenComercial(array $notas): void
    {
        $this->SetFont('Arial', '', 7.8);
        $lineasNotas = 0;
        foreach ($notas as $nota) {
            $lineasNotas += $this->numeroLineas(91, $this->texto((string) $nota['texto']));
        }
        $alto = max(72, 57 + $lineasNotas * 3.6);
        if ($this->GetY() + $alto > 243) {
            $this->tipoPagina = 'partidas';
            $this->AddPage();
        }
        $y = $this->GetY() + 4;
        $this->SetFillColor(...self::GRIS_CLARO);
        $this->SetDrawColor(...self::GRIS);
        $this->rectanguloRedondeado(14, $y, 108, $alto, 3, 'FD');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetXY(23, $y + 6);
        $this->Cell(89, 5, $this->texto('CONDICIONES COMERCIALES'), 0, 1, 'L');
        $this->SetXY(23, $y + 16);
        $this->filaCondicionComercial('TIEMPO DE ENTREGA', $this->textoTiempoEntrega());
        $this->filaCondicionComercial('CONDICIÓN DE ENTREGA', $this->textoCondicionEntrega());
        $this->filaCondicionComercial('CONDICIÓN DE PAGO', $this->textoCondicionPago());
        $this->filaCondicionComercial('GARANTÍA', $this->textoGarantia());
        $this->filaCondicionComercial('COSTOS DE ENVÍO', $this->textoCostosEnvio());
        $this->filaCondicionComercial('MONEDA', 'Pesos mexicanos (MXN)');
        if ($notas) {
            $this->SetFont('Arial', 'B', 7.2);
            $this->SetTextColor(...self::MUTED);
            $this->SetXY(23, $this->GetY() + 1);
            $this->Cell(30, 4, 'NOTA ADICIONAL', 0, 1, 'L');
            $this->SetFont('Arial', '', 7.4);
            $this->SetTextColor(...self::TEXTO);
            foreach ($notas as $nota) {
                $this->SetX(23);
                $this->MultiCell(91, 3.6, $this->texto((string) $nota['texto']), 0, 'L');
            }
        }
        $this->SetFillColor(...self::AZUL);
        $this->rectanguloRedondeado(129, $y, 73, 13, 3, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(136, $y + 4);
        $this->Cell(59, 5, 'RESUMEN', 0, 0, 'L');
        $this->SetDrawColor(...self::GRIS);
        $this->rectanguloRedondeado(129, $y + 12, 73, $alto - 12, 3, 'D');
        $filas = [
            ['Subtotal', $this->cotizacion['cot_subtotal']],
            ['IVA 16%', $this->cotizacion['cot_iva']],
            ['Total', $this->cotizacion['cot_total']],
        ];
        $yy = $y + 18;
        foreach ($filas as $indice => [$etiqueta, $valor]) {
            $esTotal = $indice === 2;
            $this->SetTextColor(...($esTotal ? self::AZUL : self::MUTED));
            $this->SetFont('Arial', $esTotal ? 'B' : '', $esTotal ? 9 : 8);
            $this->SetXY(136, $yy);
            $this->Cell(25, 5, $etiqueta, 0, 0, 'L');
            $this->Cell(34, 5, formatoMonedaMXN($valor), 0, 0, 'R');
            $yy += 7;
        }
        $this->SetY($y + $alto);
    }

    // Agrega la página final con todos los términos históricos y el bloque bancario fijo.
    public function paginaTerminos(): void
    {
        $this->tipoPagina = 'terminos';
        $this->AddPage();
        $columnas = [array_slice($this->terminos, 0, 5), array_slice($this->terminos, 5)];
        foreach ($columnas as $indiceColumna => $terminos) {
            $x = $indiceColumna === 0 ? 14 : 111;
            $y = 48;
            foreach ($terminos as $indice => $termino) {
                $numero = $indiceColumna === 0 ? $indice + 1 : $indice + 6;
                $y = $this->dibujarTermino($numero, $termino, $x, $y, 91);
            }
        }
        $this->datosBancarios(188);
    }

    // Renderiza una condición completa dentro de la columna final.
    private function dibujarTermino(int $numero, array $termino, float $x, float $y, float $ancho): float
    {
        $this->SetFillColor(...self::ROJO);
        $this->rectanguloRedondeado($x, $y + 1.1, 2.2, 2.2, 1.1, 'F');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 7.25);
        $this->SetXY($x + 4.5, $y - 0.4);
        $this->MultiCell($ancho - 4.5, 3.1, $this->texto($numero . '. ' . (string) $termino['titulo']), 0, 'L');
        $y = $this->GetY() + 0.5;
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 6.15);
        $this->SetXY($x + 4.5, $y);
        $this->MultiCell($ancho - 4.5, 2.65, $this->texto((string) $termino['texto']), 0, 'L');
        return $this->GetY() + 2.1;
    }

    // Dibuja los datos bancarios siempre pegados al pie de la última página.
    private function datosBancarios(float $y): void
    {
        $this->SetFillColor(...self::AZUL);
        $this->SetDrawColor(...self::AZUL);
        $this->rectanguloRedondeado(14, $y, 188, 55, 5, 'F');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(14, $y, 3, 22, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12.5);
        $this->SetXY(23, $y + 8);
        $this->Cell(165, 5, 'TRANSFERENCIA BANCARIA', 0, 0, 'L');
        $this->SetFont('Arial', '', 7.5);
        $this->SetXY(23, $y + 15);
        $this->Cell(165, 4, $this->texto('Datos para depósito o transferencia electrónica'), 0, 0, 'L');
        $this->SetFillColor(255, 255, 255);
        $this->rectanguloRedondeado(21, $y + 24, 174, 15, 3, 'F');
        $this->SetTextColor(...self::ROJO_OSCURO);
        $this->SetFont('Arial', 'B', 7.3);
        $this->SetXY(28, $y + 29);
        $this->Cell(20, 4, 'BANCO', 0, 0, 'L');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(150, $y + 28);
        $this->Cell(37, 5, 'Banorte', 0, 0, 'R');
        $this->SetFont('Arial', '', 7.7);
        $this->SetXY(28, $y + 34);
        $this->Cell(85, 4, $this->texto('Beneficiario: Israel Navarrete Aguilar'), 0, 0, 'L');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 6.8);
        $this->SetXY(23, $y + 44);
        $this->Cell(70, 3.5, $this->texto('NÚMERO DE CUENTA'), 0, 0, 'L');
        $this->SetXY(111, $y + 44);
        $this->Cell(77, 3.5, 'CLABE INTERBANCARIA', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10.5);
        $this->SetXY(23, $y + 49);
        $this->Cell(70, 4, '499161497', 0, 0, 'L');
        $this->SetXY(111, $y + 49);
        $this->Cell(77, 4, '072580004991614978', 0, 0, 'L');
    }

    private function encabezadoPrincipal(): void
    {
        $this->Image(__DIR__ . '/../imgs/Logo_SERVICOM_texto_negro_sin_fondo.png', 15, -1, 64);
        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetXY(104, 13);
        $this->Cell(98, 4, $this->texto('DOCUMENTO COMERCIAL'), 0, 0, 'R');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 22);
        $this->SetXY(104, 19);
        $this->Cell(98, 9, $this->texto('Cotización'), 0, 0, 'R');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(14, 35, 188, 0.9, 'F');
        $this->SetFillColor(...self::GRIS_CLARO);
        $this->SetDrawColor(...self::GRIS);
        $this->rectanguloRedondeado(14, 40, 188, 64, 4, 'FD');
        $this->SetFillColor(...self::ROJO);
        $this->rectanguloRedondeado(18, 45.2, 2.4, 2.4, 1.2, 'F');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetXY(23, 43.3);
        $this->Cell(78, 5, 'DATOS DEL CLIENTE', 0, 0, 'L');
        $this->SetFont('Arial', 'B', 7);
        $this->SetTextColor(...self::MUTED);
        $this->SetXY(20, 51);
        $this->Cell(20, 4, 'EMPRESA', 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY(20, 55);
        $this->Cell(91, 4, $this->texto((string) ($this->cotizacion['cot_empresa'] ?? '')), 0, 0, 'L');

        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY(20, 62);
        $this->Cell(42, 4, 'CONTACTO', 0, 0, 'L');
        $this->SetXY(65, 62);
        $this->Cell(46, 4, 'DEPARTAMENTO', 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 7.7);
        $this->SetXY(20, 66);
        $this->Cell(42, 4, $this->texto((string) ($this->cotizacion['cot_contacto'] ?? '')), 0, 0, 'L');
        $this->SetXY(65, 66);
        $this->Cell(46, 4, $this->texto((string) ($this->cotizacion['cot_departamento'] ?? '')), 0, 0, 'L');

        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY(20, 73);
        $this->Cell(42, 4, $this->texto('TELÉFONO'), 0, 0, 'L');
        $this->SetXY(65, 73);
        $this->Cell(46, 4, 'CORREO', 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 7.5);
        $this->SetXY(20, 77);
        $this->Cell(42, 4, $this->texto((string) ($this->cotizacion['cot_telefono'] ?? '')), 0, 0, 'L');
        $this->SetXY(65, 77);
        $this->Cell(46, 4, $this->texto((string) ($this->cotizacion['cot_correo'] ?? '')), 0, 0, 'L');

        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY(20, 84);
        $this->Cell(91, 4, $this->texto('DIRECCIÓN ASIGNADA'), 0, 0, 'L');
        $this->SetTextColor(...self::TEXTO);
        $this->SetFont('Arial', '', 7.2);
        $this->SetXY(20, 88);
        $this->MultiCell(91, 3.4, $this->texto((string) ($this->cotizacion['cot_direccion'] ?? '')), 0, 'L');

        $this->SetDrawColor(...self::GRIS);
        $this->Line(119, 47, 119, 98);
        $datos = [
            ['COTIZACIÓN', (string) $this->cotizacion['cot_numero']],
            ['FECHA', $this->fecha((string) $this->cotizacion['cot_fecha'])],
            ['VIGENCIA', $this->fecha((string) $this->cotizacion['cot_vigencia'])],
        ];
        $y = 51;
        foreach ($datos as [$etiqueta, $valor]) {
            $this->SetTextColor(...self::MUTED);
            $this->SetFont('Arial', 'B', 7);
            $this->SetXY(127, $y);
            $this->Cell(25, 4, $this->texto($etiqueta), 0, 0, 'L');
            $this->SetTextColor(...self::AZUL);
            $this->SetFont('Arial', $etiqueta === 'COTIZACIÓN' ? 'B' : '', $etiqueta === 'COTIZACIÓN' ? 10 : 7.8);
            $this->SetXY(153, $y);
            $this->Cell(41, 4, $this->texto($valor), 0, 0, 'R');
            $y += 11;
        }
        $this->encabezadoTabla(110);
        $this->SetY(120);
    }

    private function encabezadoContinuacion(): void
    {
        $this->Image(__DIR__ . '/../imgs/Logo_SERVICOM_texto_negro_sin_fondo.png', 14, 0, 46);
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(77, 12);
        $this->Cell(125, 6, $this->texto('Cotización ' . (string) $this->cotizacion['cot_numero']), 0, 0, 'R');
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(...self::MUTED);
        $this->SetXY(77, 21);
        $this->Cell(125, 4, $this->texto((string) $this->cotizacion['cot_empresa']), 0, 0, 'R');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(14, 31, 188, 0.8, 'F');
        $this->encabezadoTabla(36);
        $this->SetY(46);
    }

    private function encabezadoTerminos(): void
    {
        $this->Image(__DIR__ . '/../imgs/Logo_SERVICOM_texto_negro_sin_fondo.png', 15, -1, 54);
        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7.2);
        $this->SetXY(83, 11);
        $this->Cell(119, 4, $this->texto('TÉRMINOS COMERCIALES  -  REVISIÓN 02'), 0, 0, 'R');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 18.5);
        $this->SetXY(75, 18);
        $this->Cell(127, 8, $this->texto('Términos y condiciones'), 0, 0, 'R');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(14, 35, 188, 0.9, 'F');
        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', '', 7.2);
        $this->SetXY(14, 40);
        $this->Cell(188, 4, $this->texto('Aplicables a los servicios incluidos en la cotización y sujetos a sus condiciones particulares.'), 0, 0, 'L');
    }

    private function encabezadoTabla(float $y): void
    {
        $this->SetFillColor(...self::AZUL);
        $this->rectanguloRedondeado(14, $y, 188, 10, 2.8, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7.2);
        $this->SetXY(14, $y + 2.5);
        $this->Cell(18, 5, 'CANT.', 0, 0, 'C');
        $this->Cell(28, 5, 'UNIDAD', 0, 0, 'L');
        $this->Cell(82, 5, $this->texto('DESCRIPCIÓN'), 0, 0, 'L');
        $this->Cell(30, 5, 'VALOR UNIT.', 0, 0, 'R');
        $this->Cell(30, 5, 'IMPORTE', 0, 0, 'R');
    }

    private function arco(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k, $x3 * $this->k, ($this->h - $y3) * $this->k));
    }

    private function formatoCantidad($cantidad): string
    {
        return rtrim(rtrim(number_format((float) $cantidad, 3, '.', ''), '0'), '.');
    }

    private function fecha(string $fecha): string
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto === false ? $fecha : $objeto->format('d/m/Y');
    }

    private function textoTiempoEntrega(): string
    {
        $tipo = (string) ($this->cotizacion['cot_tiempo_entrega_tipo'] ?? '');
        $cantidad = (int) ($this->cotizacion['cot_tiempo_entrega_cantidad'] ?? 0);
        if ($tipo === 'inmediato') {
            return 'Inmediato';
        }
        if ($tipo === 'semanas') {
            return $cantidad . ($cantidad === 1 ? ' semana' : ' semanas');
        }
        if ($tipo === 'dias_habiles') {
            return $cantidad . ($cantidad === 1 ? ' día hábil' : ' días hábiles');
        }
        return 'No especificado';
    }

    private function textoCondicionEntrega(): string
    {
        return match ((string) ($this->cotizacion['cot_condicion_entrega'] ?? '')) {
            'instalaciones_cliente' => 'Instalaciones del cliente',
            'paqueteria' => 'Paquetería',
            'instalaciones_servicom' => 'Instalaciones de SERVICOM Básculas Digitales',
            default => 'No especificada',
        };
    }

    private function textoCondicionPago(): string
    {
        $tipo = (string) ($this->cotizacion['cot_condicion_pago_tipo'] ?? '');
        if ($tipo === 'anticipado') {
            return 'Pago anticipado';
        }
        if ($tipo === 'anticipado_total') {
            return 'Pago 100% anticipado';
        }
        if ($tipo === 'anticipo_saldo') {
            $anticipo = (float) ($this->cotizacion['cot_pago_anticipo_porcentaje'] ?? 0);
            $saldo = 100 - $anticipo;
            $momento = ($this->cotizacion['cot_pago_saldo_momento'] ?? '') === 'contra_aviso_entrega'
                ? 'contra aviso de entrega'
                : 'al finalizar';
            return $this->formatoPorcentaje($anticipo) . '% de anticipo y '
                . $this->formatoPorcentaje($saldo) . '% ' . $momento;
        }
        if ($tipo === 'credito') {
            $dias = (int) ($this->cotizacion['cot_pago_credito_dias'] ?? 0);
            return $dias . ($dias === 1 ? ' día de crédito' : ' días de crédito');
        }
        return 'No especificada';
    }

    private function textoGarantia(): string
    {
        $tipo = (string) ($this->cotizacion['cot_garantia_tipo'] ?? '');
        if ($tipo === 'sin_garantia') {
            return 'Sin garantía especificada';
        }
        if (!in_array($tipo, ['producto', 'mano_obra'], true)) {
            return 'No especificada';
        }
        $cantidad = (int) ($this->cotizacion['cot_garantia_vigencia'] ?? 0);
        $unidad = ($this->cotizacion['cot_garantia_unidad'] ?? '') === 'meses'
            ? ($cantidad === 1 ? 'mes' : 'meses')
            : ($cantidad === 1 ? 'día' : 'días');
        $alcance = $tipo === 'producto' ? 'Producto por defectos de fabricación' : 'Mano de obra';
        return $alcance . ': ' . $cantidad . ' ' . $unidad;
    }

    private function textoCostosEnvio(): string
    {
        return match ((string) ($this->cotizacion['cot_costos_envio'] ?? '')) {
            'incluye' => 'Incluye costos de envío',
            'no_incluye' => 'No incluye costos de envío',
            default => 'No especificado',
        };
    }

    private function filaCondicionComercial(string $etiqueta, string $valor): void
    {
        $y = $this->GetY();
        $this->SetFont('Arial', 'B', 7.2);
        $this->SetTextColor(...self::MUTED);
        $this->SetX(23);
        $this->Cell(36, 4, $this->texto($etiqueta), 0, 0, 'L');
        $this->SetFont('Arial', '', 7.4);
        $this->SetTextColor(...self::TEXTO);
        $this->SetXY(59, $y);
        $this->MultiCell(54, 3.8, $this->texto($valor), 0, 'L');
        $this->SetY(max($y + 4, $this->GetY()));
    }

    private function formatoPorcentaje(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}

$pdf = new CotizacionPDF($cotizacion, $terminos);
$pdf->AddPage();
foreach ($partidas as $partida) {
    $pdf->partida($partida);
}
$pdf->resumenComercial($notas);
$pdf->paginaTerminos();

$archivo = basename((string) $cotizacion['cot_archivo']);
if ($archivo === '' || !preg_match('/\.pdf$/i', $archivo)) {
    $archivo = 'COT SERVICOM ' . (string) $cotizacion['cot_numero'] . '.pdf';
}
$ruta = __DIR__ . '/../filesPDF/' . $archivo;
$pdf->Output('F', $ruta);
$pdf->Output('I', $archivo);
