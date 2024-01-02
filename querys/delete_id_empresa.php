<?php
    require ("conexion.php");

    $id = $_GET ['id'];

    $sql = "DELETE FROM `empresas` WHERE ID = '$id'";

    mysqli_query($conexion, $sql);

    mysqli_close($conexion);
    
    header('Location: ' . $_SERVER['HTTP_REFERER']);