<?php
$xmlDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'global_xml';
$facturas = [];

function toStringValue(mixed $value): string
{
    return isset($value) ? trim((string) $value) : '';
}

function findFirstChildByLocalName(\SimpleXMLElement $element, string $localName): ?\SimpleXMLElement
{
    foreach ($element->children() as $child) {
        if ($child->getName() === $localName) {
            return $child;
        }
    }

    foreach ($element->getNamespaces(true) as $namespace) {
        foreach ($element->children($namespace) as $child) {
            if ($child->getName() === $localName) {
                return $child;
            }
        }
    }

    return null;
}

function findFirstNodeByLocalName(\SimpleXMLElement $element, string $localName): ?\SimpleXMLElement
{
    $result = $element->xpath('//*[local-name()="' . $localName . '"][1]');

    if (is_array($result) && isset($result[0]) && $result[0] instanceof SimpleXMLElement) {
        return $result[0];
    }

    return null;
}

function findChildrenByLocalName(\SimpleXMLElement $element, string $localName): array
{
    $results = [];

    foreach ($element->children() as $child) {
        if ($child->getName() === $localName) {
            $results[] = $child;
        }
    }

    foreach ($element->getNamespaces(true) as $namespace) {
        foreach ($element->children($namespace) as $child) {
            if ($child->getName() === $localName) {
                $results[] = $child;
            }
        }
    }

    return $results;
}

function formatAmount(string $amount): string
{
    if ($amount === '' || !is_numeric($amount)) {
        return $amount;
    }

    return number_format((float) $amount, 2, '.', ',');
}

function formatCfdiDate(string $date): string
{
    if ($date === '') {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d/m/Y', $timestamp);
}

if (is_dir($xmlDirectory)) {
    $files = glob($xmlDirectory . DIRECTORY_SEPARATOR . '*.xml');

    if ($files !== false) {
        foreach ($files as $filePath) {
            if (!is_readable($filePath)) {
                continue;
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($filePath);

            if ($xml === false) {
                libxml_clear_errors();
                continue;
            }

            libxml_clear_errors();
            $comprobante = $xml;
            $receptor = findFirstNodeByLocalName($comprobante, 'Receptor');
            $conceptos = findFirstNodeByLocalName($comprobante, 'Conceptos');
            $impuestos = findFirstNodeByLocalName($comprobante, 'Impuestos');

            $concepto = null;
            if ($conceptos instanceof SimpleXMLElement) {
                $concepto = findFirstChildByLocalName($conceptos, 'Concepto');
            }

            $iva = '';
            if ($impuestos instanceof SimpleXMLElement) {
                $trasladosContainer = findFirstChildByLocalName($impuestos, 'Traslados');
                $traslados = [];

                if ($trasladosContainer instanceof SimpleXMLElement) {
                    $traslados = findChildrenByLocalName($trasladosContainer, 'Traslado');
                } else {
                    $traslados = findChildrenByLocalName($impuestos, 'Traslado');
                }

                $ivaTotal = 0.0;
                $foundIva = false;

                foreach ($traslados as $traslado) {
                    $attributes = $traslado->attributes();
                    $impuestoCode = toStringValue($attributes['Impuesto'] ?? '');
                    $importe = toStringValue($attributes['Importe'] ?? '');

                    if ($impuestoCode === '002' && is_numeric($importe)) {
                        $ivaTotal += (float) $importe;
                        $foundIva = true;
                    }
                }

                if ($foundIva) {
                    $iva = (string) $ivaTotal;
                }
            }

            $facturas[] = [
                'archivo' => basename($filePath),
                'fecha' => toStringValue($comprobante->attributes()['Fecha'] ?? ''),
                'cliente' => $receptor ? toStringValue($receptor->attributes()['Nombre'] ?? '') : '',
                'rfc' => $receptor ? toStringValue($receptor->attributes()['Rfc'] ?? '') : '',
                'descripcion' => $concepto ? toStringValue($concepto->attributes()['Descripcion'] ?? '') : '',
                'subtotal' => toStringValue($comprobante->attributes()['SubTotal'] ?? ''),
                'iva' => $iva,
                'total' => toStringValue($comprobante->attributes()['Total'] ?? ''),
            ];
        }
    }
}

usort($facturas, static function (array $a, array $b): int {
    $dateA = strtotime($a['fecha'] ?? '') ?: 0;
    $dateB = strtotime($b['fecha'] ?? '') ?: 0;

    return $dateB <=> $dateA;
});
?>
