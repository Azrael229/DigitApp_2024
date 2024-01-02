<?php

$id = $_POST['contacto_id'];
$nombre = $_POST['contacto_nombre'];
$cel = $_POST['contacto_cel'];
$email = $_POST['contacto_email'];
$depto = $_POST['contacto_depto'];
$id_empresa = $_POST['contacto_nombre_empresa'];






if ($id == "" || $id == NULL){

          require ("conexion.php");
          
          $sql = "INSERT INTO contactos  (nombre,	celular,	correo,	depto,	id_empresa)  VALUES ('$nombre', '$cel', '$email', '$depto', '$id_empresa')";
          
          mysqli_query($conexion, $sql);

          mysqli_close($conexion);

          header('Location: ' . $_SERVER['HTTP_REFERER']);

} else {

          require ("conexion.php");

          $sql = "UPDATE contactos SET 
          nombre = '$nombre', 
          celular = '$cel',
          correo = '$email',
          depto = '$depto',
          id_empresa = '$id_empresa'
          WHERE id = '$id'";

          mysqli_query($conexion, $sql);

          mysqli_close($conexion);

          header('Location: ' . $_SERVER['HTTP_REFERER']);

}





?>