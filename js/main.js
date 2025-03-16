let table = new DataTable('#example', {
     // options
     // responsive: true,
     order: [[0, 'desc']],
     pageLength: 20,
     responsive: true, 
     autoWidth: false,
     columnDefs: [
        { width: "80px", targets: 0 },  // ID
        { width: "120px", targets: 1, className: "no-wrap" }, // FECHA
        { width: "200px", targets: 2 }, // EMPRESA
        { width: "100px", targets: 3, className: "min-width-contacto" }, // CONTACTO
        { width: "100px", targets: 4, className: "no-wrap"}, // IMPORTE
        { width: "150px", targets: 5, className: "no-wrap"}  // DESCARGAR PDF
     ]
 });



