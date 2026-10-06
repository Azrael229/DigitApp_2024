<?php
$prefijoRuta = '../';
require_once __DIR__ . '/../backend/helpers/folio_informe.php';

if (isset($_GET['consultar_folio_fecha'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode(['folio' => consultarSiguienteFolioInforme((string) $_GET['consultar_folio_fecha'])]);
    } catch (RuntimeException $errorFolio) {
        http_response_code(500);
        echo json_encode(['error' => 'No fue posible consultar el siguiente folio.']);
    }
    exit;
}

require __DIR__ . '/../construct/header.php';
require __DIR__ . '/../backend/empresas/query_all_empresas.php';

try {
    $folioSugerido = consultarSiguienteFolioInforme();
} catch (RuntimeException $errorFolio) {
    $folioSugerido = (string) FOLIO_INFORME_INICIAL;
}

$reactivosInspeccion = [
    1 => 'Identificación y placa legible.',
    2 => 'Display / escala / unidad legibles.',
    3 => 'Cero, tara y estabilidad funcionan correctamente.',
    4 => 'Plataforma / plato / estructura en buen estado.',
    5 => 'Nivelación e instalación correctas.',
    6 => 'Limpieza y funcionamiento general.',
];
$bloquesPruebas = ['inicial' => 'Pruebas iniciales', 'final' => 'Pruebas finales'];
?>

<div class="container my-5 contain shadow-lg informe-contenedor">
    <form action="<?= $prefijoRuta ?>fpdf/informePDF.php" method="post" id="form_informe" data-local-draft="1">
        <div class="row">
            <div class="col text-center mt-3 mb-4">
                <h3><i class="bi bi-patch-check" id="ico_informe"></i></h3>
                <h1>Informe de Pruebas Metrológicas</h1>
                <p class="text-muted mb-0">Informe técnico de servicio - V1</p>
            </div>
        </div>

        <section class="informe-seccion">
            <h2 class="informe-titulo-seccion">Datos generales</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="inf_fecha">Fecha</label>
                    <input class="form-control" type="date" name="inf_fecha" id="inf_fecha" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="input_num_inf">No. de Informe</label>
                    <input class="form-control" type="text" id="input_num_inf" name="input_num_inf" value="<?= htmlspecialchars($folioSugerido, ENT_QUOTES, 'UTF-8') ?>" pattern="\d{5}" maxlength="5" inputmode="numeric" readonly required>
                    <div class="form-text">Folio previsto; se reserva definitivamente al generar el PDF.</div>
                </div>
            </div>
        </section>

        <section class="informe-seccion">
            <h2 class="informe-titulo-seccion">Datos del cliente</h2>
            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <label class="form-label" for="select_empresa">Empresa</label>
                    <select class="select form-control" name="select_empresa" id="select_empresa" required>
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($result_empresas as $fila): ?>
                            <option value="<?= (int) $fila['id_e'] ?>"><?= htmlspecialchars($fila['empresa'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="select_contacto">Contacto</label>
                    <select class="form-select" name="select_contacto" id="select_contacto" required disabled>
                        <option value="">Seleccione primero una empresa</option>
                    </select>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label" for="nombre_empresa">Razón social</label>
                    <textarea class="form-control" name="nombre_empresa" id="nombre_empresa" rows="2" required></textarea>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="dir_empresa">Dirección</label>
                    <select class="form-select" name="dir_empresa" id="dir_empresa" required disabled>
                        <option value="">Seleccione primero una empresa</option>
                    </select>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="nombre_contacto">Nombre del contacto</label>
                    <input class="form-control" type="text" name="nombre_contacto" id="nombre_contacto" readonly>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="correo_contacto">Correo</label>
                    <input class="form-control" type="email" name="correo_contacto" id="correo_contacto" readonly>
                </div>
            </div>
        </section>

        <section class="informe-seccion">
            <h2 class="informe-titulo-seccion">Datos del instrumento</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="select_equipo">Equipo asociado</label>
                    <select class="form-select" name="equipo_id" id="select_equipo" required disabled>
                        <option value="">Seleccione primero una empresa y una dirección</option>
                    </select>
                    <div class="form-text">Los datos se toman del registro del equipo. Para corregirlos, edite el equipo desde la empresa correspondiente.</div>
                </div>
                <div class="col-md-6"><label class="form-label" for="desc_inst">Descripción</label><input class="form-control" type="text" name="desc_inst" id="desc_inst" readonly aria-readonly="true" required></div>
                <div class="col-md-3"><label class="form-label" for="marca_inst">Marca</label><input class="form-control" type="text" name="marca_inst" id="marca_inst" readonly aria-readonly="true"></div>
                <div class="col-md-3"><label class="form-label" for="modelo_inst">Modelo</label><input class="form-control" type="text" name="modelo_inst" id="modelo_inst" readonly aria-readonly="true"></div>
                <div class="col-md-4"><label class="form-label" for="id_inst">ID / Identificación</label><input class="form-control" type="text" name="id_inst" id="id_inst" readonly aria-readonly="true"></div>
                <div class="col-md-4"><label class="form-label" for="serie_inst">Número de serie</label><input class="form-control" type="text" name="serie_inst" id="serie_inst" readonly aria-readonly="true"></div>
                <div class="col-md-4 informe-unidades">
                    <span class="form-label d-block">Unidad</span>
                    <div class="pt-2">
                        <div class="form-check form-check-inline"><input class="form-check-input unidad-instrumento" type="radio" id="unidadeskg" value="kg" disabled><label class="form-check-label" for="unidadeskg">kg</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input unidad-instrumento" type="radio" id="unidadesg" value="g" disabled><label class="form-check-label" for="unidadesg">g</label></div>
                        <input type="hidden" name="unidad" id="unidad_inst">
                    </div>
                </div>
                <div class="col-md-2"><label class="form-label" for="max">Max</label><input class="form-control parametro-instrumento" type="number" id="max" name="max" step="any" min="0" readonly aria-readonly="true" required></div>
                <div class="col-md-2"><label class="form-label" for="d">División real (d)</label><input class="form-control parametro-instrumento" type="number" name="d" id="d" step="any" min="0" readonly aria-readonly="true" required></div>
                <div class="col-md-2"><label class="form-label" for="e">División verificación (e)</label><input class="form-control parametro-instrumento" type="number" name="e" id="e" step="any" min="0" readonly aria-readonly="true" required></div>
                <div class="col-md-2"><label class="form-label" for="min">Min</label><input class="form-control" type="number" name="min" id="min" step="any" readonly></div>
                <div class="col-md-4"><label class="form-label" for="clase">Clase</label><input class="form-control" type="text" name="clase" id="clase" readonly></div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                <button name="btn_analizar" id="btn_analizar" type="button" class="btn btn-success">CALCULAR PARÁMETROS Y CARGAS</button>
                <div id="resumen_emt" class="informe-resumen-emt" aria-live="polite"></div>
            </div>
            <div class="informe-emt-referencia mt-4" aria-labelledby="titulo_tabla_emt">
                <h3 id="titulo_tabla_emt">Tabla informativa de errores máximos tolerados (EMT)</h3>
                <p>Referencia técnica general según la clase y la división de verificación calculadas.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle informe-tabla tabla-emt">
                        <thead><tr><th>Intervalo de pesaje</th><th>EMT</th></tr></thead>
                        <tbody id="tabla_emt_cuerpo"><tr><td colspan="2" class="text-center">Calcule los parámetros del instrumento para consultar los intervalos.</td></tr></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="informe-seccion">
            <h2 class="informe-titulo-seccion">Inspección visual y funcional</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle informe-tabla">
                    <thead><tr><th scope="col">Reactivo</th><th scope="col" class="informe-col-estado">Estado</th><th scope="col">Observación breve</th></tr></thead>
                    <tbody>
                        <?php foreach ($reactivosInspeccion as $numero => $reactivo): ?>
                            <tr>
                                <td><?= $numero ?>. <?= htmlspecialchars($reactivo, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <select class="form-select inspeccion-estado" name="inspeccion_<?= $numero ?>_estado" data-observacion="inspeccion_<?= $numero ?>_observacion" required>
                                        <option value="">Seleccionar</option><option value="CUMPLE">Cumple</option><option value="NO CUMPLE">No cumple</option><option value="NO APLICA">No aplica</option>
                                    </select>
                                </td>
                                <td><input class="form-control inspeccion-observacion" type="text" name="inspeccion_<?= $numero ?>_observacion" id="inspeccion_<?= $numero ?>_observacion"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="resultado-general-wrap"><span>Resultado general:</span><output id="inspeccion_resultado_texto" class="resultado-badge resultado-pendiente">PENDIENTE</output><input type="hidden" name="inspeccion_resultado" id="inspeccion_resultado"></div>
        </section>

        <?php foreach ($bloquesPruebas as $prefijo => $tituloBloque): ?>
            <section class="informe-seccion bloque-pruebas" id="pruebas_<?= $prefijo ?>">
                <div class="informe-encabezado-pruebas"><h2 class="informe-titulo-seccion mb-1"><?= $tituloBloque ?></h2><p class="text-muted mb-0"><?= $prefijo === 'inicial' ? 'Condición antes de la intervención técnica.' : 'Condición después de la intervención técnica.' ?></p></div>

                <article class="prueba-card" data-prueba="<?= $prefijo ?>_repetibilidad">
                    <div class="prueba-card-header"><h3>Repetibilidad</h3><div class="form-check form-switch"><input class="form-check-input prueba-no-aplica" type="checkbox" role="switch" name="<?= $prefijo ?>_repetibilidad_no_aplica" id="<?= $prefijo ?>_repetibilidad_no_aplica" value="1"><label class="form-check-label" for="<?= $prefijo ?>_repetibilidad_no_aplica">No aplica</label></div></div>
                    <div class="motivo-no-aplica d-none"><label class="form-label" for="<?= $prefijo ?>_repetibilidad_motivo">Motivo por el cual no aplica</label><textarea class="form-control motivo-no-aplica-input" name="<?= $prefijo ?>_repetibilidad_motivo" id="<?= $prefijo ?>_repetibilidad_motivo" rows="2"></textarea></div>
                    <div class="contenido-prueba">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-8 col-lg-4"><label class="form-label" for="<?= $prefijo ?>_repetibilidad_carga">Carga aplicada</label><input class="form-control <?= $prefijo === 'inicial' ? 'carga-sugerida' : 'carga-sincronizada' ?> prueba-entrada" type="number" step="any" name="<?= $prefijo ?>_repetibilidad_carga" id="<?= $prefijo ?>_repetibilidad_carga" data-tipo="repetibilidad"<?= $prefijo === 'final' ? ' readonly aria-readonly="true"' : '' ?>></div>
                        </div>
                        <div class="table-responsive"><table class="table table-bordered align-middle informe-tabla tabla-repetibilidad"><thead><tr><th>Prueba</th><th>Indicación</th></tr></thead><tbody>
                            <?php for ($i = 1; $i <= 5; $i++): ?><tr><td><?= $i ?></td><td><input class="form-control prueba-entrada campo-indicacion" type="number" step="any" name="<?= $prefijo ?>_repetibilidad_lectura_<?= $i ?>" id="<?= $prefijo ?>_repetibilidad_lectura_<?= $i ?>"></td></tr><?php endfor; ?>
                        </tbody></table></div>
                        <input type="hidden" name="<?= $prefijo ?>_repetibilidad_maxima" id="<?= $prefijo ?>_repetibilidad_maxima">
                        <input type="hidden" name="<?= $prefijo ?>_repetibilidad_minima" id="<?= $prefijo ?>_repetibilidad_minima">
                        <input type="hidden" name="<?= $prefijo ?>_repetibilidad_intervalo" id="<?= $prefijo ?>_repetibilidad_intervalo">
                        <div class="row g-3 resultados-calculados">
                            <div class="col-md-4"><label class="form-label" for="<?= $prefijo ?>_repetibilidad_diferencia">Diferencia máxima encontrada</label><input class="form-control" type="text" name="<?= $prefijo ?>_repetibilidad_diferencia" id="<?= $prefijo ?>_repetibilidad_diferencia" readonly></div>
                            <div class="col-md-4"><label class="form-label" for="<?= $prefijo ?>_repetibilidad_emt">EMT aplicable</label><input class="form-control" type="text" name="<?= $prefijo ?>_repetibilidad_emt" id="<?= $prefijo ?>_repetibilidad_emt" readonly></div>
                            <div class="col-md-4"><label class="form-label">Resultado</label><output class="resultado-badge resultado-pendiente prueba-resultado-texto">PENDIENTE</output><input type="hidden" name="<?= $prefijo ?>_repetibilidad_resultado" class="prueba-resultado" id="<?= $prefijo ?>_repetibilidad_resultado"></div>
                        </div>
                    </div>
                </article>

                <article class="prueba-card" data-prueba="<?= $prefijo ?>_excentricidad">
                    <div class="prueba-card-header"><h3>Excentricidad</h3><div class="form-check form-switch"><input class="form-check-input prueba-no-aplica" type="checkbox" role="switch" name="<?= $prefijo ?>_excentricidad_no_aplica" id="<?= $prefijo ?>_excentricidad_no_aplica" value="1"><label class="form-check-label" for="<?= $prefijo ?>_excentricidad_no_aplica">No aplica</label></div></div>
                    <div class="motivo-no-aplica d-none"><label class="form-label" for="<?= $prefijo ?>_excentricidad_motivo">Motivo por el cual no aplica</label><textarea class="form-control motivo-no-aplica-input" name="<?= $prefijo ?>_excentricidad_motivo" id="<?= $prefijo ?>_excentricidad_motivo" rows="2"></textarea></div>
                    <div class="contenido-prueba">
                        <div class="row g-3 align-items-end mb-3"><div class="col-md-4"><label class="form-label" for="<?= $prefijo ?>_excentricidad_carga">Carga aplicada</label><input class="form-control <?= $prefijo === 'inicial' ? 'carga-sugerida' : 'carga-sincronizada' ?> prueba-entrada" type="number" step="any" name="<?= $prefijo ?>_excentricidad_carga" id="<?= $prefijo ?>_excentricidad_carga" data-tipo="excentricidad"<?= $prefijo === 'final' ? ' readonly aria-readonly="true"' : '' ?>></div><div class="col-md-8 text-md-end"><img class="diagrama-excentricidad" src="<?= $prefijoRuta ?>imgs/El texto del párrafo.png" alt="Diagrama de posiciones para excentricidad"></div></div>
                        <div class="table-responsive"><table class="table table-bordered align-middle informe-tabla tabla-excentricidad"><thead><tr><th>Posición</th><th>Indicación</th></tr></thead><tbody>
                            <?php for ($i = 1; $i <= 5; $i++): ?><tr><td><?= $i === 1 ? '1 - Centro / referencia' : (string) $i ?></td><td><input class="form-control prueba-entrada campo-indicacion" type="number" step="any" name="<?= $prefijo ?>_excentricidad_lectura_<?= $i ?>" id="<?= $prefijo ?>_excentricidad_lectura_<?= $i ?>"></td></tr><?php endfor; ?>
                        </tbody></table></div>
                        <?php for ($i = 1; $i <= 5; $i++): ?><input type="hidden" name="<?= $prefijo ?>_excentricidad_diferencia_<?= $i ?>" id="<?= $prefijo ?>_excentricidad_diferencia_<?= $i ?>" value="<?= $i === 1 ? 'REFERENCIA' : '' ?>"><?php endfor; ?>
                        <input type="hidden" name="<?= $prefijo ?>_excentricidad_intervalo" id="<?= $prefijo ?>_excentricidad_intervalo">
                        <div class="row g-3 resultados-calculados">
                            <div class="col-md-4"><label class="form-label" for="<?= $prefijo ?>_excentricidad_diferencia_maxima">Diferencia máxima encontrada</label><input class="form-control" type="text" name="<?= $prefijo ?>_excentricidad_diferencia_maxima" id="<?= $prefijo ?>_excentricidad_diferencia_maxima" readonly></div>
                            <div class="col-md-4"><label class="form-label" for="<?= $prefijo ?>_excentricidad_emt">EMT aplicable</label><input class="form-control" type="text" name="<?= $prefijo ?>_excentricidad_emt" id="<?= $prefijo ?>_excentricidad_emt" readonly></div>
                            <div class="col-md-4"><label class="form-label">Resultado</label><output class="resultado-badge resultado-pendiente prueba-resultado-texto">PENDIENTE</output><input type="hidden" name="<?= $prefijo ?>_excentricidad_resultado" class="prueba-resultado" id="<?= $prefijo ?>_excentricidad_resultado"></div>
                        </div>
                    </div>
                </article>

                <article class="prueba-card" data-prueba="<?= $prefijo ?>_exactitud">
                    <div class="prueba-card-header"><h3>Exactitud</h3><div class="form-check form-switch"><input class="form-check-input prueba-no-aplica" type="checkbox" role="switch" name="<?= $prefijo ?>_exactitud_no_aplica" id="<?= $prefijo ?>_exactitud_no_aplica" value="1"><label class="form-check-label" for="<?= $prefijo ?>_exactitud_no_aplica">No aplica</label></div></div>
                    <div class="motivo-no-aplica d-none"><label class="form-label" for="<?= $prefijo ?>_exactitud_motivo">Motivo por el cual no aplica</label><textarea class="form-control motivo-no-aplica-input" name="<?= $prefijo ?>_exactitud_motivo" id="<?= $prefijo ?>_exactitud_motivo" rows="2"></textarea></div>
                    <div class="contenido-prueba table-responsive"><table class="table table-bordered align-middle informe-tabla tabla-exactitud"><thead><tr><th>Punto</th><th>Carga</th><th>Indicación</th><th>Error</th><th>EMT</th><th>Resultado</th></tr></thead><tbody>
                        <?php for ($i = 0; $i <= 5; $i++): ?>
                            <?php $cargaBloqueada = $i === 0 || $prefijo === 'final'; ?>
                            <tr><td><?= $i ?></td><td><input class="form-control carga-exactitud <?= $cargaBloqueada ? 'carga-sincronizada' : '' ?> prueba-entrada" type="number" step="any" name="<?= $prefijo ?>_exactitud_carga_<?= $i ?>" id="<?= $prefijo ?>_exactitud_carga_<?= $i ?>" data-punto="<?= $i ?>"<?= $i === 0 ? ' value="0"' : '' ?><?= $cargaBloqueada ? ' readonly aria-readonly="true"' : '' ?>></td><td><input class="form-control prueba-entrada campo-indicacion" type="number" step="any" name="<?= $prefijo ?>_exactitud_indicacion_<?= $i ?>" id="<?= $prefijo ?>_exactitud_indicacion_<?= $i ?>"></td><td><input class="form-control" type="text" name="<?= $prefijo ?>_exactitud_error_<?= $i ?>" id="<?= $prefijo ?>_exactitud_error_<?= $i ?>" readonly></td><td><input class="form-control" type="text" name="<?= $prefijo ?>_exactitud_emt_<?= $i ?>" id="<?= $prefijo ?>_exactitud_emt_<?= $i ?>" readonly></td><td><output class="resultado-badge resultado-pendiente resultado-punto-texto">PENDIENTE</output><input type="hidden" name="<?= $prefijo ?>_exactitud_resultado_<?= $i ?>" class="resultado-punto" id="<?= $prefijo ?>_exactitud_resultado_<?= $i ?>"><input type="hidden" name="<?= $prefijo ?>_exactitud_intervalo_<?= $i ?>" id="<?= $prefijo ?>_exactitud_intervalo_<?= $i ?>"></td></tr>
                        <?php endfor; ?>
                    </tbody></table><div class="resultado-general-wrap mt-3"><span>Resultado general:</span><output class="resultado-badge resultado-pendiente prueba-resultado-texto">PENDIENTE</output><input type="hidden" name="<?= $prefijo ?>_exactitud_resultado" class="prueba-resultado" id="<?= $prefijo ?>_exactitud_resultado"></div></div>
                </article>
            </section>
        <?php endforeach; ?>

        <section class="informe-seccion">
            <h2 class="informe-titulo-seccion">Cierre del servicio</h2>
            <div class="row g-3">
                <?php foreach (['observaciones' => 'Observaciones', 'trabajo_realizado' => 'Trabajo realizado', 'recomendaciones' => 'Recomendaciones', 'atencion_urgente' => 'Atención / servicios urgentes'] as $campo => $etiqueta): ?><div class="col-12"><label class="form-label" for="<?= $campo ?>"><?= $etiqueta ?></label><textarea class="form-control" name="<?= $campo ?>" id="<?= $campo ?>" rows="3"></textarea></div><?php endforeach; ?>
            </div>
        </section>

        <div id="errores_informe" class="alert alert-danger d-none" role="alert" tabindex="-1"></div>
        <div class="d-grid gap-2 col-lg-5 mx-auto py-4"><button type="submit" class="btn btn-success btn-lg">GENERAR Y DESCARGAR PDF</button></div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= $prefijoRuta ?>js/informe.js?v=20261002-2"></script>

<?php require __DIR__ . '/../construct/footer.html'; ?>
