const formularioInforme = document.getElementById('form_informe');
const selectEmpresa = document.getElementById('select_empresa');
const selectContacto = document.getElementById('select_contacto');
const inputEmpresa = document.getElementById('nombre_empresa');
const selectDireccion = document.getElementById('dir_empresa');
const selectEquipo = document.getElementById('select_equipo');
const inputContacto = document.getElementById('nombre_contacto');
const inputCorreo = document.getElementById('correo_contacto');
const inputFecha = document.getElementById('inf_fecha');
const inputFolio = document.getElementById('input_num_inf');
const inputDescripcion = document.getElementById('desc_inst');
const inputMarca = document.getElementById('marca_inst');
const inputModelo = document.getElementById('modelo_inst');
const inputIdentificacion = document.getElementById('id_inst');
const inputSerie = document.getElementById('serie_inst');
const inputUnidad = document.getElementById('unidad_inst');
const inputMax = document.getElementById('max');
const inputD = document.getElementById('d');
const inputE = document.getElementById('e');
const inputMin = document.getElementById('min');
const inputClase = document.getElementById('clase');
const resumenEmt = document.getElementById('resumen_emt');
const cuerpoTablaEmt = document.getElementById('tabla_emt_cuerpo');
let empresaEnCarga = '';
let equiposEmpresa = [];

if (window.jQuery && jQuery.fn.select2) {
    jQuery('#select_empresa')
        .select2({ width: '100%' })
        .on('select2:select', seleccionarEmpresa);
}

// Coloca la fecha local actual como valor inicial del informe.
function establecerFechaActual() {
    if (inputFecha.value) return;
    const ahora = new Date();
    inputFecha.value = new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

// Actualiza la vista previa con la misma regla de fecha que reservará el folio al emitir el PDF.
async function actualizarFolioPrevisto() {
    if (!inputFecha.value) return;
    const url = new URL(window.location.href);
    url.searchParams.set('consultar_folio_fecha', inputFecha.value);
    try {
        const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!respuesta.ok) throw new Error('No fue posible consultar el folio previsto.');
        const datos = await respuesta.json();
        if (/^\d{5}$/.test(datos.folio || '')) inputFolio.value = datos.folio;
    } catch (error) {
        console.warn(error.message);
    }
}

// Devuelve un número finito o null cuando el control está vacío o no es numérico.
function leerNumero(control) {
    if (!control || control.value.trim() === '') return null;
    const valor = Number(control.value);
    return Number.isFinite(valor) ? valor : null;
}

// Obtiene la precisión decimal escrita en un control, incluso si usa notación científica.
function decimalesDelControl(control, predeterminado = 3) {
    const texto = control.value.trim().replace(',', '.').toLowerCase();
    const valor = Math.abs(Number(texto));
    if (!Number.isFinite(valor) || valor <= 0) return predeterminado;
    const [mantisa, exponenteTexto = '0'] = texto.split('e');
    const decimalesMantisa = mantisa.includes('.') ? mantisa.split('.')[1].length : 0;
    return Math.min(8, Math.max(0, decimalesMantisa - Number(exponenteTexto)));
}

// Determina los decimales de los resultados metrológicos a partir de la división e.
function decimalesVerificacion() {
    return decimalesDelControl(inputE);
}

// Determina los decimales obligatorios de las indicaciones a partir de la división real d.
function decimalesDivisionReal() {
    return decimalesDelControl(inputD);
}

// Formatea resultados sin alterar los valores sin redondear usados en la evaluación.
function formatearNumero(valor, decimalesExtra = 0, mostrarSigno = false) {
    if (!Number.isFinite(valor)) return '';
    const decimales = Math.min(8, decimalesVerificacion() + decimalesExtra);
    const normalizado = Math.abs(valor) < 10 ** (-(decimales + 2)) ? 0 : valor;
    let texto = normalizado.toFixed(decimales);
    if (texto.includes('.')) texto = texto.replace(/0+$/, '').replace(/\.$/, '');
    return mostrarSigno && normalizado > 0 ? `+${texto}` : texto;
}

// Conserva en cada indicación los ceros decimales exigidos por la división real d.
function formatearIndicacion(valor) {
    if (!Number.isFinite(valor)) return '';
    const decimales = decimalesDivisionReal();
    const normalizado = Math.abs(valor) < 10 ** (-(decimales + 2)) ? 0 : valor;
    return normalizado.toFixed(decimales);
}

// Comprueba que una indicación pertenezca a la secuencia de múltiplos de d desde cero.
function esIndicacionValida(valor) {
    const divisionReal = leerNumero(inputD);
    if (valor === null || divisionReal === null || divisionReal <= 0) return true;
    const cociente = valor / divisionReal;
    const tolerancia = Math.max(1e-9, Math.abs(cociente) * 1e-10);
    return Math.abs(cociente - Math.round(cociente)) <= tolerancia;
}

