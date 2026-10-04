<?php
require __DIR__ . '/fpdf.php';
require_once __DIR__ . '/../backend/helpers/folio_informe.php';

// Obtiene un valor escalar del POST sin emitir avisos por claves ausentes.
function datoPost(string $clave, string $predeterminado = ''): string
{
    $valor = $_POST[$clave] ?? $predeterminado;
    return is_scalar($valor) ? trim((string) $valor) : $predeterminado;
}

// Convierte un dato numérico del POST a float o devuelve null si no es válido.
function numeroPost(string $clave): ?float
{
    $valor = str_replace(',', '.', datoPost($clave));
    return is_numeric($valor) ? (float) $valor : null;
}

// Obtiene la cantidad de decimales escrita en d para conservarla en todas las indicaciones.
function decimalesDivisionRealPost(): int
{
    $texto = strtolower(str_replace(',', '.', datoPost('d')));
    if (!is_numeric($texto) || (float) $texto <= 0) {
        return 3;
    }
    [$mantisa, $exponenteTexto] = array_pad(explode('e', $texto, 2), 2, '0');
    $decimalesMantisa = str_contains($mantisa, '.') ? strlen(explode('.', $mantisa, 2)[1]) : 0;
    return min(8, max(0, $decimalesMantisa - (int) $exponenteTexto));
}

// Presenta una indicación con los ceros decimales definidos por la división real d.
function indicacionPost(string $clave): string
{
    $valor = numeroPost($clave);
    return $valor === null ? datoPost($clave) : number_format($valor, decimalesDivisionRealPost(), '.', '');
}

// Verifica en el servidor que una indicación sea un múltiplo de la división real d.
function esIndicacionPostValida(string $clave, float $divisionReal): bool
{
    $valor = numeroPost($clave);
    if ($valor === null) {
        return false;
    }
    $cociente = $valor / $divisionReal;
    $tolerancia = max(1e-9, abs($cociente) * 1e-10);
    return abs($cociente - round($cociente)) <= $tolerancia;
}

// Impide crear el PDF si alguna prueba aplicable contiene indicaciones incongruentes con d.
function validarIndicacionesPost(): array
{
    $divisionReal = numeroPost('d');
    if ($divisionReal === null || $divisionReal <= 0) {
        return ['La división real d debe ser mayor que cero.'];
    }
    $errores = [];
    foreach (['inicial', 'final'] as $fase) {
        foreach (['repetibilidad' => 5, 'excentricidad' => 5] as $prueba => $cantidad) {
            $prefijo = $fase . '_' . $prueba;
            if (datoPost($prefijo . '_no_aplica') === '1') {
                continue;
            }
            for ($i = 1; $i <= $cantidad; $i++) {
                if (!esIndicacionPostValida($prefijo . '_lectura_' . $i, $divisionReal)) {
                    $errores[] = $prefijo;
                    break;
                }
            }
        }
        $prefijo = $fase . '_exactitud';
        if (datoPost($prefijo . '_no_aplica') !== '1') {
            for ($i = 0; $i <= 5; $i++) {
                if (!esIndicacionPostValida($prefijo . '_indicacion_' . $i, $divisionReal)) {
                    $errores[] = $prefijo;
                    break;
                }
            }
        }
    }
    return array_values(array_unique($errores));
}

class InformePDF extends FPDF
{
    private string $folio = '';
    private string $versionFormato = 'Revisión 02';
    private string $leyenda = 'Pruebas técnicas basadas en criterios metrológicos de la NOM-010-SCFI-1994. El alcance corresponde al procedimiento interno de servicio y no constituye por sí mismo una verificación oficial de cumplimiento de la NOM.';
    private const AZUL = [18, 56, 94];
    private const ROJO = [214, 45, 45];
    private const ROJO_OSCURO = [183, 31, 36];
    private const GRIS_CLARO = [242, 244, 246];
    private const GRIS = [216, 222, 228];
    private const TEXTO = [24, 50, 75];
    private const MUTED = [102, 116, 130];

    public function establecerFolio(string $folio): void
    {
        $this->folio = $folio;
    }

