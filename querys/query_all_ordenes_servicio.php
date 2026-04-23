<?php

require('conexion.php');

$sql = "SELECT 
            id_os,
            id_coti,
            id_e,
            empresa_nombre,
            contacto_nombre,
            fecha_creacion,
            fecha_servicio,
            importe,
            estado,
            nombre_proyecto,
            descripcion,
            observaciones,
            activo,
            fecha_actualizacion
        FROM ordenes_servicio
        WHERE activo = 1
        ORDER BY fecha_creacion DESC";

$result_ordenes_servicio = mysqli_query($conexion, $sql);

mysqli_close($conexion);

?>