// Marca indicaciones incongruentes y genera un solo mensaje por prueba al validar el envío.
function validarIndicaciones(controles, validar = false, errores = [], contexto = '') {
    const divisionReal = leerNumero(inputD);
    let validas = true;
    controles.forEach((control) => {
        const valor = leerNumero(control);
        const valida = esIndicacionValida(valor);
        const mensaje = valida ? '' : `División real no válida: la indicación debe ser múltiplo de d=${formatearIndicacion(divisionReal)}.`;
        control.setCustomValidity(mensaje);
        control.classList.toggle('is-invalid', !valida);
        if (!valida) validas = false;
    });
    if (validar && !validas) errores.push(`División real no válida en ${contexto}: las indicaciones deben avanzar en múltiplos de d=${formatearIndicacion(divisionReal)}.`);
    return validas;
}

// Ajusta el paso permitido de todos los campos de indicación a la división real capturada.
function actualizarPasoIndicaciones() {
    const divisionReal = leerNumero(inputD);
    document.querySelectorAll('.campo-indicacion').forEach((control) => {
        if (divisionReal !== null && divisionReal > 0) control.step = String(divisionReal);
        else control.removeAttribute('step');
        validarIndicaciones([control]);
    });
}

// Actualiza el texto y estilo de una etiqueta de resultado sin depender solo del color.
function pintarResultado(elemento, resultado) {
    if (!elemento) return;
    const estado = resultado || 'PENDIENTE';
    elemento.textContent = estado;
    elemento.classList.remove('resultado-cumple', 'resultado-no-cumple', 'resultado-no-aplica', 'resultado-pendiente');
    const clase = estado === 'CUMPLE' ? 'resultado-cumple' : estado === 'NO CUMPLE' ? 'resultado-no-cumple' : estado === 'NO APLICA' ? 'resultado-no-aplica' : 'resultado-pendiente';
    elemento.classList.add(clase);
}

// Limpia los datos de contacto cuando cambia o se elimina la empresa seleccionada.
function limpiarContactos(mensaje = 'Seleccione primero una empresa') {
    selectContacto.innerHTML = `<option value="">${mensaje}</option>`;
    selectContacto.disabled = true;
    inputContacto.value = '';
    inputCorreo.value = '';
}

// Limpia y deshabilita las direcciones mientras no exista una empresa cargada.
function limpiarDirecciones(mensaje = 'Seleccione primero una empresa') {
    selectDireccion.innerHTML = `<option value="">${mensaje}</option>`;
    selectDireccion.disabled = true;
}

// Borra la ficha del instrumento para impedir que queden datos de otro equipo.
function limpiarDatosInstrumento() {
    [inputDescripcion, inputMarca, inputModelo, inputIdentificacion, inputSerie, inputMax, inputD, inputE, inputMin, inputClase]
        .forEach((control) => { control.value = ''; });
    inputUnidad.value = '';
    document.querySelectorAll('.unidad-instrumento').forEach((control) => { control.checked = false; });
    resumenEmt.textContent = '';
    actualizarTablaEmt('', Number.NaN, Number.NaN, Number.NaN);
}

// Restablece el selector de equipo y la ficha vinculada a este.
function limpiarEquipos(mensaje = 'Seleccione primero una empresa y una dirección') {
    selectEquipo.innerHTML = `<option value="">${mensaje}</option>`;
    selectEquipo.disabled = true;
    limpiarDatosInstrumento();
}

// Presenta los decimales almacenados sin ceros sobrantes ni separadores de miles.
function normalizarDecimalEquipo(valor) {
    if (valor === null || valor === undefined || String(valor).trim() === '') return '';
    const numero = Number(valor);
    return Number.isFinite(numero) ? String(numero) : '';
}

// Construye una etiqueta breve que permita identificar el equipo en el selector.
function etiquetaEquipo(equipo) {
    const identidad = [equipo.descripcion, equipo.marca, equipo.modelo].filter(Boolean).join(' · ');
    const referencia = equipo.identificacion
        ? `ID ${equipo.identificacion}`
        : equipo.numero_serie ? `Serie ${equipo.numero_serie}` : `Equipo #${equipo.id}`;
    const estado = equipo.estatus && equipo.estatus !== 'activo'
        ? ` · ${equipo.estatus === 'fuera_servicio' ? 'Fuera de servicio' : 'Inactivo'}`
        : '';
    const ubicacion = equipo.ubicacion ? ` · ${equipo.ubicacion}` : '';
    return `${identidad || 'Equipo sin descripción'} · ${referencia}${ubicacion}${estado}`;
}

