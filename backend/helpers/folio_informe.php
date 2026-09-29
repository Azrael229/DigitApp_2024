<?php

const FOLIO_INFORME_INICIAL = 14327;
const FOLIO_INFORME_MAXIMO = 99999;

// Devuelve la ruta del estado persistente usado exclusivamente por los informes.
function rutaContadorInforme(?string $rutaPersonalizada = null): string
{
    if ($rutaPersonalizada !== null) {
        return $rutaPersonalizada;
    }
    if (defined('FOLIO_INFORME_RUTA_PERSONALIZADA')) {
        return (string) constant('FOLIO_INFORME_RUTA_PERSONALIZADA');
    }
    return __DIR__ . '/folio_informe_counter.txt';
}

// Normaliza la fecha del informe para que la regla diaria siempre opere con YYYY-MM-DD.
function normalizarFechaInforme(?string $fecha): string
{
    $fecha = trim((string) $fecha);
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $errores = DateTimeImmutable::getLastErrors();
    if ($objeto !== false && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
        return $objeto->format('Y-m-d');
    }
    return date('Y-m-d');
}

// Interpreta el estado JSON actual y conserva compatibilidad con el contador numérico anterior.
function estadoFolioInforme(string $contenido): array
{
    $contenido = trim($contenido);
    $estado = json_decode($contenido, true);
    if (is_array($estado)) {
        $folio = filter_var($estado['folio'] ?? null, FILTER_VALIDATE_INT);
        $fecha = isset($estado['fecha']) && is_string($estado['fecha']) ? normalizarFechaInforme($estado['fecha']) : null;
        if ($folio !== false && $folio >= FOLIO_INFORME_INICIAL && $folio <= FOLIO_INFORME_MAXIMO) {
            return ['folio' => $folio, 'fecha' => $fecha];
        }
    }
    if (preg_match('/^\d{5}$/', $contenido)) {
        $folio = (int) $contenido;
        if ($folio >= FOLIO_INFORME_INICIAL && $folio <= FOLIO_INFORME_MAXIMO) {
            return ['folio' => $folio, 'fecha' => null];
        }
    }
    return ['folio' => null, 'fecha' => null];
}

// Calcula un salto reproducible de 7 a 23 y evita los valores redondos 10 y 20.
function saltoControladoFolioInforme(string $fecha): int
{
    $residuo = 0;
    foreach (str_split($fecha) as $caracter) {
        $residuo = (($residuo * 31) + ord($caracter)) % 17;
    }
    $salto = 7 + $residuo;
    return in_array($salto, [10, 20], true) ? $salto + 1 : $salto;
}

// Determina el siguiente folio sin alterar el estado persistente.
function calcularSiguienteFolioInforme(array $estado, string $fecha): int
{
    $ultimo = $estado['folio'] ?? null;
    $ultimaFecha = $estado['fecha'] ?? null;
    if (!is_int($ultimo) || $ultimo < FOLIO_INFORME_INICIAL) {
        return FOLIO_INFORME_INICIAL;
    }
    $incremento = $ultimaFecha !== null && $fecha > $ultimaFecha
        ? saltoControladoFolioInforme($fecha)
        : 1;
    $siguiente = $ultimo + $incremento;
    if ($siguiente > FOLIO_INFORME_MAXIMO) {
        throw new RuntimeException('Se agotó el rango disponible de folios de cinco dígitos.');
    }
    return $siguiente;
}

// Consulta el siguiente folio previsto sin consumirlo; la reserva definitiva ocurre al generar el PDF.
function consultarSiguienteFolioInforme(?string $fecha = null, ?string $rutaPersonalizada = null): string
{
    $fechaNormalizada = normalizarFechaInforme($fecha);
    $ruta = rutaContadorInforme($rutaPersonalizada);
    if (!is_file($ruta)) {
        return (string) FOLIO_INFORME_INICIAL;
    }
    $archivo = fopen($ruta, 'rb');
    if ($archivo === false) {
        return (string) FOLIO_INFORME_INICIAL;
    }
    flock($archivo, LOCK_SH);
    $estado = estadoFolioInforme((string) stream_get_contents($archivo));
    flock($archivo, LOCK_UN);
    fclose($archivo);
    return (string) calcularSiguienteFolioInforme($estado, $fechaNormalizada);
}

// Reserva atómicamente el folio de la fecha indicada y persiste folio y fecha con bloqueo exclusivo.
function reservarSiguienteFolioInforme(?string $fecha = null, ?string $rutaPersonalizada = null): string
{
    $fechaNormalizada = normalizarFechaInforme($fecha);
    $ruta = rutaContadorInforme($rutaPersonalizada);
    $archivo = fopen($ruta, 'c+');
    if ($archivo === false || !flock($archivo, LOCK_EX)) {
        if (is_resource($archivo)) {
            fclose($archivo);
        }
        throw new RuntimeException('No fue posible reservar un folio para el informe.');
    }
    rewind($archivo);
    $estado = estadoFolioInforme((string) stream_get_contents($archivo));
    $siguiente = calcularSiguienteFolioInforme($estado, $fechaNormalizada);
    $contenido = json_encode(['folio' => $siguiente, 'fecha' => $fechaNormalizada], JSON_UNESCAPED_SLASHES);
    if ($contenido === false) {
        flock($archivo, LOCK_UN);
        fclose($archivo);
        throw new RuntimeException('No fue posible serializar el estado del folio.');
    }
    rewind($archivo);
    ftruncate($archivo, 0);
    fwrite($archivo, $contenido . PHP_EOL);
    fflush($archivo);
    flock($archivo, LOCK_UN);
    fclose($archivo);
    return (string) $siguiente;
}
