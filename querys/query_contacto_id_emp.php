<?php
// este id lo envia un fetch de la funcion selectEmpresa (informe.js)
// regresa toda la informacion del contacto segun el id_empresa
$id = file_get_contents('php://input');


require ('conexion.php');

$sql = "SELECT * FROM contactos WHERE id_empresa = '$id'";

$result = mysqli_query($conexion, $sql);

foreach($result as $row){
     echo json_encode ($row);
}

mysqli_close($conexion);


?>