// Filtra los equipos de la empresa por la dirección elegida en el informe.
function cargarEquiposDireccion() {
    limpiarEquipos('Seleccione un equipo');
    const direccionId = selectDireccion.options[selectDireccion.selectedIndex]?.dataset.id || '';
    if (!direccionId) {
        limpiarEquipos('Seleccione una dirección registrada');
        return;
    }
    const equiposDireccion = equiposEmpresa.filter((equipo) => String(equipo.direccion_id || '') === direccionId);
    if (!equiposDireccion.length) {
        limpiarEquipos('Sin equipos asociados a esta dirección');
        return;
    }
    equiposDireccion.forEach((equipo) => selectEquipo.add(new Option(etiquetaEquipo(equipo), String(equipo.id))));
    selectEquipo.disabled = false;
}

// Copia al informe la ficha registrada del equipo, sin ejecutar cálculos automáticos.
function cargarEquipoSeleccionado() {
    limpiarDatosInstrumento();
    const equipo = equiposEmpresa.find((registro) => String(registro.id) === selectEquipo.value);
    if (!equipo) return;
    inputDescripcion.value = equipo.descripcion || '';
    inputMarca.value = equipo.marca || '';
    inputModelo.value = equipo.modelo || '';
    inputIdentificacion.value = equipo.identificacion || '';
    inputSerie.value = equipo.numero_serie || '';
    inputUnidad.value = equipo.unidad || '';
    document.querySelectorAll('.unidad-instrumento').forEach((control) => {
        control.checked = control.value === inputUnidad.value;
    });
    inputMax.value = normalizarDecimalEquipo(equipo.capacidad_maxima);
    inputD.value = normalizarDecimalEquipo(equipo.division_real);
    inputE.value = normalizarDecimalEquipo(equipo.division_verificacion);
    inputClase.value = equipo.clase_exactitud || '';
    actualizarPasoIndicaciones();
}

// Convierte una dirección estructurada en el texto completo enviado al informe.
function formatearDireccionInforme(direccion) {
    const primeraLinea = [
        direccion.calle,
        direccion.numero_exterior ? `No. ${direccion.numero_exterior}` : '',
        direccion.numero_interior ? `Int. ${direccion.numero_interior}` : '',
    ].filter(Boolean).join(' ');
    const ubicacion = [
        direccion.colonia ? `Col. ${direccion.colonia}` : '',
        direccion.localidad,
        direccion.municipio && direccion.municipio !== direccion.ciudad ? direccion.municipio : '',
        direccion.ciudad,
        direccion.estado,
        direccion.codigo_postal ? `C.P. ${direccion.codigo_postal}` : '',
        direccion.pais,
    ].filter(Boolean).join(', ');
    const partes = [primeraLinea, ubicacion, direccion.entre_calles, direccion.referencia].filter(Boolean);
    return partes.length ? partes.join(', ') : (direccion.direccion_original || '');
}

// Carga las direcciones de la empresa y selecciona primero la marcada como principal.
function cargarDirecciones(direcciones, direccionHistorica = '') {
    selectDireccion.innerHTML = '<option value="">Seleccionar dirección</option>';
    const opciones = Array.isArray(direcciones) ? direcciones : [];
    opciones.forEach((direccion) => {
        const texto = formatearDireccionInforme(direccion);
        if (!texto) return;
        const opcion = document.createElement('option');
        const tipo = direccion.tipo_direccion === 'fiscal' ? 'Fiscal' : 'Entrega';
        const alias = direccion.alias ? ` · ${direccion.alias}` : '';
        const principal = Number(direccion.es_principal) === 1 ? ' · Principal' : '';
        opcion.value = texto;
        opcion.dataset.id = String(direccion.id || '');
        opcion.textContent = `${tipo}${alias}${principal} — ${texto}`;
        selectDireccion.appendChild(opcion);
    });
    if (selectDireccion.options.length === 1 && direccionHistorica) {
        selectDireccion.add(new Option(`Dirección registrada — ${direccionHistorica}`, direccionHistorica));
    }
    if (selectDireccion.options.length === 1) {
        selectDireccion.innerHTML = '<option value="">Sin direcciones registradas</option>';
    } else {
        selectDireccion.selectedIndex = 1;
    }
    selectDireccion.disabled = false;
}

// Carga las opciones de contacto y selecciona el contacto principal devuelto por el backend.
function cargarContactos(contactos) {
    selectContacto.innerHTML = '<option value="">Seleccionar contacto</option>';
    if (!Array.isArray(contactos) || contactos.length === 0) {
        selectContacto.innerHTML += '<option value="0">Sin contacto registrado</option>';
        selectContacto.disabled = false;
        selectContacto.value = '0';
        return;
    }
    contactos.forEach((contacto) => {
        const opcion = document.createElement('option');
        opcion.value = contacto.id;
        opcion.textContent = contacto.nombre || 'Contacto sin nombre';
        opcion.dataset.nombre = contacto.nombre || '';
        opcion.dataset.correo = contacto.correo || '';
        selectContacto.appendChild(opcion);
    });
    selectContacto.disabled = false;
    selectContacto.selectedIndex = 1;
    actualizarContactoSeleccionado();
}

