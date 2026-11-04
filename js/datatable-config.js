const defaultDataTableOptions = {
    pageLength: 10,
    lengthMenu: [10, 20, 25, 50, 100],
    responsive: false,
    scrollX: true,
    scrollCollapse: true,
    autoWidth: false,
    // Se conserva la búsqueda por columna; el buscador global no se muestra en la interfaz.
    searching: true,
    // Mantiene el selector arriba y el resumen/paginación abajo, fuera del área que desplaza la tabla.
    dom: '<"dt-top"l>rt<"dt-bottom"ip>',
    language: {
        search: 'Buscar:',
        searchPlaceholder: 'Buscar en la tabla',
        zeroRecords: 'No se encontraron registros con ese criterio.',
        emptyTable: 'No hay datos disponibles en la tabla.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrados de _MAX_ registros)',
        lengthMenu: 'Mostrar _MENU_ registros',
        paginate: {
            first: 'Primera',
            last: 'Ultima',
            next: 'Siguiente',
            previous: 'Anterior'
        }
    }
};

function mergeDataTableOptions(baseOptions, customOptions) {
    const mergedOptions = { ...baseOptions };

    Object.keys(customOptions || {}).forEach(function (key) {
        const baseValue = mergedOptions[key];
        const customValue = customOptions[key];

        if (
            baseValue &&
            customValue &&
            typeof baseValue === 'object' &&
            typeof customValue === 'object' &&
            !Array.isArray(baseValue) &&
            !Array.isArray(customValue)
        ) {
            mergedOptions[key] = mergeDataTableOptions(baseValue, customValue);
            return;
        }

        mergedOptions[key] = customValue;
    });

    return mergedOptions;
}

// Conserva en una línea los valores atómicos reconocidos y deja que los textos descriptivos se ajusten al ancho de su columna.
function getDataTableOptions(customOptions) {
    const options = mergeDataTableOptions(defaultDataTableOptions, customOptions);
    const singleLineField = /(?:^|_)(?:fecha|date|folio|numero|c[oó]digo|codigo|cantidad|importe|monto|costo|subtotal|total|iva|precio|valor|descuento|correo|email|tel[eé]fono|telefono|estatus|estado|status|created_at|updated_at|at)(?:_|$)/i;

    if (Array.isArray(options.columns)) {
        options.columns = options.columns.map(function (column) {
            if (typeof column.data !== 'string' || !singleLineField.test(column.data)) {
                return column;
            }

            const classNames = new Set(String(column.className || '').split(/\s+/).filter(Boolean));
            classNames.add('table-nowrap');
            return { ...column, className: [...classNames].join(' ') };
        });
    }

    return options;
}
