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
    private string $versionFormato = 'Versión 1.0';
    private string $leyenda = 'Pruebas técnicas basadas en criterios metrológicos de la NOM-010-SCFI-1994. El alcance corresponde al procedimiento interno de servicio y no constituye por sí mismo una verificación oficial de cumplimiento de la NOM.';

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
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(10, 6);
        $this->Cell(132, 7, $this->texto('INFORME TÉCNICO DE PRUEBAS METROLÓGICAS'), 0, 1, 'C');
        $this->SetFont('Arial', '', 7);
        $this->SetX(10);
        $this->Cell(132, 4, $this->texto('SERVICIOS DE PRECISIÓN A SISTEMAS DE PESAJE'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 7);
        $this->SetX(10);
        $this->Cell(132, 4, $this->texto('Folio: ' . $this->folio), 0, 0, 'C');
        $rutaLogo = __DIR__ . '/../imgs/LogoMakr_3N5U8p-262x87.png';
        if (is_file($rutaLogo)) {
            $this->Image($rutaLogo, 150, 6, 55);
        }
        $this->SetDrawColor(255, 182, 3);
        $this->SetLineWidth(0.45);
        $this->Line(10, 27, 206, 27);
        $this->SetLineWidth(0.2);
        $this->SetY(30);
    }

    public function Footer(): void
    {
        $this->SetY(-22);
        $this->SetDrawColor(255, 182, 3);
        $this->Line(10, $this->GetY(), 206, $this->GetY());
        $this->Ln(1.5);
        $this->SetTextColor(85, 85, 85);
        $this->SetFont('Arial', 'I', 5.6);
        $this->MultiCell(196, 2.7, $this->texto($this->leyenda), 0, 'J');
        $this->SetY(-8);
        $this->SetFont('Arial', '', 7);
        $this->Cell(98, 4, $this->texto($this->versionFormato), 0, 0, 'L');
        $this->Cell(98, 4, $this->texto('Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
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
    public function filaFija(float $y, array $valores, array $anchos, float $alto = 6, array $alineaciones = [], bool $relleno = false, float $xInicial = 10): void
    {
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
        $this->SetFillColor(205, 205, 205);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 8.5);
        $this->Rect(10, $y, 196, $alto, 'F');
        $this->SetXY(11, $y + 0.7);
        $this->Cell(194, $alto - 1.2, $this->texto($titulo), 0, 0, 'L');
    }

    // Dibuja un subtítulo compacto dentro de una página de pruebas.
    public function subtituloFijo(float $y, string $titulo): void
    {
        $this->SetDrawColor(255, 182, 3);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY(10, $y);
        $this->Cell(196, 5, $this->texto($titulo), 'B', 0, 'L');
    }

    // Dibuja un campo narrativo con tamaño fijo y contenido truncado de forma controlada.
    public function bloqueFijo(float $y, string $titulo, string $contenido, float $altoCaja = 11): void
    {
        $this->tituloFijo($y, $titulo, 5);
        $this->SetFont('Arial', '', 6.7);
        $this->Rect(10, $y + 5.5, 196, $altoCaja);
        $this->textoCelda(10, $y + 5.5, 196, $altoCaja, $contenido !== '' ? $contenido : 'Sin información registrada.');
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

    // Dibuja la gráfica fija de exactitud con EMT escalonado, escala adaptativa y error real.
    public function graficaExactitud(string $fase, float $x, float $y, float $ancho, float $alto, string $titulo): void
    {
        $this->SetDrawColor(70, 70, 70);
        $this->Rect($x, $y, $ancho, $alto);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetXY($x + 2, $y + 1);
        $this->Cell($ancho - 4, 4, $this->texto($titulo), 0, 0, 'L');

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

        $plotX = $x + 17;
        $plotY = $y + 11;
        $plotW = $ancho - 23;
        $plotH = $alto - 23;
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

        $this->SetDrawColor(210, 210, 210);
        $this->Rect($plotX, $plotY, $plotW, $plotH);
        $this->SetFont('Arial', '', 5.2);

        for ($tick = -$cantidadTicks; $tick <= $cantidadTicks; $tick++) {
            $valorTick = $tick * $pasoY;
            $tickY = $convertirY($valorTick);
            $this->SetDrawColor($tick === 0 ? 95 : 225, $tick === 0 ? 95 : 225, $tick === 0 ? 95 : 225);
            $this->Line($plotX, $tickY, $plotX + $plotW, $tickY);
            $this->SetTextColor(75, 75, 75);
            $this->SetXY($x + 1, $tickY - 1.5);
            $this->Cell(15, 3, $this->etiquetaGrafica($valorTick, $pasoY), 0, 0, 'R');
        }

        $this->SetDrawColor(190, 50, 50);
        $this->limiteEscalonado($tramos, 1, $convertirX, $convertirY);
        $this->SetDrawColor(150, 50, 110);
        $this->limiteEscalonado($tramos, -1, $convertirX, $convertirY);
        $this->SetDrawColor(30, 95, 170);
        $this->SetFillColor(30, 95, 170);
        $this->SetTextColor(0, 0, 0);
        $cantidadPuntos = count($cargas);
        for ($i = 0; $i < $cantidadPuntos; $i++) {
            if ($i < $cantidadPuntos - 1) {
                $this->Line($puntosX[$i], $errorY[$i], $puntosX[$i + 1], $errorY[$i + 1]);
            }
            $this->Rect($puntosX[$i] - 0.65, $errorY[$i] - 0.65, 1.3, 1.3, 'F');
            if ($i === 0) {
                $this->SetXY($puntosX[$i] - 7, $plotY + $plotH + 1);
                $this->Cell(5, 3, '0', 0, 0, 'R');
            } else {
                $this->SetXY($puntosX[$i] - 10, $plotY + $plotH + 1);
                $this->Cell(20, 3, $this->texto((string) $cargas[$i]), 0, 0, 'C');
            }
        }
        $this->SetFont('Arial', '', 5.5);
        $this->SetXY($plotX, $y + $alto - 7);
        $this->Cell($plotW, 3, $this->texto('Carga aplicada'), 0, 0, 'C');
        $this->SetXY($x + 2, $y + 5.5);
        $this->SetTextColor(30, 95, 170);
        $this->Cell(32, 3, $this->texto('Error real'), 0, 0, 'L');
        $this->SetTextColor(190, 50, 50);
        $this->Cell(25, 3, $this->texto('+EMT'), 0, 0, 'L');
        $this->SetTextColor(150, 50, 110);
        $this->Cell(25, 3, $this->texto('-EMT'), 0, 0, 'L');
        $this->SetTextColor(90, 90, 90);
        $this->Cell(35, 3, $this->texto('Línea cero'), 0, 0, 'L');
    }
}

// Dibuja la página 1 con todas las secciones generales en posiciones predeterminadas.
function dibujarPaginaGeneral(InformePDF $pdf, string $folio): void
{
    $pdf->SetDrawColor(90, 90, 90);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->tituloFijo(31, 'A. IDENTIFICACIÓN DEL INFORME');
    $pdf->filaFija(37, ['Folio', $folio, 'Fecha', datoPost('inf_fecha')], [30, 68, 30, 68], 7);
    $pdf->tituloFijo(46, 'B. CLIENTE');
    $pdf->filaFija(52, ['Empresa', datoPost('nombre_empresa'), 'Contacto', datoPost('nombre_contacto')], [30, 68, 30, 68], 7);
    $pdf->filaFija(59, ['Dirección', datoPost('dir_empresa'), 'Correo', datoPost('correo_contacto')], [30, 68, 30, 68], 10);
    $pdf->tituloFijo(71, 'C. INSTRUMENTO');
    $filasInstrumento = [
        ['Descripción', datoPost('desc_inst'), 'Marca', datoPost('marca_inst')],
        ['Modelo', datoPost('modelo_inst'), 'ID', datoPost('id_inst')],
        ['Serie', datoPost('serie_inst'), 'Unidad', datoPost('unidad')],
        ['Max', datoPost('max'), 'd', datoPost('d')],
        ['e', datoPost('e'), 'Min', datoPost('min')],
        ['Clase', datoPost('clase'), '', ''],
    ];
    foreach ($filasInstrumento as $indice => $fila) {
        $pdf->filaFija(77 + $indice * 6, $fila, [30, 68, 30, 68], 6);
    }
    $reactivos = [
        1 => 'Identificación y placa legible.',
        2 => 'Display / escala / unidad legibles.',
        3 => 'Cero, tara y estabilidad funcionan correctamente.',
        4 => 'Plataforma / plato / estructura en buen estado.',
        5 => 'Nivelación e instalación correctas.',
        6 => 'Limpieza y funcionamiento general.',
    ];
    $pdf->tituloFijo(115, 'D. INSPECCIÓN VISUAL Y FUNCIONAL');
    $pdf->SetFillColor(232, 232, 232);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(121, ['Reactivo', 'Estado', 'Observación'], [91, 30, 75], 6, ['L', 'C', 'L'], true);
    $pdf->SetFont('Arial', '', 6.1);
    foreach ($reactivos as $numero => $reactivo) {
        $pdf->filaFija(127 + ($numero - 1) * 5.5, [$numero . '. ' . $reactivo, datoPost('inspeccion_' . $numero . '_estado'), datoPost('inspeccion_' . $numero . '_observacion')], [91, 30, 75], 5.5, ['L', 'C', 'L']);
    }
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(160, ['Resultado general', datoPost('inspeccion_resultado', 'PENDIENTE')], [45, 151], 6);
    $pdf->bloqueFijo(168, 'E. OBSERVACIONES', datoPost('observaciones'), 11);
    $pdf->bloqueFijo(186, 'F. TRABAJO REALIZADO', datoPost('trabajo_realizado'), 11);
    $pdf->bloqueFijo(204, 'G. RECOMENDACIONES', datoPost('recomendaciones'), 11);
    $pdf->bloqueFijo(222, 'H. ATENCIÓN / SERVICIOS URGENTES', datoPost('atencion_urgente'), 11);
}

// Dibuja repetibilidad en el área fija superior de una página de pruebas.
function dibujarRepetibilidadFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_repetibilidad';
    $pdf->subtituloFijo(40, 'Repetibilidad');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->filaFija(46, ['Carga aplicada', datoPost($prefijo . '_carga'), 'Resultado', datoPost($prefijo . '_resultado', 'PENDIENTE')], [31, 67, 31, 67], 6);
    $pdf->SetFillColor(232, 232, 232);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(52, ['Prueba', 'Indicación'], [65, 131], 5, ['C', 'C'], true);
    $pdf->SetFont('Arial', '', 6.3);
    for ($i = 1; $i <= 5; $i++) {
        $pdf->filaFija(57 + ($i - 1) * 4.8, [(string) $i, indicacionPost($prefijo . '_lectura_' . $i)], [65, 131], 4.8, ['C', 'C']);
    }
    $pdf->filaFija(81, ['Diferencia máxima encontrada', datoPost($prefijo . '_diferencia'), 'EMT aplicable', datoPost($prefijo . '_emt')], [43, 55, 35, 63], 7);
}

// Dibuja excentricidad en el área fija central de una página de pruebas.
function dibujarExcentricidadFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_excentricidad';
    $pdf->subtituloFijo(91, 'Excentricidad');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->filaFija(97, ['Carga aplicada', datoPost($prefijo . '_carga'), 'Resultado', datoPost($prefijo . '_resultado', 'PENDIENTE')], [31, 67, 31, 67], 6);
    $pdf->SetFillColor(232, 232, 232);
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(103, ['Posición', 'Indicación'], [80, 116], 5, ['C', 'C'], true);
    $pdf->SetFont('Arial', '', 6.3);
    for ($i = 1; $i <= 5; $i++) {
        $posicion = $i === 1 ? '1 - Centro / referencia' : (string) $i;
        $pdf->filaFija(108 + ($i - 1) * 4.8, [$posicion, indicacionPost($prefijo . '_lectura_' . $i)], [80, 116], 4.8, ['L', 'C']);
    }
    $pdf->filaFija(132, ['Diferencia máxima encontrada', datoPost($prefijo . '_diferencia_maxima'), 'EMT aplicable', datoPost($prefijo . '_emt')], [43, 55, 35, 63], 7);
}

