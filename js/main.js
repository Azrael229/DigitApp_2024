let table = new DataTable('#example', {
     // options
     // responsive: true,
 });


 var btnNotasCalendar = document.getElementById('btn_notas_calendar');
 var formulario = document.getElementById('form_notas_calendar');

 btnNotasCalendar.addEventListener('click', function(){

    const formData = new FormData(formulario);

    fetch('querys/add_nota_calendar.php',{

    method: 'POST', 
    body: formData

    })
    .then(response => response.json())
    .then(resp => { 
        if (resp == "ok"){

            location.reload();       
        }else{
            alert('Selecciona un Mes');
        }       
    
    })

 });


 
 //  selectStatCoti.addEventListener('change', () =>{
     //     let detect = selectStatCoti.value;
     //     console.log(detect);
     //  })
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

