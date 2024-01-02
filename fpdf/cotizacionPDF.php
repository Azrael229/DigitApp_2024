<?php

//datos de control
$fecha_coti = $_POST['coti_fecha'];
$vigencia_coti = $_POST['coti_vigencia'];
$numero_coti = $_POST['numero_coti'];
//datos del cliente
$contacto_nombre = $_POST['nombre_contacto'];
$contacto_cel = $_POST['cel_contacto'];
$contacto_correo = $_POST['correo_contacto'];
$contacto_depto = $_POST['depto_contacto'];
$empresa_nombre = $_POST['nombre_empresa'];
$empresa_dir = $_POST['dir_empresa'];
//articulos cotizacion
//fila 1
$f1_cantidad = $_POST['f1_cant'];
$f1_unidad = $_POST['f1_unidad'];
$f1_descripcion = $_POST['f1_descrip'];
$f1_valUnitario = $_POST['f1_valUnit'];
$f1_total = $_POST['f1_total'];
//fila 2
$f2_cantidad = $_POST['f2_cant'];
$f2_unidad = $_POST['f2_unidad'];
$f2_descripcion = $_POST['f2_descrip'];
$f2_valUnitario = $_POST['f2_valUnit'];
$f2_total = $_POST['f2_total'];
//fila 3
$f3_cantidad = $_POST['f3_cant'];
$f3_unidad = $_POST['f3_unidad'];
$f3_descripcion = $_POST['f3_descrip'];
$f3_valUnitario = $_POST['f3_valUnit'];
$f3_total = $_POST['f3_total'];

//fila 4
$f4_cantidad = $_POST['f4_cant'];
$f4_unidad = $_POST['f4_unidad'];
$f4_descripcion = $_POST['f4_descrip'];
$f4_valUnitario = $_POST['f4_valUnit'];
$f4_total = $_POST['f4_total'];

//totales
$subtotal = $_POST['subtotal'];
$iva = $_POST['iva'];
$total = $_POST['total'];

//Tiempo de entrega 
$tiempoEntrega = $_POST['tiempo_entrega'];
$nota1 = $_POST['notas1']?? '';
$nota2 = $_POST['notas2']?? '';
$nota3 = $_POST['notas3']?? '';
$nota4 = $_POST['notas4']?? '';
$nota5 = $_POST['notas5']?? '';
$nota6 = $_POST['notas6']?? '';
$nota7 = $_POST['notas7']?? '';

$notas = array();

if($nota1==""){
     unset($notas[0]);
}else{
     $notas[] = $nota1;
}
if($nota2==""){
     unset($notas[1]);
}else{
     $notas[] = $nota2;
}
if($nota3==""){
     unset($notas[2]);
}else{
     $notas[] = $nota3;
}
if($nota4==""){
     unset($notas[3]);
}else{
     $notas[] = $nota4;
}
if($nota5==""){
     unset($notas[4]);
}else{
     $notas[] = $nota5;
}
if($nota6==""){
     unset($notas[5]);
}else{
     $notas[] = $nota6;
}
if($nota7==""){
     unset($notas[6]);
}else{
     $notas[] = $nota7;
}
// print_r ($notas);


require ('../funciones.php');
require ('fpdf.php');

$fpdf = new FPDF('P', 'mm', 'letter', true);
$fpdf-> AddPage();

// bordes exteriores GUIAS
// linea top horizontal
// $fpdf-> Line(10,10,206,10);
// // linea bottom horizontal
// $fpdf-> Line(10,270,206,270);
// // IZQ vertical
// $fpdf-> Line(10,10,10,270);
// //DERECHA vertical
// $fpdf-> Line(206,10,206,270);


