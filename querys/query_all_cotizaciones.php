<?php

require ('conexion.php');

$sql = "SELECT * FROM cotizaciones ORDER BY id_coti DESC";

$result_cotizaciones = mysqli_query($conexion, $sql);


mysqli_close($conexion);


?>