// Copia el nombre y correo del contacto elegido a los campos que viajan al PDF.
function actualizarContactoSeleccionado() {
    const opcion = selectContacto.options[selectContacto.selectedIndex];
    inputContacto.value = opcion?.dataset.nombre || '';
    inputCorreo.value = opcion?.dataset.correo || '';
}

// Consulta empresa, contactos, direcciones y equipos con los endpoints existentes.
async function seleccionarEmpresa() {
    const id = selectEmpresa.value;
    if (id && empresaEnCarga === id) return;
    empresaEnCarga = id;
    inputEmpresa.value = '';
    equiposEmpresa = [];
    limpiarContactos();
    limpiarDirecciones();
    limpiarEquipos();
    if (!id) {
        empresaEnCarga = '';
        return;
    }
    try {
        const [respuestaEmpresa, respuestaContactos] = await Promise.all([
            fetch('../backend/empresas/query_id_empresa.php', { method: 'POST', body: id }),
            fetch('../backend/contactos/query_contacto_id_emp.php', { method: 'POST', body: id }),
        ]);
        if (!respuestaEmpresa.ok || !respuestaContactos.ok) throw new Error('No fue posible consultar los datos seleccionados.');
        const empresa = await respuestaEmpresa.json();
        const contactos = await respuestaContactos.json();
        inputEmpresa.value = empresa.razon_social || empresa.empresa || '';
        equiposEmpresa = Array.isArray(contactos.equipos) ? contactos.equipos : [];
        cargarContactos(contactos.contactos || []);
        cargarDirecciones(contactos.direcciones || [], empresa.dir_entrega || '');
        cargarEquiposDireccion();
    } catch (error) {
        equiposEmpresa = [];
        limpiarContactos('No fue posible cargar contactos');
        limpiarDirecciones('No fue posible cargar direcciones');
        limpiarEquipos('No fue posible cargar equipos');
        mostrarErrores([error.message]);
    } finally {
        empresaEnCarga = '';
    }
}

// Obtiene límites en divisiones y factor de Min para la clase calculada.
function configuracionClase(clase) {
    return {
        Ordinaria: { limite1: 50, limite2: 200, factorMin: 10 },
        Media: { limite1: 500, limite2: 2000, factorMin: 20 },
        Fina: { limite1: 5000, limite2: 20000, factorMin: 50 },
        Especial: { limite1: 50000, limite2: 200000, factorMin: 50 },
    }[clase] || null;
}

// Devuelve la unidad seleccionada para presentar intervalos y resultados con contexto.
function obtenerUnidadSeleccionada() {
    return inputUnidad.value;
}

// Actualiza la tabla EMT general usando límites inclusivos separados por una división real.
function actualizarTablaEmt(clase, divisionE, divisionReal, maximo) {
    cuerpoTablaEmt.replaceChildren();
    const configuracion = configuracionClase(clase);
    if (!configuracion || !Number.isFinite(divisionE) || !Number.isFinite(divisionReal) || divisionReal <= 0 || !Number.isFinite(maximo)) {
        const fila = cuerpoTablaEmt.insertRow();
        const celda = fila.insertCell();
        celda.colSpan = 2;
        celda.className = 'text-center';
        celda.textContent = 'Calcule los parámetros del instrumento para consultar los intervalos.';
        return;
    }
    const unidad = obtenerUnidadSeleccionada();
    const sufijoUnidad = unidad ? ` ${unidad}` : '';
    const limites = [configuracion.limite1 * divisionE, configuracion.limite2 * divisionE];
    const intervalos = [
        { desde: 0, hasta: Math.min(limites[0], maximo), multiplicador: 1 },
        { desde: limites[0] + divisionReal, hasta: Math.min(limites[1], maximo), multiplicador: 2 },
        { desde: limites[1] + divisionReal, hasta: maximo, multiplicador: 3 },
    ].filter((intervalo) => intervalo.hasta >= intervalo.desde);
    intervalos.forEach((intervalo) => {
        const fila = cuerpoTablaEmt.insertRow();
        const valores = [
            `${formatearNumero(intervalo.desde)}${sufijoUnidad} ≤ m ≤ ${formatearNumero(intervalo.hasta)}${sufijoUnidad}`,
            `±${formatearNumero(intervalo.multiplicador * divisionE)}${sufijoUnidad}`,
        ];
        valores.forEach((valor) => {
            const celda = fila.insertCell();
            celda.textContent = valor;
        });
    });
}

// Copia una carga inicial en su control final correspondiente sin volverla editable.
function sincronizarCargaFinal(controlInicial) {
    const controlFinal = document.getElementById(controlInicial.id.replace(/^inicial_/, 'final_'));
    if (!controlFinal) return;
    controlFinal.value = controlInicial.value;
    const tarjetaFinal = controlFinal.closest('.prueba-card');
    if (!tarjetaFinal) return;
    if (tarjetaFinal.dataset.prueba.endsWith('_repetibilidad')) evaluarRepetibilidad(tarjetaFinal);
    else if (tarjetaFinal.dataset.prueba.endsWith('_excentricidad')) evaluarExcentricidad(tarjetaFinal);
    else evaluarExactitud(tarjetaFinal);
}