    // Convierte texto UTF-8 a la codificación compatible con las fuentes base de FPDF.
    public function texto(string $valor): string
    {
        $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $valor);
        return $convertido === false ? $valor : $convertido;
    }

    public function Header(): void
    {
        $this->SetFillColor(...self::ROJO_OSCURO);
        $this->Rect(0, 0, 58, 3.2, 'F');
        $this->SetFillColor(...self::ROJO);
        $this->Rect(58, 0, 158, 3.2, 'F');
        $rutaLogo = __DIR__ . '/../imgs/Logo_SERVICOM_texto_negro_sin_fondo.png';
        if (is_file($rutaLogo)) {
            $this->Image($rutaLogo, 14, 8, 64);
        }
        $this->SetTextColor(...self::MUTED);
        $this->SetFont('Arial', 'B', 7.2);
        $this->SetXY(116, 10);
        $this->Cell(86, 4, $this->texto('DOCUMENTO TÉCNICO'), 0, 0, 'R');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 15.5);
        $this->SetXY(104, 16);
        $this->Cell(98, 7, $this->texto('Informe de servicio'), 0, 0, 'R');
        $this->SetFont('Arial', 'B', 7.6);
        $this->SetXY(116, 25);
        $this->Cell(86, 4, $this->texto('Folio ' . $this->folio), 0, 0, 'R');
        $this->SetDrawColor(...self::ROJO);
        $this->SetLineWidth(0.55);
        $this->Line(14, 36, 202, 36);
        $this->SetLineWidth(0.2);
        $this->SetY(40);
    }

    public function Footer(): void
    {
        $this->SetFillColor(...self::ROJO);
        $this->Rect(0, 250, 216, 1.8, 'F');
        $this->SetFillColor(...self::AZUL);
        $this->Rect(0, 251.8, 216, 27.6, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY(14, 257);
        $this->Cell(95, 4, $this->texto('SERVICOM Básculas Digitales'), 0, 0, 'L');
        $this->SetFont('Arial', '', 6.9);
        $this->SetXY(14, 264);
        $this->Cell(108, 4, 'contacto@servicombasculas.com.mx  |  servicombasculas.com.mx', 0, 0, 'L');
        $this->SetXY(116, 257);
        $this->Cell(86, 4, $this->texto('PRECISIÓN  -  SERVICIO  -  CONFIANZA'), 0, 0, 'R');
        $this->SetXY(116, 264);
        $this->Cell(86, 4, $this->texto('F-INF-01  |  ' . $this->versionFormato . '  |  Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
        $this->SetTextColor(220, 229, 238);
        $this->SetFont('Arial', 'I', 4.7);
        $this->SetXY(14, 270);
        $this->Cell(188, 3, $this->texto($this->leyenda), 0, 0, 'L');
    }

    // Dibuja rectángulos redondeados como los utilizados en el formato de cotización.
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

    private function arco(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }

    // Divide un texto en un máximo de líneas y agrega puntos suspensivos si debe truncarse.
    private function lineasLimitadas(string $valor, float $ancho, int $maximoLineas): array
    {
        $texto = $this->texto(trim(preg_replace('/\s+/', ' ', $valor) ?? $valor));
        if ($texto === '') {
            return [''];
        }
        $palabras = preg_split('/\s+/', $texto) ?: [$texto];
        $lineas = [];
        $linea = '';
        foreach ($palabras as $indice => $palabra) {
            $candidata = $linea === '' ? $palabra : $linea . ' ' . $palabra;
            if ($linea !== '' && $this->GetStringWidth($candidata) > $ancho) {
                $lineas[] = $linea;
                $linea = $palabra;
                if (count($lineas) === $maximoLineas) {
                    $restante = implode(' ', array_slice($palabras, $indice));
                    $lineas[$maximoLineas - 1] = $this->recortarLinea($lineas[$maximoLineas - 1] . ' ' . $restante, $ancho);
                    return $lineas;
                }
            } else {
                $linea = $candidata;
            }
        }
        if ($linea !== '' && count($lineas) < $maximoLineas) {
            $lineas[] = $linea;
        }
        return $lineas;
    }

    // Recorta una línea al ancho disponible conservando una terminación visible.
    private function recortarLinea(string $texto, float $ancho): string
    {
        $texto = trim($texto);
        while ($texto !== '' && $this->GetStringWidth($texto . '...') > $ancho) {
            $texto = substr($texto, 0, -1);
        }
        return rtrim($texto) . '...';
    }

    // Dibuja texto limitado dentro de una celda fija sin alterar la composición de la página.
    private function textoCelda(float $x, float $y, float $ancho, float $alto, string $valor, string $alineacion = 'L'): void
    {
        $altoLinea = 3.1;
        $maximoLineas = max(1, (int) floor(($alto - 1.2) / $altoLinea));
        $lineas = $this->lineasLimitadas($valor, $ancho - 2, $maximoLineas);
        foreach ($lineas as $indice => $linea) {
            $this->SetXY($x + 1, $y + 0.6 + $indice * $altoLinea);
            $this->Cell($ancho - 2, $altoLinea, $linea, 0, 0, $alineacion);
        }
    }

    // Dibuja una fila de altura fija para mantener posiciones idénticas entre informes.
    public function filaFija(float $y, array $valores, array $anchos, float $alto = 6, array $alineaciones = [], bool $relleno = false, float $xInicial = 14): void
    {
        $this->SetDrawColor(...self::GRIS);
        $this->SetTextColor(...self::TEXTO);
        $x = $xInicial;
        foreach ($valores as $indice => $valor) {
            $ancho = (float) $anchos[$indice];
            $this->Rect($x, $y, $ancho, $alto, $relleno ? 'DF' : 'D');
            $this->textoCelda($x, $y, $ancho, $alto, (string) $valor, $alineaciones[$indice] ?? 'L');
            $x += $ancho;
        }
    }

    // Dibuja un título de sección en una coordenada fija.
    public function tituloFijo(float $y, string $titulo, float $alto = 6): void
    {
        $this->SetFillColor(...self::AZUL);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.3);
        $this->rectanguloRedondeado(14, $y, 188, $alto, 2.4, 'F');
        $this->SetXY(21, $y + 0.7);
        $this->Cell(174, $alto - 1.2, $this->texto($titulo), 0, 0, 'L');
    }

    // Dibuja un subtítulo compacto dentro de una página de pruebas.
    public function subtituloFijo(float $y, string $titulo): void
    {
        $this->SetFillColor(...self::AZUL);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.2);
        $this->rectanguloRedondeado(14, $y, 188, 6, 2.2, 'F');
        $this->SetFillColor(...self::ROJO);
        $this->rectanguloRedondeado(18, $y + 1.8, 2.3, 2.3, 1.15, 'F');
        $this->SetXY(23, $y + 0.7);
        $this->Cell(173, 4.6, $this->texto($titulo), 0, 0, 'L');
    }

    // Dibuja un campo narrativo con tamaño fijo y contenido truncado de forma controlada.
    public function bloqueFijo(float $y, string $titulo, string $contenido, float $altoCaja = 11): void
    {
        $this->tituloFijo($y, $titulo, 5);
        $this->SetFont('Arial', '', 6.7);
        $this->Rect(10, $y + 5.5, 196, $altoCaja);
        $this->textoCelda(10, $y + 5.5, 196, $altoCaja, $contenido !== '' ? $contenido : 'Sin información registrada.');
    }

    // Reúne el cierre del servicio en una sola tarjeta, sin dispersar la información.
    public function bloqueCierreServicio(float $y, float $alto): void
    {
        $this->SetFillColor(...self::GRIS_CLARO);
        $this->SetDrawColor(...self::GRIS);
        $this->rectanguloRedondeado(14, $y, 188, $alto, 3.5, 'FD');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 9.2);
        $this->SetXY(22, $y + 5);
        $this->Cell(172, 5, $this->texto('CIERRE DEL SERVICIO'), 0, 0, 'L');

        $bloques = [
            ['OBSERVACIONES', datoPost('observaciones')],
            ['TRABAJO REALIZADO', datoPost('trabajo_realizado')],
            ['RECOMENDACIONES', datoPost('recomendaciones')],
            ['ATENCIÓN / SERVICIOS URGENTES', datoPost('atencion_urgente')],
        ];
        $ancho = 87;
        $altoBloque = ($alto - 16) / 2;
        foreach ($bloques as $indice => [$titulo, $contenido]) {
            $columna = $indice % 2;
            $fila = intdiv($indice, 2);
            $x = 22 + $columna * 91;
            $yy = $y + 13 + $fila * $altoBloque;
            $this->SetFillColor(...self::ROJO);
            $this->rectanguloRedondeado($x, $yy + 1, 2.2, 2.2, 1.1, 'F');
            $this->SetTextColor(...self::AZUL);
            $this->SetFont('Arial', 'B', 6.8);
            $this->SetXY($x + 4.5, $yy);
            $this->Cell($ancho - 4.5, 4, $this->texto($titulo), 0, 0, 'L');
            $this->SetTextColor(...self::TEXTO);
            $this->SetFont('Arial', '', 5.7);
            $this->textoCelda($x + 3.5, $yy + 4.2, $ancho - 3.5, $altoBloque - 4.8, $contenido !== '' ? $contenido : 'Sin información registrada.');
            if ($columna === 0) {
                $this->SetDrawColor(...self::GRIS);
                $this->Line(108, $yy, 108, $yy + $altoBloque - 2);
            }
        }
    }

    // Dibuja una línea discontinua simple sin depender de extensiones de FPDF.
    private function lineaDiscontinua(float $x1, float $y1, float $x2, float $y2, float $segmento = 2.2): void
    {
        $distancia = hypot($x2 - $x1, $y2 - $y1);
        if ($distancia <= 0) {
            return;
        }
        $pasos = max(1, (int) floor($distancia / $segmento));
        for ($i = 0; $i < $pasos; $i += 2) {
            $inicio = $i / $pasos;
            $fin = min(1, ($i + 1) / $pasos);
            $this->Line($x1 + ($x2 - $x1) * $inicio, $y1 + ($y2 - $y1) * $inicio, $x1 + ($x2 - $x1) * $fin, $y1 + ($y2 - $y1) * $fin);
        }
    }

    // Devuelve los límites de clase ya utilizados por el cálculo metrológico del formulario.
    private function configuracionClaseGrafica(string $clase): ?array
    {
        $configuraciones = [
            'Ordinaria' => [50, 200],
            'Media' => [500, 2000],
            'Fina' => [5000, 20000],
            'Especial' => [50000, 200000],
        ];
        return $configuraciones[$clase] ?? null;
    }

    // Selecciona un paso legible 1, 2 o 5 multiplicado por una potencia de diez.
    private function pasoLegibleGrafica(float $valor): float
    {
        if ($valor <= 0) {
            return 1.0;
        }
        $potencia = 10 ** floor(log10($valor));
        $normalizado = $valor / $potencia;
        if ($normalizado <= 1) {
            $factor = 1;
        } elseif ($normalizado <= 2) {
            $factor = 2;
        } elseif ($normalizado <= 5) {
            $factor = 5;
        } else {
            $factor = 10;
        }
        return $factor * $potencia;
    }

    // Formatea etiquetas numéricas compactas sin perder decimales significativos.
    private function etiquetaGrafica(float $valor, float $paso): string
    {
        $decimales = max(0, min(6, (int) ceil(-log10(max($paso, 0.000001)))));
        $texto = number_format(abs($valor) < 1e-12 ? 0 : $valor, $decimales, '.', '');
        $texto = rtrim(rtrim($texto, '0'), '.');
        return $texto === '-0' || $texto === '' ? '0' : $texto;
    }

    // Dibuja un límite EMT mediante horizontales y saltos verticales, nunca diagonales.
    private function limiteEscalonado(array $tramos, float $signo, callable $convertirX, callable $convertirY): void
    {
        foreach ($tramos as $indice => $tramo) {
            [$inicio, $fin, $emt] = $tramo;
            $yActual = $convertirY($signo * $emt);
            $this->lineaDiscontinua($convertirX($inicio), $yActual, $convertirX($fin), $yActual);
            if (isset($tramos[$indice + 1])) {
                $ySiguiente = $convertirY($signo * $tramos[$indice + 1][2]);
                $this->lineaDiscontinua($convertirX($fin), $yActual, $convertirX($fin), $ySiguiente, 1.2);
            }
        }
    }

    // Dibuja la gráfica de exactitud con banda de tolerancia, escala adaptativa y alertas fuera de EMT.
    public function graficaExactitud(string $fase, float $x, float $y, float $ancho, float $alto, string $titulo): void
    {
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(...self::GRIS);
        $this->rectanguloRedondeado($x, $y, $ancho, $alto, 2.5, 'FD');
        $this->SetTextColor(...self::AZUL);
        $this->SetFont('Arial', 'B', 7.6);
        $this->SetXY($x + 4, $y + 1.3);
        $this->Cell($ancho - 8, 4, $this->texto($titulo), 0, 0, 'L');

        $cargas = [];
        $errores = [];
        $emts = [];
        for ($i = 0; $i <= 5; $i++) {
            $cargas[] = numeroPost($fase . '_exactitud_carga_' . $i);
            $errores[] = numeroPost($fase . '_exactitud_error_' . $i);
            $emts[] = numeroPost($fase . '_exactitud_emt_' . $i);
        }
        $divisionE = numeroPost('e');
        $minimoInstrumento = numeroPost('min');
        $maximoInstrumento = numeroPost('max');
        $configuracion = $this->configuracionClaseGrafica(datoPost('clase'));
        if (in_array(null, $cargas, true) || in_array(null, $errores, true) || in_array(null, $emts, true)
            || $divisionE === null || $divisionE <= 0 || $minimoInstrumento === null || $maximoInstrumento === null
            || $maximoInstrumento <= $minimoInstrumento || $configuracion === null) {
            $this->SetFont('Arial', 'I', 7);
            $this->SetXY($x + 2, $y + $alto / 2 - 2);
            $this->Cell($ancho - 4, 4, $this->texto('Sin datos suficientes para graficar.'), 0, 0, 'C');
            return;
        }

        $plotX = $x + 18;
        $plotY = $y + 13;
        $plotW = $ancho - 24;
        $plotH = $alto - 26;
        $minCarga = 0.0;
        $maxCarga = max(array_merge($cargas, [$maximoInstrumento]));
        $rangoCarga = max($maxCarga - $minCarga, 1.0);

        $limitesCarga = [$configuracion[0] * $divisionE, $configuracion[1] * $divisionE];
        $multiplicador = $minCarga > $limitesCarga[1] ? 3 : ($minCarga > $limitesCarga[0] ? 2 : 1);
        $inicioTramo = $minCarga;
        $tramos = [];
        foreach ($limitesCarga as $limiteCarga) {
            if ($limiteCarga <= $minCarga) {
                continue;
            }
            if ($limiteCarga >= $maxCarga) {
                break;
            }
            $tramos[] = [$inicioTramo, $limiteCarga, $multiplicador * $divisionE];
            $inicioTramo = $limiteCarga;
            $multiplicador = min(3, $multiplicador + 1);
        }
        $tramos[] = [$inicioTramo, $maxCarga, min(3, $multiplicador) * $divisionE];

        $emtMaximo = max(array_column($tramos, 2));
        $maximoRelevante = max(array_merge(array_map('abs', $errores), [$emtMaximo, $divisionE]));
        $pasoY = $this->pasoLegibleGrafica(($maximoRelevante * 1.10) / 4);
        $limiteY = max($pasoY, ceil(($maximoRelevante * 1.10) / $pasoY) * $pasoY);
        $cantidadTicks = (int) round($limiteY / $pasoY);

        $convertirX = static fn(float $carga): float => $plotX + (($carga - $minCarga) / $rangoCarga) * $plotW;
        $convertirY = static fn(float $valor): float => $plotY + (($limiteY - $valor) / (2 * $limiteY)) * $plotH;
        $puntosX = array_map($convertirX, $cargas);
        $errorY = array_map($convertirY, $errores);

        $fueraDeTolerancia = 0;
        foreach ($errores as $indice => $error) {
            $resultadoPunto = strtoupper(datoPost($fase . '_exactitud_resultado_' . $indice));
            if ($resultadoPunto === 'NO CUMPLE' || abs($error) > $emts[$indice] + 1e-12) {
                $fueraDeTolerancia++;
            }
        }

        $this->SetFont('Arial', 'B', 5.5);
        $this->SetTextColor(...($fueraDeTolerancia > 0 ? self::ROJO_OSCURO : self::AZUL));
        $this->SetXY($x + $ancho - 51, $y + 1.3);
        $estadoGrafica = $fueraDeTolerancia > 0
            ? $fueraDeTolerancia . ' PUNTO' . ($fueraDeTolerancia === 1 ? '' : 'S') . ' FUERA DE EMT'
            : 'TODOS LOS PUNTOS DENTRO DEL EMT';
        $this->Cell(47, 4, $this->texto($estadoGrafica), 0, 0, 'R');

        // La franja escalonada permite reconocer de inmediato la zona admisible.
        $this->SetFillColor(231, 240, 247);
        foreach ($tramos as [$inicio, $fin, $emt]) {
            $xInicio = $convertirX($inicio);
            $xFin = $convertirX($fin);
            $ySuperior = $convertirY($emt);
            $yInferior = $convertirY(-$emt);
            $this->Rect($xInicio, $ySuperior, max(0.2, $xFin - $xInicio), $yInferior - $ySuperior, 'F');
        }

        $this->SetDrawColor(...self::GRIS);
        $this->Rect($plotX, $plotY, $plotW, $plotH);
        $this->SetFont('Arial', '', 5.5);

        for ($tick = -$cantidadTicks; $tick <= $cantidadTicks; $tick++) {
            $valorTick = $tick * $pasoY;
            $tickY = $convertirY($valorTick);
            $this->SetDrawColor($tick === 0 ? 92 : 215, $tick === 0 ? 106 : 222, $tick === 0 ? 120 : 229);
            $this->SetLineWidth($tick === 0 ? 0.35 : 0.12);
            $this->Line($plotX, $tickY, $plotX + $plotW, $tickY);
            $this->SetTextColor(...self::MUTED);
            $this->SetXY($x + 1, $tickY - 1.6);
            $this->Cell(15.5, 3.2, $this->etiquetaGrafica($valorTick, $pasoY), 0, 0, 'R');
        }

        // Las verticales se limitan a los puntos realmente medidos para evitar ruido visual.
        $this->SetDrawColor(225, 230, 235);
        $this->SetLineWidth(0.12);
        foreach (array_values(array_unique(array_map(static fn(float $valor): string => (string) $valor, $cargas))) as $cargaUnica) {
            $gridX = $convertirX((float) $cargaUnica);
            $this->Line($gridX, $plotY, $gridX, $plotY + $plotH);
        }

        $this->SetDrawColor(...self::ROJO);
        $this->SetLineWidth(0.28);
        $this->limiteEscalonado($tramos, 1, $convertirX, $convertirY);
        $this->limiteEscalonado($tramos, -1, $convertirX, $convertirY);
        $this->SetDrawColor(...self::AZUL);
        $this->SetLineWidth(0.52);
        $this->SetTextColor(...self::TEXTO);
        $cantidadPuntos = count($cargas);
        for ($i = 0; $i < $cantidadPuntos; $i++) {
            if ($i < $cantidadPuntos - 1) {
                $this->SetDrawColor(...self::AZUL);
                $this->SetLineWidth(0.52);
                $this->Line($puntosX[$i], $errorY[$i], $puntosX[$i + 1], $errorY[$i + 1]);
            }
            $resultadoPunto = strtoupper(datoPost($fase . '_exactitud_resultado_' . $i));
            $puntoFuera = $resultadoPunto === 'NO CUMPLE' || abs($errores[$i]) > $emts[$i] + 1e-12;
            $this->SetFillColor(...($puntoFuera ? self::ROJO : self::AZUL));
            $this->SetDrawColor(255, 255, 255);
            $this->Rect($puntosX[$i] - 0.95, $errorY[$i] - 0.95, 1.9, 1.9, 'DF');
            if ($i === 0) {
                $this->SetXY($puntosX[$i] - 6, $plotY + $plotH + 1);
                $this->Cell(5, 3, '0', 0, 0, 'R');
            } elseif ($i === 1 && abs($puntosX[$i] - $puntosX[0]) < 9) {
                $this->SetXY($puntosX[$i] + 1, $plotY + $plotH + 1);
                $this->Cell(12, 3, $this->texto((string) $cargas[$i]), 0, 0, 'L');
            } else {
                $this->SetXY($puntosX[$i] - 10, $plotY + $plotH + 1);
                $this->Cell(20, 3, $this->texto((string) $cargas[$i]), 0, 0, 'C');
            }
        }
        $unidad = datoPost('unidad');
        $sufijoUnidad = $unidad !== '' ? ' (' . $unidad . ')' : '';
        $this->SetLineWidth(0.2);
        $this->SetFont('Arial', '', 5.5);
        $this->SetTextColor(...self::MUTED);
        $this->SetXY($plotX, $y + $alto - 5.2);
        $this->Cell($plotW, 3, $this->texto('Carga aplicada' . $sufijoUnidad), 0, 0, 'C');
        $this->SetXY($x + 1.5, $plotY - 4.2);
        $this->Cell(15.5, 3, $this->texto('Error' . $sufijoUnidad), 0, 0, 'R');

        $leyendaY = $y + 6.7;
        $this->SetFillColor(231, 240, 247);
        $this->SetDrawColor(181, 198, 212);
        $this->Rect($x + 4, $leyendaY, 5, 2.4, 'DF');
        $this->SetTextColor(...self::MUTED);
        $this->SetXY($x + 10, $leyendaY - 0.3);
        $this->Cell(33, 3, $this->texto('Zona admisible'), 0, 0, 'L');
        $this->SetDrawColor(...self::AZUL);
        $this->SetLineWidth(0.52);
        $this->Line($x + 44, $leyendaY + 1.2, $x + 50, $leyendaY + 1.2);
        $this->SetXY($x + 51, $leyendaY - 0.3);
        $this->Cell(25, 3, $this->texto('Error medido'), 0, 0, 'L');
        $this->SetFillColor(...self::ROJO);
        $this->Rect($x + 77, $leyendaY + 0.3, 1.8, 1.8, 'F');
        $this->SetXY($x + 80, $leyendaY - 0.3);
        $this->Cell(32, 3, $this->texto('Fuera de EMT'), 0, 0, 'L');
        $this->SetDrawColor(...self::ROJO);
        $this->SetLineWidth(0.28);
        $this->lineaDiscontinua($x + 113, $leyendaY + 1.2, $x + 120, $leyendaY + 1.2, 1.1);
        $this->SetXY($x + 121, $leyendaY - 0.3);
        $this->Cell(28, 3, $this->texto('Límites EMT'), 0, 0, 'L');
        $this->SetLineWidth(0.2);
    }
}

