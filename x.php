var selectStatCoti = document.getElementById('sel_status_coti');

function selStatus(id){
    
    let idCot = id;
    let idStat = selectStatCoti.value;

    console.log(idStat);

    const formData = new FormData();

    // Agregar valores al FormData
    formData.append('id_cot', idCot);
    formData.append('id_status', idStat); 

    fetch('querys/update_coti_status.php',{
     
        method: 'POST', 
        body: formData
        
    })
    .then(response => response.json())
    .then(resp => { 

        // console.log(resp);
        location.reload();       
    
    })
}






<option value="" style="background-color: rgba(11, 26, 165, 0.877);">Espera</option>   
<option value="" style="background-color: rgba(11, 201, 11, 0.795);">Aceptada</option>   
<option value="" style="background-color: rgba(201, 11, 11, 0.795);">Cancelada</option>






<!-- Encabezados -->
<div class="row text-center mb-3">
            <div class="col-1 border">
                <h6>Cantidad</h6>
            </div>
            <div class="col-2 border">
                <h6>Unidad</h6>
            </div>
            <div class="col-5 border">
                <h6>Descripción</h6>
            </div>
            <div class="col-2 border">
                <h6>Valor Unitario</h6>
            </div>
            <div class="col-2 border">
                <h6>Total</h6>
            </div>
        </div>
        <!-- Encabezados -->

        <!-- fila 1 -->
        <div class="row mb-4">
            <div class="col-1 border">
                <input type="number" class="col-12">        
            </div>
            <div class="col-2 border">
                <div class="mb-3">                  
                    <select
                        class="form-select form-select-sm"
                        name=""
                        id=""
                    >
                        <option selected>Select one</option>
                        <option value="">Servicio</option>
                        <option value="">Pieza</option>
                    </select>
                </div>
                
            </div>
            <div class="col-5 border">
                <textarea name="" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea>
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
        </div>
        <!-- fila 1 -->

        <!-- fila 2 -->
        <div class="row mb-4">
            <div class="col-1 border">
                <input type="number" class="col-12">        
            </div>
            <div class="col-2 border">
                <div class="mb-3">                  
                    <select
                        class="form-select form-select-sm"
                        name=""
                        id=""
                    >
                        <option selected>Select one</option>
                        <option value="">Servicio</option>
                        <option value="">Pieza</option>
                    </select>
                </div>
                
            </div>
            <div class="col-5 border">
                <textarea name="" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea>
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
        </div>
        <!-- fila 2 -->

        <!-- fila 3 -->
        <div class="row mb-4">
            <div class="col-1 border">
                <input type="number" class="col-12">        
            </div>
            <div class="col-2 border">
                <div class="mb-3">                  
                    <select
                        class="form-select form-select-sm"
                        name=""
                        id=""
                    >
                        <option selected>Select one</option>
                        <option value="">Servicio</option>
                        <option value="">Pieza</option>
                    </select>
                </div>
                
            </div>
            <div class="col-5 border">
                <textarea name="" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea>
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
        </div>
        <!-- fila 3 -->

        <!-- fila 4 -->
        <div class="row mb-4">
            <div class="col-1 border">
                <input type="number" class="col-12">        
            </div>
            <div class="col-2 border">
                <div class="mb-3">                  
                    <select
                        class="form-select form-select-sm"
                        name=""
                        id=""
                    >
                        <option selected>Select one</option>
                        <option value="">Servicio</option>
                        <option value="">Pieza</option>
                    </select>
                </div>
                
            </div>
            <div class="col-5 border">
                <textarea name="" id="" cols="60" rows="4" style="resize: none;" maxlength="260"></textarea>
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
            <div class="col-2 border">
                <input type="number">
            </div>
        </div>
        <!-- fila 4 -->