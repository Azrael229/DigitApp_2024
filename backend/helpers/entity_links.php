<?php

function renderizarEnlaceEntidad(string $tipo, $id, string $etiqueta, string $prefijo = ''): string
{
    $texto = htmlspecialchars($etiqueta !== '' ? $etiqueta : '—', ENT_QUOTES, 'UTF-8');
    $idValido = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($idValido === false) {
        return $texto;
    }

    if ($tipo === 'empresa') {
        $pagina = 'ver_empresa.php';
    } elseif ($tipo === 'contacto') {
        $pagina = 'ver_contacto.php';
    } else {
        return $texto;
    }

    $url = $prefijo . $pagina . '?id=' . rawurlencode((string) $idValido);

    return '<a class="entity-link" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $texto . '</a>';
}
