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
            
        location.reload();       
    
    })

 } )