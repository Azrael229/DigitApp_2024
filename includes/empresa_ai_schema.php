<?php
declare(strict_types=1);

function obtenerSchemaEmpresaAI(): array
{
    return [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => [
            'tipo_documento_detectado',
            'confianza_global',
            'empresa',
            'direccion_fiscal',
            'contacto',
            'clasificacion_sugerida',
            'campos_detectados',
            'campos_no_detectados',
            'advertencias',
        ],
        'properties' => [
            'tipo_documento_detectado' => [
                'type' => 'string',
                'description' => 'Tipo de documento detectado a partir del archivo, imagen o texto.',
                'enum' => [
                    'constancia_fiscal',
                    'factura',
                    'cfdi_xml',
                    'cotizacion',
                    'correo',
                    'texto_libre',
                    'imagen',
                    'documento_empresa',
                    'desconocido',
                ],
            ],
            'confianza_global' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
                'description' => 'Nivel estimado de confianza de la extraccion de datos. 0 significa sin confianza y 100 significa muy alta confianza.',
            ],
            'empresa' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'rfc',
                    'razon_social',
                    'nombre_comercial_sugerido',
                    'regimen_fiscal',
                    'regimen_capital_detectado',
                    'regimen_capital',
                    'estatus_fiscal',
                    'fecha_inicio_operaciones',
                ],
                'properties' => [
                    'rfc' => [
                        'type' => 'string',
                        'description' => 'RFC mexicano detectado. No modificar ni inventar.',
                    ],
                    'razon_social' => [
                        'type' => 'string',
                        'description' => 'Razon social, denominacion o nombre completo detectado.',
                    ],
                    'nombre_comercial_sugerido' => [
                        'type' => 'string',
                        'description' => 'Nombre comercial sugerido si puede inferirse claramente; si no, cadena vacia.',
                    ],
                    'regimen_fiscal' => [
                        'type' => 'string',
                        'description' => 'Regimen fiscal detectado. Si aparece clave y descripcion, incluir ambas.',
                    ],
                    'regimen_capital_detectado' => [
                        'type' => 'string',
                        'description' => 'Texto original del regimen capital detectado en el documento, imagen o texto. Ejemplo: SOCIEDAD ANONIMA DE CAPITAL VARIABLE.',
                    ],
                    'regimen_capital' => [
                        'type' => 'string',
                        'description' => 'Version abreviada y normalizada del regimen capital cuando sea posible. Ejemplo: S.A. de C.V. Si no es posible normalizar con seguridad, usar el texto detectado y agregar advertencia.',
                    ],
                    'estatus_fiscal' => [
                        'type' => 'string',
                        'description' => 'Estatus fiscal si aparece explicitamente, por ejemplo ACTIVO.',
                    ],
                    'fecha_inicio_operaciones' => [
                        'type' => 'string',
                        'description' => 'Fecha de inicio de operaciones si aparece explicitamente.',
                    ],
                ],
            ],
            'direccion_fiscal' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'codigo_postal',
                    'calle',
                    'numero_exterior',
                    'numero_interior',
                    'colonia',
                    'localidad',
                    'municipio',
                    'estado',
                    'pais',
                ],
                'properties' => [
                    'codigo_postal' => [
                        'type' => 'string',
                        'description' => 'Codigo postal detectado exactamente como aparece en el documento.',
                    ],
                    'calle' => [
                        'type' => 'string',
                        'description' => 'Calle fiscal solo si aparece explicitamente.',
                    ],
                    'numero_exterior' => [
                        'type' => 'string',
                        'description' => 'Numero exterior solo si aparece explicitamente.',
                    ],
                    'numero_interior' => [
                        'type' => 'string',
                        'description' => 'Numero interior solo si aparece explicitamente. No inventar.',
                    ],
                    'colonia' => [
                        'type' => 'string',
                        'description' => 'Colonia solo si aparece explicitamente.',
                    ],
                    'localidad' => [
                        'type' => 'string',
                        'description' => 'Localidad o ciudad solo si aparece explicitamente.',
                    ],
                    'municipio' => [
                        'type' => 'string',
                        'description' => 'Municipio solo si aparece explicitamente.',
                    ],
                    'estado' => [
                        'type' => 'string',
                        'description' => 'Estado solo si aparece explicitamente.',
                    ],
                    'pais' => [
                        'type' => 'string',
                        'description' => 'Pais detectado. Usar Mexico solo cuando corresponda claramente.',
                    ],
                ],
            ],
            'contacto' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'nombre_contacto',
                    'telefono',
                    'correo',
                    'sitio_web',
                ],
                'properties' => [
                    'nombre_contacto' => [
                        'type' => 'string',
                        'description' => 'Nombre de contacto solo si aparece explicitamente.',
                    ],
                    'telefono' => [
                        'type' => 'string',
                        'description' => 'Telefono solo si aparece explicitamente.',
                    ],
                    'correo' => [
                        'type' => 'string',
                        'description' => 'Correo solo si aparece explicitamente.',
                    ],
                    'sitio_web' => [
                        'type' => 'string',
                        'description' => 'Sitio web solo si aparece explicitamente.',
                    ],
                ],
            ],
            'clasificacion_sugerida' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => [
                    'rol',
                    'estatus',
                    'mercado',
                    'giro',
                ],
                'properties' => [
                    'rol' => [
                        'type' => 'string',
                        'description' => 'Sugerencia de rol: Cliente, Proveedor, Prospecto o cadena vacia.',
                    ],
                    'estatus' => [
                        'type' => 'string',
                        'description' => 'Sugerencia de estatus: Prospecto, Cliente activo, Inactivo o cadena vacia.',
                    ],
                    'mercado' => [
                        'type' => 'string',
                        'description' => 'Sugerencia de mercado solo si hay evidencia clara.',
                    ],
                    'giro' => [
                        'type' => 'string',
                        'description' => 'Sugerencia de giro solo si hay evidencia clara.',
                    ],
                ],
            ],
            'campos_detectados' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Lista de nombres de campos detectados con confianza razonable.',
            ],
            'campos_no_detectados' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Lista de nombres de campos esperados que no fueron encontrados.',
            ],
            'advertencias' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Advertencias sobre datos ambiguos, incompletos, inferidos o dudosos.',
            ],
        ],
    ];
}

