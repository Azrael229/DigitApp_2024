$('#select_contacto').select2({
});



//funcion que saca un aviso cuando se quiere cambiar o recargar la pagina cotizacion
window.addEventListener('beforeunload', function (event) {
     // Ejecutar acciones antes de que la página se descargue
     // Puedes mostrar un mensaje al usuario para confirmar si quiere dejar la página o realizar alguna acción adicional.
     // Sin embargo, la manipulación directa o el rastreo del cierre del navegador no es posible de manera confiable por razones de seguridad.
     // Devuelve un mensaje (algunos navegadores lo ignoran) para mostrar al usuario.
     event.preventDefault();
     event.returnValue = '¿Al salir de esta página se perderán los daots no guardados?';
});





var inputNumCotizacion = document.getElementById('numero_coti');
// Genera un numero unico y lo pinta en el input Numero de informe
function generarNumeroUnico() {
     const fechaActual = new Date().getTime(); // Obtiene la marca de tiempo actual en milisegundos
     const numeroAleatorio = Math.floor(Math.random() * 10000); // Genera un número aleatorio entre 0 y 9999
   
     const numeroUnico = `Q ${fechaActual}${numeroAleatorio}`; // Combina la marca de tiempo y el número aleatorio

     inputNumCotizacion.value = numeroUnico;
     
}
generarNumeroUnico()






var inputNombreEmpresa = document.getElementById('nombre_empresa');
var inputDirEmpresa = document.getElementById('dir_empresa');
var selectNombContacto = document.getElementById('select_contacto');
var inputNombreContacto = document.getElementById('nombre_contacto');
var inputCorreoContacto = document.getElementById('correo_contacto');
var inputCelContacto = document.getElementById('cel_contacto');
var inputDeptoContacto = document.getElementById('depto_contacto');

// pinta los inputs de empresa y contacto segun la empresa seleccionada en el selector
function selectContacto(){
     // console.log(selectNombContacto.value);
    let id = selectNombContacto.value;

     fetch('querys/query_id_contacto.php',{

          method: 'POST', 
          body: id
      
      })
      .then(response => response.json())
      .then(data => { 
                                  
          // console.log(data['id']);

          inputNombreEmpresa.innerHTML = data['empresa'];
          inputDirEmpresa.innerHTML = data['dir_entrega'];
          inputNombreContacto.innerHTML = data['nombre'];
          inputCorreoContacto.innerHTML = data['correo'];
          inputCelContacto.innerHTML = data['celular'];
          inputDeptoContacto.innerHTML = data['depto'];
     })
}


var inputCantidadF1 = document.getElementById('f1_cant');
var inputValorUnitF1 = document.getElementById('f1_valUnit');
var inputTotalF1 = document.getElementById('f1_total');
var inputUnitarioDistF1 = document.getElementById('f1_unitario_dist');
var inputTotalDistF1 = document.getElementById('f1_total_dist');

//Funcion que pinta en la columa total fila1 
function operacionTotalF1(){
     let resultadoF1 = inputCantidadF1.value * inputValorUnitF1.value;
     let resultadoDistF1 = inputCantidadF1.value * inputUnitarioDistF1.value;

     inputTotalF1.value = resultadoF1.toFixed(2);
     inputTotalDistF1.value = resultadoDistF1.toFixed(2);

     totalizar();
     resultUtilidad();

}

var inputCantidadF2 = document.getElementById('f2_cant');
var inputValorUnitF2 = document.getElementById('f2_valUnit');
var inputTotalF2 = document.getElementById('f2_total');
var inputUnitarioDistF2 = document.getElementById('f2_unitario_dist');
var inputTotalDistF2 = document.getElementById('f2_total_dist');

//Funcion que pinta en la columa total fila2 
function operacionTotalF2(){
     let resultadoF2 = inputCantidadF2.value * inputValorUnitF2.value;
     let resultadoDistF2 = inputCantidadF2.value * inputUnitarioDistF2.value;

     inputTotalF2.value = resultadoF2.toFixed(2);
     inputTotalDistF2.value = resultadoDistF2.toFixed(2);

     totalizar();
     resultUtilidad();

}

var inputCantidadF3 = document.getElementById('f3_cant');
var inputValorUnitF3 = document.getElementById('f3_valUnit');
var inputTotalF3 = document.getElementById('f3_total');
var inputUnitarioDistF3 = document.getElementById('f3_unitario_dist');
var inputTotalDistF3 = document.getElementById('f3_total_dist');

//Funcion que pinta en la columa total fila3 
function operacionTotalF3(){
     let resultadoF3 = inputCantidadF3.value * inputValorUnitF3.value;
     let resultadoDistF3 = inputCantidadF3.value * inputUnitarioDistF3.value;

     inputTotalF3.value = resultadoF3.toFixed(2);
     inputTotalDistF3.value = resultadoDistF3.toFixed(2);

     totalizar();
     resultUtilidad();

}

