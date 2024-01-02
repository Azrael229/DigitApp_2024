<?php

require ('conexion.php');

$sql = "SELECT * FROM cotizaciones";

$result_cotizaciones = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>