//Encabezado
     // // TOP
     // $fpdf-> Line(10,10,206,10);
     // // IZQ
     // $fpdf-> Line(10,10,10,50);

     //PRESENTACION Y DIRECCION
     $fpdf-> SetXY(10,11);
     $fpdf-> SetFont('arial', 'B', 11);
     $fpdf-> Cell(135,5,'SERVICIOS DE PRECISIÓN A SISTEMAS DE PESAJE',0,0,'C',0);

     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> SetXY(15,17);
     $fpdf-> MultiCell(55,4,'Cerro El Gavilán 302-12, Ex Hacienda Santana, 76116, Querétaro, Qro.',0,'L',0);

     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> SetXY(90,17);
     $fpdf-> MultiCell(49,4,'contacto@servicombasculas.com.mx  servicombasculas.com.mx',0,'R',0);

     // GUIAS
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
     $fpdf-> Cell(196,10,'COTIZACIÓN',0,0,'C',0);

     //NUMEROS DE CONTROL
     $bordeceldanumcontrol = 0;
     $fpdf-> SetTextColor(0,0,0 );

     /////////
     $fpdf-> SetXY(142,38);
     $fpdf-> SetFont('arial', 'B', 9);
     $fpdf-> Cell(27,5, '', $bordeceldanumcontrol,0,'R',0);
     $fpdf-> SetFont('arial', '', 9);
     $fpdf-> Cell(33,5, $numero_coti, $bordeceldanumcontrol,1,'L',0);
     ////////
     $fpdf-> SetXY(12,38);
     $fpdf-> SetFont('arial', 'B', 9);
     $fpdf-> Cell(33,5, 'FECHA DE EMISIÓN:', $bordeceldanumcontrol,0,'L',0);
     $fpdf-> SetFont('arial', '', 9);
     $fpdf-> Cell(20,5, $fecha_coti, $bordeceldanumcontrol,1,'L',0);
     ////////
     $fpdf-> SetXY(70,38);
     $fpdf-> SetFont('arial', 'B', 9);
     $fpdf-> Cell(26,5, 'VÁLIDA HASTA:', $bordeceldanumcontrol,0,'L',0);
     $fpdf-> SetFont('arial', '', 9);
     $fpdf-> Cell(20,5, $vigencia_coti, $bordeceldanumcontrol,1,'L',0);

     // DER
     // $fpdf-> Line(206,10,206,50);
     // BOTTOM
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,45,206,45);
     $fpdf-> Line(10,46,206,46);
//Encabezado





//Datos cliente
     $bordesDatosClientes = 0;
     //Lienas de Guia
     $fpdf-> SetDrawColor(0, 0, 0 );
     // vertical media
     // $fpdf-> Line(108,50,108,78);

     $fpdf-> SetXY(10,48);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> Cell(98,6, '    DATOS DEL CLIENTE', 0,0,'L',1);

     //Datos de Empresa
     $fpdf-> SetXY(12,55);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(19,5, 'Empresa:', $bordesDatosClientes,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(72,5, $empresa_nombre, $bordesDatosClientes,0,'L',0);//borde

     $fpdf-> SetXY(12,60);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(19,5, 'Dirección:', $bordesDatosClientes,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> MultiCell(72,4, $empresa_dir, $bordesDatosClientes,'L',0);//borde

     // Datos de Contacto
     $fpdf-> SetXY(108,48);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> Cell(98,6, '    DATOS DEL CONTACTO', 0,0,'L',1);

     $fpdf-> SetXY(110,55);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Nombre:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $contacto_nombre, 0,0,'L',0);//borde

     $fpdf-> SetXY(110,60);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Teléfono:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $contacto_cel, 0,0,'L',0);//borde

     $fpdf-> SetXY(110,65);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Correo:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $contacto_correo, 0,0,'L',0);//borde

     $fpdf-> SetXY(110,70);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(15,5, 'Depto:', 0,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(79,5, $contacto_depto, 0,0,'L',0);//borde

     // bottom
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,78,206,78);
     $fpdf-> Line(10,79,206,79);
//Datos cliente


