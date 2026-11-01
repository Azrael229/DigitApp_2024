const billingHasSelection = document.querySelector('#example thead .factura-select-all, #example thead #seleccionar-pagina');
const billingOffset = billingHasSelection ? 1 : 0;
const table = new DataTable('#example', getDataTableOptions({
    order: [[billingOffset, 'desc']],
    columnDefs: [
        ...(billingOffset ? [{ width: "44px", targets: 0, orderable: false, searchable: false }] : []),
        { width: "110px", targets: billingOffset, className: "no-wrap factura-fecha" },
        { width: "240px", targets: billingOffset + 1 },
        { width: "300px", targets: billingOffset + 2, className: "factura-wrap" },
        { width: "120px", targets: billingOffset + 3, className: "no-wrap text-end" },
        { width: "120px", targets: billingOffset + 4, className: "no-wrap text-end" },
        { width: "120px", targets: billingOffset + 5, className: "no-wrap text-end" },
        { width: "100px", targets: billingOffset + 6, className: "no-wrap" }
    ]
}));

applyColumnFilters(table);

function parseValue(value) {
    if (typeof value === 'string') {
        return parseFloat(value.replace(/,/g, '')) || 0;
    }
    return typeof value === 'number' ? value : 0;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
}

function setText(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

// Suma los importes de una columna únicamente para las filas que superan los filtros activos.
function getColumnTotal(api, columnIndex) {
    return api.rows({ search: 'applied' }).nodes().toArray().reduce((total, row) => {
        const cell = row.cells[columnIndex];
        return total + parseValue(cell ? cell.getAttribute('data-value') : 0);
    }, 0);
}

// Recalcula los subtotales sobre el conjunto que DataTables deja visible tras los filtros.
function updateFacturaSummary(api) {
    const registros = api.rows({ search: 'applied' }).count();
    const subtotal = getColumnTotal(api, 3 + billingOffset);
    const iva = getColumnTotal(api, 4 + billingOffset);
    const total = getColumnTotal(api, 5 + billingOffset);

    setText('total-subtotal', formatCurrency(subtotal));
    setText('total-iva', formatCurrency(iva));
    setText('total-general', formatCurrency(total));
    setText('card-registros', registros.toString());
    setText('card-subtotal', formatCurrency(subtotal));
    setText('card-iva', formatCurrency(iva));
    setText('card-total', formatCurrency(total));
}

updateFacturaSummary(table);
table.on('draw', () => updateFacturaSummary(table));
table.on('search', () => updateFacturaSummary(table));
table.on('page', () => updateFacturaSummary(table));
table.on('length', () => updateFacturaSummary(table));
