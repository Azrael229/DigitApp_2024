<?php

require (__DIR__ . "/../../config/conexion.php");
require_once __DIR__ . '/common.php';

$sql = "SELECT c.id_coti, c.cot_numero, c.cot_fecha,
               CONCAT_WS(' ', COALESCE(NULLIF(TRIM(e.razon_social), ''), e.empresa, c.cot_empresa), NULLIF(TRIM(e.regimen_capital), '')) AS cot_empresa,
               COALESCE(ct.nombre, c.cot_contacto) AS cot_contacto,
               COALESCE(ct.correo, c.cot_correo) AS cot_correo,
               c.cot_total, c.cot_status
        FROM cotizaciones c
        LEFT JOIN empresas e ON e.id_e = c.empresa_id
        LEFT JOIN contactos ct ON ct.id = c.contacto_id
        ORDER BY c.id_coti DESC";

$result_cotizaciones = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>
