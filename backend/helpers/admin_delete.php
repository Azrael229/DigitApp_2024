<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = auth_require_login();
if (($user['rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'Solo el administrador puede eliminar registros.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'La sesión de seguridad cambió. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (mb_strtoupper(trim((string) ($_POST['confirmacion'] ?? '')), 'UTF-8') !== 'BORRAR') {
    http_response_code(422);
    echo json_encode(['error' => 'Escribe BORRAR para confirmar la eliminación.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$entity = (string) ($_POST['entidad'] ?? '');
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || !in_array($entity, ['empresa', 'contacto', 'direccion', 'equipo', 'cotizacion'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'El registro solicitado no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = auth_db();
$db->set_charset('utf8mb4');

// Devuelve el número de dependencias activas para impedir eliminaciones que rompan el historial.
function adminDeleteCount(mysqli $db, string $sql, int $id): int
{
    $query = $db->prepare($sql);
    $query->bind_param('i', $id);
    $query->execute();
    $total = (int) ($query->get_result()->fetch_assoc()['total'] ?? 0);
    $query->close();
    return $total;
}

// Elimina por ID usando exclusivamente nombres de tabla y llave definidos por el servidor.
function adminDeleteRow(mysqli $db, string $table, string $key, int $id): bool
{
    $query = $db->prepare("DELETE FROM {$table} WHERE {$key} = ?");
    $query->bind_param('i', $id);
    $query->execute();
    $deleted = $query->affected_rows === 1;
    $query->close();
    return $deleted;
}

try {
    $db->begin_transaction();
    if ($entity === 'empresa') {
        $dependencies = [
            'contactos vinculados' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM empresa_contactos WHERE id_empresa = ?', $id),
            'direcciones' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM empresa_direcciones WHERE empresa_id = ?', $id),
            'equipos' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM empresa_equipos WHERE empresa_id = ?', $id),
            'oportunidades' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM oportunidades_comerciales WHERE empresa_id = ?', $id),
            'cotizaciones' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM cotizaciones WHERE empresa_id = ?', $id),
            'órdenes de venta' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_venta WHERE empresa_id = ?', $id),
        ];
        $blocked = array_filter($dependencies);
        if ($blocked) {
            throw new DomainException('No se puede eliminar la empresa porque conserva: ' . implode(', ', array_keys($blocked)) . '.');
        }
        $deleted = adminDeleteRow($db, 'empresas', 'id_e', $id);
    } elseif ($entity === 'contacto') {
        $dependencies = [
            'oportunidades' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM oportunidades_comerciales WHERE contacto_id = ?', $id),
            'cotizaciones' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM cotizaciones WHERE contacto_id = ?', $id),
            'órdenes de venta' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_venta WHERE contacto_id = ?', $id),
            'órdenes de servicio' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_servicio WHERE entrega_contacto_id = ?', $id),
        ];
        $blocked = array_filter($dependencies);
        if ($blocked) {
            throw new DomainException('No se puede eliminar el contacto porque conserva: ' . implode(', ', array_keys($blocked)) . '.');
        }
        $deleteAddresses = $db->prepare('DELETE FROM empresa_direccion_contactos WHERE contacto_id = ?');
        $deleteAddresses->bind_param('i', $id);
        $deleteAddresses->execute();
        $deleteAddresses->close();
        $deleteCompanies = $db->prepare('DELETE FROM empresa_contactos WHERE id_contacto = ?');
        $deleteCompanies->bind_param('i', $id);
        $deleteCompanies->execute();
        $deleteCompanies->close();
        $deleted = adminDeleteRow($db, 'contactos', 'id', $id);
    } elseif ($entity === 'direccion') {
        $companyId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($companyId === false) { throw new InvalidArgumentException('La empresa de la dirección no es válida.'); }
        $dependencies = [
            'equipos' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM empresa_equipos WHERE direccion_id = ?', $id),
            'oportunidades' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM oportunidades_comerciales WHERE direccion_id = ?', $id),
            'cotizaciones' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM cotizaciones WHERE direccion_id = ?', $id),
            'órdenes de venta' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_venta WHERE direccion_id = ?', $id),
            'órdenes de servicio' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_servicio WHERE entrega_direccion_id = ?', $id),
        ];
        $blocked = array_filter($dependencies);
        if ($blocked) {
            throw new DomainException('No se puede eliminar la dirección porque conserva: ' . implode(', ', array_keys($blocked)) . '.');
        }
        $deleteContacts = $db->prepare('DELETE FROM empresa_direccion_contactos WHERE direccion_id = ?');
        $deleteContacts->bind_param('i', $id);
        $deleteContacts->execute();
        $deleteContacts->close();
        $query = $db->prepare('DELETE FROM empresa_direcciones WHERE id = ? AND empresa_id = ?');
        $query->bind_param('ii', $id, $companyId);
        $query->execute();
        $deleted = $query->affected_rows === 1;
        $query->close();
    } elseif ($entity === 'equipo') {
        if (adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM orden_servicio_equipos WHERE empresa_equipo_id = ?', $id) > 0) {
            throw new DomainException('No se puede eliminar el equipo porque ya forma parte de una orden de servicio.');
        }
        $deleted = adminDeleteRow($db, 'empresa_equipos', 'id', $id);
    } else {
        $dependencies = [
            'oportunidades' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM oportunidad_cotizaciones WHERE cotizacion_id = ?', $id),
            'órdenes de venta' => adminDeleteCount($db, 'SELECT COUNT(*) AS total FROM ordenes_venta WHERE cotizacion_id = ?', $id),
        ];
        $blocked = array_filter($dependencies);
        if ($blocked) {
            throw new DomainException('No se puede eliminar la cotización porque conserva: ' . implode(', ', array_keys($blocked)) . '.');
        }
        foreach (['cotizacion_notas', 'cotizacion_partidas'] as $table) {
            $query = $db->prepare("DELETE FROM {$table} WHERE cotizacion_id = ?");
            $query->bind_param('i', $id);
            $query->execute();
            $query->close();
        }
        $deleted = adminDeleteRow($db, 'cotizaciones', 'id_coti', $id);
    }

    if (!$deleted) {
        throw new OutOfBoundsException('El registro ya no existe o no pudo eliminarse.');
    }
    $db->commit();
    auth_log('eliminacion_' . $entity, 'correcto', null, (int) $user['id'], 'ID ' . $id);
    echo json_encode(['ok' => true, 'message' => 'Registro eliminado correctamente.'], JSON_UNESCAPED_UNICODE);
} catch (DomainException | InvalidArgumentException | OutOfBoundsException $error) {
    $db->rollback();
    http_response_code(409);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'No fue posible eliminar el registro porque conserva relaciones activas.'], JSON_UNESCAPED_UNICODE);
}