//SECCION Productos y Servicios

     //titulo
     $fpdf-> SetXY(10,81);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> Cell(196,5, '    COTIZACIÓN ', 0,0,'C',1);

     //articulos ENCABEZACOS 192
     $fpdf-> SetDrawColor(0,0,0);
     $fpdf-> SetXY(10,86);
     $fpdf-> SetFont('arial', 'I', 8);
     // $fpdf-> SetfillColor(96, 134, 161 );

     $fpdf-> Cell(20,5, 'CANTIDAD', 0,0,'C',1);//borde
     $fpdf-> Cell(30,5, 'UNIDAD', 0,0,'C',1);//borde
     $fpdf-> Cell(76,5, 'DESCRIPCIÓN', 0,0,'C',1);//borde
     $fpdf-> Cell(35,5, 'VALOR UNITARIO', 0,0,'R',1);//borde
     $fpdf-> Cell(35,5, 'TOTAL', 0,0,'R',1);//borde

     //ARTICULOS 
     $bordecelda = 0;
     $fpdf-> SetFont('arial', '', 9);


     //fila1
     $fpdf-> SetXY(10,95);
     $fpdf-> Cell(20,5, $f1_cantidad, $bordecelda,0,'C',0);//borde
     $fpdf-> Cell(30,5, $f1_unidad, $bordecelda,0,'C',0);//borde
     //maximo 250 caracteres
     $fpdf-> MultiCell(76,4, $f1_descripcion, $bordecelda,'J',0);//borde
     $fpdf-> SetXY(136,95);
     $fpdf-> Cell(35,5, formatoMonedaMXN($f1_valUnitario), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(35,5, formatoMonedaMXN($f1_total), $bordecelda,0,'R',0);//borde

     //fila2
     $fpdf-> SetXY(10,118);
     $fpdf-> Cell(20,5, $f2_cantidad, $bordecelda,0,'C',0);//borde
     $fpdf-> Cell(30,5, $f2_unidad, $bordecelda,0,'C',0);//borde
     //maximo 250 caracteres
     $fpdf-> MultiCell(76,4, $f2_descripcion, $bordecelda,'J',0);//borde
     $fpdf-> SetXY(136,118);
     $fpdf-> Cell(35,5, formatoMonedaMXN($f2_valUnitario), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(35,5, formatoMonedaMXN($f2_total), $bordecelda,0,'R',0);//borde

     //fila3
     $fpdf-> SetXY(10,141);
     $fpdf-> Cell(20,5, $f3_cantidad, $bordecelda,0,'C',0);//borde
     $fpdf-> Cell(30,5, $f3_unidad, $bordecelda,0,'C',0);//borde
     //maximo 250 caracteres
     $fpdf-> MultiCell(76,4, $f3_descripcion, $bordecelda,'J',0);//borde
     $fpdf-> SetXY(136,141);
     $fpdf-> Cell(35,5, formatoMonedaMXN($f3_valUnitario), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(35,5, formatoMonedaMXN($f3_total), $bordecelda,0,'R',0);//borde

     //fila4
     $fpdf-> SetXY(10,164);
     $fpdf-> Cell(20,5, $f4_cantidad, $bordecelda,0,'C',0);//borde
     $fpdf-> Cell(30,5, $f4_unidad, $bordecelda,0,'C',0);//borde
     //maximo 250 caracteres
     $fpdf-> MultiCell(76,4, $f4_descripcion, $bordecelda,'J',0);//borde
     $fpdf-> SetXY(136,164);
     $fpdf-> Cell(35,5, formatoMonedaMXN($f4_valUnitario), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(35,5, formatoMonedaMXN($f4_total), $bordecelda,0,'R',0);//borde

     //COSTOS
     //SUBTOTAL
     $fpdf-> SetXY(141,187);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 9);
     $fpdf-> Cell(35,5, 'SUBTOTAL', $bordecelda,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 10);
     $fpdf-> Cell(25,5, formatoMonedaMXN($subtotal), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(5,5, '', $bordecelda,0,'R',0);//borde

     //IVA
     $fpdf-> SetXY(141,192);
     $fpdf-> SetFont('arial', 'I', 9);
     $fpdf-> Cell(35,5, 'IVA', $bordecelda,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 10);
     $fpdf-> Cell(25,5, formatoMonedaMXN($iva), $bordecelda,0,'R',0);//borde
     $fpdf-> Cell(5,5, '', $bordecelda,0,'R',0);//borde


     //TOTAL
     $fpdf-> SetXY(141,197);
     $fpdf-> SetFont('arial', 'I', 9);
     $fpdf-> Cell(35,5, 'TOTAL', $bordecelda,0,'L',1);//borde
     $fpdf-> SetFont('arial', '', 10);
     $fpdf-> Cell(25,5, formatoMonedaMXN($total), $bordecelda,0,'R',1);//borde
     $fpdf-> Cell(5,5, '', $bordecelda,0,'R',1);//borde
     $fpdf-> SetXY(190,203);
     $fpdf-> Cell(10,5, 'MXN', $bordecelda,1,'R',0);//borde

     


     // bottom
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,210,206,210);
     $fpdf-> Line(10,211,206,211);
//SECCION Productos y Servicios

//Tiempo de Entrega
     //guias
     // linea  horizontal
     // $fpdf-> Line(10,187,206,187);

     $fpdf-> SetXY(20,189);
     $fpdf-> SetFont('arial', 'B', 9);
     $fpdf-> Cell(35,5, 'TIEMPO DE ENTREGA: ', $bordecelda,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 9);
     $fpdf-> Cell(25,5, $tiempoEntrega.'  días hábiles', $bordecelda,0,'C',0);//borde

     $fpdf-> SetXY(20,194);
     foreach($notas as $fila){
          $fpdf-> SetX(20);
          $fpdf-> SetFont('arial', 'B', 9);
          $fpdf-> Cell(35,5, $fila, $bordecelda,0,'L',0);//borde
          $fpdf-> Ln(5);
     }
     
//Tiempo de Entrega


//SECCION Datos Bancarios
     $bordesDatosBanco = 0;

     $fpdf-> SetXY(10,213);
     $fpdf-> SetfillColor(189,189,189);
     $fpdf-> SetFont('arial', 'I', 8);
     $fpdf-> Cell(196,6, '    DATOS BANCARIOS', 0,0,'L',1);

     $fpdf-> SetXY(12,222);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'Transferencia Bancaria a la siguiente cuenta:', $bordesDatosBanco,0,'L',0);//borde

     $fpdf-> SetXY(12,227);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'BANCO:', $bordesDatosBanco,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(140,5, 'Banorte', $bordesDatosBanco,0,'L',0);//borde

     $fpdf-> SetXY(12,232);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'TITULAR:', $bordesDatosBanco,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(140,5, 'Israel Navarrete Aguilar', $bordesDatosBanco,0,'L',0);//borde

     $fpdf-> SetXY(12,237);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'CUENTA:', $bordesDatosBanco,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(140,5, '499161497', $bordesDatosBanco,0,'L',0);//borde

     $fpdf-> SetXY(12,242);
     $fpdf-> SetFont('arial', 'B', 8);
     $fpdf-> Cell(20,5, 'CLABE:', $bordesDatosBanco,0,'L',0);//borde
     $fpdf-> SetFont('arial', '', 8);
     $fpdf-> Cell(140,5, '072580004991614978', $bordesDatosBanco,0,'L',0);//borde

     // bottom
     // $fpdf-> SetDrawColor(255, 182, 3 );
     // $fpdf-> Line(10,250,206,250);
     // $fpdf-> Line(10,251,206,251);

//SECCION Datos Bancarios


//Footer
     $fpdf-> SetDrawColor(255, 182, 3 );
     $fpdf-> Line(10,250,206,250);
     $fpdf-> Line(10,251,206,251);

     $fpdf-> SetXY(12,254);
     $fpdf-> SetFont('arial', 'B', 14);
     $fpdf-> SetTextColor(196,55,44,255);
     $fpdf-> Cell(55,5, 'contacto@servicombasculas.com.mx', 0,0,'L',0);
     $fpdf-> SetXY(137,254);
     $fpdf-> Cell(50,5, 'servicombasculas.com.mx', 0,0,'L',0);

//Footer

// bottom
$fpdf-> SetDrawColor(255, 182, 3 );
// $fpdf-> Line(10,190,206,190);
// $fpdf-> Line(10,221,206,221);




$fpdf-> OutPut('F','../filesPDF/COT SERVICOM '.$numero_coti.'.pdf');
$fpdf-> OutPut('I','COT SERVICOM '.$numero_coti.'.pdf');

?>