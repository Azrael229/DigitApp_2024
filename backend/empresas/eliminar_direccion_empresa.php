<?php
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
