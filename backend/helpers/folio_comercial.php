<?php
declare(strict_types=1);

// Calcula un salto diario de 7 a 23 para que los folios Q no revelen el volumen real.
function saltoDiarioFolioCotizacion(string $fecha): int
{
    $residuo = 0;
    foreach (str_split($fecha) as $caracter) {
        $residuo = (($residuo * 31) + ord($caracter)) % 17;
    }
    $salto = 7 + $residuo;
    return in_array($salto, [10, 20], true) ? $salto + 1 : $salto;
}

// Elige un salto irregular y siempre positivo para ocultar el volumen de órdenes de venta.
function saltoFolioOrdenVenta(): int
{
    $saltos = [2, 3, 5, 10, 11];
    return $saltos[random_int(0, count($saltos) - 1)];
}

// Reserva dentro de la transacción activa un folio anual único para OPC, Q, OV u OS.
function reservarFolioComercial(mysqli $conexion, string $tipo, string $fecha): string
{
    if (!in_array($tipo, ['OPC', 'Q', 'OV', 'OS'], true)) {
        throw new InvalidArgumentException('El tipo de folio comercial no es válido.');
    }
    $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $erroresFecha = DateTimeImmutable::getLastErrors();
    if ($fechaObjeto === false || ($erroresFecha !== false
        && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))) {
        throw new InvalidArgumentException('La fecha del folio comercial no es válida.');
    }

    $anio = (int) $fechaObjeto->format('Y');
    $inicial = match ($tipo) {
        'Q' => 14327,
        'OV' => 1001,
        'OS' => 12781,
        default => 1,
    };
    $anteriorInicial = $inicial - 1;

    $crear = $conexion->prepare(
        'INSERT IGNORE INTO control_folios_comerciales
            (tipo, anio, ultimo_numero, ultima_fecha)
         VALUES (?, ?, ?, NULL)'
    );
    $crear->bind_param('sii', $tipo, $anio, $anteriorInicial);
    $crear->execute();
    $crear->close();

    $consulta = $conexion->prepare(
        'SELECT ultimo_numero, ultima_fecha
         FROM control_folios_comerciales
         WHERE tipo = ? AND anio = ?
         FOR UPDATE'
    );
    $consulta->bind_param('si', $tipo, $anio);
    $consulta->execute();
    $estado = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    if ($estado === null) {
        throw new RuntimeException('No fue posible reservar el folio comercial.');
    }

    $ultimo = (int) $estado['ultimo_numero'];
    if ($ultimo < $inicial) {
        $siguiente = $inicial;
    } elseif ($tipo === 'Q' && !empty($estado['ultima_fecha']) && $fecha > $estado['ultima_fecha']) {
        $siguiente = $ultimo + saltoDiarioFolioCotizacion($fecha);
    } elseif (in_array($tipo, ['OV', 'OS'], true)) {
        $siguiente = $ultimo + saltoFolioOrdenVenta();
    } else {
        $siguiente = $ultimo + 1;
    }
    if ($siguiente > 99999) {
        throw new RuntimeException('Se agotó el rango anual de folios comerciales.');
    }

    $actualizar = $conexion->prepare(
        'UPDATE control_folios_comerciales
         SET ultimo_numero = ?, ultima_fecha = ?
         WHERE tipo = ? AND anio = ?'
    );
    $actualizar->bind_param('issi', $siguiente, $fecha, $tipo, $anio);
    $actualizar->execute();
    $actualizar->close();

    $anioVisible = $tipo === 'Q' ? $fechaObjeto->format('y') : $fechaObjeto->format('Y');
    return sprintf('%s-%s-%05d', $tipo, $anioVisible, $siguiente);
}
