<?php

require ('conexion.php');

$sql = "SELECT * FROM notas_2024 WHERE mes = '1'";

$res_notas_enero = mysqli_query($conexion, $sql);


mysqli_close($conexion);