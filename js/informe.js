$('#select_empresa').select2({
});

var inputNumInforme = document.getElementById('input_num_inf');
// Genera un numero unico y lo pinta en el input Numero de informe
function generarNumeroUnico() {
     const fechaActual = new Date().getTime(); // Obtiene la marca de tiempo actual en milisegundos
     const numeroAleatorio = Math.floor(Math.random() * 10000); // Genera un número aleatorio entre 0 y 9999
   
     const numeroUnico = `${fechaActual}${numeroAleatorio}`; // Combina la marca de tiempo y el número aleatorio

     inputNumInforme.value = numeroUnico;
     
}
generarNumeroUnico()



var inputNombreEmpresa = document.getElementById('nombre_empresa');
var inputDirEmpresa = document.getElementById('dir_empresa');
var selectNombEmpresa = document.getElementById('select_empresa');
var inputNombreContacto = document.getElementById('nombre_contacto');
var inputCorreoContacto = document.getElementById('correo_contacto');

// pinta los inputs de empresa y contacto segun la empresa seleccionada en el selector 
function selectEmpresa(){
     // console.log(selectNombEmpresa.value);
    let id = selectNombEmpresa.value;

     fetch('querys/query_id_empresa.php',{

          method: 'POST', 
          body: id
      
      })
      .then(response => response.json())
      .then(data => { 
                                  
          // console.log(data['id']);

          inputNombreEmpresa.innerHTML = data['razon_social'];
          inputDirEmpresa.innerHTML = data['dir_entrega'];
          
     })

     fetch('querys/query_contacto_id_emp.php',{

          method: 'POST', 
          body: id
      
      })
      .then(response => response.json())
      .then(data => { 
                                  
          // console.log(data['id']);

          inputNombreContacto.innerHTML = data['nombre'];
          inputCorreoContacto.innerHTML = data['correo'];
          
     })

}



var unit;
function obtenerUnidades(){
    
    unidades.forEach(function(opcion) {
        if(opcion.checked){  
            
            unit = opcion.value;
        }
    } )
    
}


var inputClase = document.getElementById('clase');
var inputMin = document.getElementById('min');
var btnAnalizar = document.getElementById('btn_analizar');
var max = document.getElementById('max');
var e = document.getElementById('e');
var d = document.getElementById('d');
var maxVal;
var eVal;
var dVal;
var divisiones;
var clase;
var min;

btnAnalizar.addEventListener('click', function(){
     
     maxVal = max.value;
     eVal = e.value;
     dVal = d.value;

     divisiones = Number(maxVal)/Number(eVal);

     // console.log(divisiones);

     if (divisiones <= 1000){
        
          min = eVal * 10;
          clase = "Ordinaria";

          inputClase.value = clase;
          inputMin.value = min
          
      }else if (divisiones <= 10000 ){
          
          min = eVal * 20;
          clase = "Media";

          inputClase.value = clase;
          inputMin.value = min
          
      }else if (divisiones <= 100000){
  
          min = eVal * 50;
          clase = "Fina";

          inputClase.value = clase;
          inputMin.value = min
  
      }else if (divisiones > 100000 ){
  
          min = eVal * 50;
          clase = "Especial";

          inputClase.value = clase;
          inputMin.value = min
  
      }
})