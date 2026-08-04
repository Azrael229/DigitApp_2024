<?php

require (__DIR__ . "/../../config/conexion.php");

$sql = "SELECT * FROM empresas";

$result_empresas = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>