// Dibuja la página 1 con todas las secciones generales en posiciones predeterminadas.
function dibujarPaginaGeneral(InformePDF $pdf, string $folio): void
{
    $pdf->SetTextColor(24, 50, 75);
    $pdf->SetFont('Arial', '', 6.3);
    $pdf->tituloFijo(42, 'IDENTIFICACIÓN Y DATOS DEL CLIENTE', 7);
    $pdf->filaFija(49, ['Folio', $folio, 'Fecha', datoPost('inf_fecha')], [26, 68, 26, 68], 6);
    $pdf->filaFija(55, ['Empresa', datoPost('nombre_empresa'), 'Contacto', datoPost('nombre_contacto')], [26, 68, 26, 68], 7);
    $pdf->filaFija(62, ['Dirección', datoPost('dir_empresa'), 'Correo', datoPost('correo_contacto')], [26, 68, 26, 68], 12);
    $pdf->tituloFijo(76, 'INSTRUMENTO', 7);
    $filasInstrumento = [
        ['Descripción', datoPost('desc_inst'), 'Marca', datoPost('marca_inst')],
        ['Modelo', datoPost('modelo_inst'), 'ID', datoPost('id_inst')],
        ['Serie', datoPost('serie_inst'), 'Unidad', datoPost('unidad')],
        ['Max', datoPost('max'), 'd', datoPost('d')],
        ['e', datoPost('e'), 'Min', datoPost('min')],
        ['Clase', datoPost('clase'), '', ''],
    ];
    foreach ($filasInstrumento as $indice => $fila) {
        $pdf->filaFija(83 + $indice * 5.5, $fila, [26, 68, 26, 68], 5.5);
    }
    $reactivos = [
        1 => 'Identificación y placa legible.',
        2 => 'Display / escala / unidad legibles.',
        3 => 'Cero, tara y estabilidad funcionan correctamente.',
        4 => 'Plataforma / plato / estructura en buen estado.',
        5 => 'Nivelación e instalación correctas.',
        6 => 'Limpieza y funcionamiento general.',
    ];
    $pdf->tituloFijo(120, 'INSPECCIÓN VISUAL Y FUNCIONAL', 7);
    $pdf->SetFillColor(242, 244, 246);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(127, ['Reactivo', 'Estado', 'Observación'], [88, 28, 72], 5, ['L', 'C', 'L'], true);
    $pdf->SetFont('Arial', '', 6.1);
    foreach ($reactivos as $numero => $reactivo) {
        $pdf->filaFija(132 + ($numero - 1) * 5, [$numero . '. ' . $reactivo, datoPost('inspeccion_' . $numero . '_estado'), datoPost('inspeccion_' . $numero . '_observacion')], [88, 28, 72], 5, ['L', 'C', 'L']);
    }
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(162, ['Resultado general', datoPost('inspeccion_resultado', 'PENDIENTE')], [43, 145], 6);
    $pdf->bloqueCierreServicio(172, 72);
}

