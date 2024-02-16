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





function obtenerUnidades(){

    var spunit = document.getElementsByClassName('unidades');
    var unidades = document.querySelector('input[name="unidad"]:checked');

    if(unidades){ 
        
        let unit = unidades.value;

        // Utilizar un bucle for...of para iterar sobre la colección
        for (var span of spunit) {
        // Cambiar el contenido de cada span utilizando innerHTML
        span.innerHTML = unit;
        }
        
    }
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
var spIntervalo1Col2 = document.getElementById('sp_interv1_col2');
var spIntervalo2Col1 = document.getElementById('sp_interv2_col1');
var spIntervalo2Col2 = document.getElementById('sp_interv2_col2');
var spIntervalo3Col1 = document.getElementById('sp_interv3_col1');
var spIntervalo3Col2 = document.getElementById('sp_interv3_col2');
var spEMT1 = document.getElementById('emt_1');
var spEMT2 = document.getElementById('emt_2');
var spEMT3 = document.getElementById('emt_3');


//realiza el analisis de los EMT Repetibilidad, excentricidad, Exactitud y los pinta en la tabla
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

          //   se determinan los valores de intervalos de tabla EMT
          prim = eVal * 50;
          second = eVal * 200;
          tertio = eVal * 1000; 
          
      }else if (divisiones <= 10000 ){
          
          min = eVal * 20;
          clase = "Media";

            // se pintan los campos clase y min de datos de abscula
          inputClase.value = clase;
          inputMin.value = min

        //   se determinan los valores de intervalos de tabla EMT
            prim = eVal * 500;
            second = eVal * 2000;
            tertio = eVal * 10000;         

      }else if (divisiones <= 100000){
  
          min = eVal * 50;
          clase = "Fina";

          inputClase.value = clase;
          inputMin.value = min

          //   se determinan los valores de intervalos de tabla EMT
          prim = eVal * 5000;
          second = eVal * 20000;
          tertio = eVal * 100000; 
  
      }else if (divisiones > 100000 ){
  
          min = eVal * 50;
          clase = "Especial";

          inputClase.value = clase;
          inputMin.value = min

          //   se determinan los valores de intervalos de tabla EMT
          prim = eVal * 50000;
          second = eVal * 200000;
          tertio = eVal * 1000000; 
      }

      // EMT para inspeccion periodica
      emt1 = eVal * 1;
      emt2 = eVal * 2;
      emt3 = eVal * 3;

      // se pintan los valores de intervalos de la tabla EMT
      spIntervalo1Col2.innerHTML = prim;
      spIntervalo2Col1.innerHTML = prim + Number(dVal);
      spIntervalo2Col2.innerHTML = second;
      spIntervalo3Col1.innerHTML = second + Number(dVal);
      spIntervalo3Col2.innerHTML = maxVal;

      //pintar los emt en la tabla 
      spEMT1.innerHTML = emt1;
      spEMT2.innerHTML = emt2;
      spEMT3.innerHTML = Math.round(emt3 * 100)/100;

    obtenerUnidades();
    valRepe();
    valExce();
    exactitud();
})

const spRepe = document.getElementById('sp_repe');

function valRepe(){
    let valRepet = Number(maxVal) / 2 ;
    spRepe.innerHTML =  Math.round(Number(valRepet) * 10)/10;
}

const spExce = document.getElementById('sp_exce');

function valExce(){
    let valExce = Number(maxVal) / 3 ;
    spExce.innerHTML =  Math.round(Number(valExce) * 1)/1;
}

const inputExact1 = document.getElementById('exact1');
const inputExact2 = document.getElementById('exact2');
const inputExact3 = document.getElementById('exact3');
const inputExact4 = document.getElementById('exact4');
const inputExact5 = document.getElementById('exact5');

function exactitud(){
    let exactitud1 = Number(maxVal)/5;
    let exactitud2 = Number(exactitud1)*2;
    let exactitud3 = Number(exactitud1)*3;
    let exactitud4 = Number(exactitud1)*4;
    let exactitud5 = Number(exactitud1)*5;


    inputExact1.value =  Math.round(Number(exactitud1) * 1)/1;
    inputExact2.value =  Math.round(Number(exactitud2) * 1)/1;
    inputExact3.value =  Math.round(Number(exactitud3) * 1)/1;
    inputExact4.value =  Math.round(Number(exactitud4) * 1)/1;
    inputExact5.value =  Math.round(Number(exactitud5) * 1)/1;
}


function evaluarRepetibilidad(){

    
}