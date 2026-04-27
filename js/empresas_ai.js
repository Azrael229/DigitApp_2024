document.addEventListener('DOMContentLoaded', function () {
    const archivoEmpresaAI = document.getElementById('archivo_empresa_ai');
    const textoEmpresaAI = document.getElementById('texto_empresa_ai');
    const btnProcesarEmpresaAI = document.getElementById('btnProcesarEmpresaAI');
    const mensajeEmpresaAI = document.getElementById('mensajeEmpresaAI');

    if (!archivoEmpresaAI || !textoEmpresaAI || !btnProcesarEmpresaAI || !mensajeEmpresaAI) {
        return;
    }

    const extensionesPermitidas = [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'xml', 'json', 'doc', 'docx', 'rtf', 'csv'
    ];

    const TAMANIO_MAXIMO_MB = 5;
    const TAMANIO_MAXIMO_BYTES = TAMANIO_MAXIMO_MB * 1024 * 1024;
    const textoBotonOriginal = (btnProcesarEmpresaAI.textContent || '').trim() || 'Detectar datos de empresa';

    function escaparHtml(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function obtenerInputEmpresaAI(id) {
        return document.getElementById(id) || null;
    }

    function normalizarComparacionEmpresaAI(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function campoTieneValorEmpresaAI(elemento) {
        if (!elemento) {
            return false;
        }
        return String(elemento.value || '').trim() !== '';
    }

    function marcarCampoLlenadoIA(elemento) {
        if (!elemento) {
            return;
        }

        elemento.classList.add('ai-field-filled');

        const quitarMarca = function () {
            elemento.classList.remove('ai-field-filled');
        };

        elemento.addEventListener('input', quitarMarca, { once: true });
        elemento.addEventListener('change', quitarMarca, { once: true });
    }

    function asignarValorEmpresaAI(id, valor, opciones = {}) {
        const resultado = {
            id: id,
            llenado: false,
            motivo: 'valor_vacio'
        };

        if (valor === undefined || valor === null || String(valor).trim() === '') {
            return resultado;
        }

        const elemento = obtenerInputEmpresaAI(id);
        if (!elemento) {
            resultado.motivo = 'mapeo_no_disponible';
            return resultado;
        }

        const sobrescribir = opciones.sobrescribir === true;
        if (!sobrescribir && campoTieneValorEmpresaAI(elemento)) {
            resultado.motivo = 'campo_con_valor';
            return resultado;
        }

        const valorLimpio = String(valor).trim();

        if (elemento.tagName === 'SELECT') {
            const valorAnterior = elemento.value;
            elemento.value = valorLimpio;

            if (elemento.value !== valorLimpio) {
                const buscado = normalizarComparacionEmpresaAI(valorLimpio);
                const opcionesSelect = Array.from(elemento.options || []);
                const option = opcionesSelect.find(function (opt) {
                    const textoOption = normalizarComparacionEmpresaAI(opt.text);
                    const valueOption = normalizarComparacionEmpresaAI(opt.value);
                    return textoOption === buscado || valueOption === buscado;
                });

                if (option) {
                    elemento.value = option.value;
                } else {
                    elemento.value = valorAnterior;
                    resultado.motivo = 'opcion_select_no_encontrada';
                    return resultado;
                }
            }
        } else if ('value' in elemento) {
            elemento.value = valorLimpio;
        } else {
            resultado.motivo = 'mapeo_no_disponible';
            return resultado;
        }

        marcarCampoLlenadoIA(elemento);
        resultado.llenado = true;
        resultado.motivo = 'llenado';
        return resultado;
    }

    function determinarModoFormularioEmpresaAI() {
        const form = document.querySelector('form[data-modo-formulario]');
        if (form) {
            const modo = String(form.dataset.modoFormulario || '').trim().toLowerCase();
            if (modo === 'crear' || modo === 'editar') {
                return modo;
            }
        }

        const idEmpresa = obtenerInputEmpresaAI('id_empresa');
        if (idEmpresa && String(idEmpresa.value || '').trim() !== '') {
            return 'editar';
        }

        const idEmpresaAlterno = obtenerInputEmpresaAI('id_e');
        if (idEmpresaAlterno && String(idEmpresaAlterno.value || '').trim() !== '') {
            return 'editar';
        }

        return 'crear';
    }

    function debeSobrescribirCamposEmpresaAI() {
        const modo = determinarModoFormularioEmpresaAI();
        return modo === 'crear';
    }

    function agruparCamposOmitidosEmpresaAI(camposOmitidos) {
        const contador = {
            valor_vacio: 0,
            campo_con_valor: 0,
            opcion_select_no_encontrada: 0,
            mapeo_no_disponible: 0
        };

        if (!Array.isArray(camposOmitidos)) {
            return contador;
        }

        camposOmitidos.forEach(function (item) {
            const motivo = item && item.motivo ? String(item.motivo) : '';
            if (Object.prototype.hasOwnProperty.call(contador, motivo)) {
                contador[motivo] += 1;
            }
        });

        return contador;
    }

    function seleccionarIdDisponibleEmpresaAI(ids) {
        if (!Array.isArray(ids) || ids.length === 0) {
            return null;
        }

        for (let i = 0; i < ids.length; i += 1) {
            const id = String(ids[i] || '').trim();
            if (id !== '' && obtenerInputEmpresaAI(id)) {
                return id;
            }
        }

        return null;
    }

    function asignarValorConIdsEmpresaAI(ids, valor, opciones = {}) {
        const idDisponible = seleccionarIdDisponibleEmpresaAI(ids);
        if (!idDisponible) {
            return { id: '', llenado: false, motivo: 'mapeo_no_disponible' };
        }
        return asignarValorEmpresaAI(idDisponible, valor, opciones);
    }

    function obtenerPartesRegimenFiscalEmpresaAI(regimenFiscal) {
        const valor = String(regimenFiscal || '').trim();
        if (valor === '') {
            return { codigo: '', descripcion: '' };
        }

        const match = valor.match(/^(\d{3})\s*[-:]\s*(.+)$/);
        if (match) {
            return {
                codigo: String(match[1] || '').trim(),
                descripcion: valor
            };
        }

        if (/^\d{3}$/.test(valor)) {
            return { codigo: valor, descripcion: '' };
        }

        return { codigo: '', descripcion: valor };
    }

    function inferirTipoPersonaEmpresaAI(empresa) {
        const regimenDetectado = String(empresa && empresa.regimen_capital_detectado ? empresa.regimen_capital_detectado : '').toLowerCase();
        const regimenNormalizado = String(empresa && empresa.regimen_capital ? empresa.regimen_capital : '').toLowerCase();
        const base = regimenDetectado + ' ' + regimenNormalizado;

        if (base.includes('persona fisica') || base.includes('persona física')) {
            return 'Fisica';
        }
        if (base !== '') {
            return 'Moral';
        }
        return '';
    }

    function llenarInputsEmpresaAI(datos) {
        const modo = determinarModoFormularioEmpresaAI();
        const sobrescribir = debeSobrescribirCamposEmpresaAI();
        const camposLlenados = [];
        const camposOmitidos = [];

        if (!datos || typeof datos !== 'object') {
            return {
                modo_formulario: modo,
                sobrescribir: sobrescribir,
                campos_llenados: camposLlenados,
                campos_omitidos: camposOmitidos
            };
        }

        const empresa = datos.empresa && typeof datos.empresa === 'object' ? datos.empresa : {};
        const direccion = datos.direccion_fiscal && typeof datos.direccion_fiscal === 'object' ? datos.direccion_fiscal : {};
        const contacto = datos.contacto && typeof datos.contacto === 'object' ? datos.contacto : {};
        const clasificacion = datos.clasificacion_sugerida && typeof datos.clasificacion_sugerida === 'object'
            ? datos.clasificacion_sugerida
            : {};
        const regimenFiscalPartes = obtenerPartesRegimenFiscalEmpresaAI(empresa.regimen_fiscal);
        const tipoPersonaInferido = inferirTipoPersonaEmpresaAI(empresa);

        const mapeo = [
            [['rfc'], empresa.rfc],
            [['empresa', 'nombre_comercial'], empresa.nombre_comercial_sugerido || empresa.razon_social],
            [['razon_social'], empresa.razon_social],
            [['regimen_fiscal_codigo'], regimenFiscalPartes.codigo],
            [['regimen_fiscal_descripcion', 'regimen_fiscal'], regimenFiscalPartes.descripcion],
            [['regimen_capital'], empresa.regimen_capital],
            [['tipo_persona'], tipoPersonaInferido],
            [['codigo_postal'], direccion.codigo_postal],
            [['calle'], direccion.calle],
            [['numero_exterior'], direccion.numero_exterior],
            [['numero_interior'], direccion.numero_interior],
            [['colonia'], direccion.colonia],
            [['localidad'], direccion.localidad],
            [['municipio'], direccion.municipio],
            [['ciudad'], direccion.localidad || direccion.municipio],
            [['estado'], direccion.estado],
            [['pais'], direccion.pais],
            [['telefono_principal', 'telefono'], contacto.telefono],
            [['email_principal', 'correo'], contacto.correo],
            [['pagina_web', 'sitio_web'], contacto.sitio_web],
            [['rol'], clasificacion.rol],
            [['estatus'], clasificacion.estatus],
            [['mercado'], clasificacion.mercado],
            [['giro_mercantil', 'giro', 'actividad_economica'], clasificacion.giro]
        ];

        mapeo.forEach(function (par) {
            const resultado = asignarValorConIdsEmpresaAI(par[0], par[1], { sobrescribir: sobrescribir });
            if (resultado.llenado) {
                camposLlenados.push(resultado.id);
            } else if (resultado.motivo !== 'mapeo_no_disponible') {
                camposOmitidos.push(resultado);
            }
        });

        return {
            modo_formulario: modo,
            sobrescribir: sobrescribir,
            campos_llenados: camposLlenados,
            campos_omitidos: camposOmitidos
        };
    }

    function mostrarMensajeEmpresaAI(tipo, mensaje, lista = []) {
        const clasesPorTipo = {
            info: 'alert alert-info',
            success: 'alert alert-success',
            warning: 'alert alert-warning',
            danger: 'alert alert-danger'
        };

        const claseAlert = clasesPorTipo[tipo] || clasesPorTipo.info;
        const elementosLista = Array.isArray(lista) ? lista : [];
        let html = '<div class="' + claseAlert + ' mb-0" role="alert">';
        html += '<div>' + escaparHtml(mensaje) + '</div>';

        if (elementosLista.length > 0) {
            html += '<ul class="mb-0 mt-2">';
            for (let i = 0; i < elementosLista.length; i += 1) {
                html += '<li>' + escaparHtml(elementosLista[i]) + '</li>';
            }
            html += '</ul>';
        }

        html += '</div>';
        mensajeEmpresaAI.innerHTML = html;
    }

    function mostrarResumenLlenadoEmpresaAI(resumen, datos, warnings = []) {
        const tipoDocumento = datos && datos.tipo_documento_detectado
            ? String(datos.tipo_documento_detectado)
            : 'desconocido';
        const confianza = Number(datos && datos.confianza_global ? datos.confianza_global : 0);

        const modoLabel = resumen.modo_formulario === 'editar' ? 'Editar' : 'Crear';
        const sobrescrituraLabel = resumen.sobrescribir ? 'Activada' : 'Desactivada';
        const omitidosAgrupados = agruparCamposOmitidosEmpresaAI(resumen.campos_omitidos || []);
        const omitidosAccionables = Number(omitidosAgrupados.campo_con_valor)
            + Number(omitidosAgrupados.opcion_select_no_encontrada);
        const sinDatoDetectado = Number(omitidosAgrupados.valor_vacio);
        const lista = [
            'Modo del formulario: ' + modoLabel,
            'Sobrescritura: ' + sobrescrituraLabel,
            'Campos llenados: ' + Number((resumen.campos_llenados || []).length),
            'Campos omitidos: ' + omitidosAccionables,
            'Confianza global: ' + confianza,
            'Tipo de documento: ' + tipoDocumento
        ];

        if (sinDatoDetectado > 0) {
            lista.push('Campos sin dato detectado por IA: ' + sinDatoDetectado);
        }

        if (Array.isArray(resumen.campos_llenados) && resumen.campos_llenados.length > 0) {
            lista.push('IDs llenados: ' + resumen.campos_llenados.join(', '));
        }

        if (resumen.modo_formulario === 'editar') {
            lista.push('Modo edición: no se sobrescribieron campos que ya tenían información.');
        }

        if (omitidosAgrupados.campo_con_valor > 0) {
            lista.push('Algunos campos se omitieron porque ya tenían información.');
        }

        if (omitidosAgrupados.opcion_select_no_encontrada > 0) {
            lista.push('Campos omitidos por opción no encontrada en select: ' + omitidosAgrupados.opcion_select_no_encontrada);
        }

        if (Array.isArray(warnings)) {
            warnings.forEach(function (warning) {
                const texto = String(warning || '').trim();
                if (texto !== '') {
                    lista.push(texto);
                }
            });
        }

        mostrarMensajeEmpresaAI(
            'success',
            'Datos detectados y cargados en el formulario. Revisa la información antes de guardar.',
            lista
        );
    }

    function obtenerExtensionArchivo(nombreArchivo) {
        if (!nombreArchivo || typeof nombreArchivo !== 'string') {
            return '';
        }

        const nombreLimpio = nombreArchivo.trim();
        const ultimoPunto = nombreLimpio.lastIndexOf('.');

        if (ultimoPunto <= 0 || ultimoPunto === nombreLimpio.length - 1) {
            return '';
        }

        return nombreLimpio.slice(ultimoPunto + 1).toLowerCase();
    }

    function validarEntradaEmpresaAI() {
        const archivos = archivoEmpresaAI.files;
        const hayArchivo = archivos && archivos.length > 0;
        const texto = textoEmpresaAI.value.trim();

        if (!hayArchivo && texto.length === 0) {
            mostrarMensajeEmpresaAI('warning', 'Carga un archivo o pega texto para detectar datos de empresa.');
            return { valido: false, archivo: null, texto: '' };
        }

        let archivoSeleccionado = null;
        if (hayArchivo) {
            archivoSeleccionado = archivos[0];
            const extensionArchivo = obtenerExtensionArchivo(archivoSeleccionado.name);

            if (!extensionesPermitidas.includes(extensionArchivo)) {
                mostrarMensajeEmpresaAI('danger', 'Archivo no permitido.');
                return { valido: false, archivo: null, texto: '' };
            }

            if (archivoSeleccionado.size > TAMANIO_MAXIMO_BYTES) {
                mostrarMensajeEmpresaAI('warning', 'El archivo supera el tamaño máximo permitido de 5 MB.');
                return { valido: false, archivo: null, texto: '' };
            }
        }

        if (texto.length > 0 && texto.length < 10) {
            mostrarMensajeEmpresaAI('warning', 'El texto ingresado es demasiado corto para detectar datos de empresa.');
            return { valido: false, archivo: null, texto: '' };
        }

        return {
            valido: true,
            archivo: archivoSeleccionado || null,
            texto: texto || ''
        };
    }

    function setProcesandoEmpresaAI(procesando) {
        btnProcesarEmpresaAI.disabled = Boolean(procesando);
        btnProcesarEmpresaAI.textContent = procesando ? 'Procesando...' : textoBotonOriginal;
    }

    async function procesarEmpresaAI(event) {
        event.preventDefault();

        const resultado = validarEntradaEmpresaAI();
        if (!resultado.valido) {
            return;
        }

        const formData = new FormData();
        if (resultado.archivo) {
            formData.append('archivo_empresa_ai', resultado.archivo);
        }
        if (resultado.texto) {
            formData.append('texto_empresa_ai', resultado.texto);
        }

        setProcesandoEmpresaAI(true);
        mostrarMensajeEmpresaAI('info', 'Enviando entrada al servidor...');

        try {
            const response = await fetch('querys/procesar_documento_empresa.php', {
                method: 'POST',
                body: formData
            });

            let result;
            try {
                result = await response.json();
            } catch (error) {
                mostrarMensajeEmpresaAI('danger', 'No se pudo interpretar la respuesta del servidor.');
                return;
            }

            const warnings = result && Array.isArray(result.warnings) ? result.warnings : [];

            if (result && result.datos && typeof result.datos === 'object') {
                const resumen = llenarInputsEmpresaAI(result.datos);
                mostrarResumenLlenadoEmpresaAI(resumen, result.datos, warnings);
                return;
            }

            if (!response.ok || !result || !result.ok) {
                const mensajeError = result && result.message
                    ? result.message
                    : 'No se pudo procesar la entrada.';
                const tipoError = response.status >= 500 ? 'danger' : 'warning';
                mostrarMensajeEmpresaAI(tipoError, mensajeError, warnings);
                return;
            }

            const lista = [];
            if (Array.isArray(warnings)) {
                warnings.forEach(function (w) {
                    const texto = String(w || '').trim();
                    if (texto !== '') {
                        lista.push(texto);
                    }
                });
            }

            mostrarMensajeEmpresaAI('success', result.message || 'Entrada recibida correctamente.', lista);
        } catch (error) {
            mostrarMensajeEmpresaAI('danger', 'Ocurrio un error de red al enviar la entrada.');
        } finally {
            setProcesandoEmpresaAI(false);
        }
    }

    btnProcesarEmpresaAI.addEventListener('click', procesarEmpresaAI);
});

