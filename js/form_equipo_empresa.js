var formularioEquipo = document.getElementById('form_equipo_empresa');
var mensajeFormularioEquipo = document.getElementById('mensaje_form_equipo');
var subtituloEquipo = document.getElementById('subtitulo_equipo');
var campoEquipoId = document.getElementById('equipo_id');
var campoDireccionEquipo = document.getElementById('direccion_id');
var campoDescripcionEquipo = document.getElementById('descripcion_id');
var campoMarcaEquipo = document.getElementById('marca_id');
var campoCapacidadEquipo = document.getElementById('capacidad_maxima');
var campoDivisionRealEquipo = document.getElementById('division_real');
var campoDivisionVerificacionEquipo = document.getElementById('division_verificacion');
var campoClaseEquipo = document.getElementById('clase_exactitud');
var mensajeNumerosEquipo = document.getElementById('mensaje_numeros_equipo');
var botonGuardarEquipo = document.getElementById('btn_guardar_equipo');
var tokenEquipo = formularioEquipo.querySelector('input[name="csrf"]').value;
var patronNumeroEquipo = /^(?:0|[1-9]\d*)(?:\.\d{1,9})?$/;

// Construye una etiqueta clara con el alias y los datos disponibles de la dirección.
function etiquetaDireccionEquipo(direccion) {
    var alias = String(direccion.alias || direccion.tipo_direccion || 'Dirección').trim();
    var detalle = [direccion.calle, direccion.numero_exterior, direccion.colonia, direccion.ciudad, direccion.estado]
        .filter(function (valor) { return String(valor || '').trim() !== ''; })
        .join(', ');
    return detalle ? alias + ' — ' + detalle : alias;
}

// Agrega las opciones de un catálogo conservando el texto recibido por el servidor.
function llenarSelectorEquipo(selector, elementos, crearEtiqueta) {
    elementos.forEach(function (elemento) {
        var opcion = document.createElement('option');
        opcion.value = elemento.id;
        opcion.textContent = crearEtiqueta ? crearEtiqueta(elemento) : elemento.nombre;
        selector.appendChild(opcion);
    });
}

// Inserta una opción recién creada, evita duplicados por identificador y la deja seleccionada.
function seleccionarNuevoElementoCatalogo(selector, elemento) {
    var opcion = Array.from(selector.options).find(function (item) {
        return String(item.value) === String(elemento.id);
    });
    if (!opcion) {
        opcion = document.createElement('option');
        opcion.value = elemento.id;
        selector.appendChild(opcion);
    }
    opcion.textContent = elemento.nombre;
    selector.value = String(elemento.id);
}

// Guarda una descripción o marca desde el formulario sin interrumpir la captura del equipo.
async function guardarElementoCatalogo(tipo, entrada, boton, selector, estado) {
    var nombre = entrada.value.trim().replace(/\s+/g, ' ');
    if (!nombre) {
        estado.textContent = 'Escribe el nombre que deseas agregar.';
        estado.className = 'empresa-notes-status empresa-notes-status-error mt-1';
        entrada.focus();
        return;
    }

    boton.disabled = true;
    estado.textContent = 'Guardando...';
    estado.className = 'empresa-notes-status mt-1';
    try {
        var respuesta = await fetch('../backend/empresas/guardar_catalogo_equipo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({ tipo: tipo, nombre: nombre, csrf: tokenEquipo })
        });
        var datos = await respuesta.json();
        if (!respuesta.ok || datos.error) {
            throw new Error(datos.error || 'No fue posible guardar el nuevo elemento.');
        }
        seleccionarNuevoElementoCatalogo(selector, datos.elemento);
        entrada.value = '';
        estado.textContent = datos.elemento.nombre + ' quedó disponible y seleccionado.';
        estado.className = 'empresa-notes-status empresa-notes-status-success mt-1';
    } catch (error) {
        estado.textContent = error.message;
        estado.className = 'empresa-notes-status empresa-notes-status-error mt-1';
    } finally {
        boton.disabled = false;
    }
}

// Quita ceros decimales sobrantes sin agregar separadores de miles.
function normalizarDecimalEquipo(valor) {
    var texto = String(valor || '').trim();
    if (!texto.includes('.')) {
        return texto;
    }
    return texto.replace(/0+$/, '').replace(/\.$/, '');
}

// Calcula la clase usando la misma relación Max/e del módulo de informes.
function actualizarClaseEquipo() {
    var capacidadTexto = campoCapacidadEquipo.value.trim();
    var divisionTexto = campoDivisionVerificacionEquipo.value.trim();
    if (!patronNumeroEquipo.test(capacidadTexto) || !patronNumeroEquipo.test(divisionTexto)) {
        campoClaseEquipo.value = '';
        return;
    }
    var capacidad = Number(capacidadTexto);
    var division = Number(divisionTexto);
    if (capacidad <= 0 || division <= 0) {
        campoClaseEquipo.value = '';
        return;
    }
    var numeroDivisiones = capacidad / division;
    if (numeroDivisiones <= 1000) campoClaseEquipo.value = 'Ordinaria';
    else if (numeroDivisiones <= 10000) campoClaseEquipo.value = 'Media';
    else if (numeroDivisiones <= 100000) campoClaseEquipo.value = 'Fina';
    else campoClaseEquipo.value = 'Especial';
}

