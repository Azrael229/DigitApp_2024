<?php

$id = file_get_contents('php://input');


require ('conexion.php');

$sql = "SELECT * FROM empresas WHERE id_e = '$id'";

$result = mysqli_query($conexion, $sql);

foreach($result as $row){
     echo json_encode ($row);
}

mysqli_close($conexion);


?>