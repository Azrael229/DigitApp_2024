<?php
require ("conexion.php");

$id = $_GET['id_coti'] ?? '';
$pdfPath = '';
$status = 'ok';
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../tablaCotizaciones.php';

if ($id === '') {
    mysqli_close($conexion);
    header('Location: ' . $redirectUrl . '?delete_status=missing_id');
    exit;
}

$sqlArchivo = "SELECT cot_archivo FROM cotizaciones WHERE id_coti = '$id' LIMIT 1";
$resultArchivo = mysqli_query($conexion, $sqlArchivo);

if ($resultArchivo && mysqli_num_rows($resultArchivo) > 0) {
    $rowArchivo = mysqli_fetch_assoc($resultArchivo);
    $archivoPdf = trim((string) ($rowArchivo['cot_archivo'] ?? ''));

    if ($archivoPdf !== '') {
        $basePdfDirectory = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'filesPDF');
        $candidatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'filesPDF' . DIRECTORY_SEPARATOR . basename($archivoPdf);
        $resolvedCandidatePath = realpath($candidatePath);

        if (
            $basePdfDirectory !== false &&
            $resolvedCandidatePath !== false &&
            str_starts_with($resolvedCandidatePath, $basePdfDirectory . DIRECTORY_SEPARATOR) &&
            is_file($resolvedCandidatePath)
        ) {
            $pdfPath = $resolvedCandidatePath;
        }
    }
} elseif ($resultArchivo === false) {
    $status = 'query_error';
}

if ($status === 'ok' && $pdfPath !== '' && !unlink($pdfPath)) {
    $status = 'pdf_error';
}

if ($status === 'ok') {
    $sqlDelete = "DELETE FROM cotizaciones WHERE id_coti = '$id'";

    if (!mysqli_query($conexion, $sqlDelete)) {
        $status = 'delete_error';
    }
}

mysqli_close($conexion);

header('Location: ' . $redirectUrl . '?delete_status=' . $status);
exit;