// Dibuja repetibilidad en el área fija superior de una página de pruebas.
function dibujarRepetibilidadFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_repetibilidad';
    $pdf->subtituloFijo(52, 'REPETIBILIDAD');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->filaFija(58, ['Carga aplicada', datoPost($prefijo . '_carga'), 'Resultado', datoPost($prefijo . '_resultado', 'PENDIENTE')], [29, 65, 29, 65], 6);
    $pdf->SetFillColor(242, 244, 246);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(64, ['Prueba', 'Indicación'], [62, 126], 5, ['C', 'C'], true);
    $pdf->SetFont('Arial', '', 6.3);
    for ($i = 1; $i <= 5; $i++) {
        $pdf->filaFija(69 + ($i - 1) * 4.2, [(string) $i, indicacionPost($prefijo . '_lectura_' . $i)], [62, 126], 4.2, ['C', 'C']);
    }
    $pdf->filaFija(90, ['Diferencia máxima encontrada', datoPost($prefijo . '_diferencia'), 'EMT aplicable', datoPost($prefijo . '_emt')], [43, 51, 33, 61], 6);
}

// Dibuja excentricidad en el área fija central de una página de pruebas.
function dibujarExcentricidadFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_excentricidad';
    $pdf->subtituloFijo(100, 'EXCENTRICIDAD');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->filaFija(106, ['Carga aplicada', datoPost($prefijo . '_carga'), 'Resultado', datoPost($prefijo . '_resultado', 'PENDIENTE')], [29, 65, 29, 65], 6);
    $pdf->SetFillColor(242, 244, 246);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(112, ['Posición', 'Indicación'], [76, 112], 5, ['C', 'C'], true);
    $pdf->SetFont('Arial', '', 6.3);
    for ($i = 1; $i <= 5; $i++) {
        $posicion = $i === 1 ? '1 - Centro / referencia' : (string) $i;
        $pdf->filaFija(117 + ($i - 1) * 4.2, [$posicion, indicacionPost($prefijo . '_lectura_' . $i)], [76, 112], 4.2, ['L', 'C']);
    }
    $pdf->filaFija(138, ['Diferencia máxima encontrada', datoPost($prefijo . '_diferencia_maxima'), 'EMT aplicable', datoPost($prefijo . '_emt')], [43, 51, 33, 61], 6);
}

