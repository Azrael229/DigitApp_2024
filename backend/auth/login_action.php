<?php
declare(strict_types=1);

require_once __DIR__ . '/service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !auth_verify_csrf($_POST['csrf'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}

try {
    $user = auth_attempt_login((string) ($_POST['correo'] ?? ''), (string) ($_POST['password'] ?? ''), $_POST['dispositivo'] ?? null);
    if (!empty($user['debe_cambiar_password'])) {
        header('Location: ../../paginas/mi_cuenta.php?cambio=obligatorio');
    } else {
        header('Location: ../../index.php');
    }
    exit;
} catch (Throwable $error) {
    $_SESSION['login_error'] = $error instanceof RuntimeException || $error instanceof InvalidArgumentException
        ? $error->getMessage() : 'No fue posible iniciar sesión.';
    header('Location: ../../login.php');
    exit;
}

