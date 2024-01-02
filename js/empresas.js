let table = new DataTable('#example', {
     // options
     responsive: true,
});

var inputNombre = document.getElementById('empresa_nombre');
var inputDirEntrega = document.getElementById('emp_dir_entrega');
var inputRazonSoc = document.getElementById('emp_razon_soc');
var inputRfc = document.getElementById('emp_rfc');
var inputDirFiscal = document.getElementById('emp_dir_fiscal');
var inputId = document.getElementById('empresa_id');
var inputProveedor = document.getElementById('emp_proveedor');
var inputCliente = document.getElementById('emp_cliente');
var btnSubmitEmp = document.getElementById('btn_submit_emp');

function editar(id){

    // console.log(id);

    fetch('querys/query_id_empresa.php',{

        method: 'POST', 
        body: id
    
    })
    .then(response => response.json())
    .then(data => { 
                                
        // console.log(data['id']);
        
        inputId.value = data['id_e'];
        inputNombre.innerHTML = data['empresa'];
        inputDirEntrega.innerHTML = data['dir_entrega'];
        inputRazonSoc.innerHTML = data['razon_social'];
        inputRfc.innerHTML = data['rfc'];
        inputDirFiscal.innerHTML = data['dir_fiscal'];
        btnSubmitEmp.innerHTML = "Actualizar";
        if (data['rol'] == "Proveedor"){

            inputProveedor.checked = true;
        }else{
            inputCliente.checked = true;
        }
    })
        
}