function obtenerFormatoRespuestaEmpresaAI(): array
{
    return [
        'format' => [
            'type' => 'json_schema',
            'name' => 'extraccion_datos_empresa',
            'strict' => true,
            'schema' => obtenerSchemaEmpresaAI(),
        ],
    ];
}

function validarEstructuraEmpresaAI(array $datos): array
{
    $warnings = [];
    $clavesRaiz = [
        'tipo_documento_detectado',
        'confianza_global',
        'empresa',
        'direccion_fiscal',
        'contacto',
        'clasificacion_sugerida',
        'campos_detectados',
        'campos_no_detectados',
        'advertencias',
    ];

    foreach ($clavesRaiz as $clave) {
        if (!array_key_exists($clave, $datos)) {
            $warnings[] = 'Falta la clave ' . $clave;
        }
    }

    if (!array_key_exists('empresa', $datos) || !is_array($datos['empresa'])) {
        $warnings[] = 'empresa no tiene estructura valida';
    } else {
        $clavesEmpresa = [
            'rfc',
            'razon_social',
            'nombre_comercial_sugerido',
            'regimen_fiscal',
            'regimen_capital_detectado',
            'regimen_capital',
            'estatus_fiscal',
            'fecha_inicio_operaciones',
        ];
        foreach ($clavesEmpresa as $claveEmpresa) {
            if (!array_key_exists($claveEmpresa, $datos['empresa'])) {
                $warnings[] = 'Falta empresa.' . $claveEmpresa;
            }
        }
    }

    if (array_key_exists('direccion_fiscal', $datos) && !is_array($datos['direccion_fiscal'])) {
        $warnings[] = 'direccion_fiscal no tiene estructura valida';
    }

    if (array_key_exists('contacto', $datos) && !is_array($datos['contacto'])) {
        $warnings[] = 'contacto no tiene estructura valida';
    }

    if (array_key_exists('clasificacion_sugerida', $datos) && !is_array($datos['clasificacion_sugerida'])) {
        $warnings[] = 'clasificacion_sugerida no tiene estructura valida';
    }

    if (array_key_exists('campos_detectados', $datos) && !is_array($datos['campos_detectados'])) {
        $warnings[] = 'campos_detectados no tiene estructura valida';
    }

    if (array_key_exists('campos_no_detectados', $datos) && !is_array($datos['campos_no_detectados'])) {
        $warnings[] = 'campos_no_detectados no tiene estructura valida';
    }

    if (array_key_exists('advertencias', $datos) && !is_array($datos['advertencias'])) {
        $warnings[] = 'advertencias no tiene estructura valida';
    }

    return [
        'ok' => count($warnings) === 0,
        'warnings' => $warnings,
    ];
}
