const dashboardCharts = {};
const dashboardCurrency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const dashboardNumber = new Intl.NumberFormat('es-MX');

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficasHistoricas();
    establecerPeriodoPredeterminado();
    cargarGraficasDePeriodo();
    document.getElementById('dashboard-aplicar').addEventListener('click', cargarGraficasDePeriodo);
    document.getElementById('dashboard-estatus-oportunidad').addEventListener('change', cargarGraficasDePeriodo);
    document.getElementById('dashboard-agrupacion').addEventListener('change', cargarGraficasDePeriodo);
});

// Construye las gráficas históricas con la base homogénea de ventas netas sin IVA.
function inicializarGraficasHistoricas() {
    const invoices = Array.isArray(window.dashboardInvoiceData) ? window.dashboardInvoiceData : [];
    const topClients = obtenerTopClientesDashboard(invoices);
    const yearlySales = obtenerVentasAnualesDashboard(invoices);
    const topMonths = obtenerTopMesesDashboard(invoices);
    const rankedTopMonths = topMonths.map((item, index) => ({ ...item, label: `${index + 1}. ${item.label}` }));
    const monthlySales = obtenerVentasMensualesDashboard(invoices, 13);

    dashboardCharts.clients = new Chart(document.getElementById('dashboard-top-clientes'), {
        type: 'bar',
        data: { labels: topClients.map(item => item.name), datasets: [{
            label: 'Ventas netas sin IVA', data: topClients.map(item => item.total),
            backgroundColor: '#35c7b3', borderRadius: 6
        }] },
        options: crearOpcionesGrafica({ horizontal: true, currency: true })
    });

    dashboardCharts.yearly = new Chart(document.getElementById('dashboard-ventas-anuales'), {
        type: 'bar',
        data: { labels: yearlySales.map(item => item.year), datasets: [{
            label: 'Ventas netas sin IVA', data: yearlySales.map(item => item.total),
            backgroundColor: '#5b7cfa', borderRadius: 6
        }] },
        options: crearOpcionesGrafica({ currency: true })
    });

    const topMonthsOptions = crearOpcionesGrafica({ horizontal: true, currency: true });
    topMonthsOptions.scales.y.ticks.autoSkip = false;
    topMonthsOptions.scales.y.ticks.maxRotation = 0;
    topMonthsOptions.scales.y.ticks.font = { size: 12 };
    topMonthsOptions.scales.y.ticks.callback = value => rankedTopMonths[value]?.label ?? '';
    dashboardCharts.months = new Chart(document.getElementById('dashboard-top-meses'), {
        type: 'bar',
        data: { labels: rankedTopMonths.map(item => item.label), datasets: [{
            label: 'Ventas netas sin IVA', data: rankedTopMonths.map(item => item.total),
            backgroundColor: '#9c7cf7', borderRadius: 6
        }] },
        options: topMonthsOptions
    });

    dashboardCharts.monthly = new Chart(document.getElementById('dashboard-ventas-mensuales'), {
        type: 'bar',
        data: { labels: monthlySales.map(item => item.label), datasets: [{
            label: 'Ventas netas sin IVA', data: monthlySales.map(item => item.total),
            backgroundColor: monthlySales.map((_, index) => index === monthlySales.length - 1
                ? '#35c7b3' : 'rgba(96, 165, 250, 0.72)'),
            borderRadius: 6
        }] },
        options: crearOpcionesGrafica({ currency: true })
    });

    dashboardCharts.opportunities = new Chart(document.getElementById('dashboard-oportunidades'), {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'Importe de oportunidades', data: [], borderColor: '#35c7b3',
            backgroundColor: 'rgba(53, 199, 179, 0.18)', fill: true, tension: 0.25, pointRadius: 3
        }] },
        options: crearOpcionesGrafica({ currency: true })
    });

    dashboardCharts.orders = new Chart(document.getElementById('dashboard-ordenes'), {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'Importe de órdenes completadas sin IVA', data: [], borderColor: '#60a5fa',
            backgroundColor: 'rgba(96, 165, 250, 0.16)', fill: true, tension: 0.25, pointRadius: 3
        }] },
        options: crearOpcionesGrafica({ currency: true })
    });

    dashboardCharts.registrations = new Chart(document.getElementById('dashboard-clientes-prospectos'), {
        type: 'bar',
        data: { labels: [], datasets: [
            { label: 'Clientes', data: [], backgroundColor: '#35c7b3', borderRadius: 5 },
            { label: 'Prospectos', data: [], backgroundColor: '#fbbf24', borderRadius: 5 }
        ] },
        options: crearOpcionesGrafica({ grouped: true })
    });
}

