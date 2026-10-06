<?php
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('clientes');
require(__DIR__ . "/../../config/conexion.php");

function redirigirEmpresaTrasEliminar(int $empresaId, string $parametro): void
{
    header('Location: ../../paginas/ver_empresa.php?id=' . $empresaId . '&' . $parametro);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$empresaId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT);
$direccionId = filter_var($_POST['direccion_id'] ?? null, FILTER_VALIDATE_INT);

if ($empresaId === false || $empresaId === null || $direccionId === false || $direccionId === null) {
    header('Location: ../../paginas/empresas.php');
    exit;
}

$consultaEliminar = $conexion->prepare('DELETE FROM empresa_direcciones WHERE id = ? AND empresa_id = ?');
$consultaEliminar->bind_param('ii', $direccionId, $empresaId);

try {
    $consultaEquipos = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM empresa_equipos WHERE direccion_id = ? AND empresa_id = ?'
    );
    $consultaEquipos->bind_param('ii', $direccionId, $empresaId);
    $consultaEquipos->execute();
    $totalEquipos = (int) $consultaEquipos->get_result()->fetch_assoc()['total'];
    $consultaEquipos->close();

    if ($totalEquipos > 0) {
        $consultaEliminar->close();
        mysqli_close($conexion);
        redirigirEmpresaTrasEliminar(
            $empresaId,
            'error=No%20se%20puede%20eliminar%20una%20dirección%20con%20equipos%20asociados'
        );
    }

    $consultaEliminar->execute();
    $eliminada = $consultaEliminar->affected_rows === 1;
    $consultaEliminar->close();
    mysqli_close($conexion);
    redirigirEmpresaTrasEliminar($empresaId, $eliminada ? 'direccion_eliminada=1' : 'error=Dirección%20no%20encontrada');
} catch (Throwable $error) {
    $consultaEliminar->close();
    mysqli_close($conexion);
    redirigirEmpresaTrasEliminar($empresaId, 'error=No%20fue%20posible%20eliminar%20la%20dirección');
}
?>
