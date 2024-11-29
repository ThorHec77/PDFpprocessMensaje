<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Smalot\PdfParser\Parser;

function extraerTextoDelPdf($filePath) {
    $parser = new Parser();
    $pdfParsed = $parser->parseFile($filePath);
    return mb_convert_encoding($pdfParsed->getText(), 'UTF-8', 'auto');
}

function procesarTexto($texto, $fechaPost, $horaPost, $nombreMedico, $nuevaFecha, $nuevoHorario) {
    $pacientes = [];
    $nomMedico = '';
    $diaPdf = '';
    $horaInicioPdf = '';

    // Preparar fecha y hora completas para la plantilla
    $fechaPostCompleta = '';
    if ($fechaPost && $horaPost) {
        $fechaDateTime = DateTime::createFromFormat('Y-m-d H:i', "$fechaPost $horaPost");
        $fechaPostCompleta = $fechaDateTime ? $fechaDateTime->format('d/m/Y H:i') : '';
    }

    // Obtener datos del médico y la fecha del PDF
    if (preg_match('/Profesional:\s*([\p{L}\s]+)(?=\s+Especialidad)/', $texto, $medico_match)) {
        $nomMedico = trim($medico_match[1]);
    }

    if (preg_match('/Horario Atención:\s*([\d]{2}:[\d]{2})/', $texto, $hora_match)) {
        $horaInicioPdf = $hora_match[1];
    }

    if (preg_match('/Día:\s*([\d]{2}\/[\d]{2}\/[\d]{4})/', $texto, $dia_match)) {
        $diaPdf = $dia_match[1];
    }

    // Extraer los datos de pacientes del PDF
    preg_match_all('/(\d+)\s*([A-ZÁÉÍÓÚÑ\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $nombre = trim($match[2]);
        $numero = $match[1];
        $telefonos = [];
        if (preg_match('/^09\d{7,8}$/', trim($match[3]))) {
            $telefonos[] = trim($match[3]);
        }
        if (isset($match[4]) && preg_match('/^09\d{7,8}$/', trim($match[4]))) {
            $telefonos[] = trim($match[4]);
        }

        if (!empty($telefonos)) {
            $telefonoUnico = array_unique($telefonos)[0];

            $pacientes[] = [
                'Destino' => $telefonoUnico,
                'NombrePlantilla' => 'reagenda_consulta',
                'Fecha' => $fechaPostCompleta,
                'AdjuntoPlantilla' => '',
                'CampoPlantilla1' => $nombre,
                'CampoPlantilla2' => $diaPdf,
                'CampoPlantilla3' => $nomMedico,
                'CampoPlantilla4' => $horaInicioPdf,
                'CampoPlantilla5' => "Nº $numero",
                'CampoPlantilla6' => $nuevaFecha,
                'CampoPlantilla7' => $nombreMedico,
                'CampoPlantilla8' => $nuevoHorario
            ];
        }
    }

    return $pacientes;
}

// Procesamiento de archivos subidos y generación de Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();

    $directorioSubida = 'uploads/';
    if (!is_dir($directorioSubida)) {
        mkdir($directorioSubida, 0777, true);
    }

    $archivosPdf = [];
    foreach ($_FILES['pdfFiles']['tmp_name'] as $key => $tmpName) {
        $nombreArchivo = basename($_FILES['pdfFiles']['name'][$key]);
        $rutaArchivo = $directorioSubida . $nombreArchivo;
        if (move_uploaded_file($tmpName, $rutaArchivo)) {
            $archivosPdf[] = $rutaArchivo;
        }
    }

    // Parámetros adicionales
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $nombreMedico = $_POST['nombreMedico'] ?? '';
    $nuevaFecha = $_POST['nuevaFecha'] ?? '';
    $nuevoHorario = $_POST['nuevoHorario'] ?? '';

    $todosLosPacientes = [];
    foreach ($archivosPdf as $rutaPdf) {
        $texto = extraerTextoDelPdf($rutaPdf);
        $pacientes = procesarTexto($texto, $fecha, $hora, $nombreMedico, $nuevaFecha, $nuevoHorario);
        $todosLosPacientes = array_merge($todosLosPacientes, $pacientes);
    }

    $spreadsheet = new Spreadsheet();
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->setTitle('Pacientes');

    // Establecer los encabezados de las columnas
    $hoja->setCellValue('A1', 'Destino');
    $hoja->setCellValue('B1', 'NombrePlantilla');
    $hoja->setCellValue('C1', 'Fecha');
    $hoja->setCellValue('D1', 'AdjuntoPlantilla');
    $hoja->setCellValue('E1', 'CampoPlantilla1');
    $hoja->setCellValue('F1', 'CampoPlantilla2');
    $hoja->setCellValue('G1', 'CampoPlantilla3');
    $hoja->setCellValue('H1', 'CampoPlantilla4');
    $hoja->setCellValue('I1', 'CampoPlantilla5');
    $hoja->setCellValue('J1', 'CampoPlantilla6');
    $hoja->setCellValue('K1', 'CampoPlantilla7');
    $hoja->setCellValue('L1', 'CampoPlantilla8');

    // Rellenar las filas con los datos de los pacientes
    $fila = 2;
    foreach ($todosLosPacientes as $paciente) {
        $hoja->setCellValue('A' . $fila, $paciente['Destino']);
        $hoja->setCellValue('B' . $fila, $paciente['NombrePlantilla']);
        $hoja->setCellValue('C' . $fila, $paciente['Fecha']);
        $hoja->setCellValue('D' . $fila, $paciente['AdjuntoPlantilla']);
        $hoja->setCellValue('E' . $fila, $paciente['CampoPlantilla1']);
        $hoja->setCellValue('F' . $fila, $paciente['CampoPlantilla2']);
        $hoja->setCellValue('G' . $fila, $paciente['CampoPlantilla3']);
        $hoja->setCellValue('H' . $fila, $paciente['CampoPlantilla4']);
        $hoja->setCellValue('I' . $fila, $paciente['CampoPlantilla5']);
        $hoja->setCellValue('J' . $fila, $paciente['CampoPlantilla6']);
        $hoja->setCellValue('K' . $fila, $paciente['CampoPlantilla7']);
        $hoja->setCellValue('L' . $fila, $paciente['CampoPlantilla8']);
        $fila++;
    }

    $nombreArchivoExcel = 'ReagendaConsulta.xls';
    $rutaArchivoExcel = $directorioSubida . $nombreArchivoExcel;

    $writer = new Xls($spreadsheet);
    $writer->save($rutaArchivoExcel);

    ob_end_clean();

    // Enviar el archivo Excel para descargar
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $nombreArchivoExcel . '"');
    header('Content-Length: ' . filesize($rutaArchivoExcel));
    readfile($rutaArchivoExcel);

    // Eliminar los archivos subidos después de la descarga
    function eliminarArchivosEnDirectorio($directorio) {
        $archivos = glob($directorio . '/*');
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
    }

    eliminarArchivosEnDirectorio($directorioSubida);

    exit;
}
?>
