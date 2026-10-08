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

        /* DataTables conserva un encabezado tecnico dentro del cuerpo al usar scrollX. */
        .dataTables_scrollBody thead .dt-filter-row {
            height: 0 !important;
            visibility: hidden !important;
        }

        .dataTables_scrollBody thead .dt-filter-row th,
        .dataTables_scrollBody thead .dt-filter-row td {
            height: 0 !important;
            padding-block: 0 !important;
            border: 0 !important;
        }

        .dataTables_scrollBody thead .dt-filter-row .dt-filter-input {
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

    injectDataTableFilterStyles();
    const container = api.table().container();
    const settings = api.settings()[0];
    const filterValues = api.columns().indexes().toArray().map(function (columnIndex) {
        return api.column(columnIndex).search();
    });
    const timers = new Map();
    let activeColumn = null;

    // Sincroniza el valor visible y ejecuta una sola busqueda al terminar de escribir.
    const scheduleSearch = function (columnIndex, value) {
        filterValues[columnIndex] = value;
        container.querySelectorAll(`.dt-filter-input[data-column-index="${columnIndex}"]`).forEach(function (input) {
            if (input.value !== value) {
                input.value = value;
            }
        });
        window.clearTimeout(timers.get(columnIndex));
        timers.set(columnIndex, window.setTimeout(function () {
            const column = api.column(columnIndex);
            if (column.search() !== value) {
                activeColumn = columnIndex;
                column.search(value).draw();
            }
        }, 220));
    };

    // Reconstruye los filtros en todos los encabezados, incluidos los clonados por scrollX.
    const renderFilters = function () {
        container.querySelectorAll('thead').forEach(function (thead) {
            const headerRow = thead.querySelector('tr:not(.dt-filter-row)');
            if (!headerRow) {
                return;
            }
            thead.querySelectorAll('.dt-filter-row').forEach(function (row) { row.remove(); });
            const filterRow = headerRow.cloneNode(true);
            filterRow.className = 'dt-filter-row';

            Array.from(filterRow.children).forEach(function (cell, columnIndex) {
                const columnConfig = settings.aoColumns[columnIndex];
                cell.textContent = '';
                if (!columnConfig || !columnConfig.bSearchable) {
                    return;
                }
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'dt-filter-input';
                input.placeholder = 'Filtrar...';
                input.dataset.columnIndex = String(columnIndex);
                input.value = filterValues[columnIndex] || '';
                input.addEventListener('click', function (event) { event.stopPropagation(); });
                input.addEventListener('keydown', function (event) { event.stopPropagation(); });
                input.addEventListener('input', function () {
                    activeColumn = columnIndex;
                    scheduleSearch(columnIndex, this.value);
                });
                cell.appendChild(input);
            });
            thead.appendChild(filterRow);
        });

        if (activeColumn !== null) {
            const visibleInput = Array.from(container.querySelectorAll(`.dt-filter-input[data-column-index="${activeColumn}"]`))
                .find(function (input) { return input.offsetParent !== null; });
            if (visibleInput) {
                visibleInput.focus({ preventScroll: true });
                visibleInput.setSelectionRange(visibleInput.value.length, visibleInput.value.length);
            }
        }
    };

    api.off('draw.dtColumnFilters');
    api.on('draw.dtColumnFilters', function () {
        window.requestAnimationFrame(renderFilters);
    });
    renderFilters();
    return api;
}
