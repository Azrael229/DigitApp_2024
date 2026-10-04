<?php
declare(strict_types=1);

const COTIZACION_FORMATO_VERSION = 'F-COT-01-R05';
const COTIZACION_TERMINOS_VERSION = 'TC-2026-R02';

// Devuelve los términos comerciales vigentes que se fotografían en cada cotización nueva.
function cotizacionTerminosVigentes(): array
{
    return [
        [
            'titulo' => 'Aceptación',
            'texto' => 'La cotización se considera aceptada mediante orden de compra, firma, pago o autorización por escrito.',
        ],
        [
            'titulo' => 'Imparcialidad en calibración',
            'texto' => 'Cuando el servicio incluya calibración, esta será realizada y documentada de manera independiente para preservar la imparcialidad. Las actividades de calibración, obtención y emisión de resultados, así como la emisión del certificado, se mantienen separadas e independientes de los servicios de mantenimiento, reparación y ajuste realizados por SERVICOM Básculas Digitales.',
        ],
        [
            'titulo' => 'Alcance de la calibración',
            'texto' => 'El servicio de calibración comprende únicamente las actividades específicas establecidas en la cotización y la emisión del certificado correspondiente por parte del laboratorio. No incluye mantenimiento, limpieza, reparación, ajuste ni pruebas adicionales ajenas al método de calibración, salvo que se encuentren expresamente cotizadas.',
        ],
        [
            'titulo' => 'Alcance del mantenimiento preventivo',
            'texto' => 'El servicio de mantenimiento preventivo comprende las actividades establecidas en la cotización, incluyendo los ajustes y las pruebas metrológicas necesarias para evaluar el funcionamiento del instrumento después del mantenimiento. Estas pruebas no constituyen un servicio de calibración ni incluyen la emisión de un certificado de calibración. El servicio no incluye ningún tipo de trabajo de obra civil, soldadura, pailería, trabajos estructurales, reparaciones mayores ni suministro de refacciones. Cuando se detecten reparaciones correctivas o refacciones necesarias, estas serán informadas y cotizadas por separado.',
        ],
        [
            'titulo' => 'Alcance del diagnóstico',
            'texto' => 'El servicio de diagnóstico tiene como finalidad evaluar el estado del instrumento e informar las posibles causas de la falla, las acciones correctivas recomendadas y las posibles refacciones necesarias para recuperar su funcionamiento y desempeño. El diagnóstico no incluye ni garantiza la reparación del instrumento, y el técnico no está obligado a dejarlo funcionando como parte de este servicio. La viabilidad de la reparación dependerá de su condición, uso, historial de mantenimiento y disponibilidad de refacciones. Cualquier reparación, ajuste o servicio correctivo deberá cotizarse y autorizarse por separado.',
        ],
        [
            'titulo' => 'Programación y servicios urgentes',
            'texto' => 'Las fechas y horarios se acuerdan con el cliente. Los servicios urgentes o fuera de horario están sujetos a disponibilidad. Cualquier recargo se evaluará y autorizará previamente para cada caso.',
        ],
        [
            'titulo' => 'Servicios en instalaciones de SERVICOM Básculas Digitales',
            'texto' => 'La recepción del equipo se realizará en el día, fecha y hora previamente comunicados al cliente. La duración dependerá del servicio requerido y podrá extenderse de un día a otro. Una vez confirmada la conclusión del trabajo, se comunicará al cliente la fecha y hora disponibles para la recolección. Si el equipo no se recoge dentro de los 15 días posteriores al aviso y no existe respuesta del cliente, será trasladado a almacenamiento y podrá considerarse abandonado; SERVICOM Básculas Digitales podrá disponer de él.',
        ],
        [
            'titulo' => 'Certificados de calibración',
            'texto' => 'Los certificados de calibración se entregan en formato electrónico. El plazo máximo estimado de entrega es de 15 días hábiles posteriores a la conclusión del servicio y al cumplimiento de las condiciones aplicables.',
        ],
        [
            'titulo' => 'Cancelaciones y reprogramaciones',
            'texto' => 'Las cancelaciones o reprogramaciones deberán notificarse por escrito con la mayor anticipación posible. Cuando ya se hayan generado gastos de traslado, viáticos, reservaciones, permisos o asignación de personal técnico, se aplicará un cargo equivalente al 40% del valor total de la cotización, por concepto de gastos operativos y administrativos.',
        ],
        [
            'titulo' => 'Acceso, seguridad y equipo de protección',
            'texto' => 'El cliente deberá facilitar acceso seguro, permisos, disponibilidad de los equipos e información correcta. El equipo de protección personal básico de SERVICOM Básculas Digitales considera casco de seguridad, chaleco reflejante, botas y guantes de seguridad, lentes de seguridad y tapones auditivos cuando las condiciones del sitio lo requieran.',
        ],
        [
            'titulo' => 'Resultados y confidencialidad',
            'texto' => 'Los resultados describen el estado del instrumento al momento del servicio y no garantizan su funcionamiento futuro. El cliente conserva la responsabilidad de uso, mantenimiento y control de sus equipos. La información proporcionada por el cliente y la generada durante el servicio será tratada como confidencial, salvo obligación legal o autorización expresa.',
        ],
    ];
}

// Serializa los términos vigentes para conservar exactamente la versión emitida.
function cotizacionTerminosSerializados(): string
{
    return json_encode(
        cotizacionTerminosVigentes(),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
}

// Recupera la fotografía histórica o utiliza la versión vigente para cotizaciones anteriores.
function cotizacionTerminosDesdeSnapshot(?string $snapshot): array
{
    if ($snapshot !== null && trim($snapshot) !== '') {
        try {
            $terminos = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($terminos) && $terminos !== []) {
                return $terminos;
            }
        } catch (JsonException $error) {
            error_log('Cotizaciones: términos históricos inválidos: ' . $error->getMessage());
        }
    }
    return cotizacionTerminosVigentes();
}
