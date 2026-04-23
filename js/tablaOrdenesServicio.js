const tablaOrdenesServicio = new DataTable('#tablaOrdenesServicio', {
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    order: [[0, 'desc']],
    language: {
        search: 'Buscar:',
        searchPlaceholder: 'Empresa, contacto o proyecto',
        zeroRecords: 'No se encontraron ordenes de servicio con ese criterio.',
        emptyTable: 'No hay ordenes de servicio activas disponibles.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ ordenes',
        infoEmpty: 'Mostrando 0 a 0 de 0 ordenes',
        infoFiltered: '(filtradas de _MAX_ ordenes)',
        lengthMenu: 'Mostrar _MENU_ registros',
        paginate: {
            first: 'Primera',
            last: 'Ultima',
            next: 'Siguiente',
            previous: 'Anterior'
        }
    },
    columnDefs: [
        { targets: [6, 7, 8], orderable: false, searchable: false }
    ]
});

applyColumnFilters(tablaOrdenesServicio);
