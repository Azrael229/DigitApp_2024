<?php
// el id viene de fetch funcion editar (contacto.js)
$id = file_get_contents('php://input');


require ('conexion.php');

$sql = "SELECT * FROM contactos, empresas WHERE  id = $id AND contactos.id_empresa = empresas.id_e";


$result = mysqli_query($conexion, $sql);

foreach($result as $row){
     echo json_encode ($row);
}

mysqli_close($conexion);


?>