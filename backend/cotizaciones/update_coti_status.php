<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode([
    'error' => 'El estatus de la cotización solo puede actualizarse desde Editar cotización.',
], JSON_UNESCAPED_UNICODE);
