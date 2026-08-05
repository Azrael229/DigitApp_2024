let chartClientes = null;
let chartMeses = null;

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficas();

        const datosGlobales = leerDatosGlobalesFacturas();
        console.log("Datos globales:", datosGlobales);

        const ventasMensuales = obtenerVentasMensuales(datosGlobales);
        actualizarGraficaVentasMensuales(ventasMensuales);
        
        const top10Clientes = obtenerTop10Clientes(datosGlobales);
        console.log("Top 10 clientes:", top10Clientes);

        actualizarGraficaTopClientes(top10Clientes);


});

/**
 * FUNCION: inicializarGraficas
 *
 * Objetivo:
 * Crear e inicializar las instancias de Chart.js utilizadas en el módulo
 * de analítica de facturación.
 *
 * Esta función prepara dos gráficas:
 * 1) Top 10 clientes por facturación
 * 2) Facturación por mes
 *
 * En esta etapa las gráficas se crean vacías; posteriormente se llenarán
 * con datos reales obtenidos de la tabla de facturas.
 */

function inicializarGraficas() {

    /**
     * Obtener los elementos canvas desde el DOM.
     * Cada canvas será el contenedor donde Chart.js dibujará la gráfica.
     */
    const canvasClientes = document.getElementById("chartClientes");
    const canvasMeses = document.getElementById("chartMeses");

    /**
     * Validación de seguridad.
     * Si alguno de los canvas no existe en la página, se detiene
     * la inicialización para evitar errores de JavaScript.
     */
    if (!canvasClientes || !canvasMeses) {
        console.error("No se encontraron los canvas de las gráficas");
        return;
    }

    /**
     * --------------------------------------------------------
     * GRÁFICA 1 — Top 10 clientes por facturación
     * --------------------------------------------------------
     *
     * Esta gráfica mostrará los clientes con mayor monto total
     * facturado dentro del sistema.
     *
     * Tipo:
     * Barra horizontal para mejorar la lectura de nombres largos.
     */

    chartClientes = new Chart(canvasClientes, {

        // Tipo de gráfica
        type: 'bar',

        // Configuración de datos iniciales (vacíos)
        data: {
            labels: [],

            datasets: [{
                // Texto que aparece en la leyenda de la gráfica
                label: 'Top 10 clientes por facturación',

                // Aquí se cargarán los montos facturados por cliente
                data: [],
                // Colores de relleno para cada barra
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(99, 255, 132, 0.6)',
                    'rgba(201, 203, 207, 0.6)',
                    'rgba(255, 99, 255, 0.6)',
                    'rgba(54, 235, 162, 0.6)'
                ],

                // Colores del borde de cada barra
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(99, 255, 132, 1)',
                    'rgba(201, 203, 207, 1)',
                    'rgba(255, 99, 255, 1)',
                    'rgba(54, 235, 162, 1)'
                ],

                borderWidth: 1,
                borderRadius: 6
            }]
        },

        // Opciones de configuración de Chart.js
        options: {
            layout: {
                padding: {
                    left: 10,
                    right: 20,
                    top: 10,
                    bottom: 10
                }
            },
            // Hace que la gráfica se adapte al tamaño del contenedor
            responsive: true,

            // Permite controlar la altura desde el contenedor CSS
            maintainAspectRatio: false,

            /**
             * Cambia la orientación de la gráfica.
             * Con 'y' se generan barras horizontales en lugar
             * de columnas verticales.
             */
            indexAxis: 'y',
            
            scales: {
                x: {
                    ticks: {
                    callback: function(value) {
                        return formatoMonedaMXN(value);
                        }
                    }
                }
            },

            plugins: {

                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Top 10 clientes por facturación',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const valor = context.raw;
                            return formatoMonedaMXN(valor);
                        }
                    }
                }
            }

        }

    });

/**
 * Formatea números como moneda MXN.
 * Entrada: número (ej. 12450.5)
 * Salida:  "$12,450.50"
 */
function formatoMonedaMXN(valor) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(valor);
}


    /**
     * --------------------------------------------------------
     * GRÁFICA 2 — Facturación por mes
     * --------------------------------------------------------
     *
     * Esta gráfica se utilizará posteriormente para mostrar
     * la evolución de la facturación a lo largo del tiempo.
     *
     * Tipo:
     * Línea para representar tendencias temporales.
     */

    chartMeses = new Chart(canvasMeses, {

        // Tipo de gráfica
        type: 'bar',

        // Configuración inicial sin datos
        data: {
            labels: [],

            datasets: [{
                // Título del dataset
                label: 'Ventas mensuales',

                // Valores que se cargarán posteriormente
                data: [],
                backgroundColor: function(context) {
                    const ultimoIndice = context.dataset.data.length - 1;
                    return context.dataIndex === ultimoIndice ? 'rgba(228, 0, 124, 0.72)' : 'rgba(32, 141, 151, 0.78)';
                },
                borderRadius: 6
            }]
        },

        // Opciones generales
        options: {

            // Permite que la gráfica sea adaptable al contenedor
            responsive: true,

            // Permite manejar la altura mediante CSS
            maintainAspectRatio: false,

            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return formatoMonedaMXN(value);
                        }
                    }
                }
            },

            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatoMonedaMXN(context.raw);
                        }
                    }
                }
            }
        }

    });

}

