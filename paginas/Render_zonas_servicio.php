<?php $prefijoRuta = '../'; ?>
<?php  require (__DIR__ . "/../construct/header.php")  ?>

<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg" ">


    <!-- row de Contenido -->
    <div class="row border-top justify-content-center ">
        <!-- titulo de contenido  -->
        <div class="row">
            <div class="col text-center mt-3 mb-5">
                <!-- ===================================================== -->
                <!-- MÓDULO: ZONAS DE SERVICIO / MAPA DE COBERTURA SERVICOM -->
                <!-- Archivo sugerido: zonas_servicio.php -->
                <!-- Inserta este bloque dentro de tu layout Bootstrap -->
                <!-- ===================================================== -->

                <div class="container-fluid mt-4">

                    <!-- Título -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h3 class="fw-bold">
                                Zonas de Servicio - SERVICOM Básculas Digitales
                            </h3>
                            <p class="text-muted mb-0">
                                Cobertura comercial y radios operativos desde base principal
                            </p>
                        </div>
                    </div>

                    <!-- Tarjeta principal -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body">

                            <!-- Información breve -->
                            <div class="mb-3">
                                <strong>Centro de operación:</strong><br>
                                Cerro El Gavilán 302-12, Ex Hacienda Santana,
                                Santiago de Querétaro, Qro.
                            </div>

                            <!-- MAPA EMBEBIDO -->
                            <!-- IMPORTANTE:
                                Reemplaza TU_MAP_ID con el ID real de Google My Maps
                                después de subir tu archivo KML
                            -->

                            <div class="ratio ratio-16x9">
                                <iframe
                                    src="https://www.google.com/maps/d/embed?mid=1PYzxgvEgpTHE5r0-N1c2i7opWx4Rvho&ll=20.561619746479042%2C-100.47238574517998&z=11"
                                    width="100%"
                                    height="600"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy">
                                </iframe>
                            </div>

                        </div>
                    </div>

                    <!-- Tabla rápida de referencia -->
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-body">

                            <h5 class="mb-3">
                                Referencia de Cobertura por Zona
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">

                                  <thead class="table-light">
                                      <tr>
                                          <th style="width: 5%;">Zona</th>
                                          <th style="width: 10%;">Radio desde tu ubicación</th>
                                          <th style="width: 10%;">Km operativos reales<br>(ida y vuelta aprox.)</th>
                                          <th style="width: 10%;">Cargo de viáticos recomendado</th>
                                          <th style="width: 10%;">Costo estimado operativo</th>
                                          <th style="width: 25%;">zonas</th>
                                          <th style="width: 25%;">Parques industriales incluidos</th>
                                      </tr>
                                  </thead>

                                  <tbody>

                                      <tr>
                                          <td>Zona 1 amarillo</td>
                                          <td>0 – 15 km</td>
                                          <td>10 – 30 km</td>
                                          <td>Incluido o $250</td>
                                          <td>$150 – $250</td>
                                          <td class="text-start">
                                              <div>Santiago de Querétaro (zona norte y centro)</div>
                                              <div>Santa Rosa Jáuregui</div>
                                              <div>Jurica</div>
                                              <div>Juriquilla</div>
                                              <div>Hércules</div>
                                              <div>Centro Histórico de Querétaro</div>
                                              <div>Carretas</div>
                                              <div>El Refugio cercano (parcial)</div>
                                              <div>Corregidora cercana (parcial)</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Parque Industrial Benito Juárez</div>
                                              <div>Parque Industrial Jurica</div>
                                              <div>Parque Industrial Querétaro Norte</div>
                                              <div>Micro Parque Industrial Santiago</div>
                                              <div>Zona Industrial 5 de Febrero</div>
                                              <div>Parque Industrial La Montaña</div>
                                              <div>Industrialización cercana de Jurica</div>
                                            </td>
                                      </tr>

                                      <tr>
                                          <td>Zona 2 verde</td>
                                          <td>15 – 30 km</td>
                                          <td>30 – 60 km</td>
                                          <td>$350 – $500</td>
                                          <td>$300 – $450</td>
                                          <td class="text-start">
                                              <div>El Marqués</div>
                                              <div>La Cañada</div>
                                              <div>Corregidora (zona extendida)</div>
                                              <div>Huimilpan cercano (parcial)</div>
                                              <div>Apaseo el Grande</div>
                                              <div>Apaseo el Alto</div>
                                              <div>El Nacimiento</div>
                                              <div>Presa de Bravo</div>
                                              <div>La Piedad</div>
                                              <div>El Colorado</div>
                                              <div>La Griega</div>
                                              <div>Amazcala</div>
                                              <div>Chichimequillas</div>
                                              <div>La Estancia</div>
                                              <div>Pie de Gallo</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Zona Aeropuerto</div>
                                              <div>Parque Aeroespacial</div>
                                              <div>Parque Industrial Querétaro</div>
                                              <div>Parque Industrial Bernardo Quintana</div>
                                              <div>FINSA Querétaro</div>
                                              <div>Parque Industrial El Marqués</div>
                                              <div>Parque Industrial Advance Querétaro</div>
                                              <div>Parque Industrial Innovación Querétaro</div>
                                              <div>Parque Industrial PyME</div>
                                              <div>Parque Industrial O'Donnell Aeropuerto</div>
                                              <div>Parque Industrial Aerotech</div>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>Zona 3 azul</td>
                                          <td>30 – 50 km</td>
                                          <td>60 – 100 km</td>
                                          <td>$700</td>
                                          <td>$550 – $700</td>
                                          <td class="text-start">
                                            <div>Celaya</div>
                                            <div>San Miguel de Allende</div>
                                            <div>Comonfort</div>
                                            <div>San José Iturbide</div>
                                            <div>Colón</div>
                                            <div>Ajuchitlán</div>
                                            <div>Galeras</div>
                                            <div>Pedro Escobedo</div>
                                            <div>Huimilpan</div>
                                          </td>   
                                      
                                          <td class="text-start">
                                            <div>Parque Industrial Opción</div>
                                            <div>Polígono Empresarial San Miguel</div>
                                            <div>Parque Industrial Los Rodríguez</div>
                                            <div>Parque Industrial Amistad Celaya</div>
                                            <div>Parque Industrial Celaya</div>
                                            <div>Ciudad Industrial Celaya</div>
                                            <div>Parque Industrial Cuadritos</div>
                                            <div>Parque Industrial Benito Juárez Celaya</div>
                                            <div>Parque Industrial Castro del Río (zona extendida)</div>
                                            <div>Parque Industrial FINSA II Guanajuato (zona extendida)</div>
                                            <div>Agropark Querétaro</div>
                                            <div>Parque Industrial Point</div>
                                            <div>Parque Industrial Colón</div>
                                            <div>Parque Aeroespacial de Querétaro</div>
                                            <div>Corredor Industrial Querétaro – San Miguel – Celaya</div>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>Zona 4 morada</td>
                                          <td>50 – 80 km</td>
                                          <td>100 – 160 km</td>
                                          <td>$1,000 – $1,300</td>
                                          <td>$850 – $1,100</td>
                                          <td class="text-start">
                                              <div>Villagrán</div>
                                              <div>Juventino Rosas</div>
                                              <div>Cortazar</div>
                                              <div>Salamanca</div>
                                              <div>Dolores Hidalgo</div>
                                              <div>San Luis de la Paz</div>
                                              <div>Cadereyta</div>
                                              <div>Ezequiel Montes</div>
                                              <div>Vizarrón</div>
                                              <div>Tequisquiapan</div>
                                              <div>San Juan del Río</div>
                                              <div>Palmillas</div>
                                              <div>Amealco</div>
                                              <div>Jerécuaro</div>
                                              <div>Acámbaro</div>
                                              <div>Salvatierra</div>
                                              <div>Jaral del Progreso</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Parque Industrial Salamanca</div>
                                              <div>Mazda Supplier Park Salamanca</div>
                                              <div>Parque Industrial Bajío Salamanca</div>
                                              <div>Parque Industrial Castro del Río</div>
                                              <div>Parque Industrial Amistad Celaya</div>
                                              <div>Parque Industrial Celaya</div>
                                              <div>Ciudad Industrial Celaya</div>
                                              <div>Parque Industrial Cortazar</div>
                                              <div>Parque Industrial Villagrán</div>
                                              <div>Parque Industrial San Juan del Río</div>
                                              <div>Nuevo Parque Industrial San Juan</div>
                                              <div>Valle de Oro Industrial</div>
                                              <div>Parque Industrial Tequisquiapan</div>
                                              <div>Parque Industrial Opción</div>
                                              <div>Parque Opción San José Iturbide</div>
                                              <div>Parque Industrial Querétaro Interior</div>
                                              <div>Corredor Industrial Querétaro – Celaya – Salamanca</div>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>Zona 5 roja</td>
                                          <td>80 – 120 km</td>
                                          <td>160 – 240 km</td>
                                          <td>$1,500 – $2,000</td>
                                          <td>$1,300 – $1,700</td>
                                          <td class="text-start">
                                              <div>Irapuato</div>
                                              <div>Silao</div>
                                              <div>Guanajuato</div>
                                              <div>San Diego de la Unión</div>
                                              <div>Huichapan</div>
                                              <div>Aculco</div>
                                              <div>Polotitlán</div>
                                              <div>Acambay</div>
                                              <div>Atlacomulco</div>
                                              <div>Maravatío</div>
                                              <div>Moroleón</div>
                                              <div>Uriangato</div>
                                              <div>Yuriria</div>
                                              <div>Valle de Santiago</div>
                                              <div>Abasolo</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Parque Industrial Castro del Río</div>
                                              <div>Parque Industrial Apolo</div>
                                              <div>Parque Industrial FIPASI</div>
                                              <div>Parque Industrial Marabis</div>
                                              <div>Puerto Interior Guanajuato</div>
                                              <div>Parque Industrial Colinas de Silao</div>
                                              <div>Parque Industrial Las Colinas</div>
                                              <div>Parque Industrial Guanajuato</div>
                                              <div>Parque Industrial PILBA</div>
                                              <div>Parque Industrial León-Silao</div>
                                              <div>Parque Industrial Bajío</div>
                                              <div>Parque Industrial Abasolo</div>
                                              <div>Parque Industrial Valle de Santiago</div>
                                              <div>Corredor Industrial Irapuato – Silao – Guanajuato</div>
                                              <div>Corredor Industrial Bajío Occidente</div>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>Zona 6 </td>
                                          <td>120 – 180 km</td>
                                          <td>240 – 360 km</td>
                                          <td>$2,200 – $3,000</td>
                                          <td>$1,900 – $2,500</td>
                                          <td class="text-start">
                                              <div>León</div>
                                              <div>San Francisco del Rincón</div>
                                              <div>Lagos de Moreno</div>
                                              <div>San Luis Potosí</div>
                                              <div>Villa de Arriaga</div>
                                              <div>Villa de Reyes</div>
                                              <div>Tula de Allende</div>
                                              <div>Tepeji del Río</div>
                                              <div>Jilotepec</div>
                                              <div>Tepotzotlán</div>
                                              <div>Cuautitlán Izcalli</div>
                                              <div>Toluca</div>
                                              <div>Morelia</div>
                                              <div>La Piedad</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Parque Industrial PILBA</div>
                                              <div>Parque Industrial Colinas de León</div>
                                              <div>Parque Industrial Las Colinas</div>
                                              <div>Parque Industrial Santa Fe León</div>
                                              <div>Parque Industrial Castro del Río</div>
                                              <div>Puerto Interior Guanajuato</div>
                                              <div>World Trade Center Industrial León</div>
                                              <div>Parque Industrial Logistik</div>
                                              <div>WTC Industrial San Luis Potosí</div>
                                              <div>Parque Industrial Millennium</div>
                                              <div>Parque Industrial Tres Naciones</div>
                                              <div>Parque Industrial Colinas de San Luis</div>
                                              <div>Parque Industrial Villa de Reyes</div>
                                              <div>Parque Industrial Tepeji</div>
                                              <div>Parque Industrial Tula</div>
                                              <div>Parque Industrial Cuamatla</div>
                                              <div>Parque Industrial Xhala</div>
                                              <div>Parque Industrial Toluca 2000</div>
                                              <div>Parque Industrial Exportec I y II</div>
                                              <div>Parque Industrial Morelia</div>
                                              <div>Corredor Industrial Bajío – Centro – Norte</div>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>fuera de zona</td>
                                          <td>+180 km</td>
                                          <td>Según ruta</td>
                                          <td>Cotización especial</td>
                                          <td>Variable</td>
                                          <td class="text-start">
                                              <div>Ciudad de México</div>
                                              <div>Pachuca</div>
                                              <div>Cuernavaca</div>
                                              <div>Puebla</div>
                                              <div>Monterrey</div>
                                              <div>Guadalajara</div>
                                              <div>Aguascalientes</div>
                                              <div>Zacatecas</div>
                                              <div>San Luis Potosí</div>
                                              <div>Veracruz</div>
                                          </td>
                                          <td class="text-start">
                                              <div>Parque Industrial Vallejo</div>
                                              <div>Parque Industrial Cuautitlán</div>
                                              <div>Parque Industrial Xhala</div>
                                              <div>Parque Industrial Toluca 2000</div>
                                              <div>Parque Industrial Tepeji</div>
                                              <div>Parque Industrial Platah (Pachuca)</div>
                                              <div>Ciudad Textil Puebla</div>
                                              <div>Parque Industrial FINSA Puebla</div>
                                              <div>Parque Industrial Puebla 2000</div>
                                              <div>Parque Industrial Bernardo Quintana Puebla</div>
                                              <div>Parque Industrial Civac (Cuernavaca)</div>
                                              <div>Parque Industrial Jiutepec</div>
                                              <div>Parque Industrial El Salto (Guadalajara)</div>
                                              <div>Parque Industrial Technology Park Guadalajara</div>
                                              <div>Parque Industrial Logistik (San Luis Potosí)</div>
                                              <div>WTC Industrial San Luis Potosí</div>
                                              <div>Parque Industrial Tres Naciones</div>
                                              <div>Parque Industrial FINSA Aguascalientes</div>
                                              <div>Parque Industrial del Valle de Aguascalientes</div>
                                              <div>Parque Industrial Guadalupe (Zacatecas)</div>
                                              <div>Interpuerto Monterrey</div>
                                              <div>Parque Industrial FINSA Monterrey</div>
                                              <div>Parque Industrial Hofusan</div>
                                              <div>Parque Industrial Bruno Pagliai (Veracruz)</div>
                                              <div>Parque Industrial Santa Fe I y II (Veracruz)</div>
                                              <div>Corredor Industrial Foráneo Nacional</div>
                                          </td>
                                      </tr>

                                  </tbody>

                              </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- titulo de contenido  -->
    </div>
    <!-- row de Contenido -->

</div>
<!-- container -->


<?php  require ("construct/footer.html")   ?>
