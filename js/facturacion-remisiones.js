document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('dialog-remision');
    const select = document.getElementById('orden-remision');
    const detail = document.getElementById('detalle-remision');
    const errorBox = document.getElementById('error-remision');
    const saveButton = document.getElementById('confirmar-remision');
    if (!dialog || !select || !saveButton) return;

    let orders = [];
    const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 });

    // Carga las órdenes completadas disponibles cuando se abre el selector de remisiones.
    async function loadOrders() {
        select.innerHTML = '<option value="">Cargando órdenes…</option>';
        saveButton.disabled = true;
        errorBox.textContent = '';
        try {
            const response = await fetch('../backend/facturacion/remisiones_api.php', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'No se pudieron consultar las órdenes.');
            orders = data.ordenes || [];
            select.innerHTML = '<option value="">Selecciona una orden</option>';
            orders.forEach(function (order) {
                const option = document.createElement('option');
                option.value = order.id;
                option.textContent = `${order.numero_venta} · ${order.cliente} · ${money.format(Number(order.importe_sin_iva))}`;
                select.append(option);
            });
            if (!orders.length) select.innerHTML = '<option value="">No hay órdenes completadas disponibles</option>';
        } catch (error) {
            select.innerHTML = '<option value="">No se pudieron cargar las órdenes</option>';
            errorBox.textContent = error.message;
        }
    }

    document.getElementById('abrir-remision').addEventListener('click', async function () {
        dialog.showModal();
        await loadOrders();
    });

    // Muestra los datos canónicos de la orden y habilita su registro solo tras revisar el importe.
    select.addEventListener('change', function () {
        const order = orders.find(item => String(item.id) === select.value);
        saveButton.disabled = !order;
        errorBox.textContent = '';
        if (!order) {
            detail.classList.add('d-none');
            return;
        }
        detail.innerHTML = `<strong>Revisa antes de agregar</strong><br>Fecha: ${escapeHtml(order.fecha_venta)}<br>Razón social: ${escapeHtml(order.cliente)}<br>Descripción: ${escapeHtml(order.descripcion)}<br>Total: ${money.format(Number(order.importe_sin_iva))}`;
        detail.classList.remove('d-none');
    });

    // Envía únicamente el identificador: el servidor vuelve a validar la orden y toma sus importes.
    saveButton.addEventListener('click', async function () {
        const order = orders.find(item => String(item.id) === select.value);
        if (!order || !window.confirm(`¿Agregar la orden ${order.numero_venta} como remisión por ${money.format(Number(order.importe_sin_iva))}?`)) return;
        saveButton.disabled = true;
        errorBox.textContent = '';
        try {
            const response = await fetch('../backend/facturacion/remisiones_api.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ orden_id: order.id, csrf: window.facturacionRemisionCsrf })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'No se pudo registrar la remisión.');
            window.location.reload();
        } catch (error) {
            errorBox.textContent = error.message;
            saveButton.disabled = false;
        }
    });

    dialog.addEventListener('close', function () {
        detail.classList.add('d-none');
        select.value = '';
        errorBox.textContent = '';
    });
});

// Escapa los textos provenientes de la orden antes de presentarlos en el resumen del diálogo.
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
}
