# Dashboard administrativo

- `paginas/dashboard.php` presenta los indicadores históricos y los gráficos por periodo; su acceso se limita al permiso `dashboard`.
- `backend/dashboard/api.php` agrega oportunidades por `oportunidades_comerciales.fecha` y órdenes completadas por su primer cambio a completada en `orden_venta_seguimiento`. Las órdenes suman `importe_sin_iva`.
- Los dos gráficos por periodo son de barras, mensuales y comparables en una cuadrícula de dos columnas. El periodo inicial es septiembre de 2026; se puede cambiar la fecha de inicio (sin retroceder de ese mes), la fecha final y el estatus de oportunidad.
- Top 10 clientes presenta nombres en el eje vertical y el tooltip apunta al elemento bajo el cursor. Se retiró la gráfica de clientes y prospectos registrados por mes.
- Los indicadores históricos de ventas netas incluyen CFDI (subtotal) y remisiones (importe sin IVA), conforme a los datos preparados en `dashboardInvoiceData`.
