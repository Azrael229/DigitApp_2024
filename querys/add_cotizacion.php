<?php
//utilizada en el archivo cotizacion.js funcion click con FETCH del boton guardar cotizacion 

// echo json_encode ('ya estoy aqui');

$num_unico = $_POST['numero_coti'];
$fecha_cot = $_POST['coti_fecha'];
$vigencia_cot = $_POST['coti_vigencia'];
$contacto_nombre = $_POST['nombre_contacto'];
$empresa_nombre = $_POST['nombre_empresa'];
$total_cot = $_POST['total'];
$cot_archivo = 'COT SERVICOM '.$num_unico.'.pdf';

// echo json_encode ($cot_nombre);
// echo json_encode ($num_cot);
// die();

require ('conexion.php');

$sql = "INSERT INTO cotizaciones (cot_fecha, cot_empresa, cot_contacto, cot_total, cot_archivo, cot_numero, cot_vigencia) VALUES ('$fecha_cot', '$empresa_nombre', '$contacto_nombre', '$total_cot', '$cot_archivo', '$num_unico', '$vigencia_cot')";

mysqli_query($conexion, $sql);

mysqli_close($conexion);
