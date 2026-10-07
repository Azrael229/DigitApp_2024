<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
require_once __DIR__ . '/normalizador_datos.php';

header('Content-Type: application/json; charset=utf-8');

$tipo = (string) ($_GET['tipo'] ?? '');
auth_require_permission($tipo === 'equipo' ? 'productos' : 'clientes');
$db = auth_db();

function duplicadoTexto(?string $valor): string
{
    $texto = mb_strtolower(trim((string) $valor), 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = $ascii === false ? $texto : $ascii;
    return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', $texto));
}

function duplicadoComparable(?string $valor): string
{
    return str_replace(' ', '', duplicadoTexto($valor));
}

function duplicadoIdExcluido(): int
{
    $id = filter_var($_GET['excluir_id'] ?? null, FILTER_VALIDATE_INT);
    return $id === false || $id === null || $id < 1 ? 0 : (int) $id;
}

function duplicadoNombreSimilar(string $entrada, string $existente, float $umbral): bool
{
    if (mb_strlen($entrada) < 4 || mb_strlen($existente) < 4) {
        return false;
    }
    similar_text($entrada, $existente, $porcentaje);
    return $porcentaje >= $umbral;
}

$resultados = [];
$excluirId = duplicadoIdExcluido();

if ($tipo === 'contacto') {
    $nombre = duplicadoTexto($_GET['nombre'] ?? '');
    $telefono = preg_replace('/\D+/', '', (string) ($_GET['telefono'] ?? ''));
    $correo = mb_strtolower(trim((string) ($_GET['correo'] ?? '')), 'UTF-8');
    $consulta = $db->query(
        'SELECT c.id, c.nombre, c.celular, c.correo,
                GROUP_CONCAT(DISTINCT e.empresa ORDER BY ec.es_principal DESC, e.empresa SEPARATOR ", ") AS empresas
         FROM contactos c
         LEFT JOIN empresa_contactos ec ON ec.id_contacto = c.id AND ec.activo = 1
         LEFT JOIN empresas e ON e.id_e = ec.id_empresa
         GROUP BY c.id, c.nombre, c.celular, c.correo
         ORDER BY c.id DESC'
    );
    while ($fila = $consulta->fetch_assoc()) {
        if ((int) $fila['id'] === $excluirId) {
            continue;
        }
        $motivos = [];
        if ($correo !== '' && mb_strtolower(trim((string) $fila['correo']), 'UTF-8') === $correo) {
            $motivos[] = 'correo idéntico';
        }
        if ($telefono !== '' && preg_replace('/\D+/', '', (string) $fila['celular']) === $telefono) {
            $motivos[] = 'teléfono idéntico';
        }
        if ($nombre !== '' && duplicadoNombreSimilar($nombre, duplicadoTexto($fila['nombre']), 86.0)) {
            $motivos[] = 'nombre similar';
        }
        if ($motivos === []) {
            continue;
        }
        $resultados[] = [
            'id' => (int) $fila['id'],
            'titulo' => (string) $fila['nombre'],
            'detalle' => (string) ($fila['empresas'] ?: 'Sin empresa'),
            'motivos' => $motivos,
            'url' => 'ver_contacto.php?id=' . (int) $fila['id'],
        ];
    }
} elseif ($tipo === 'empresa') {
    $nombre = duplicadoTexto($_GET['nombre'] ?? '');
    $razon = duplicadoTexto($_GET['razon_social'] ?? '');
    $rfc = duplicadoComparable($_GET['rfc'] ?? '');
    $telefono = preg_replace('/\D+/', '', (string) ($_GET['telefono'] ?? ''));
    $correo = mb_strtolower(trim((string) ($_GET['correo'] ?? '')), 'UTF-8');
    $consulta = $db->query('SELECT id_e, empresa, razon_social, rfc, telefono_principal, email_principal FROM empresas ORDER BY id_e DESC');
    while ($fila = $consulta->fetch_assoc()) {
        if ((int) $fila['id_e'] === $excluirId) {
            continue;
        }
        $motivos = [];
        if ($rfc !== '' && duplicadoComparable($fila['rfc']) === $rfc) {
            $motivos[] = 'RFC idéntico';
        }
        if ($correo !== '' && mb_strtolower(trim((string) $fila['email_principal']), 'UTF-8') === $correo) {
            $motivos[] = 'correo idéntico';
        }
        if ($telefono !== '' && preg_replace('/\D+/', '', (string) $fila['telefono_principal']) === $telefono) {
            $motivos[] = 'teléfono idéntico';
        }
        $nombreExistente = duplicadoTexto($fila['empresa']);
        $razonExistente = duplicadoTexto($fila['razon_social']);
        if (($nombre !== '' && duplicadoNombreSimilar($nombre, $nombreExistente, 88.0))
            || ($razon !== '' && duplicadoNombreSimilar($razon, $razonExistente, 88.0))) {
            $motivos[] = 'nombre o razón social similar';
        }
        if ($motivos === []) {
            continue;
        }
        $resultados[] = [
            'id' => (int) $fila['id_e'],
            'titulo' => (string) $fila['empresa'],
            'detalle' => (string) ($fila['rfc'] ?: $fila['razon_social'] ?: 'Sin RFC'),
            'motivos' => array_values(array_unique($motivos)),
            'url' => 'ver_empresa.php?id=' . (int) $fila['id_e'],
        ];
    }
} elseif ($tipo === 'equipo') {
    $identificacion = duplicadoComparable($_GET['identificacion'] ?? '');
    $serie = duplicadoComparable($_GET['numero_serie'] ?? '');
    $consulta = $db->query(
        'SELECT eq.id, eq.empresa_id, eq.identificacion, eq.numero_serie, eq.modelo,
                e.empresa, COALESCE(d.nombre, "Equipo") AS descripcion
         FROM empresa_equipos eq
         JOIN empresas e ON e.id_e = eq.empresa_id
         LEFT JOIN catalogo_descripciones_equipo d ON d.id = eq.descripcion_id
         ORDER BY eq.id DESC'
    );
    while ($fila = $consulta->fetch_assoc()) {
        if ((int) $fila['id'] === $excluirId) {
            continue;
        }
        $motivos = [];
        if ($identificacion !== '' && duplicadoComparable($fila['identificacion']) === $identificacion) {
            $motivos[] = 'identificación idéntica';
        }
        if ($serie !== '' && duplicadoComparable($fila['numero_serie']) === $serie) {
            $motivos[] = 'número de serie idéntico';
        }
        if ($motivos === []) {
            continue;
        }
        $resultados[] = [
            'id' => (int) $fila['id'],
            'titulo' => trim((string) $fila['descripcion'] . ' ' . (string) $fila['modelo']),
            'detalle' => (string) $fila['empresa'],
            'motivos' => $motivos,
            'url' => 'form_equipo_empresa.php?empresa_id=' . (int) $fila['empresa_id'] . '&equipo_id=' . (int) $fila['id'],
        ];
    }
} else {
    http_response_code(422);
    echo json_encode(['error' => 'El tipo de comparación no es válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['coincidencias' => array_slice($resultados, 0, 8)], JSON_UNESCAPED_UNICODE);
