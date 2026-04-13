function injectDataTableFilterStyles() {
    if (document.getElementById('dt-column-filters-style')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'dt-column-filters-style';
    style.textContent = `
        /* Ajuste visual global de tablas con filtros por columna */
        .dt-filter-row th,
        .dt-filter-row td {
            /* Estilos de fila de filtros */
            background: #002550 !important;
            color: #284b63 !important;
            font-weight: 400 !important;
            border-top: 0 !important;
            border-bottom:0.5px solid #00badb !important;
            padding: 0.5rem !important;
            vertical-align: middle;
        }

        .dt-filter-row th:first-child,
        .dt-filter-row td:first-child {
            border-left: 0 !important;
        }

        .dt-filter-row th::before,
        .dt-filter-row th::after {
            display: none !important;
        }

        /* Estilos de inputs de filtros */
        .dt-filter-input {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid #9fbad0;
            border-radius: 0.375rem;
            background: #f4f8fc;
            color: #1f3f57;
            font-size: 0.875rem;
            line-height: 1.5;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .dt-filter-input::placeholder {
            color: #6b8aa3;
        }

        .dt-filter-input:focus {
            background: #ffffff;
            border-color: #5f8fb8;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(95, 143, 184, 0.18);
        }
    `;

    document.head.appendChild(style);
}

function resolveDataTableApi(tableInstance) {
    if (!tableInstance) {
        return null;
    }

    if (typeof tableInstance.table === 'function' && typeof tableInstance.columns === 'function') {
        return tableInstance;
    }

    if (typeof window.jQuery !== 'undefined' && typeof tableInstance === 'string' && jQuery.fn.dataTable.isDataTable(tableInstance)) {
        return jQuery(tableInstance).DataTable();
    }

    return null;
}

function applyColumnFilters(tableInstance) {
    const api = resolveDataTableApi(tableInstance);

    if (!api) {
        return null;
    }

    const tableNode = api.table().node();
    const thead = tableNode ? tableNode.querySelector('thead') : null;
    const headerRow = thead ? thead.querySelector('tr:not(.dt-filter-row)') : null;

    // Prevencion de duplicados
    if (!thead || !headerRow || thead.querySelector('.dt-filter-row')) {
        return api;
    }

    injectDataTableFilterStyles();

    // Crear la fila de filtros separada del encabezado real
    const filterRow = headerRow.cloneNode(true);
    filterRow.className = 'dt-filter-row';

    api.columns().every(function (columnIndex) {
        const column = this;
        const cell = filterRow.children[columnIndex];
        const columnConfig = api.settings()[0].aoColumns[columnIndex];

        if (!cell) {
            return;
        }

        cell.textContent = '';

        // Crear inputs por columna
        if (columnConfig && columnConfig.bSearchable) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'dt-filter-input';
            input.placeholder = 'Filtrar...';

            // Evento de busqueda por columna
            const applySearch = function () {
                if (column.search() !== this.value) {
                    column.search(this.value).draw();
                }
            };

            // Evitar que el sort se active al usar los inputs
            input.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            input.addEventListener('keydown', function (event) {
                event.stopPropagation();
            });

            input.addEventListener('keyup', applySearch);
            input.addEventListener('change', applySearch);
            cell.appendChild(input);
        }
    });

    thead.appendChild(filterRow);
    return api;
}