// Sincroniza todas las cargas finales después de recalcular sugerencias iniciales.
function sincronizarTodasLasCargas() {
    document.querySelectorAll('#pruebas_inicial .carga-sugerida, #pruebas_inicial .carga-exactitud').forEach(sincronizarCargaFinal);
}

// Clasifica el instrumento con la lógica aprobada para esta V1.
function obtenerClase(numeroDivisiones) {
    if (numeroDivisiones <= 1000) return 'Ordinaria';
    if (numeroDivisiones <= 10000) return 'Media';
    if (numeroDivisiones <= 100000) return 'Fina';
    return 'Especial';
}

// Determina intervalo, multiplicador y EMT para una carga real capturada.
function obtenerEMT(carga, clase, divisionE) {
    const configuracion = configuracionClase(clase);
    if (!configuracion || !Number.isFinite(carga) || !Number.isFinite(divisionE) || divisionE <= 0) return null;
    const divisionesCarga = carga / divisionE;
    let intervalo = 1;
    if (divisionesCarga > configuracion.limite2) intervalo = 3;
    else if (divisionesCarga > configuracion.limite1) intervalo = 2;
    return { intervalo, multiplicador: intervalo, valor: divisionE * intervalo, divisionesCarga };
}

// Añade una carga sin duplicados cuando está dentro del alcance del instrumento.
function agregarCarga(cargas, valor, minimo, maximo) {
    if (!Number.isFinite(valor) || valor < minimo || valor > maximo) return;
    const tolerancia = Math.max(1e-12, Math.abs(maximo) * 1e-12);
    if (!cargas.some((existente) => Math.abs(existente - valor) <= tolerancia)) cargas.push(valor);
}

// Propone cinco cargas crecientes priorizando Min, cambios reales de EMT y Max.
function obtenerCargasExactitud(minimo, maximo, clase, divisionE) {
    const configuracion = configuracionClase(clase);
    const cargas = [];
    [minimo, configuracion.limite1 * divisionE, configuracion.limite2 * divisionE, maximo].forEach((valor) => agregarCarga(cargas, valor, minimo, maximo));
    while (cargas.length < 5 && maximo > minimo) {
        cargas.sort((a, b) => a - b);
        let inicioMayor = cargas[0];
        let finMayor = cargas[cargas.length - 1];
        let amplitudMayor = -1;
        for (let i = 0; i < cargas.length - 1; i += 1) {
            const amplitud = cargas[i + 1] - cargas[i];
            if (amplitud > amplitudMayor) {
                amplitudMayor = amplitud;
                inicioMayor = cargas[i];
                finMayor = cargas[i + 1];
            }
        }
        if (amplitudMayor <= 0) break;
        agregarCarga(cargas, inicioMayor + (finMayor - inicioMayor) / 2, minimo, maximo);
    }
    return cargas.sort((a, b) => a - b).slice(0, 5);
}

// Coloca una carga sugerida sin sobrescribir una modificación manual del técnico.
function colocarSugerencia(control, valor, forzar) {
    if (!control || (!forzar && control.dataset.automatica === 'false')) return;
    control.value = formatearNumero(valor);
    control.dataset.automatica = 'true';
}

// Calcula clase, Min, resumen EMT y cargas sugeridas de las seis pruebas.
function actualizarParametros(forzarSugerencias = false) {
    const maximo = leerNumero(inputMax);
    const divisionReal = leerNumero(inputD);
    const divisionE = leerNumero(inputE);
    if (maximo === null || divisionReal === null || divisionE === null || maximo <= 0 || divisionReal <= 0 || divisionE <= 0) {
        inputMin.value = '';
        inputClase.value = '';
        resumenEmt.textContent = '';
        actualizarTablaEmt('', Number.NaN, Number.NaN, Number.NaN);
        return false;
    }
    const clase = obtenerClase(maximo / divisionE);
    const configuracion = configuracionClase(clase);
    const minimo = configuracion.factorMin * divisionE;
    inputClase.value = clase;
    inputMin.value = formatearNumero(minimo);
    resumenEmt.textContent = `Clase ${clase}. EMT en servicio: ±${formatearNumero(divisionE)}, ±${formatearNumero(2 * divisionE)} y ±${formatearNumero(3 * divisionE)}.`;
    actualizarTablaEmt(clase, divisionE, divisionReal, maximo);
    colocarSugerencia(document.getElementById('inicial_repetibilidad_carga'), maximo / 2, forzarSugerencias);
    colocarSugerencia(document.getElementById('inicial_excentricidad_carga'), maximo / 3, forzarSugerencias);
    obtenerCargasExactitud(Math.min(minimo, maximo), maximo, clase, divisionE).forEach((carga, indice) => colocarSugerencia(document.getElementById(`inicial_exactitud_carga_${indice + 1}`), carga, forzarSugerencias));
    sincronizarTodasLasCargas();
    return true;
}

