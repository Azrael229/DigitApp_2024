<?php

require (__DIR__ . "/../../config/conexion.php");
require_once __DIR__ . '/common.php';

$sql = "SELECT id_coti, cot_numero, cot_fecha, cot_empresa, cot_contacto,
               cot_correo, cot_total, cot_status
        FROM cotizaciones
        ORDER BY id_coti DESC";

$result_cotizaciones = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>