// Aplica una ventana móvil de doce meses como periodo inicial en los gráficos filtrables.
function establecerPeriodoPredeterminado() {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth() - 11, 1);
    document.getElementById('dashboard-desde').value = dashboardFechaLocal(start);
    document.getElementById('dashboard-hasta').value = dashboardFechaLocal(today);
}

// Devuelve una fecha local en formato ISO sin convertirla a UTC.
function dashboardFechaLocal(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

// Carga en paralelo oportunidades, órdenes terminadas y registros de clientes/prospectos para el periodo.
async function cargarGraficasDePeriodo() {
    const from = document.getElementById('dashboard-desde').value;
    const to = document.getElementById('dashboard-hasta').value;
    const errorBox = document.getElementById('dashboard-error');
    errorBox.classList.add('d-none');

    if (!from || !to || from > to) {
        errorBox.textContent = 'La fecha inicial debe ser anterior o igual a la fecha final.';
        errorBox.classList.remove('d-none');
        return;
    }
    if (document.getElementById('dashboard-agrupacion').value === 'dia'
        && (new Date(`${to}T12:00:00`) - new Date(`${from}T12:00:00`)) / 86400000 > 366) {
        errorBox.textContent = 'Para intervalos mayores de un año, selecciona agrupación mensual o anual.';
        errorBox.classList.remove('d-none');
        return;
    }

    const parameters = new URLSearchParams({
        desde: from,
        hasta: to,
        agrupacion: document.getElementById('dashboard-agrupacion').value,
        estatus_oportunidad: document.getElementById('dashboard-estatus-oportunidad').value
    });
    const button = document.getElementById('dashboard-aplicar');
    button.disabled = true;
    try {
        const response = await fetch(`../backend/dashboard/api.php?${parameters}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'No se pudieron cargar los indicadores.');
        }
        actualizarGraficasDePeriodo(data, from, to);
    } catch (error) {
        errorBox.textContent = error.message || 'No se pudieron cargar los indicadores.';
        errorBox.classList.remove('d-none');
    } finally {
        button.disabled = false;
    }
}

// Convierte las respuestas del servicio en series con periodos compartidos y valores numéricos.
function actualizarGraficasDePeriodo(data, from, to) {
    const opportunities = new Map();
    (data.oportunidades || []).forEach(function (row) {
        opportunities.set(row.periodo, sumarImporteDashboard(opportunities.get(row.periodo) || 0, row.importe));
    });
    const orders = new Map((data.ordenes_venta || []).map(row => [row.periodo, Number(row.importe || 0)]));
    const periods = crearPeriodosDashboard(from, to, document.getElementById('dashboard-agrupacion').value);
    dashboardCharts.opportunities.data.labels = periods.map(formatearPeriodoDashboard);
    dashboardCharts.opportunities.data.datasets[0].data = periods.map(period => opportunities.get(period) || 0);
    dashboardCharts.orders.data.labels = periods.map(formatearPeriodoDashboard);
    dashboardCharts.orders.data.datasets[0].data = periods.map(period => orders.get(period) || 0);

    const registrations = new Map((data.registros_empresa || []).map(row => [row.periodo, {
        clients: Number(row.clientes || 0), prospects: Number(row.prospectos || 0)
    }]));
    const months = crearPeriodosDashboard(from, to, 'mes');
    dashboardCharts.registrations.data.labels = months.map(formatearPeriodoDashboard);
    dashboardCharts.registrations.data.datasets[0].data = months.map(period => registrations.get(period)?.clients || 0);
    dashboardCharts.registrations.data.datasets[1].data = months.map(period => registrations.get(period)?.prospects || 0);

    dashboardCharts.opportunities.update();
    dashboardCharts.orders.update();
    dashboardCharts.registrations.update();
}

// Genera cada día, mes o año del periodo para conservar ceros y una escala comparable.
function crearPeriodosDashboard(from, to, grouping) {
    const periods = [];
    const start = new Date(`${from}T12:00:00`);
    const end = new Date(`${to}T12:00:00`);
    if (grouping === 'anio') {
        for (let year = start.getFullYear(); year <= end.getFullYear(); year++) periods.push(String(year));
    } else if (grouping === 'mes') {
        const cursor = new Date(start.getFullYear(), start.getMonth(), 1);
        const last = new Date(end.getFullYear(), end.getMonth(), 1);
        while (cursor <= last) {
            periods.push(`${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`);
            cursor.setMonth(cursor.getMonth() + 1);
        }
    } else {
        const cursor = new Date(start);
        while (cursor <= end) {
            periods.push(dashboardFechaLocal(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }
    }
    return periods;
}

// Presenta claves de periodo con nombres de mes legibles para el usuario.
function formatearPeriodoDashboard(period) {
    if (/^\d{4}-\d{2}$/.test(period)) {
        const [year, month] = period.split('-').map(Number);
        return new Intl.DateTimeFormat('es-MX', { month: 'short', year: 'numeric' })
            .format(new Date(year, month - 1, 1));
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(period)) {
        return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: '2-digit' })
            .format(new Date(`${period}T12:00:00`));
    }
    return period;
}

// Consolida clientes por RFC y recurre al nombre normalizado cuando el XML no tiene RFC.
function obtenerTopClientesDashboard(invoices) {
    const clients = new Map();
    invoices.forEach(function (invoice) {
        const name = String(invoice.cliente || 'Sin nombre').trim() || 'Sin nombre';
        const rfc = String(invoice.rfc || '').trim().toUpperCase();
        const normalizedName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        const key = rfc || normalizedName || name;
        const current = clients.get(key) || { name, total: 0 };
        current.total = sumarImporteDashboard(current.total, invoice.importe_neto);
        clients.set(key, current);
    });
    return Array.from(clients.values()).sort((a, b) => b.total - a.total).slice(0, 10);
}

// Suma las ventas netas sin IVA por año calendario y ordena cronológicamente.
function obtenerVentasAnualesDashboard(invoices) {
    const totals = new Map();
    invoices.forEach(function (invoice) {
        const year = String(invoice.fecha || '').slice(0, 4);
        if (/^\d{4}$/.test(year)) totals.set(year, sumarImporteDashboard(totals.get(year) || 0, invoice.importe_neto));
    });
    return Array.from(totals, ([year, total]) => ({ year, total })).sort((a, b) => a.year.localeCompare(b.year));
}

// Calcula los diez periodos mes-año con mayores ventas netas sin IVA.
function obtenerTopMesesDashboard(invoices) {
    const totals = new Map();
    invoices.forEach(function (invoice) {
        const month = String(invoice.fecha || '').slice(0, 7);
        if (/^\d{4}-\d{2}$/.test(month)) totals.set(month, sumarImporteDashboard(totals.get(month) || 0, invoice.importe_neto));
    });
    return Array.from(totals, ([period, total]) => ({ label: formatearMesCompletoDashboard(period), total }))
        .sort((a, b) => b.total - a.total).slice(0, 10);
}

// Agrupa ventas netas y devuelve los últimos trece meses, incluso los que no tuvieron ventas.
function obtenerVentasMensualesDashboard(invoices, months = 13) {
    const totals = new Map();
    const today = new Date();
    for (let offset = months - 1; offset >= 0; offset--) {
        const date = new Date(today.getFullYear(), today.getMonth() - offset, 1);
        const period = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        totals.set(period, 0);
    }
    invoices.forEach(function (invoice) {
        const period = String(invoice.fecha || '').slice(0, 7);
        if (totals.has(period)) totals.set(period, sumarImporteDashboard(totals.get(period), invoice.importe_neto));
    });
    return Array.from(totals, ([period, total]) => ({ label: formatearPeriodoDashboard(period), total }));
}

// Escribe el mes completo y el año para identificar con claridad cada puesto del Top 10.
function formatearMesCompletoDashboard(period) {
    const [year, month] = period.split('-').map(Number);
    const monthName = new Intl.DateTimeFormat('es-MX', { month: 'long' }).format(new Date(year, month - 1, 1));
    return `${monthName.charAt(0).toLocaleUpperCase('es-MX')}${monthName.slice(1)} ${year}`;
}

// Mantiene sumas monetarias con dos decimales durante la agregación del lado cliente.
function sumarImporteDashboard(actual, adicional) {
    return (Math.round((Number(actual) || 0) * 100) + Math.round((Number(adicional) || 0) * 100)) / 100;
}

// Establece opciones compartidas de moneda, ejes y leyenda para los gráficos administrativos.
function crearOpcionesGrafica({ horizontal = false, currency = false, grouped = false } = {}) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 18, right: 28, bottom: 40, left: 28 } },
        indexAxis: horizontal ? 'y' : 'x',
        interaction: { intersect: false, mode: 'index' },
        scales: {
            x: { stacked: false, ticks: { color: '#e2e8f0', padding: 14, maxRotation: 45 } },
            y: { beginAtZero: true, stacked: false, ticks: {
                color: '#e2e8f0', padding: 8, callback: value => currency ? dashboardCurrency.format(Number(value)) : dashboardNumber.format(Number(value))
            } }
        },
        plugins: {
            legend: { display: grouped, labels: { color: '#e2e8f0' } },
            tooltip: { callbacks: { label: context => `${context.dataset.label}: ${currency
                ? dashboardCurrency.format(Number(context.raw) || 0) : dashboardNumber.format(Number(context.raw) || 0)}` } }
        }
    };
    if (horizontal) {
        options.scales.x.ticks.callback = value => currency ? dashboardCurrency.format(Number(value)) : dashboardNumber.format(Number(value));
        options.scales.y.ticks.callback = undefined;
    }
    return options;
}
