function abrirSelectorXml() {
    const inputXml = document.getElementById('input-xml-cfdi');

    if (inputXml) {
        inputXml.click();
    }
}

function abrirSelectorPdf() {
    const inputPdf = document.getElementById('input-pdf-cfdi');

    if (inputPdf) {
        inputPdf.click();
    }
}

function validarArchivoSeleccionado(file, allowedExtensions, allowedMimeTypes) {
    if (!file) {
        return false;
    }

    const fileName = (file.name || '').toLowerCase();
    const fileType = (file.type || '').toLowerCase();

    const extensionValida = allowedExtensions.some(function (extension) {
        return fileName.endsWith(extension);
    });

    const mimeValido = fileType === '' || allowedMimeTypes.includes(fileType);

    return extensionValida && mimeValido;
}

function prepararArchivoSeleccionado(file, tipo) {
    if (!file) {
        return;
    }

    console.log('Archivo listo para procesarse:', {
        tipo: tipo,
        nombre: file.name,
        mime: file.type,
        tamano: file.size
    });

    guardarArchivoCfdi(file, tipo);
}

function guardarArchivoCfdi(file, tipo) {
    if (!file) {
        alert('No se selecciono ningun archivo.');
        return;
    }

    const allowedExtensions = tipo === 'xml' ? ['.xml'] : ['.pdf'];
    const allowedMimeTypes = tipo === 'xml' ? ['application/xml', 'text/xml'] : ['application/pdf'];

    if (!validarArchivoSeleccionado(file, allowedExtensions, allowedMimeTypes)) {
        alert('El archivo seleccionado no es valido.');
        return;
    }

    // captura del archivo seleccionado
    const formData = new FormData();

    // creacion de FormData
    formData.append('cfdi_file', file);

    // envio al servidor
    fetch('../backend/facturacion/upload_cfdi.php', {
        method: 'POST',
        body: formData
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data.ok) {
                alert(data.message || 'No se pudo guardar el archivo.');
                return;
            }

            alert(data.message || 'Archivo guardado correctamente.');

            window.DigitAppMasterSync?.publish('cfdi', Date.now());
        })
        .catch(function () {
            alert('Ocurrio un error al enviar el archivo.');
        });
}

function configurarCargaArchivosCfdi() {
    const inputXml = document.getElementById('input-xml-cfdi');
    const inputPdf = document.getElementById('input-pdf-cfdi');

    if (inputXml) {
        inputXml.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

            if (!validarArchivoSeleccionado(file, ['.xml'], ['application/xml', 'text/xml'])) {
                alert('Selecciona un archivo XML valido.');
                event.target.value = '';
                return;
            }

            prepararArchivoSeleccionado(file, 'xml');
        });
    }

    if (inputPdf) {
        inputPdf.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

            if (!validarArchivoSeleccionado(file, ['.pdf'], ['application/pdf'])) {
                alert('Selecciona un archivo PDF valido.');
                event.target.value = '';
                return;
            }

            prepararArchivoSeleccionado(file, 'pdf');
        });
    }
}

// Mantiene la selección de cada CFDI aunque DataTables filtre, ordene o cambie de página.
function configurarSeleccionFacturacion() {
    const tableElement = document.getElementById('example');
    if (!tableElement || typeof jQuery === 'undefined' || !jQuery.fn.dataTable.isDataTable(tableElement)) {
        return;
    }

    const api = jQuery(tableElement).DataTable();
    const selection = new Set();
    const modeSelect = document.getElementById('filtro-seleccion');
    const pageCheckbox = document.getElementById('seleccionar-pagina');
    const selectionFilter = function (settings, _data, dataIndex) {
        if (settings.nTable !== tableElement) {
            return true;
        }
        const rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!rowNode) {
            return true;
        }
        const mode = modeSelect.value;
        const isSelected = selection.has(rowNode.dataset.recordKey);
        return mode === 'selected' ? isSelected : mode === 'unselected' ? !isSelected : true;
    };

    jQuery.fn.dataTable.ext.search.push(selectionFilter);

    // Refleja la selección almacenada en las filas actuales y el estado de selección de la página.
    function syncVisibleCheckboxes() {
        const rows = api.rows({ page: 'current' }).nodes().toArray();
        rows.forEach(function (row) {
            const checkbox = row.querySelector('.factura-seleccion');
            if (checkbox) {
                checkbox.checked = selection.has(row.dataset.recordKey);
            }
        });
        if (pageCheckbox) {
            pageCheckbox.checked = rows.length > 0 && rows.every(function (row) {
                return selection.has(row.dataset.recordKey);
            });
            pageCheckbox.indeterminate = rows.some(function (row) {
                return selection.has(row.dataset.recordKey);
            }) && !pageCheckbox.checked;
        }
    }

    // Actualiza el filtro y los totales después de cualquier cambio de selección o búsqueda.
    function redrawSelection() {
        api.draw(false);
        syncVisibleCheckboxes();
    }

    tableElement.addEventListener('change', function (event) {
        const checkbox = event.target.closest('.factura-seleccion');
        if (!checkbox) {
            return;
        }
        const row = checkbox.closest('tr[data-record-key]');
        if (row) {
            if (checkbox.checked) {
                selection.add(row.dataset.recordKey);
            } else {
                selection.delete(row.dataset.recordKey);
            }
            redrawSelection();
        }
    });

    if (pageCheckbox) {
        pageCheckbox.addEventListener('change', function () {
            api.rows({ page: 'current' }).nodes().toArray().forEach(function (row) {
                if (pageCheckbox.checked) {
                    selection.add(row.dataset.recordKey);
                } else {
                    selection.delete(row.dataset.recordKey);
                }
            });
            redrawSelection();
        });
    }

    modeSelect.addEventListener('change', redrawSelection);

    document.getElementById('seleccionar-visibles').addEventListener('click', function () {
        api.rows({ search: 'applied', page: 'all' }).nodes().toArray().forEach(function (row) {
            selection.add(row.dataset.recordKey);
        });
        redrawSelection();
    });

    document.getElementById('limpiar-seleccion').addEventListener('click', function () {
        selection.clear();
        redrawSelection();
    });

    api.on('draw.dt.facturaSelection', syncVisibleCheckboxes);
    syncVisibleCheckboxes();
}

document.addEventListener('DOMContentLoaded', function () {
    configurarCargaArchivosCfdi();
    configurarSeleccionFacturacion();
});