// Dibuja exactitud en un área fija y conserva las seis columnas aprobadas.
function dibujarExactitudFija(InformePDF $pdf, string $fase): void
{
    $prefijo = $fase . '_exactitud';
    $pdf->subtituloFijo(142, 'Exactitud');
    $pdf->SetFillColor(232, 232, 232);
    $pdf->SetFont('Arial', 'B', 6.1);
    $anchos = [16, 36, 38, 30, 30, 46];
    $alineaciones = ['C', 'C', 'C', 'C', 'C', 'C'];
    $pdf->filaFija(148, ['Punto', 'Carga', 'Indicación', 'Error', 'EMT', 'Resultado'], $anchos, 5, $alineaciones, true);
    $pdf->SetFont('Arial', '', 6.1);
    for ($i = 0; $i <= 5; $i++) {
        $pdf->filaFija(153 + $i * 4.5, [(string) $i, datoPost($prefijo . '_carga_' . $i), indicacionPost($prefijo . '_indicacion_' . $i), datoPost($prefijo . '_error_' . $i), datoPost($prefijo . '_emt_' . $i), datoPost($prefijo . '_resultado_' . $i)], $anchos, 4.5, $alineaciones);
    }
    $pdf->SetFont('Arial', 'B', 6.3);
    $pdf->filaFija(180, ['Resultado general', datoPost($prefijo . '_resultado', 'PENDIENTE')], [45, 151], 6);
}

// Construye una página fija de pruebas y coloca la gráfica en coordenadas absolutas idénticas.
function dibujarPaginaPruebas(InformePDF $pdf, string $fase, string $tituloPagina, string $tituloGrafica): void
{
    $pdf->tituloFijo(31, $tituloPagina, 7);
    dibujarRepetibilidadFija($pdf, $fase);
    dibujarExcentricidadFija($pdf, $fase);
    dibujarExactitudFija($pdf, $fase);
    $pdf->graficaExactitud($fase, 20, 189, 176, 60, $tituloGrafica);
}

$erroresDivisionReal = validarIndicacionesPost();
if ($erroresDivisionReal !== []) {
    http_response_code(422);
    exit('División real no válida. Revise las indicaciones de: ' . implode(', ', $erroresDivisionReal) . '. Todas deben ser múltiplos de d=' . datoPost('d') . '.');
}

try {
    $folio = reservarSiguienteFolioInforme(datoPost('inf_fecha'));
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
$pdf->Output('D', 'Informe_SERVICOM_' . $folio . '.pdf', true);
