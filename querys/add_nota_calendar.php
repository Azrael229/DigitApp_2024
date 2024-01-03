<?php

$id_mes = $_POST['sel_id_mes'];
$nota = $_POST['input_nota'];
$file = $_FILES['input_file']['name'];

$ruta = "../docs/";
$file_tmp = $_FILES['input_file']['tmp_name'];

move_uploaded_file($file_tmp, $ruta.$file);


$link = '<a href="docs/' .$file. '" download> '.$file.'</a>' ;

require('conexion.php');

if($nota){
     
     $sql = "INSERT INTO notas_2024 (registro, mes) VALUES ('$nota', '$id_mes')";
}else{
     $sql = "INSERT INTO notas_2024 (registro, mes) VALUES ('$link', '$id_mes')";
}

mysqli_query($conexion, $sql);

mysqli_close($conexion);

echo json_encode('ok');

