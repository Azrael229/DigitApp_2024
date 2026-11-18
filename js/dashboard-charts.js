const dashboardCharts = {};
const dashboardCurrency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 });
const dashboardNumber = new Intl.NumberFormat('es-MX');

document.addEventListener('DOMContentLoaded', function () {
    inicializarGraficasHistoricas();
    inicializarGraficasDiariasOportunidades();
    inicializarGraficaMensualOportunidades();
    cargarGraficasDiariasOportunidades();
    window.DigitAppMasterSync?.subscribe(() => {
        cargarGraficasDiariasOportunidades();
        actualizarGraficasHistoricasDesdeServidor().catch(error => {
            document.getElementById('dashboard-error').textContent = error.message;
            document.getElementById('dashboard-error').classList.remove('d-none');
        });
    });
});

// Construye las gráficas históricas con la base homogénea de ventas netas sin IVA.
function inicializarGraficasHistoricas() {
    const invoices = Array.isArray(window.dashboardInvoiceData) ? window.dashboardInvoiceData : [];
    const topClients = obtenerTopClientesDashboard(invoices);
    const yearlySales = obtenerVentasAnualesDashboard(invoices);
    const topMonths = obtenerTopMesesDashboard(invoices);
    const rankedTopMonths = topMonths.map((item, index) => ({ ...item, label: `${index + 1}. ${item.label}` }));
    const monthlySales = obtenerVentasMensualesDashboard(invoices, 13);
    const topClientsOptions = crearOpcionesGrafica({ horizontal: true, currency: true });
    topClientsOptions.scales.y.ticks.autoSkip = false;
    topClientsOptions.scales.y.ticks.callback = value => dashboardCharts.clients?.data.labels[value] ?? '';

    dashboardCharts.clients = new Chart(document.getElementById('dashboard-top-clientes'), {
        type: 'bar',
        data: { labels: topClients.map(item => item.name), datasets: [{
            label: 'Ventas netas sin IVA', data: topClients.map(item => item.total),
            backgroundColor: '#35c7b3', borderRadius: 6
        }] },
        options: topClientsOptions
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
    topMonthsOptions.scales.y.ticks.callback = value => dashboardCharts.months?.data.labels[value] ?? '';
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
}

// Prepara las gráficas de conteo diario para el mes anterior y el mes actual.
function inicializarGraficasDiariasOportunidades() {
    dashboardCharts.opportunitiesPrevious = new Chart(document.getElementById('dashboard-oportunidades-mes-anterior'), {
        type: 'bar',
        data: { labels: [], datasets: [{
            label: 'Oportunidades creadas', data: [], backgroundColor: '#60a5fa', borderRadius: 6
        }] },
        options: crearOpcionesConteoDiarioDashboard()
    });

    dashboardCharts.opportunitiesCurrent = new Chart(document.getElementById('dashboard-oportunidades-mes-actual'), {
        type: 'bar',
        data: { labels: [], datasets: [{
            label: 'Oportunidades creadas', data: [], backgroundColor: '#35c7b3', borderRadius: 6
        }] },
        options: crearOpcionesConteoDiarioDashboard()
    });
}

// Mantiene visibles todos los días del mes y ajusta los números al ancho del gráfico.
function crearOpcionesConteoDiarioDashboard() {
    const options = crearOpcionesGrafica();
    options.scales.x.ticks.autoSkip = false;
    options.scales.x.ticks.maxRotation = 90;
    options.scales.x.ticks.minRotation = 0;
    options.scales.x.ticks.font = context => ({ size: context.chart.width < 360 ? 8 : 11 });
    options.layout.padding.bottom = 54;
    return options;
}

// Prepara las barras mensuales agrupadas por estado comercial.
function inicializarGraficaMensualOportunidades() {
    const options = crearOpcionesGrafica({ grouped: true });
    options.scales.x.ticks.autoSkip = false;
    options.scales.x.ticks.maxRotation = 45;
    options.scales.x.ticks.minRotation = 0;
    options.plugins.tooltip.callbacks.footer = items => {
        if (!items.length) { return ''; }
        const chart = items[0].chart;
        const total = chart.$dashboardMonthTotals?.[items[0].dataIndex] || 0;
        return `Total de oportunidades del mes: ${dashboardNumber.format(total)}`;
    };
    dashboardCharts.opportunitiesMonthly = new Chart(document.getElementById('dashboard-oportunidades-por-mes'), {
        type: 'bar',
        data: { labels: [], datasets: [
            { label: 'En negociación', data: [], backgroundColor: '#60a5fa', borderRadius: 4 },
            { label: 'Ganadas', data: [], backgroundColor: '#35c7b3', borderRadius: 4 },
            { label: 'Canceladas / rechazadas', data: [], backgroundColor: '#ef4444', borderRadius: 4 }
        ] },
        options
    });
}

// Actualiza los gráficos históricos desde las ventas y nombres maestros que devuelve la página.
async function actualizarGraficasHistoricasDesdeServidor() {
    const response = await fetch(location.href, {cache: 'no-store', credentials: 'same-origin'});
    if (!response.ok) { throw new Error('No se pudieron actualizar las ventas históricas.'); }
    const documentActualizado = new DOMParser().parseFromString(await response.text(), 'text/html');
    const script = Array.from(documentActualizado.scripts).find(item => item.textContent.includes('window.dashboardInvoiceData'));
    const assignment = script?.textContent.match(/window\.dashboardInvoiceData\s*=\s*(\[[\s\S]*\]);?/);
    if (!assignment) { return; }
    window.dashboardInvoiceData = JSON.parse(assignment[1]);
    const invoices = window.dashboardInvoiceData;
    const clients = obtenerTopClientesDashboard(invoices);
    dashboardCharts.clients.data.labels = clients.map(item => item.name);
    dashboardCharts.clients.data.datasets[0].data = clients.map(item => item.total);
    dashboardCharts.clients.update();
    const years = obtenerVentasAnualesDashboard(invoices);
    dashboardCharts.yearly.data.labels = years.map(item => item.year);
    dashboardCharts.yearly.data.datasets[0].data = years.map(item => item.total);
    dashboardCharts.yearly.update();
    const months = obtenerTopMesesDashboard(invoices).map((item, index) => ({...item, label: `${index + 1}. ${item.label}`}));
    dashboardCharts.months.data.labels = months.map(item => item.label);
    dashboardCharts.months.data.datasets[0].data = months.map(item => item.total);
    dashboardCharts.months.update();
    const monthly = obtenerVentasMensualesDashboard(invoices, 13);
    dashboardCharts.monthly.data.labels = monthly.map(item => item.label);
    dashboardCharts.monthly.data.datasets[0].data = monthly.map(item => item.total);
    dashboardCharts.monthly.update();
}

// Carga los conteos de created_at para los dos meses fijos que devuelve el servidor.
async function cargarGraficasDiariasOportunidades() {
    const errorBox = document.getElementById('dashboard-error');
    errorBox.classList.add('d-none');
    try {
        const response = await fetch('../backend/dashboard/api.php', { cache: 'no-store', headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'No se pudieron cargar los indicadores.');
        }
        actualizarGraficasDiariasOportunidades(data);
        actualizarGraficaMensualOportunidades(data);
    } catch (error) {
        errorBox.textContent = error.message || 'No se pudieron cargar los indicadores.';
        errorBox.classList.remove('d-none');
    }
}

// Completa con ceros los días sin registros y actualiza los nombres de mes de cada gráfica.
function actualizarGraficasDiariasOportunidades(data) {
    const dailyCounts = new Map((data.oportunidades_por_dia || []).map(row => [row.dia, Number(row.cantidad) || 0]));
    [
        { key: 'mes_anterior', chart: dashboardCharts.opportunitiesPrevious, title: 'dashboard-oportunidades-mes-anterior-titulo', prefix: 'Mes anterior' },
        { key: 'mes_actual', chart: dashboardCharts.opportunitiesCurrent, title: 'dashboard-oportunidades-mes-actual-titulo', prefix: 'Mes actual' }
    ].forEach(({ key, chart, title, prefix }) => {
        const month = data[key];
        const [year, monthNumber] = month.split('-').map(Number);
        const daysInMonth = new Date(year, monthNumber, 0).getDate();
        const labels = Array.from({ length: daysInMonth }, (_, index) => String(index + 1));
        chart.data.labels = labels;
        chart.data.datasets[0].data = labels.map((_, index) => dailyCounts.get(`${month}-${String(index + 1).padStart(2, '0')}`) || 0);
        document.getElementById(title).textContent = `Oportunidades creadas · ${prefix} (${formatearMesCompletoDashboard(month)})`;
        chart.update();
    });
}

// Actualiza las tres series y rellena los meses sin registros con cero.
function actualizarGraficaMensualOportunidades(data) {
    const monthlyCounts = new Map();
    const currentMonth = data.mes_actual;
    const [year, monthNumber] = currentMonth.split('-').map(Number);
    const months = Array.from({ length: 12 }, (_, index) => {
        const date = new Date(year, monthNumber - 1 - (11 - index), 1);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        monthlyCounts.set(key, { negotiation: 0, won: 0, closed: 0, total: 0 });
        return key;
    });

    (data.oportunidades_por_mes || []).forEach(row => {
        const totals = monthlyCounts.get(row.mes);
        if (!totals) { return; }
        const count = Number(row.cantidad) || 0;
        totals.total += count;
        if (row.estatus === 'negociacion') { totals.negotiation += count; }
        if (row.estatus === 'ganada') { totals.won += count; }
        if (['perdida', 'cancelada', 'rechazada'].includes(row.estatus)) { totals.closed += count; }
    });

    const chart = dashboardCharts.opportunitiesMonthly;
    chart.data.labels = months.map(formatearPeriodoDashboard);
    chart.$dashboardMonthTotals = months.map(month => monthlyCounts.get(month).total);
    chart.data.datasets[0].data = months.map(month => monthlyCounts.get(month).negotiation);
    chart.data.datasets[1].data = months.map(month => monthlyCounts.get(month).won);
    chart.data.datasets[2].data = months.map(month => monthlyCounts.get(month).closed);
    chart.update();
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
        interaction: { intersect: true, mode: 'nearest', axis: horizontal ? 'xy' : 'x' },
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
