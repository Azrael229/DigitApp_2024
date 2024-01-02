<?php
require ('fpdf.php');

// Variables del post del informe
$fecha = $_POST['inf_fecha'];
$inf_num = $_POST['input_num_inf'];
$nomb_empresa = $_POST['nombre_empresa'];
$dir_empresa = $_POST['dir_empresa'];
$nombre_contacto = $_POST['nombre_contacto'];
$correo_contacto = $_POST['correo_contacto'];


$fpdf = new FPDF('P', 'mm', 'letter');
$fpdf-> AddPage();

// bordes exteriores GUIAS
// linea top horizontal
$fpdf-> Line(10,10,206,10);
// linea bottom horizontal
$fpdf-> Line(10,270,206,270);
// IZQ vertical
$fpdf-> Line(10,10,10,270);
//DERECHA vertical
$fpdf-> Line(206,10,206,270);


//Encabezado
     // // TOP
     // $fpdf-> Line(10,10,206,10);
     // // IZQ
     // $fpdf-> Line(10,10,10,50);

     //PRESENTACION Y DIRECCION
     $fpdf-> SetXY(10,11);
     $fpdf-> SetFont('arial', 'B', 10);
     $fpdf-> Cell(135,5,'SERVICIOS DE PRECISION A SISTEMAS DE PESAJE',0,0,'C',0);

     $fpdf-> SetFont('arial', 'I', 7);
     $fpdf-> SetXY(15,17);
     $fpdf-> MultiCell(55,4,'Cerro El Gavilan 302-12, Ex Hacienda Santana, 76116, Queretaro, Qro.',0,'L',0);

     $fpdf-> SetFont('arial', 'I', 7);
     $fpdf-> SetXY(97,17);
     $fpdf-> MultiCell(43,4,'contacto@servicombasculas.com.mx  servicombasculas.com.mx',0,'R',0);

     //GUIAS
     // $fpdf-> Line(145,10,145,27);
     // $fpdf-> Line(10,27,206,27);


     
     //LOGO
     $fpdf-> Image('../imgs/LogoMakr_3N5U8p-262x87.png',140,6,70,0);
     $fpdf-> SetXY(157,20);
     $fpdf-> SetTextColor(150, 150, 150 );
     $fpdf-> SetFont('arial', 'B', 11);
     $fpdf-> Cell(45,6,'BASCULAS DIGITALES',0,0,'C',0);
     //LOGO

     //TITULO
     $fpdf-> SetXY(10,27);
     $fpdf-> SetTextColor(100,100,100 );
     $fpdf-> SetFont('arial', 'B', 15);
     $fpdf-> Cell(196,10,'INFORME DE PRUEBAS METROLOGICAS',0,0,'C',0);

     //NUMEROS DE CONTROL
     $fpdf-> SetTextColor(0,0,0 );
     $fpdf-> SetXY(12,38);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'INFORME No.', 0,0,'L',0);
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(40,5, '789654543213', 0,1,'L',0);
     $fpdf-> SetXY(168,38);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(17,5, 'FECHA:', 0,0,'L',0);
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(20,5, '2023-12-28', 0,1,'L',0);

     // DER
     // $fpdf-> Line(206,10,206,50);
     // BOTTOM
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,45,206,45);
     $fpdf-> Line(10,46,206,46);
//Encabezado


// Datos del cliente e instrumento
     // Lineas de guia
     // $fpdf-> SetDrawColor(0,0,0 );
     // // IZQ vertical
     // $fpdf-> Line(10,50,10,115);
     // // vertical media
     // $fpdf-> Line(108,50,108,115);
     // // horizontal media
     // $fpdf-> Line(10,75,206,75);
     // // DER vertical
     // $fpdf-> Line(206,50,206,115);


     // Datos del Cliente
     $fpdf-> SetXY(10,48);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> Cell(98,6, '    DATOS DEL CLIENTE', 0,0,'L',1);


     $fpdf-> SetXY(12,55);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(29,5, 'Nombre del Cliente:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(65,5, $nomb_empresa, 0,0,'L',0);//borde

     $fpdf-> SetXY(12,60);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(29,5, 'Direccion:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> MultiCell(65,4, $dir_empresa, 0,'L',0);//borde

     // Datos de Contacto
     $fpdf-> SetXY(108,48);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> Cell(98,6, '    DATOS DEL CONTACTO', 0,0,'L',1);

     $fpdf-> SetXY(110,55);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Nombre:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $nombre_contacto, 0,0,'L',0);//borde

     $fpdf-> SetXY(110,60);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Correo:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $correo_contacto, 0,0,'L',0);//borde

     // Datos del Instrumento 
     // columna izquierda
     $fpdf-> SetXY(10,75);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> Cell(196,5, 'DATOS DEL INSTRUMENTO', 0,0,'C',1);

     $fpdf-> SetXY(12,82);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'Descripcion:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(69,5, 'Bascula electronica de paltaforma', 1,0,'L',0);//borde

     $fpdf-> SetXY(12,87);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'Marca:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(69,5, 'Avery Weigh Tronix', 1,0,'L',0);//borde
     
     $fpdf-> SetXY(12,92);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'Modelo:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(69,5, 'ZM303-SD1', 1,0,'L',0);//borde

     $fpdf-> SetXY(12,97);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'Id:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(69,5, 'sin id', 1,0,'L',0);//borde

     $fpdf-> SetXY(12,102);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(25,5, 'Serie:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(69,5, '17895141102', 1,0,'L',0);//borde

     // Datos Instrumento columna derecha

     $fpdf-> SetXY(110,82);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Capacidad:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, '2 000', 1,0,'L',0);//borde

     $fpdf-> SetXY(110,87);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Resolucion:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, '0.5', 1,0,'L',0);//borde

     $fpdf-> SetXY(110,92);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Division (e):', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, '0.5', 1,0,'L',0);//borde

     $fpdf-> SetXY(110,97);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Min:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, '2', 1,0,'L',0);//borde

     $fpdf-> SetXY(110,102);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Unidades:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, 'kg', 1,0,'L',0);//borde

     $fpdf-> SetXY(110,107);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Clase:', 1,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(74,5, 'Media', 1,0,'L',0);//borde

     // bottom
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,115,206,115);
     $fpdf-> Line(10,116,206,116);

// Datos del cliente e instrumento



// $fpdf-> Cell(196,40,'Encabezados',1,1,'C',0);
// $fpdf-> Cell(196,40,'datos del cliente y datos del instrumento',1,1,'C',0);
// $fpdf-> Cell(196,30,'Metodos y patrones utilizados',1,1,'C',0);
// $fpdf-> Cell(98,30,'EMT',1,0,'C',0);
// $fpdf-> Cell(98,30,'Resultados Repetibilidad',1,1,'C',0);
// $fpdf-> Cell(98,70,'Resultados Exactitud',1,0,'C',0);
// $fpdf-> Cell(98,70,'Resultados de Excentricidad',1,1,'C',0);
// $fpdf-> Cell(196,39,'Observaciones',1,1,'C',0);







$fpdf-> OutPut();



?>