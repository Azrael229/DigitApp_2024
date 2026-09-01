<?php

require __DIR__ . '/../../config/conexion.php';

$consultaContactos = $conexion->prepare(
    'SELECT c.id, c.nombre, c.celular, c.correo, c.depto, c.id_empresa, c.id_departamento,
            COALESCE(d.nombre, c.depto) AS departamento, c.puesto, c.activo,
            relaciones.empresa_id, relaciones.empresa, COALESCE(relaciones.empresas_relacionadas, 0) AS empresas_relacionadas
     FROM contactos AS c
     LEFT JOIN catalogo_departamentos AS d ON d.id = c.id_departamento
     LEFT JOIN (
         SELECT ec.id_contacto,
                COALESCE(MAX(CASE WHEN ec.es_principal = 1 THEN e.id_e END), MIN(e.id_e)) AS empresa_id,
                COALESCE(MAX(CASE WHEN ec.es_principal = 1 THEN e.empresa END), MIN(e.empresa)) AS empresa,
                COUNT(*) AS empresas_relacionadas
         FROM empresa_contactos AS ec
         INNER JOIN empresas AS e ON e.id_e = ec.id_empresa
         WHERE ec.activo = 1
         GROUP BY ec.id_contacto
     ) AS relaciones ON relaciones.id_contacto = c.id
     ORDER BY c.nombre ASC, c.id ASC'
);
$consultaContactos->execute();
$result_contactos = $consultaContactos->get_result();
$consultaContactos->close();

// La pagina actual consume $result_contactos durante el renderizado.
mysqli_close($conexion);
?>
