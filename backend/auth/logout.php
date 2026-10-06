<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
auth_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}
auth_logout(false);
header('Location: ../../login.php');
exit;

