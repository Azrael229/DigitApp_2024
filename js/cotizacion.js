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
//Funcion que pinta en la columa total fila1 
function operacionTotalF1(){
     let resultadoF1 = inputCantidadF1.value * inputValorUnitF1.value;
     inputTotalF1.value = resultadoF1.toFixed(2);
     totalizar();

}

var inputCantidadF2 = document.getElementById('f2_cant');
var inputValorUnitF2 = document.getElementById('f2_valUnit');
var inputTotalF2 = document.getElementById('f2_total');
//Funcion que pinta en la columa total fila2 
function operacionTotalF2(){
     let resultadoF2 = inputCantidadF2.value * inputValorUnitF2.value;
     inputTotalF2.value = resultadoF2.toFixed(2);
     totalizar();

}

var inputCantidadF3 = document.getElementById('f3_cant');
var inputValorUnitF3 = document.getElementById('f3_valUnit');
var inputTotalF3 = document.getElementById('f3_total');
//Funcion que pinta en la columa total fila3 
function operacionTotalF3(){
     let resultadoF3 = inputCantidadF3.value * inputValorUnitF3.value;
     inputTotalF3.value = resultadoF3.toFixed(2);
     totalizar();

}

var inputCantidadF4 = document.getElementById('f4_cant');
var inputValorUnitF4 = document.getElementById('f4_valUnit');
var inputTotalF4 = document.getElementById('f4_total');
//Funcion que pinta en la columa total fila4 
function operacionTotalF4(){
     let resultadoF4 = inputCantidadF4.value * inputValorUnitF4.value;
     inputTotalF4.value = resultadoF4.toFixed(2);

     totalizar();
}


var inputSubtotal = document.getElementById('subtotal');
var inputIva = document.getElementById('iva');
var inputTotal = document.getElementById('total');
//funcion que suma los resultados Total de cada fila, calcula el iva y TOTAL
function totalizar(){
     
     let totF1 = Number(inputTotalF1.value);
     let totF2 = Number(inputTotalF2.value);
     let totF3 = Number(inputTotalF3.value);
     let totF4 = Number(inputTotalF4.value);

     let subtotal = totF1 + totF2 + totF3 + totF4;
     // console.log(typeof totF4);
     inputSubtotal.value = subtotal.toFixed(2);
     // console.log(typeof formatoMoneda(subtotal));

     let iva = subtotal * .16;
     inputIva.value = iva.toFixed(2);


     let total = iva + subtotal;

     inputTotal.value = total.toFixed(2);

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
