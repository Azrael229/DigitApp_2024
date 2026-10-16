'use strict';

// Inicializa los directorios con filtros ordinarios por columna y resumen filtrado.
$(function () {
    const hasAction = Boolean(document.querySelector('#catalogo-table thead .table-action-column'));
    const table = $('#catalogo-table').DataTable(getDataTableOptions({
        pageLength: 25,
        order: [[0, 'desc']],
        orderCellsTop: true,
        responsive: false,
        columnDefs: hasAction ? [{targets: [-1], orderable: false, searchable: false}] : []
    }));
    applyColumnFilters(table);

    // Refleja todas las filas coincidentes, incluso cuando ocupan varias páginas.
    function updateCatalogSummary() {
        document.getElementById('catalog-records').textContent = table.rows({search: 'applied'}).count();
    }

    table.on('draw', updateCatalogSummary);
    updateCatalogSummary();
});