// Dibuja exactitud en un área fija y conserva las seis columnas aprobadas.
function dibujarExactitudFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_exactitud';
    $pdf->subtituloFijo(148, 'EXACTITUD');
    $pdf->SetFillColor(242, 244, 246);
    $pdf->SetFont('Arial', 'B', 6.1);
    $anchos = [16, 34, 36, 29, 29, 44];
    $alineaciones = ['C', 'C', 'C', 'C', 'C', 'C'];
    $pdf->filaFija(154, ['Punto', 'Carga', 'Indicación', 'Error', 'EMT', 'Resultado'], $anchos, 5, $alineaciones, true);
    $pdf->SetFont('Arial', '', 6.1);
    for ($i = 0; $i <= 5; $i++) {
        $pdf->filaFija(159 + $i * 4, [(string) $i, datoPost($prefijo . '_carga_' . $i), indicacionPost($prefijo . '_indicacion_' . $i), datoPost($prefijo . '_error_' . $i), datoPost($prefijo . '_emt_' . $i), datoPost($prefijo . '_resultado_' . $i)], $anchos, 4, $alineaciones);
    }
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(183, ['Resultado general', datoPost($prefijo . '_resultado', 'PENDIENTE')], [43, 145], 6);
}

// Construye una página fija de pruebas y coloca la gráfica en coordenadas absolutas idénticas.
function dibujarPaginaPruebas(InformePDF $pdf, string $fase, string $tituloPagina, string $tituloGrafica): void
{
    $pdf->tituloFijo(42, $tituloPagina, 7);
    dibujarRepetibilidadFija($pdf, $fase);
    dibujarExcentricidadFija($pdf, $fase);
    dibujarExactitudFija($pdf, $fase);
    $pdf->graficaExactitud($fase, 14, 191, 188, 53, $tituloGrafica);
}

