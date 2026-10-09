<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
$user = auth_require_permission('clientes');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$companyId = filter_var($_POST['empresa_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$contactId = filter_var($_POST['contacto_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($companyId === false || $contactId === false) {
    http_response_code(422);
    echo json_encode(['error' => 'Selecciona una empresa y un contacto válidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = auth_db();
try {
    $db->begin_transaction();
    $company = $db->prepare('SELECT id_e FROM empresas WHERE id_e = ? FOR UPDATE');
    $company->bind_param('i', $companyId);
    $company->execute();
    $companyExists = $company->get_result()->fetch_assoc() !== null;
    $company->close();
    $contact = $db->prepare('SELECT id FROM contactos WHERE id = ? AND activo = 1 FOR UPDATE');
    $contact->bind_param('i', $contactId);
    $contact->execute();
    $contactExists = $contact->get_result()->fetch_assoc() !== null;
    $contact->close();
    if (!$companyExists || !$contactExists) {
        throw new InvalidArgumentException('La empresa o el contacto ya no está disponible.');
    }

    $principalQuery = $db->prepare('SELECT COUNT(*) AS total FROM empresa_contactos WHERE id_contacto = ? AND activo = 1');
    $principalQuery->bind_param('i', $contactId);
    $principalQuery->execute();
    $isPrincipal = (int) $principalQuery->get_result()->fetch_assoc()['total'] === 0 ? 1 : 0;
    $principalQuery->close();

    $save = $db->prepare('INSERT INTO empresa_contactos
        (id_empresa, id_contacto, activo, es_principal, fecha_creacion, fecha_actualizacion)
        VALUES (?, ?, 1, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE activo = 1, fecha_actualizacion = NOW()');
    $save->bind_param('iii', $companyId, $contactId, $isPrincipal);
    $save->execute();
    $save->close();
    $db->commit();
    auth_log('contacto_vinculado_empresa', 'correcto', null, (int) $user['id'], 'Empresa ' . $companyId . ', contacto ' . $contactId);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $error) {
    $db->rollback();
    http_response_code(422);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $db->rollback();
    http_response_code(409);
    echo json_encode(['error' => 'No fue posible vincular el contacto.'], JSON_UNESCAPED_UNICODE);
}