/**
 * FUNCION: leerDatosGlobalesFacturas
 *
 * Objetivo:
 * Leer todos los registros del DataTable #example, sin importar
 * filtros, búsqueda, paginación o visibilidad en pantalla.
 *
 * Esta función será la base para construir la gráfica fija
 * de Top 10 clientes global.
 */
function leerDatosGlobalesFacturas() {

    const tablaDataTable = $('#example').DataTable();
    const datos = [];

    tablaDataTable.rows().every(function () {

        const fila = this.node();

        const fecha = fila.cells[0]?.dataset.order || fila.cells[0]?.textContent.trim() || "";
        const cliente = fila.cells[1]?.textContent.trim() || "";
        const total = parseFloat(fila.cells[5]?.dataset.value || fila.cells[5]?.textContent || "0") || 0;

        datos.push({
            fecha: fecha,
            cliente: cliente,
            total: total
        });

    });

    return datos;
}

function obtenerVentasMensuales(facturas, meses = 12) {
    const ventasPorMes = {};

    const fechaActual = new Date();
    for (let i = meses - 1; i >= 0; i--) {
        const fechaMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth() - i, 1);
        const anio = fechaMes.getFullYear();
        const mes = String(fechaMes.getMonth() + 1).padStart(2, "0");
        ventasPorMes[`${anio}-${mes}`] = 0;
    }

    facturas.forEach(({ fecha, total }) => {
        const mes = fecha.slice(0, 7);
        if (ventasPorMes[mes] !== undefined) {
            ventasPorMes[mes] += total;
        }
    });

    return Object.entries(ventasPorMes);
}




/**
 * FUNCION: obtenerTop10Clientes
 *
 * Objetivo:
 * Recibir el arreglo global de facturas y agruparlo por cliente,
 * sumando el total facturado de cada uno.
 *
 * Después ordena los clientes de mayor a menor
 * y devuelve solo los primeros 10.
 */
/**
 * Agrupa todas las facturas por cliente.
 *
 * Entrada:
 * Arreglo de objetos con estructura:
 * { fecha, cliente, total }
 *
 * Proceso:
 * - recorre todos los registros
 * - acumula el total facturado por cliente
 * - convierte el acumulado en arreglo
 * - ordena de mayor a menor
 * - devuelve solo los 10 clientes con mayor facturación
 *
 * Salida esperada:
 * [
 *   { cliente: "Cliente A", total: 15200 },
 *   { cliente: "Cliente B", total: 9200 }
 * ]
 */

function obtenerTop10Clientes(datosFacturas) {

    const acumuladoPorCliente = {};

    datosFacturas.forEach((registro) => {

        const cliente = registro.cliente || "Sin nombre";
        const total = parseFloat(registro.total) || 0;

        if (!acumuladoPorCliente[cliente]) {
            acumuladoPorCliente[cliente] = 0;
        }

        acumuladoPorCliente[cliente] += total;
    });

    const ranking = Object.entries(acumuladoPorCliente).map(([cliente, total]) => {
        return {
            cliente: cliente,
            total: total
        };
    });

    ranking.sort((a, b) => b.total - a.total);

    return ranking.slice(0, 10);
}



/**
 * FUNCION: actualizarGraficaTopClientes
 *
 * Objetivo:
 * Recibir el ranking Top 10 de clientes y cargarlo
 * en la gráfica chartClientes.
 */
function actualizarGraficaTopClientes(top10Clientes) {

    if (!chartClientes) {
        console.error("La gráfica chartClientes no está inicializada");
        return;
    }

    const labels = top10Clientes.map(item => item.cliente);
    const data = top10Clientes.map(item => item.total);

    chartClientes.data.labels = labels;
    chartClientes.data.datasets[0].data = data;
    chartClientes.update();
}

function actualizarGraficaVentasMensuales(ventasMensuales) {

    if (!chartMeses) {
        console.error("La gráfica chartMeses no está inicializada");
        return;
    }

    const labels = ventasMensuales.map(([mes]) => {
        const [anio, numeroMes] = mes.split('-');
        const fecha = new Date(anio, numeroMes - 1, 1);
        return fecha.toLocaleDateString('es-MX', { month: 'short', year: 'numeric' });
    });
    const data = ventasMensuales.map(([, total]) => total);

    chartMeses.data.labels = labels;
    chartMeses.data.datasets[0].data = data;
    chartMeses.update();
}
