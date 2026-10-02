// Conserva el orden del servidor para mostrar primero el contacto recién agregado.
var tablaContactos = new DataTable('#example', getDataTableOptions({ order: [] }));

applyColumnFilters(tablaContactos);
