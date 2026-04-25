document.addEventListener('DOMContentLoaded', function () {
    const constanciaPdf = document.getElementById('constancia_pdf');
    const btnLeerConstancia = document.getElementById('btnLeerConstancia');
    const mensajeConstancia = document.getElementById('mensajeConstancia');
    const nombreArchivo = document.getElementById('constancia_fiscal_pdf_nombre');
    const tamanoMaximoBytes = 5 * 1024 * 1024;

    if (!constanciaPdf || !btnLeerConstancia || !mensajeConstancia) {
        return;
    }

    function mostrarMensajeConstancia(tipo, texto) {
        mensajeConstancia.innerHTML = '<div class="alert alert-' + tipo + ' mb-0" role="alert">' + texto + '</div>';
    }

    // Función: procesar constancia fiscal temporal
    async function procesarConstanciaFiscal() {
        const archivo = constanciaPdf.files && constanciaPdf.files.length > 0 ? constanciaPdf.files[0] : null;

        // Validar archivo PDF
        if (!archivo) {
            mostrarMensajeConstancia('warning', 'Selecciona un archivo PDF antes de continuar.');
            return;
        }

        if (archivo.type !== 'application/pdf') {
            mostrarMensajeConstancia('warning', 'El archivo seleccionado no es un PDF válido.');
            return;
        }

        if (archivo.size > tamanoMaximoBytes) {
            mostrarMensajeConstancia('warning', 'El archivo excede el tamaño máximo permitido de 5 MB.');
            return;
        }

        btnLeerConstancia.disabled = true;
        mostrarMensajeConstancia('info', 'Validando constancia fiscal y preparando archivo para procesamiento...');

        try {
            const formData = new FormData();
            formData.append('constancia_pdf', archivo);

            const response = await fetch('querys/procesar_constancia_fiscal.php', {
                method: 'POST',
                body: formData
            });

            let result;

            try {
                result = await response.json();
            } catch (error) {
                mostrarMensajeConstancia('danger', 'La respuesta del servidor no es un JSON válido.');
                return;
            }

            if (result && result.fase === 'pendiente_nuevo_metodo') {
                mostrarMensajeConstancia('info', 'El procesamiento automático se implementará en la siguiente fase.');
                return;
            }

            if (!response.ok || !result.ok) {
                mostrarMensajeConstancia('warning', result && result.message ? result.message : 'No fue posible validar la constancia fiscal.');
                return;
            }

            mostrarMensajeConstancia('info', 'El procesamiento automático se implementará en la siguiente fase.');
        } catch (error) {
            mostrarMensajeConstancia('danger', 'Ocurrió un error de red al validar la constancia fiscal.');
        } finally {
            btnLeerConstancia.disabled = false;
        }
    }

    // Evento: lectura automática al seleccionar PDF
    constanciaPdf.addEventListener('change', function () {
        const archivo = constanciaPdf.files && constanciaPdf.files.length > 0 ? constanciaPdf.files[0] : null;

        if (nombreArchivo) {
            nombreArchivo.value = archivo ? archivo.name : 'Ningun archivo seleccionado';
        }

        procesarConstanciaFiscal();
    });

    // Evento: lectura manual con botón
    btnLeerConstancia.addEventListener('click', function () {
        procesarConstanciaFiscal();
    });
});
