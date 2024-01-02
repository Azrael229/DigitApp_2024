<?php

function formatoMonedaMXN($numero) {
     if ($numero == ""){
          return "";
     }
     return "$" . "  " . number_format($numero, 2, '.', ' '); // Dos decimales, punto como separador decimal y coma como separador de miles
 }






