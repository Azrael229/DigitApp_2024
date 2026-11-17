'use strict';

// Inicializa los directorios con filtros ordinarios por columna.
$(function () {
    const hasAction = Boolean(document.querySelector('#catalogo-table thead .table-action-column'));
    const table = $('#catalogo-table').DataTable(getDataTableOptions({
        pageLength: 10,
        order: [[0, 'desc']],
        orderCellsTop: true,
        responsive: false,
        columnDefs: hasAction ? [{targets: [-1], orderable: false, searchable: false}] : []
    }));
    applyColumnFilters(table);
});
