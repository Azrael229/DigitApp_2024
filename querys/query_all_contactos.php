<?php

require ('conexion.php');

// $sql = "SELECT * FROM contactos";

$sql = "SELECT * FROM `contactos` INNER JOIN empresas ON contactos.id_empresa = empresas.id_e";


$result_contactos = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>