(function () {
    'use strict';

    // Construye un aviso reutilizable con accesos seguros a los registros posiblemente repetidos.
    function crearAvisoDuplicados(formulario, configuracion, coincidencias) {
        var anterior = document.getElementById(configuracion.idAviso);
        if (anterior) anterior.remove();

        var aviso = document.createElement('div');
        aviso.id = configuracion.idAviso;
        aviso.className = 'alert alert-warning mt-4';
        aviso.setAttribute('role', 'alert');

        var titulo = document.createElement('h2');
        titulo.className = 'h5';
        titulo.textContent = configuracion.titulo;
        aviso.appendChild(titulo);

        var explicacion = document.createElement('p');
        explicacion.textContent = 'Revisa las coincidencias antes de decidir si realmente necesitas crear o guardar otro registro.';
        aviso.appendChild(explicacion);

        var lista = document.createElement('ul');
        lista.className = 'mb-3';
        coincidencias.forEach(function (item) {
            var elemento = document.createElement('li');
            var enlace = document.createElement('a');
            enlace.href = item.url;
            enlace.target = '_blank';
            enlace.rel = 'noopener';
            enlace.className = 'entity-link';
            enlace.textContent = item.titulo || 'Registro existente';
            elemento.appendChild(enlace);
            elemento.appendChild(document.createTextNode(' — ' + (item.detalle || '') + ' (' + item.motivos.join(', ') + ')'));
            lista.appendChild(elemento);
        });
        aviso.appendChild(lista);

        var acciones = document.createElement('div');
        acciones.className = 'd-flex flex-wrap gap-2';
        var continuar = document.createElement('button');
        continuar.type = 'button';
        continuar.className = 'btn btn-warning';
        continuar.textContent = 'Continuar y guardar';
        var cancelar = document.createElement('button');
        cancelar.type = 'button';
        cancelar.className = 'btn btn-secondary';
        cancelar.textContent = 'Cancelar y revisar';
        acciones.append(continuar, cancelar);
        aviso.appendChild(acciones);

        formulario.prepend(aviso);
        aviso.scrollIntoView({behavior: 'smooth', block: 'start'});
        return {aviso: aviso, continuar: continuar, cancelar: cancelar};
    }

    // Intercepta el guardado una sola vez y permite continuar expresamente después del aviso.
    function protegerFormulario(configuracion) {
        var formulario = configuracion.formulario;
        var continuarConfirmado = false;
        formulario.addEventListener('submit', function (evento) {
            if (evento.defaultPrevented) {
                configuracion.boton.disabled = false;
                return;
            }
            if (continuarConfirmado) {
                continuarConfirmado = false;
                return;
            }
            if (!formulario.checkValidity()) return;
            evento.preventDefault();
            configuracion.boton.disabled = true;
            fetch(configuracion.crearUrl())
                .then(function (respuesta) {
                    if (!respuesta.ok) throw new Error('No fue posible revisar posibles duplicados.');
                    return respuesta.json();
                })
                .then(function (datos) {
                    var coincidencias = datos.coincidencias || [];
                    if (!coincidencias.length) {
                        continuarConfirmado = true;
                        formulario.requestSubmit();
                        return;
                    }
                    configuracion.boton.disabled = false;
                    var controles = crearAvisoDuplicados(formulario, configuracion, coincidencias);
                    controles.continuar.addEventListener('click', function () {
                        continuarConfirmado = true;
                        formulario.requestSubmit();
                    });
                    controles.cancelar.addEventListener('click', function () {
                        controles.aviso.remove();
                    });
                })
                .catch(function (error) {
                    configuracion.boton.disabled = false;
                    configuracion.mostrarError(error.message);
                });
        });
    }

    window.DigitAppDuplicateWarning = {protegerFormulario: protegerFormulario};
}());