$erroresDivisionReal = validarIndicacionesPost();
if ($erroresDivisionReal !== []) {
    http_response_code(422);
    exit('División real no válida. Revise las indicaciones de: ' . implode(', ', $erroresDivisionReal) . '. Todas deben ser múltiplos de d=' . datoPost('d') . '.');
}

try {
    $folio = defined('INFORME_FOLIO_FORZADO')
        ? trim((string) constant('INFORME_FOLIO_FORZADO'))
        : reservarSiguienteFolioInforme(datoPost('inf_fecha'));
} catch (RuntimeException $error) {
    http_response_code(500);
    exit('No fue posible asignar el folio consecutivo del informe.');
}

$pdf = new InformePDF('P', 'mm', 'letter');
$pdf->establecerFolio($folio);
$pdf->AliasNbPages();
$pdf->SetMargins(10, 30, 10);
$pdf->SetAutoPageBreak(false);
$pdf->SetTitle($pdf->texto('Informe técnico de pruebas metrológicas ' . $folio));
$pdf->SetAuthor($pdf->texto('SERVICOM Básculas Digitales'));
$pdf->AddPage();
dibujarPaginaGeneral($pdf, $folio);
$pdf->AddPage();
dibujarPaginaPruebas($pdf, 'inicial', 'PRUEBAS METROLÓGICAS INICIALES', 'Exactitud - Comportamiento inicial');
$pdf->AddPage();
dibujarPaginaPruebas($pdf, 'final', 'PRUEBAS METROLÓGICAS FINALES', 'Exactitud - Comportamiento final');
$nombreArchivo = 'Informe_SERVICOM_' . $folio . '.pdf';
if (defined('INFORME_PDF_DESTINO')) {
    $pdf->Output('F', (string) constant('INFORME_PDF_DESTINO'));
} else {
    $pdf->Output('D', $nombreArchivo, true);
}
