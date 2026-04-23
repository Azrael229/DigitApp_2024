function eliminar(id) {
    if (!id) {
        return;
    }

    const confirmar = window.confirm('Se eliminara la cotizacion seleccionada. Desea continuar?');

    if (!confirmar) {
        return;
    }

    window.location.href = `querys/delete_id_cotizacion.php?id_coti=${encodeURIComponent(id)}`;
}



document.addEventListener('click', async function(e) {
    if (!e.target.classList.contains('btnGenerarOS')) return;

    const idCoti = e.target.dataset.idCoti;

    try {
        const response = await fetch('querys/add_orden_servicio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id_coti=${encodeURIComponent(idCoti)}`
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = `Render_OS.php?id_os=${data.id_os}`;
        } else {
            alert(data.message || 'No se pudo generar la orden de servicio');
        }

    } catch (error) {
        console.error(error);
        alert('Error de comunicación con el servidor');
    }
});