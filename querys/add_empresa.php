<?php

$id = $_POST['empresa_id'];
$nombre = $_POST['emp_nombre'];
$dir_entrega = $_POST['emp_dir_entrega'];
$razon_social = $_POST['emp_razon_soc'];
$rfc = $_POST['emp_rfc'];
$dir_fiscal = $_POST['emp_dir_fiscal'];
$rol = $_POST['emp_rol'];



if ($id == "" || $id == NULL){

          require ("conexion.php");
          
          $sql = "INSERT INTO empresas  (empresa,	dir_entrega,	razon_social,	rfc,	dir_fiscal,	rol)  VALUES ('$nombre', '$dir_entrega', '$razon_social', '$rfc', '$dir_fiscal', '$rol')";
          
          mysqli_query($conexion, $sql);

          mysqli_close($conexion);

          header('Location: ' . $_SERVER['HTTP_REFERER']);

} else {

          require ("conexion.php");

          $sql = "UPDATE empresas SET 
          empresa = '$nombre', 
          dir_entrega = '$dir_entrega',
          razon_social = '$razon_social',
          rfc = '$rfc',
          dir_fiscal = '$dir_fiscal',
          rol = '$rol' 
          WHERE id_e = '$id'";

          mysqli_query($conexion, $sql);

          mysqli_close($conexion);

          header('Location: ' . $_SERVER['HTTP_REFERER']);
}





?>