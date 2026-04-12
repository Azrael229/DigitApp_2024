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