// Coloca los datos de un equipo existente en el formulario de edición.
function mostrarEquipoEnFormulario(equipo) {
    if (!equipo) {
        return;
    }
    campoDireccionEquipo.value = equipo.direccion_id || '';
    campoDescripcionEquipo.value = equipo.descripcion_id || '';
    campoMarcaEquipo.value = equipo.marca_id || '';
    document.getElementById('modelo').value = equipo.modelo || '';
    document.getElementById('identificacion').value = equipo.identificacion || '';
    document.getElementById('numero_serie').value = equipo.numero_serie || '';
    document.getElementById('ubicacion').value = equipo.ubicacion || '';
    document.getElementById('unidad').value = equipo.unidad || '';
    campoCapacidadEquipo.value = normalizarDecimalEquipo(equipo.capacidad_maxima);
    campoDivisionRealEquipo.value = normalizarDecimalEquipo(equipo.division_real);
    campoDivisionVerificacionEquipo.value = normalizarDecimalEquipo(equipo.division_verificacion);
    document.getElementById('estatus').value = equipo.estatus || 'activo';
    document.getElementById('equipo_version').value = equipo.version || '';
    actualizarClaseEquipo();
}

// Carga direcciones, descripciones, marcas y, cuando aplica, el equipo a editar.
async function prepararFormularioEquipo() {
    var empresaId = document.getElementById('empresa_id').value;
    var url = '../backend/empresas/query_equipo_empresa.php?empresa_id=' + encodeURIComponent(empresaId);
    if (campoEquipoId.value) {
        url += '&equipo_id=' + encodeURIComponent(campoEquipoId.value);
    }

    try {
        var respuesta = await fetch(url);
        var datos = await respuesta.json();
        if (!respuesta.ok || datos.error) {
            throw new Error(datos.error || 'No fue posible preparar el formulario.');
        }
        subtituloEquipo.textContent = datos.empresa.empresa;
        llenarSelectorEquipo(campoDireccionEquipo, datos.direcciones || [], etiquetaDireccionEquipo);
        llenarSelectorEquipo(campoDescripcionEquipo, datos.descripciones || []);
        llenarSelectorEquipo(campoMarcaEquipo, datos.marcas || []);

        mostrarEquipoEnFormulario(datos.equipo);
        mensajeFormularioEquipo.classList.add('d-none');
        formularioEquipo.classList.remove('d-none');
    } catch (error) {
        mensajeFormularioEquipo.textContent = error.message;
        mensajeFormularioEquipo.className = 'alert alert-danger';
    }
}

// Rechaza separadores, exponentes y decimales sin cero a la izquierda antes del envío.
function validarNumerosEquipo(evento) {
    var camposObligatorios = [campoCapacidadEquipo, campoDivisionRealEquipo];
    var invalido = camposObligatorios.find(function (campo) {
        var valor = campo.value.trim();
        return !patronNumeroEquipo.test(valor) || Number(valor) <= 0;
    });
    var divisionVerificacion = campoDivisionVerificacionEquipo.value.trim();
    if (!invalido && divisionVerificacion
        && (!patronNumeroEquipo.test(divisionVerificacion) || Number(divisionVerificacion) <= 0)) {
        invalido = campoDivisionVerificacionEquipo;
    }

    if (invalido) {
        evento.preventDefault();
        invalido.focus();
        mensajeNumerosEquipo.textContent = 'Escribe valores mayores que cero, sin espacios ni comas y con cero antes del punto decimal.';
        mensajeNumerosEquipo.className = 'empresa-notes-status empresa-notes-status-error mt-3';
        return;
    }
    if (!formularioEquipo.checkValidity()) {
        evento.preventDefault();
        formularioEquipo.reportValidity();
    }
}

[campoCapacidadEquipo, campoDivisionVerificacionEquipo].forEach(function (campo) {
    campo.addEventListener('input', actualizarClaseEquipo);
});
formularioEquipo.addEventListener('submit', validarNumerosEquipo);
DigitAppDuplicateWarning.protegerFormulario({
    formulario: formularioEquipo,
    boton: botonGuardarEquipo,
    idAviso: 'aviso-duplicado-equipo',
    titulo: 'Es posible que este equipo ya exista',
    crearUrl: function () {
        return '../backend/helpers/query_posibles_duplicados.php?' + new URLSearchParams({
            tipo: 'equipo',
            identificacion: document.getElementById('identificacion').value,
            numero_serie: document.getElementById('numero_serie').value,
            excluir_id: campoEquipoId.value || ''
        }).toString();
    },
    mostrarError: function (mensaje) {
        mensajeNumerosEquipo.textContent = mensaje;
        mensajeNumerosEquipo.className = 'empresa-notes-status empresa-notes-status-error mt-3';
    }
});
// Vincula el alta rápida de una descripción con su selector.
document.getElementById('btn_agregar_descripcion').addEventListener('click', function () {
    guardarElementoCatalogo(
        'descripcion',
        document.getElementById('nueva_descripcion_equipo'),
        this,
        campoDescripcionEquipo,
        document.getElementById('estado_descripcion_equipo')
    );
});
// Vincula el alta rápida de una marca con su selector.
document.getElementById('btn_agregar_marca').addEventListener('click', function () {
    guardarElementoCatalogo(
        'marca',
        document.getElementById('nueva_marca_equipo'),
        this,
        campoMarcaEquipo,
        document.getElementById('estado_marca_equipo')
    );
});
// Permite confirmar las altas rápidas con Enter sin enviar el formulario completo.
[
    ['nueva_descripcion_equipo', 'btn_agregar_descripcion'],
    ['nueva_marca_equipo', 'btn_agregar_marca']
].forEach(function (controles) {
    document.getElementById(controles[0]).addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            document.getElementById(controles[1]).click();
        }
    });
});
prepararFormularioEquipo();
