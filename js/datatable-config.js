const defaultDataTableOptions = {
    pageLength: 20,
    responsive: true,
    autoWidth: false,
    searching: true,
    dom: '<"dt-top"flp>rt<"dt-bottom"lip>',
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

function getDataTableOptions(customOptions) {
    return mergeDataTableOptions(defaultDataTableOptions, customOptions);
}
