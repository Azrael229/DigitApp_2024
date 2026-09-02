const table = new DataTable('#example', getDataTableOptions({
    order: [[0, 'desc']],
    columnDefs: [
        { width: "110px", targets: 0, className: "no-wrap factura-fecha" },
        { width: "240px", targets: 1 },
        { width: "300px", targets: 2, className: "factura-wrap" },
        { width: "120px", targets: 3, className: "no-wrap text-end" },
        { width: "120px", targets: 4, className: "no-wrap text-end" },
        { width: "120px", targets: 5, className: "no-wrap text-end" }
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

function getColumnTotal(api, columnIndex) {
    return api.column(columnIndex, { search: 'applied' }).nodes().reduce((total, cell) => {
        return total + parseValue(cell.getAttribute('data-value'));
    }, 0);
}

function updateFacturaSummary(api) {
    const registros = api.rows({ search: 'applied' }).count();
    const subtotal = getColumnTotal(api, 3);
    const iva = getColumnTotal(api, 4);
    const total = getColumnTotal(api, 5);

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
