<?php

require (__DIR__ . "/../../config/conexion.php");

$sql = "SELECT empresas.*,
        (
            SELECT ciudad
            FROM empresa_direcciones
            WHERE empresa_id = empresas.id_e
            ORDER BY es_principal DESC, updated_at DESC, id DESC
            LIMIT 1
        ) AS ciudad_principal,
        (
            SELECT estado
            FROM empresa_direcciones
            WHERE empresa_id = empresas.id_e
            ORDER BY es_principal DESC, updated_at DESC, id DESC
            LIMIT 1
        ) AS estado_principal
        FROM empresas
        ORDER BY created_at DESC, id_e DESC";

$result_empresas = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>
