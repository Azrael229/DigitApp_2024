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
    fetch('querys/upload_cfdi.php', {
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

            // Recargar la página automáticamente para actualizar la tabla con el nuevo CFDI cargado
            setTimeout(function () {
                window.location.reload();
            }, 500);
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

function parseCfdiValue(value) {
    if (typeof value !== 'string') {
        return typeof value === 'number' ? value : 0;
    }

    return parseFloat(value.replace(/,/g, '')) || 0;
}

function formatCfdiValue(value) {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
}

function updateSummaryValue(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function configurarResumenFacturacion() {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.dataTable || !jQuery.fn.dataTable.isDataTable('#example')) {
        return;
    }

    const api = jQuery('#example').DataTable();

    function updateFilteredCards() {
        let subtotal = 0;
        let iva = 0;
        let total = 0;

        const rows = api.rows({ search: 'applied', page: 'all' }).nodes().toArray();

        rows.forEach(function (row) {
            const cells = row.querySelectorAll('td');

            subtotal += parseCfdiValue(cells[3] ? cells[3].getAttribute('data-value') || '0' : '0');
            iva += parseCfdiValue(cells[4] ? cells[4].getAttribute('data-value') || '0' : '0');
            total += parseCfdiValue(cells[5] ? cells[5].getAttribute('data-value') || '0' : '0');
        });

        updateSummaryValue('card-registros', String(rows.length));
        updateSummaryValue('card-subtotal', formatCfdiValue(subtotal));
        updateSummaryValue('card-iva', formatCfdiValue(iva));
        updateSummaryValue('card-total', formatCfdiValue(total));
        updateSummaryValue('total-subtotal', formatCfdiValue(subtotal));
        updateSummaryValue('total-iva', formatCfdiValue(iva));
        updateSummaryValue('total-general', formatCfdiValue(total));
    }

    jQuery('#example').off('.cfdiSummary');
    jQuery('#example').on('draw.dt.cfdiSummary search.dt.cfdiSummary page.dt.cfdiSummary length.dt.cfdiSummary', updateFilteredCards);

    updateFilteredCards();
}

document.addEventListener('DOMContentLoaded', function () {
    configurarCargaArchivosCfdi();
    configurarResumenFacturacion();
});
