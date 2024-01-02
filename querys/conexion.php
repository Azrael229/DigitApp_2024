<?php
$servername = "localhost";  // Change to your database server
$username = "root";     // Change to your database username
$password = "";     // Change to your database password
$database = "digitapp2024"; // Change to your database name

// Create a connection
$conexion = new mysqli($servername, $username, $password, $database);

// Check the connection
// if ($conexion->connect_error) {
//     die("Connection failed: " . $conexion->connect_error);
// } else {
//     echo "Connected successfully";
// }

// Close the connection when you're done
// $conexion->close();
?>