

let table = new DataTable('#example', {
    // options
    responsive: true,
});


$('#contacto_nombre_empresa').select2({
});

var checkboxAsocEmpresa = document.getElementById('asoc_empresa');
var selectNombEmpresa = document.getElementById('contacto_nombre_empresa');

var inputId = document.getElementById('contacto_id');
var inputNombre = document.getElementById('contacto_nombre');
var inputCelular = document.getElementById('contacto_cel');
var inputEmail = document.getElementById('contacto_email');
var inputDepto = document.getElementById('contacto_depto');
var btnSubmitContacto = document.getElementById('btn_sumbit_contacto');
var spanEmpresa = document.getElementById('sp_empresa');



// funcion para editar contactos 
function editar(id){
    // id del contacto
    console.log(id);

    fetch('querys/query_id_contacto.php',{

        method: 'POST', 
        body: id
    
        })
        .then(response => response.json())
        .then(data => { 
                                  
            // console.log(data['empresa']);
           
            inputId.value = data['id'];
            inputNombre.innerHTML = data['nombre'];
            inputCelular.innerHTML = data['celular'];
            inputEmail.innerHTML = data['correo'];
            inputDepto.innerHTML = data['depto'];
            btnSubmitContacto.innerHTML = "Actualizar";
            spanEmpresa.innerHTML = `
            <div class="col-3 ">
                <label>Empresa</label>
            </div>
            <div class="col">
                <input type="text" value="${data['empresa']}">
            </div>
            `;
            selectNombEmpresa.value = data['id_empresa'];
        })

}