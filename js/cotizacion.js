$('#select_contacto').select2({
     width: '100%'
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
var selectDireccion = document.getElementById('select_direccion');
var inputNombreContacto = document.getElementById('nombre_contacto');
var inputCorreoContacto = document.getElementById('correo_contacto');
var inputCelContacto = document.getElementById('cel_contacto');
var inputDeptoContacto = document.getElementById('depto_contacto');
var direccionAntigua = '';
var direccionesEmpresa = [];

// Convierte una direccion estructurada en el texto que se muestra y se envia al PDF.
function formatearDireccion(direccion) {
     var primeraLinea = [
          direccion.calle,
          direccion.numero_exterior ? 'No. ' + direccion.numero_exterior : '',
          direccion.numero_interior ? 'Int. ' + direccion.numero_interior : ''
     ].filter(Boolean).join(' ');
     var ubicacion = [
          direccion.colonia ? 'Col. ' + direccion.colonia : '',
          direccion.localidad,
          direccion.municipio && direccion.municipio !== direccion.ciudad ? direccion.municipio : '',
          direccion.ciudad,
          direccion.estado,
          direccion.codigo_postal ? 'C.P. ' + direccion.codigo_postal : '',
          direccion.pais
     ].filter(Boolean).join(', ');
     var partes = [primeraLinea, ubicacion, direccion.entre_calles, direccion.referencia].filter(Boolean);

     return partes.length ? partes.join(', ') : (direccion.direccion_original || '');
}

// Carga las alternativas disponibles para la empresa seleccionada.
function cargarSelectorDirecciones() {
     selectDireccion.innerHTML = '';

     if (direccionAntigua) {
          selectDireccion.add(new Option('Dirección antigua', 'antigua'));
     }

     direccionesEmpresa.forEach(function (direccion) {
          var etiqueta = direccion.tipo_direccion === 'fiscal' ? 'Dirección fiscal' : 'Dirección de entrega';
          if (direccion.alias) {
               etiqueta += ' - ' + direccion.alias;
          }
          selectDireccion.add(new Option(etiqueta, 'nueva:' + direccion.id));
     });

     if (!selectDireccion.options.length) {
          selectDireccion.add(new Option('Sin direcciones registradas', ''));
          inputDirEmpresa.value = '';
          return;
     }

     actualizarDireccionSeleccionada();
}

// Muestra en el formulario la direccion elegida para que el PDF reciba el mismo texto.
function actualizarDireccionSeleccionada() {
     if (selectDireccion.value === 'antigua') {
          inputDirEmpresa.value = direccionAntigua;
          return;
     }

     var direccionId = selectDireccion.value.replace('nueva:', '');
     var direccion = direccionesEmpresa.find(function (item) {
          return String(item.id) === direccionId;
     });

     inputDirEmpresa.value = direccion ? formatearDireccion(direccion) : '';
}

// Carga los datos del contacto y las direcciones disponibles de su empresa.
function selectContacto(){
    let id = selectNombContacto.value;

     if (!id) {
          direccionAntigua = '';
          direccionesEmpresa = [];
          cargarSelectorDirecciones();
          return;
     }

     fetch('../backend/contactos/query_id_contacto.php',{

          method: 'POST', 
          body: id
      
      })
      .then(response => response.json())
      .then(data => { 
          if (data.error) {
               throw new Error(data.error);
          }

          inputNombreEmpresa.value = data['empresa'] || '';
          inputNombreContacto.value = data['nombre'] || '';
          inputCorreoContacto.value = data['correo'] || '';
          inputCelContacto.value = data['celular'] || '';
          inputDeptoContacto.value = data['depto'] || '';
          direccionAntigua = data['dir_entrega'] || '';
          direccionesEmpresa = data['direcciones'] || [];
          cargarSelectorDirecciones();
     })
     .catch(function () {
          direccionAntigua = '';
          direccionesEmpresa = [];
          cargarSelectorDirecciones();
     });
}

selectDireccion.addEventListener('change', actualizarDireccionSeleccionada);


var inputCantidadF1 = document.getElementById('f1_cant');
var inputValorUnitF1 = document.getElementById('f1_valUnit');
var inputTotalF1 = document.getElementById('f1_total');

// Calcula el total oculto del primer concepto y actualiza el resumen.
function operacionTotalF1(){
     let resultadoF1 = inputCantidadF1.value * inputValorUnitF1.value;
     inputTotalF1.value = resultadoF1.toFixed(2);
     totalizar();
}

var inputCantidadF2 = document.getElementById('f2_cant');
var inputValorUnitF2 = document.getElementById('f2_valUnit');
var inputTotalF2 = document.getElementById('f2_total');

// Calcula el total oculto del segundo concepto y actualiza el resumen.
function operacionTotalF2(){
     let resultadoF2 = inputCantidadF2.value * inputValorUnitF2.value;
     inputTotalF2.value = resultadoF2.toFixed(2);
     totalizar();
}

var inputCantidadF3 = document.getElementById('f3_cant');
var inputValorUnitF3 = document.getElementById('f3_valUnit');
var inputTotalF3 = document.getElementById('f3_total');

// Calcula el total oculto del tercer concepto y actualiza el resumen.
function operacionTotalF3(){
     let resultadoF3 = inputCantidadF3.value * inputValorUnitF3.value;
     inputTotalF3.value = resultadoF3.toFixed(2);
     totalizar();
}

var inputCantidadF4 = document.getElementById('f4_cant');
var inputValorUnitF4 = document.getElementById('f4_valUnit');
var inputTotalF4 = document.getElementById('f4_total');

// Calcula el total oculto del cuarto concepto y actualiza el resumen.
function operacionTotalF4(){
     let resultadoF4 = inputCantidadF4.value * inputValorUnitF4.value;
     inputTotalF4.value = resultadoF4.toFixed(2);
     totalizar();
}


var inputSubtotal = document.getElementById('subtotal');
var inputIva = document.getElementById('iva');
var inputTotal = document.getElementById('total');

// Suma los conceptos y actualiza subtotal, IVA y total de la cotización.
function totalizar(){
     let totF1 = Number(inputTotalF1.value);
     let totF2 = Number(inputTotalF2.value);
     let totF3 = Number(inputTotalF3.value);
     let totF4 = Number(inputTotalF4.value);
     let subtotal = totF1 + totF2 + totF3 + totF4;
     inputSubtotal.value = subtotal.toFixed(2);
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
     
          fetch('../backend/cotizaciones/add_cotizacion.php',{
     
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
