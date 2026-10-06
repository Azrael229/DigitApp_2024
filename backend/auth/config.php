<?php
declare(strict_types=1);

const DIGITAPP_VERSION = '20261005-usuarios-1';
const AUTH_SESSION_DAYS = 30;
const AUTH_PASSWORD_MIN = 5;
const AUTH_PASSWORD_MAX = 128;
const AUTH_AUDIT_DAYS = 90;
const AUTH_COOKIE = 'digitapp_acceso';
const AUTH_DEVICE_COOKIE = 'digitapp_dispositivo';

const AUTH_ROLE_LABELS = [
    'administrador' => 'Administrador',
    'administrativo' => 'Administrativo',
    'tecnico_administrativo' => 'Técnico-administrativo',
];

const AUTH_ROLE_PERMISSIONS = [
    'administrador' => ['*'],
    'administrativo' => ['clientes', 'comercial', 'operacion', 'informes', 'productos', 'facturacion'],
    'tecnico_administrativo' => ['clientes', 'comercial', 'operacion', 'informes', 'productos'],
];
