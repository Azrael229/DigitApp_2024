<?php
//datos locales
$servername = "localhost";  // Change to your database server
$username = "root";     // Change to your database username
$password = "";     // Change to your database password
$database = "digitapp2024"; // Change to your database name

//datos remotos
$servernameR = "localhost";  // Change to your database server
$usernameR = "servico1_israelprogramador";     // Change to your database username
$passwordR = "744920lovepass";     // Change to your database password
$databaseR = "servico1_digitapp2024"; // Change to your database name



// Create a connection local
// $conexion = new mysqli($servername, $username, $password, $database);




// Create a connection Remota
$conexion = new mysqli($servernameR, $usernameR, $passwordR, $databaseR);

// Check the connection
// if ($conexion->connect_error) {
//     die("Connection failed: " . $conexion->connect_error);
// } else {
//     echo "Connected successfully";
// }

// Close the connection when you're done
// $conexion->close();
?>