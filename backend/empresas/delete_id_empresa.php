<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_login();

// Ruta heredada desactivada: la eliminación exige rol administrador, POST, CSRF y confirmación BORRAR.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo 'Esta acción fue reemplazada por la eliminación administrativa segura.';