var inputCantidadF4 = document.getElementById('f4_cant');
var inputValorUnitF4 = document.getElementById('f4_valUnit');
var inputTotalF4 = document.getElementById('f4_total');
var inputUnitarioDistF4 = document.getElementById('f4_unitario_dist');
var inputTotalDistF4 = document.getElementById('f4_total_dist');

//Funcion que pinta en la columa total fila4 
function operacionTotalF4(){
     let resultadoF4 = inputCantidadF4.value * inputValorUnitF4.value;
     let resultadoDistF4 = inputCantidadF4.value * inputUnitarioDistF4.value;

     inputTotalF4.value = resultadoF4.toFixed(2);
     inputTotalDistF4.value = resultadoDistF4.toFixed(2);

     totalizar();
     resultUtilidad();
}


var inputSubtotal = document.getElementById('subtotal');
var inputIva = document.getElementById('iva');
var inputTotal = document.getElementById('total');
var inputSubtotalDist = document.getElementById('subtotal_dist');
var inputIvaDist = document.getElementById('iva_dist');
var inputTotalDist = document.getElementById('total_dist');
var inputSubutilidad = document.getElementById('subutilidad');
var inputTotalUtilidad = document.getElementById('total_utilidad');

//funcion que suma los resultados Total de cada fila, calcula el iva y TOTAL
function totalizar(){
     
     //colocar los valores numericos en variables
     let totF1 = Number(inputTotalF1.value);
     let totF2 = Number(inputTotalF2.value);
     let totF3 = Number(inputTotalF3.value);
     let totF4 = Number(inputTotalF4.value);

     let totDistF1 =Number(inputTotalDistF1.value);
     let totDistF2 =Number(inputTotalDistF2.value);
     let totDistF3 =Number(inputTotalDistF3.value);
     let totDistF4 =Number(inputTotalDistF4.value);

     let gastosOper = Number(inputGastosOper.value);

     //sumatoria de todas las filas par los campos de SUBTOTLAES
     let subtotal = totF1 + totF2 + totF3 + totF4;
     let subtotalDist = totDistF1 + totDistF2 + totDistF3 + totDistF4;
     // console.log(typeof totF4);

     //pinta en los inputs de los subtotales
     inputSubtotal.value = subtotal.toFixed(2);
     inputSubtotalDist.value = subtotalDist.toFixed(2);
     // console.log(typeof formatoMoneda(subtotal));

     //saca el iva de cada subtotal 
     let iva = subtotal * .16;
     let ivaDist = subtotalDist * .16;

     //pinta en los inputs del IVA
     inputIva.value = iva.toFixed(2);
     inputIvaDist.value = ivaDist.toFixed(2);

     // determina el total sumando iva + subtotal
     let total = iva + subtotal;
     let totalDist = ivaDist + subtotalDist;

     //pinta en los inputs Totales 
     inputTotal.value = total.toFixed(2);
     inputTotalDist.value = totalDist.toFixed(2);

     //diferencia de subtotal cliente - sobtotal distribuidor
     let subutilidad = subtotal - subtotalDist;

     //pintar el resultado en el imput subutilidad
     inputSubutilidad.value = subutilidad.toFixed(2);

     //resultado subutilidad - gastos de operacion
     let totalUtilidad = subutilidad - gastosOper;

     //pinta en el input total Utilidad 
     inputTotalUtilidad.value = totalUtilidad.toFixed(2);



}


var inputGastosOper = document.getElementById('gastos_operacion');
var inputCostos = document.getElementById('costos');

function resultUtilidad(){
     //recoger el valor del input 
     let gastosOper = Number(inputGastosOper.value);
     let subutilidad = Number(inputSubutilidad.value);
     let totalDist = Number(inputTotalDist.value);

     //resultado gastos de operacion sin iva
     let subGastosOper = gastosOper / 1.16;

     //resultado
     let totalUtilidad = subutilidad - subGastosOper;
     //resultado de sumar gasots de operacion + total distribuidores
     let resGOTD =  gastosOper + totalDist;


     //pintar en el input total Utilidad
     inputTotalUtilidad.value = totalUtilidad.toFixed(2);
     //pintar el resultado
     inputCostos.value = resGOTD.toFixed(2);
}




//funcion que guarda la informacion de la cotizacion
//numero consecutivo----BD
//fecha de emision-----
//fecha de vigencia-----
// total-----
// empresa------
// contaacto-------
// no. cotizacion -----
// pdf-----
var btnGuardar = document.getElementById('btn_guardar');
var formulario = document.getElementById('form_cotizacion');

btnGuardar.addEventListener('click', function() {
     var confirmacion = confirm("Se guardará un archivo PDF en el sistema, desea continuar?");
     
     if(confirmacion == true){

          const formData = new FormData(formulario);
     
          fetch('querys/add_cotizacion.php',{
     
          method: 'POST', 
          body: formData
          
          })
          .then(response => response.json())
          .then(resp => { 
                    
               console.log(resp);       
          
          })
     }else{
          alert('El archivo no se guardó en el sistema');
     }

     // console.log('se hizo click en guardar')
})
