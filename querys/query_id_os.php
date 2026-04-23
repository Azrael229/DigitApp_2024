<?php
require'conexion.php';

if (!isset($_GET['id_os'])||empty($_GET['id_os'])) {
die("ID no proporcionado.");
}

$id_os= (int)$_GET['id_os'];

$sql="SELECT
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
            observaciones
        FROM ordenes_servicio
        WHERE id_os = ? AND activo = 1
        LIMIT 1";

$stmt= $conexion->prepare($sql);
$stmt->bind_param("i",$id_os);
$stmt->execute();
$result= $stmt->get_result();

if ($result->num_rows===0) {
die("Orden no encontrada.");
}

$orden= $result->fetch_assoc();
?>