// Cambia una prueba entre evaluación automática y estado No aplica.
function actualizarNoAplica(tarjeta) {
    const noAplica = tarjeta.querySelector('.prueba-no-aplica').checked;
    const motivo = tarjeta.querySelector('.motivo-no-aplica-input');
    const contenido = tarjeta.querySelector('.contenido-prueba');
    tarjeta.querySelector('.motivo-no-aplica').classList.toggle('d-none', !noAplica);
    motivo.required = noAplica;
    contenido.classList.toggle('prueba-deshabilitada', noAplica);
    contenido.querySelectorAll('input').forEach((control) => {
        if (!control.classList.contains('prueba-resultado') && !control.classList.contains('resultado-punto')) control.disabled = noAplica;
    });
    guardarResultadoPrueba(tarjeta, noAplica ? 'NO APLICA' : '');
}

// Calcula el resultado general del checklist y exige motivo para cada No aplica.
function evaluarInspeccion(validar = false, errores = []) {
    let incompleto = false;
    let noCumple = false;
    document.querySelectorAll('.inspeccion-estado').forEach((select) => {
        const observacion = document.getElementById(select.dataset.observacion);
        observacion.required = select.value === 'NO APLICA';
        if (!select.value || (select.value === 'NO APLICA' && !observacion.value.trim())) incompleto = true;
        if (select.value === 'NO CUMPLE') noCumple = true;
    });
    const resultado = incompleto ? '' : noCumple ? 'NO CUMPLE' : 'CUMPLE';
    document.getElementById('inspeccion_resultado').value = resultado;
    pintarResultado(document.getElementById('inspeccion_resultado_texto'), resultado);
    if (validar && incompleto) errores.push('Complete los seis reactivos de inspección y el motivo de cada No aplica.');
    return Boolean(resultado);
}

// Registra un resultado de prueba en el campo POST y en su distintivo visible.
function guardarResultadoPrueba(tarjeta, resultado) {
    tarjeta.querySelector('.prueba-resultado').value = resultado || '';
    pintarResultado(tarjeta.querySelector('.prueba-resultado-texto'), resultado);
}

// Evalúa cinco lecturas de repetibilidad contra el EMT de la carga aplicada.
function evaluarRepetibilidad(tarjeta, validar = false, errores = []) {
    const prefijo = tarjeta.dataset.prueba;
    if (tarjeta.querySelector('.prueba-no-aplica').checked) {
        const motivo = tarjeta.querySelector('.motivo-no-aplica-input').value.trim();
        if (validar && !motivo) errores.push(`Indique el motivo de No aplica en ${prefijo.replaceAll('_', ' ')}.`);
        guardarResultadoPrueba(tarjeta, 'NO APLICA');
        return Boolean(motivo);
    }
    const carga = leerNumero(document.getElementById(`${prefijo}_carga`));
    const controlesLectura = Array.from({ length: 5 }, (_, indice) => document.getElementById(`${prefijo}_lectura_${indice + 1}`));
    const lecturas = controlesLectura.map(leerNumero);
    const indicacionesValidas = validarIndicaciones(controlesLectura, validar, errores, prefijo.replaceAll('_', ' '));
    if (carga === null || lecturas.some((valor) => valor === null)) {
        guardarResultadoPrueba(tarjeta, '');
        if (validar) errores.push(`Complete carga y cinco lecturas de ${prefijo.replaceAll('_', ' ')}.`);
        return false;
    }
    if (!indicacionesValidas) {
        ['maxima', 'minima', 'diferencia', 'intervalo', 'emt'].forEach((sufijo) => {
            document.getElementById(`${prefijo}_${sufijo}`).value = '';
        });
        guardarResultadoPrueba(tarjeta, '');
        return false;
    }
    const emt = obtenerEMT(carga, inputClase.value, Number(inputE.value));
    const maxima = Math.max(...lecturas);
    const minima = Math.min(...lecturas);
    const diferencia = maxima - minima;
    document.getElementById(`${prefijo}_maxima`).value = formatearNumero(maxima);
    document.getElementById(`${prefijo}_minima`).value = formatearNumero(minima);
    document.getElementById(`${prefijo}_diferencia`).value = formatearNumero(diferencia);
    document.getElementById(`${prefijo}_intervalo`).value = String(emt.intervalo);
    document.getElementById(`${prefijo}_emt`).value = formatearNumero(emt.valor);
    guardarResultadoPrueba(tarjeta, diferencia <= emt.valor ? 'CUMPLE' : 'NO CUMPLE');
    return true;
}

