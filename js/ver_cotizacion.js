'use strict';

// Activa paginación, búsqueda y filtros por columna en las partidas de la cotización.
const partidasCotizacionTable = new DataTable('#tabla-partidas-cotizacion', getDataTableOptions({
    order: [[0, 'asc']],
    orderCellsTop: true,
}));

applyColumnFilters(partidasCotizacionTable);
