document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('chartMeses');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const ventas = Array.isArray(window.facturacionDatosVentas) ? window.facturacionDatosVentas : [];
    const mensual = obtenerVentasMensuales(ventas, 13);
    window.facturacionChartVentas = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: mensual.map(([periodo]) => formatearMesFacturacion(periodo)),
            datasets: [{
                label: 'Ventas netas sin IVA',
                data: mensual.map(([, importe]) => importe),
                backgroundColor: mensual.map((_, index) => index === mensual.length - 1
                    ? 'rgba(228, 0, 124, 0.78)' : 'rgba(32, 141, 151, 0.78)'),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 18, right: 24, bottom: 18, left: 24 } },
            scales: {
                y: { beginAtZero: true, ticks: { padding: 8, callback: formatoMonedaMXN } },
                x: { ticks: { padding: 8, maxRotation: 45, minRotation: 0 } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: context => formatoMonedaMXN(context.raw) } }
            }
        }
    });
});

// Actualiza la gráfica mensual cuando otra pestaña registra o cambia una venta.
window.addEventListener('digitapp:facturacion-data-refreshed', function () {
    if (!window.facturacionChartVentas) { return; }
    const monthly = obtenerVentasMensuales(window.facturacionDatosVentas || [], 13);
    window.facturacionChartVentas.data.labels = monthly.map(([period]) => formatearMesFacturacion(period));
    window.facturacionChartVentas.data.datasets[0].data = monthly.map(([, amount]) => amount);
    window.facturacionChartVentas.update();
});

// Muestra el periodo como mes abreviado y año para facilitar la comparación interanual.
function formatearMesFacturacion(periodo) {
    const [year, month] = periodo.split('-').map(Number);
    return new Intl.DateTimeFormat('es-MX', { month: 'short', year: 'numeric' })
        .format(new Date(year, month - 1, 1));
}

// Agrupa ventas netas (subtotal CFDI más remisiones) en los últimos N meses, incluido el actual.
function obtenerVentasMensuales(facturas, meses = 13) {
    const ventasPorMes = new Map();
    const fechaActual = new Date();
    for (let offset = meses - 1; offset >= 0; offset--) {
        const fechaMes = new Date(fechaActual.getFullYear(), fechaActual.getMonth() - offset, 1);
        const clave = `${fechaMes.getFullYear()}-${String(fechaMes.getMonth() + 1).padStart(2, '0')}`;
        ventasPorMes.set(clave, 0);
    }

    facturas.forEach(function (factura) {
        const fecha = String(factura.fecha || '').slice(0, 7);
        if (ventasPorMes.has(fecha)) {
            ventasPorMes.set(fecha, sumarImporteFacturacion(ventasPorMes.get(fecha), factura.importe_neto));
        }
    });
    return Array.from(ventasPorMes.entries());
}

// Acumula importes en centavos para evitar errores de redondeo binario.
function sumarImporteFacturacion(actual, adicional) {
    return (Math.round((Number(actual) || 0) * 100) + Math.round((Number(adicional) || 0) * 100)) / 100;
}

// Formatea importes de los gráficos en pesos mexicanos.
function formatoMonedaMXN(valor) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency', currency: 'MXN', minimumFractionDigits: 2, maximumFractionDigits: 2
    }).format(Number(valor) || 0);
}
