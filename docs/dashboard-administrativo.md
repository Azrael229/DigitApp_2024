# Dashboard administrativo

- `paginas/dashboard.php` presenta los indicadores históricos y la actividad de oportunidades; su acceso se limita al permiso `dashboard`.
- `backend/dashboard/api.php` agrupa por día las oportunidades según `created_at` y devuelve el mes calendario anterior y el actual, sin filtros manuales.
- Al final del Dashboard aparecen dos gráficos de barras en una misma fila: cantidad de oportunidades creadas por día durante el mes anterior y el actual. Ambos muestran todos los días calendario, también cuando no hubo registros.
- Debajo de ellos, un gráfico de barras agrupadas compara por mes de creación (últimos 12 meses, incluido el actual) las oportunidades según su estado actual: negociación, ganadas y perdidas/canceladas/rechazadas. El tooltip muestra el total mensual, incluidos otros estados. Usa `created_at` y se actualiza con el evento de sincronización de la aplicación.
- Top 10 clientes presenta nombres en el eje vertical y el tooltip apunta al elemento bajo el cursor. Se retiró la gráfica de clientes y prospectos registrados por mes.
- Los indicadores históricos de ventas netas incluyen CFDI (subtotal) y remisiones (importe sin IVA), conforme a los datos preparados en `dashboardInvoiceData`.