// Evalúa diferencias absolutas de excentricidad respecto de la lectura central.
function evaluarExcentricidad(tarjeta, validar = false, errores = []) {
    const prefijo = tarjeta.dataset.prueba;
    if (tarjeta.querySelector('.prueba-no-aplica').checked) {
        const motivo = tarjeta.querySelector('.motivo-no-aplica-input').value.trim();
        if (validar && !motivo) errores.push(`Indique el motivo de No aplica en ${prefijo.replaceAll('_', ' ')}.`);
        guardarResultadoPrueba(tarjeta, 'NO APLICA');
        return Boolean(motivo);
    }
    const carga = leerNumero(document.getElementById(`${prefijo}_carga`));
    const controlesLectura = Array.from({ length: 5 }, (_, indice) => document.getElementById(`${prefijo}_lectura_${indice + 1}`));
    const lecturas = controlesLectura.map(leerNumero);
    const indicacionesValidas = validarIndicaciones(controlesLectura, validar, errores, prefijo.replaceAll('_', ' '));
    if (carga === null || lecturas.some((valor) => valor === null)) {
        guardarResultadoPrueba(tarjeta, '');
        if (validar) errores.push(`Complete carga y cinco posiciones de ${prefijo.replaceAll('_', ' ')}.`);
        return false;
    }
    if (!indicacionesValidas) {
        for (let i = 2; i <= 5; i += 1) document.getElementById(`${prefijo}_diferencia_${i}`).value = '';
        ['diferencia_maxima', 'intervalo', 'emt'].forEach((sufijo) => {
            document.getElementById(`${prefijo}_${sufijo}`).value = '';
        });
        guardarResultadoPrueba(tarjeta, '');
        return false;
    }
    const diferencias = lecturas.map((lectura, indice) => indice === 0 ? 0 : Math.abs(lectura - lecturas[0]));
    const diferenciaMaxima = Math.max(...diferencias.slice(1));
    const emt = obtenerEMT(carga, inputClase.value, Number(inputE.value));
    for (let i = 2; i <= 5; i += 1) document.getElementById(`${prefijo}_diferencia_${i}`).value = formatearNumero(diferencias[i - 1]);
    document.getElementById(`${prefijo}_diferencia_maxima`).value = formatearNumero(diferenciaMaxima);
    document.getElementById(`${prefijo}_intervalo`).value = String(emt.intervalo);
    document.getElementById(`${prefijo}_emt`).value = formatearNumero(emt.valor);
    guardarResultadoPrueba(tarjeta, diferenciaMaxima <= emt.valor ? 'CUMPLE' : 'NO CUMPLE');
    return true;
}

// Evalúa error con signo, EMT y resultado de los seis puntos de exactitud.
function evaluarExactitud(tarjeta, validar = false, errores = []) {
    const prefijo = tarjeta.dataset.prueba;
    if (tarjeta.querySelector('.prueba-no-aplica').checked) {
        const motivo = tarjeta.querySelector('.motivo-no-aplica-input').value.trim();
        if (validar && !motivo) errores.push(`Indique el motivo de No aplica en ${prefijo.replaceAll('_', ' ')}.`);
        guardarResultadoPrueba(tarjeta, 'NO APLICA');
        return Boolean(motivo);
    }
    let completo = true;
    let indicacionesValidas = true;
    let resultadoGeneral = 'CUMPLE';
    for (let i = 0; i <= 5; i += 1) {
        const carga = leerNumero(document.getElementById(`${prefijo}_carga_${i}`));
        const controlIndicacion = document.getElementById(`${prefijo}_indicacion_${i}`);
        const indicacion = leerNumero(controlIndicacion);
        const campoResultado = document.getElementById(`${prefijo}_resultado_${i}`);
        if (carga === null || indicacion === null) {
            completo = false;
            campoResultado.value = '';
            pintarResultado(campoResultado.previousElementSibling, '');
            continue;
        }
        if (!validarIndicaciones([controlIndicacion])) {
            indicacionesValidas = false;
            document.getElementById(`${prefijo}_error_${i}`).value = '';
            document.getElementById(`${prefijo}_intervalo_${i}`).value = '';
            document.getElementById(`${prefijo}_emt_${i}`).value = '';
            campoResultado.value = '';
            pintarResultado(campoResultado.previousElementSibling, '');
            continue;
        }
        const error = indicacion - carga;
        const emt = obtenerEMT(carga, inputClase.value, Number(inputE.value));
        const resultadoPunto = Math.abs(error) <= emt.valor ? 'CUMPLE' : 'NO CUMPLE';
        if (resultadoPunto === 'NO CUMPLE') resultadoGeneral = 'NO CUMPLE';
        document.getElementById(`${prefijo}_error_${i}`).value = formatearNumero(error, 0, true);
        document.getElementById(`${prefijo}_intervalo_${i}`).value = String(emt.intervalo);
        document.getElementById(`${prefijo}_emt_${i}`).value = formatearNumero(emt.valor);
        campoResultado.value = resultadoPunto;
        pintarResultado(campoResultado.previousElementSibling, resultadoPunto);
    }
    if (!completo) {
        guardarResultadoPrueba(tarjeta, '');
        if (validar) errores.push(`Complete las seis cargas e indicaciones de ${prefijo.replaceAll('_', ' ')}.`);
        return false;
    }
    if (!indicacionesValidas) {
        guardarResultadoPrueba(tarjeta, '');
        if (validar) validarIndicaciones(Array.from({ length: 6 }, (_, indice) => document.getElementById(`${prefijo}_indicacion_${indice}`)), true, errores, prefijo.replaceAll('_', ' '));
        return false;
    }
    guardarResultadoPrueba(tarjeta, resultadoGeneral);
    return true;
}

