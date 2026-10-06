<?php
declare(strict_types=1);

require_once __DIR__ . '/service.php';

function auth_mail_settings(): ?array
{
    $path = __DIR__ . '/../../config/correo.php';
    if (!is_file($path)) {
        return null;
    }
    require $path;
    if (empty($digitappCorreoRemitente) || !filter_var($digitappCorreoRemitente, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return [(string) $digitappCorreoRemitente, (string) ($digitappCorreoNombre ?? 'DigitApp 2024')];
}

function auth_absolute_url(string $path): string
{
    $scheme = auth_is_https() ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $root = preg_replace('#/backend/auth/[^/]+$#', '', $script) ?: '';
    return $scheme . '://' . $host . $root . '/' . ltrim($path, '/');
}

function auth_send_mail(string $to, string $subject, string $body): bool
{
    $settings = auth_mail_settings();
    if (!$settings) {
        return false;
    }
    [$from, $name] = $settings;
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $name . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

