<?php

$id_cot = $_POST['id_cot'];
$id_status = $_POST['id_status'];


if($id_status == "2"){
  
  $cot_status = '<i class="bi bi-circle-fill" style="color: green;"> Aceptada </i>';

  require ('conexion.php');

  $sql = "UPDATE cotizaciones
  SET cot_status = '$cot_status'
  WHERE id_coti = '$id_cot'";

  mysqli_query($conexion, $sql);
  mysqli_close($conexion);

  echo json_encode('2');
  // header('Location: ' . $_SERVER['HTTP_REFERER']);

}
if($id_status == "3"){
  
  $cot_status = '<i class="bi bi-circle-fill" style="color: red;"> Cancelada </i>';

  require ('conexion.php');

  $sql = "UPDATE cotizaciones
  SET cot_status = '$cot_status'
  WHERE id_coti = '$id_cot'";

  mysqli_query($conexion, $sql);
  mysqli_close($conexion);

  echo json_encode('3');

  // header('Location: ' . $_SERVER['HTTP_REFERER']);
}
if($id_status == "1"){
  
  $cot_status = '<i class="bi bi-circle-fill" style="color: blue;"> Esperar </i>';

  require ('conexion.php');

  $sql = "UPDATE cotizaciones
  SET cot_status = '$cot_status'
  WHERE id_coti = '$id_cot'";

  mysqli_query($conexion, $sql);
  mysqli_close($conexion);

  echo json_encode('1');

  // header('Location: ' . $_SERVER['HTTP_REFERER']);
}
