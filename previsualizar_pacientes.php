<?php

require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;

function extraerTextoDelPdf($filePath) {
    $parser = new Parser();
    $pdfParsed = $parser->parseFile($filePath);
    return mb_convert_encoding($pdfParsed->getText(), 'UTF-8', 'auto');
}

function procesarTexto($texto) {
    $pacientes = [];

    // Expresión regular para capturar nombres y números de teléfono
    preg_match_all('/(\d+)\s*([A-ZÁÉÍÓÚÑ\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $nombre = trim($match[2]);
        $telefono1 = $match[3];
        $telefono2 = isset($match[4]) ? $match[4] : null;

        // Agregar nombre y teléfono al array de pacientes
        $pacientes[] = [
            'nombre' => $nombre,
            'telefono' => $telefono1
            
        ];
    }

    return $pacientes;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $todosLosPacientes = [];
    foreach ($archivosPdf as $rutaPdf) {
        $texto = extraerTextoDelPdf($rutaPdf);
        $pacientes = procesarTexto($texto);
        $todosLosPacientes = array_merge($todosLosPacientes, $pacientes);
    }

    // Responder con nombres y teléfonos en formato JSON
    echo json_encode([
        'success' => true,
        'pacientes' => $todosLosPacientes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'pacientes' => []
    ]);
}

?>

