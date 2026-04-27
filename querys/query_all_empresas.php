<?php

require ('conexion.php');

$sql = "SELECT
            e.*,
            d.estado AS estado_fiscal,
            d.ciudad AS ciudad_fiscal,
            d.municipio AS municipio_fiscal
        FROM empresas e
        LEFT JOIN empresa_direcciones d
            ON d.id = (
                SELECT d2.id
                FROM empresa_direcciones d2
                WHERE d2.empresa_id = e.id_e
                  AND d2.tipo_direccion = 'fiscal'
                ORDER BY d2.es_principal DESC, d2.id ASC
                LIMIT 1
            )
        ORDER BY e.id_e DESC";

$result_empresas = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>