// Evalúa las seis pruebas y acumula mensajes de validación para el envío final.
function evaluarTodasLasPruebas(validar = false) {
    const errores = [];
    document.querySelectorAll('[data-prueba$="_repetibilidad"]').forEach((tarjeta) => evaluarRepetibilidad(tarjeta, validar, errores));
    document.querySelectorAll('[data-prueba$="_excentricidad"]').forEach((tarjeta) => evaluarExcentricidad(tarjeta, validar, errores));
    document.querySelectorAll('[data-prueba$="_exactitud"]').forEach((tarjeta) => evaluarExactitud(tarjeta, validar, errores));
    return errores;
}

// Presenta errores de validación en un único bloque visible y accesible.
function mostrarErrores(errores) {
    const contenedor = document.getElementById('errores_informe');
    if (!errores.length) {
        contenedor.classList.add('d-none');
        contenedor.innerHTML = '';
        return;
    }
    contenedor.innerHTML = `<strong>Revise antes de generar el PDF:</strong><ul class="mb-0">${errores.map((error) => `<li>${error}</li>`).join('')}</ul>`;
    contenedor.classList.remove('d-none');
    contenedor.focus();
}

// Ejecuta la validación integral y permite el POST solo cuando el informe está completo.
function validarEnvio(evento) {
    const errores = [];
    if (!actualizarParametros(false)) errores.push('El equipo seleccionado debe tener capacidad máxima, división real y división de verificación mayores que cero. Corrija los datos desde el formulario del equipo.');
    evaluarInspeccion(true, errores);
    errores.push(...evaluarTodasLasPruebas(true));
    if (!formularioInforme.checkValidity()) {
        evento.preventDefault();
        formularioInforme.reportValidity();
        errores.unshift('Complete los campos generales obligatorios marcados por el navegador.');
    }
    if (errores.length) {
        evento.preventDefault();
        mostrarErrores([...new Set(errores)]);
    } else mostrarErrores([]);
}

establecerFechaActual();
actualizarFolioPrevisto();
limpiarContactos();
limpiarEquipos();
inputFecha.addEventListener('change', actualizarFolioPrevisto);
selectEmpresa.addEventListener('change', seleccionarEmpresa);
selectContacto.addEventListener('change', actualizarContactoSeleccionado);
selectDireccion.addEventListener('change', cargarEquiposDireccion);
selectEquipo.addEventListener('change', cargarEquipoSeleccionado);
document.getElementById('btn_analizar').addEventListener('click', () => {
    if (!actualizarParametros(true)) mostrarErrores(['El equipo seleccionado debe tener capacidad máxima, división real y división de verificación mayores que cero. Corrija los datos desde el formulario del equipo.']);
    else {
        mostrarErrores([]);
        evaluarTodasLasPruebas(false);
    }
});
document.querySelectorAll('#pruebas_inicial .carga-sugerida, #pruebas_inicial .carga-exactitud').forEach((control) => control.addEventListener('input', () => {
    control.dataset.automatica = 'false';
    sincronizarCargaFinal(control);
}));
inputD.addEventListener('input', () => {
    actualizarPasoIndicaciones();
    evaluarTodasLasPruebas(false);
});
document.querySelectorAll('.campo-indicacion').forEach((control) => control.addEventListener('blur', () => {
    const valor = leerNumero(control);
    if (valor !== null && esIndicacionValida(valor)) control.value = formatearIndicacion(valor);
    validarIndicaciones([control]);
}));
document.querySelectorAll('.prueba-no-aplica').forEach((casilla) => {
    const tarjeta = casilla.closest('.prueba-card');
    casilla.addEventListener('change', () => actualizarNoAplica(tarjeta));
});
document.querySelectorAll('.inspeccion-estado, .inspeccion-observacion').forEach((control) => control.addEventListener('input', () => evaluarInspeccion(false)));
document.querySelectorAll('.prueba-card').forEach((tarjeta) => {
    tarjeta.addEventListener('input', () => {
        if (tarjeta.dataset.prueba.endsWith('_repetibilidad')) evaluarRepetibilidad(tarjeta);
        else if (tarjeta.dataset.prueba.endsWith('_excentricidad')) evaluarExcentricidad(tarjeta);
        else evaluarExactitud(tarjeta);
    });
});
actualizarPasoIndicaciones();
formularioInforme.addEventListener('submit', validarEnvio);
