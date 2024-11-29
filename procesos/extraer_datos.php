<?php

require __DIR__ . '/../vendor/autoload.php';


use Smalot\PdfParser\Parser;

function extraerTextoDelPdf($filePath) {
    $parser = new Parser();
    $pdfParsed = $parser->parseFile($filePath);
    return mb_convert_encoding($pdfParsed->getText(), 'UTF-8', 'auto');
}

function extraerPacientes($texto) {
    $pacientes = [];

    // Expresión regular para extraer nombres y números de teléfono
    preg_match_all(
        '/(\d+)\s*([\p{L}\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/',
        $texto,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $nombre = trim($match[2]);

        // Verificar teléfonos
        $telefonos = [];
        if (preg_match('/^09\d{7,8}$/', trim($match[3]))) {
            $telefonos[] = trim($match[3]);
        }
        if (isset($match[4]) && preg_match('/^09\d{7,8}$/', trim($match[4]))) {
            $telefonos[] = trim($match[4]);
        }

        // Agregar paciente si tiene al menos un número de teléfono válido
        if (!empty($telefonos)) {
            $pacientes[] = [
                'nombre' => $nombre,
                'telefono' => array_unique($telefonos)[0], // Tomar un único número válido
            ];
        }
    }

    return $pacientes;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $directorioSubida = 'uploads/';
    if (!is_dir($directorioSubida)) {
        mkdir($directorioSubida, 0777, true);
    }

    $respuesta = [];

    // Procesar el archivo PDF subido
    foreach ($_FILES['pdfFiles']['tmp_name'] as $key => $tmpName) {
        $nombreArchivo = basename($_FILES['pdfFiles']['name'][$key]);
        $rutaArchivo = $directorioSubida . $nombreArchivo;
        if (move_uploaded_file($tmpName, $rutaArchivo)) {
            $texto = extraerTextoDelPdf($rutaArchivo);
            $pacientes = extraerPacientes($texto);
            $respuesta = array_merge($respuesta, $pacientes);
        }
    }

    // Devolver los datos en formato JSON
    header('Content-Type: application/json');
    echo json_encode($respuesta);

    // Eliminar los archivos subidos
    foreach (glob($directorioSubida . '*') as $archivo) {
        unlink($archivo);
    }

    exit;
}
