<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Smalot\PdfParser\Parser;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Extraer acción desde el formulario
        $action = $_POST['action'] ?? '';



        // Procesar la acción enviada
        switch ($action) {
            case 'aviso_consulta':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                    function extraerTextoDelPdf($filePath) {
                        $parser = new Parser();
                        $pdfParsed = $parser->parseFile($filePath);
                        return mb_convert_encoding($pdfParsed->getText(), 'UTF-8', 'auto');
                    }
                    
                    function procesarTexto($texto, $fechaPost, $horaPost) {
                        $pacientes = [];
                        $nomMedico = '';
                        $diaPdf = ''; // Fecha extraída del PDF
                        $horaInicioPdf = ''; // Hora extraída del PDF
                    
                        // Verificar si la fecha y hora del POST están disponibles y formatearlas
                        $fechaPostCompleta = '';
                        if ($fechaPost && $horaPost) {
                            $fechaDateTime = DateTime::createFromFormat('Y-m-d H:i', "$fechaPost $horaPost");
                            $fechaPostCompleta = $fechaDateTime ? $fechaDateTime->format('d/m/Y H:i') : ''; // Formatear a "d/m/Y H:i"
                        }
                    
                        // Extraer nombre del médico
                        if (preg_match('/Profesional:\s*([\p{L}\s]+)(?=\s+Especialidad)/', $texto, $medico_match)) {
                            $nomMedico = trim($medico_match[1]);
                        }
                    
                        // Extraer horario de inicio desde el PDF
                        if (preg_match('/Horario Atención:\s*([\d]{2}:[\d]{2})/', $texto, $hora_match)) {
                            $horaInicioPdf = $hora_match[1];
                        }
                    
                        // Extraer el día desde el PDF
                        if (preg_match('/Día:\s*([\d]{2}\/[\d]{2}\/[\d]{4})/', $texto, $dia_match)) {
                            $diaPdf = $dia_match[1];
                        }
                    
                        // Extraer pacientes usando la expresión regular
                        preg_match_all('/(\d+)\s*([\p{L}\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                        //preg_match_all('/(\d+)\s*([A-ZÁÉÍÓÚÑ\s]+(?:\s+[A-ZÁÉÍÓÚÑ\s]+)*)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                        //preg_match_all('/(\d+)\s*([\p{Lu}\s]+(?:\s+[\p{Lu}\s]+)*)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                    
                    
                    
                        foreach ($matches as $match) {
                            $nombre = trim($match[2]);
                            $numero = $match[1];
                    
                            // Verificar teléfonos
                            $telefonos = [];
                            if (preg_match('/^09\d{7,8}$/', trim($match[3]))) {
                                $telefonos[] = trim($match[3]);
                            }
                            if (isset($match[4]) && preg_match('/^09\d{7,8}$/', trim($match[4]))) {
                                $telefonos[] = trim($match[4]);
                            }
                    
                            // Solo agregar el paciente si hay teléfonos válidos y únicos
                            if (!empty($telefonos)) {
                                $telefonoUnico = array_unique($telefonos)[0]; // Tomar solo un número único
                    
                                $pacientes[] = [
                                    'Destino' => $telefonoUnico,
                                    'NombrePlantilla' => 'recordatorio_consulta',
                                    'Fecha' => $fechaPostCompleta, // Fecha y hora recibidas por POST en formato "d/m/Y H:i"
                                    'AdjuntoPlantilla' => '', // Dejar vacío como en el ejemplo
                                    'CampoPlantilla1' => $nombre, // Nombre del paciente
                                    'CampoPlantilla2' => $diaPdf, // Fecha extraída del PDF
                                    'CampoPlantilla3' => $nomMedico, // Nombre del médico
                                    'CampoPlantilla4' => $horaInicioPdf, // Hora extraída del PDF
                                    'CampoPlantilla5' => "Nº $numero" // Número del paciente
                                ];
                            }
                        }
                    
                        return $pacientes;
                    }

                    ob_start(); // Iniciar el buffer de salida
                
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
                
                    // Recibir fecha y hora desde el formulario POST
                    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : ''; // Si no se recibe, dejar vacío
                    $hora = isset($_POST['hora']) ? $_POST['hora'] : ''; // Si no se recibe, dejar vacío
                
                    $todosLosPacientes = [];
                
                    foreach ($archivosPdf as $rutaPdf) {
                        $texto = extraerTextoDelPdf($rutaPdf);
                        $pacientes = procesarTexto($texto, $fecha, $hora);
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
                        $fila++;
                    }
                
                    $nombreArchivoExcel = 'PlantillaAvisoWhatsApp.xls';
                    $rutaArchivoExcel = $directorioSubida . $nombreArchivoExcel;
                
                    $writer = new Xls($spreadsheet); // Cambiado a Xls para generar archivo en formato .xls
                    $writer->save($rutaArchivoExcel);
                
                    ob_end_clean(); // Limpiar el buffer de salida
                
                    // Enviar el archivo Excel para descargar
                    header('Content-Type: application/vnd.ms-excel'); // Cambiado para .xls
                    header('Content-Disposition: attachment; filename="' . $nombreArchivoExcel . '"');
                    header('Content-Length: ' . filesize($rutaArchivoExcel));
                    readfile($rutaArchivoExcel);
                
                    // Función para eliminar todos los archivos en un directorio
                    function eliminarArchivosEnDirectorio($directorio) {
                        $archivos = glob($directorio . '/*'); // Obtener todos los archivos en el directorio
                        foreach ($archivos as $archivo) {
                            if (is_file($archivo)) {
                                unlink($archivo); // Eliminar el archivo
                            }
                        }
                    }
                
                    // Llamar a la función para eliminar los archivos después de la descarga
                    eliminarArchivosEnDirectorio($directorioSubida);
                
                    exit; // Salir del script después de la descarga
                }
                case 'reagenda_pacientes':

                            // Función para procesar texto de PDF y extraer información
                    function procesarTexto($texto, $fechaPost, $horaPost, $nombreMedico = '', $nuevaFecha = '', $nuevoHorario = '') {
                        $pacientes = [];
                        $nomMedico = $nombreMedico;
                        $diaPdf = '';
                        $horaInicioPdf = '';
                        $fechaPostCompleta = '';

                        if ($fechaPost && $horaPost) {
                            $fechaDateTime = DateTime::createFromFormat('Y-m-d H:i', "$fechaPost $horaPost");
                            $fechaPostCompleta = $fechaDateTime ? $fechaDateTime->format('d/m/Y H:i') : '';
                        }

                        // Extraer datos con expresiones regulares
                        if (preg_match('/Profesional:\s*([\p{L}\s]+)(?=\s+Especialidad)/', $texto, $medico_match)) {
                            $nomMedico = trim($medico_match[1]);
                        }

                        if (preg_match('/Horario Atención:\s*([\d]{2}:[\d]{2})/', $texto, $hora_match)) {
                            $horaInicioPdf = $hora_match[1];
                        }

                        if (preg_match('/Día:\s*([\d]{2}\/[\d]{2}\/[\d]{4})/', $texto, $dia_match)) {
                            $diaPdf = $dia_match[1];
                        }

                        preg_match_all('/(\d+)\s*([\p{L}\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);

                        foreach ($matches as $match) {
                            $nombre = trim($match[2]);
                            $numero = $match[1];
                            $telefonos = [];

                            if (preg_match('/^09\d{7}$/', trim($match[3]))) {
                                $telefonos[] = trim($match[3]);
                            }
                            if (isset($match[4]) && preg_match('/^09\d{7}$/', trim($match[4]))) {
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
                                    'CampoPlantilla4' => $nuevaFecha,
                                    'CampoPlantilla5' => $nuevoHorario,
                                    'CampoPlantilla6' => $nombreMedico
                                ];
                            }
                        }

                        return $pacientes;
                    }

                    // Función para extraer texto de un archivo PDF
                    function extraerTextoDelPdf($rutaPdf) {
                        $parser = new Parser();
                        $pdf = $parser->parseFile($rutaPdf);
                        return $pdf->getText();
                    }

                    ob_start();
                
                    $directorioSubida = 'uploads/';
                    if (!is_dir($directorioSubida)) {
                        mkdir($directorioSubida, 0777, true);
                    }
                
                    // Subir archivos PDF
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
                
                    // Crear el archivo Excel
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
                    // Se eliminan las columnas H e I
                    $hoja->setCellValue('H1', 'CampoPlantilla4');  // Nueva columna para 'nuevaFecha'
                    $hoja->setCellValue('I1', 'CampoPlantilla5');  // Nueva columna para 'nuevoHorario'
                    $hoja->setCellValue('J1', 'CampoPlantilla6');  // Nueva columna para 'nombreMedico'
                
                    // Rellenar las filas con los datos de los pacientes
                    $fila = 2;
                    foreach ($todosLosPacientes as $paciente) {
                        $nombrePlantilla = isset($paciente['reagenda_pacientes']) ? $paciente['reagenda_pacientes'] : 'Valor Predeterminado';
                
                        $hoja->setCellValue('A' . $fila, $paciente['Destino']);
                        $hoja->setCellValue('B' . $fila, $paciente['NombrePlantilla']); // Asignar el valor de 'reagenda_pacientes' o el valor predeterminado
                        $hoja->setCellValue('C' . $fila, $paciente['Fecha']);
                        $hoja->setCellValue('D' . $fila, $paciente['AdjuntoPlantilla']);
                        $hoja->setCellValue('E' . $fila, $paciente['CampoPlantilla1']);
                        $hoja->setCellValue('F' . $fila, $paciente['CampoPlantilla2']);
                        $hoja->setCellValue('G' . $fila, $paciente['CampoPlantilla3']);
                        $hoja->setCellValue('H' . $fila, $paciente['CampoPlantilla4']);
                        $hoja->setCellValue('I' . $fila, $paciente['CampoPlantilla5']);
                        $hoja->setCellValue('J' . $fila, $paciente['CampoPlantilla6']);
                        $fila++;
                    }
                
                    $nombreArchivoExcel = 'PlantillaWhatsReagenda.xls';
                    $rutaArchivoExcel = $directorioSubida . $nombreArchivoExcel;
                
                    $writer = new Xls($spreadsheet); // Cambiado a Xls para generar archivo en formato .xls
                    $writer->save($rutaArchivoExcel);
                
                    ob_end_clean(); // Limpiar el buffer de salida
                
                    // Enviar el archivo Excel para descargar
                    header('Content-Type: application/vnd.ms-excel'); // Cambiado para .xls
                    header('Content-Disposition: attachment; filename="' . $nombreArchivoExcel . '"');
                    header('Content-Length: ' . filesize($rutaArchivoExcel));
                    readfile($rutaArchivoExcel);
                
                    // Función para eliminar todos los archivos en un directorio
                    function eliminarArchivosEnDirectorio($directorio) {
                        $archivos = glob($directorio . '/*'); // Obtener todos los archivos en el directorio
                        foreach ($archivos as $archivo) {
                            if (is_file($archivo)) {
                                unlink($archivo); // Eliminar el archivo
                            }
                        }
                    }
                
                    // Llamar a la función para eliminar los archivos después de la descarga
                    eliminarArchivosEnDirectorio($directorioSubida);
                
                    exit; // Salir del script después de la descarga
                


            case 'cancelacion_consulta':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                    function extraerTextoDelPdf($filePath) {
                        $parser = new Parser();
                        $pdfParsed = $parser->parseFile($filePath);
                        return mb_convert_encoding($pdfParsed->getText(), 'UTF-8', 'auto');
                    }
                    
                    function procesarTexto($texto, $fechaPost, $horaPost) {
                        $pacientes = [];
                        $nomMedico = '';
                        $diaPdf = ''; // Fecha extraída del PDF
                        $horaInicioPdf = ''; // Hora extraída del PDF
                    
                        // Verificar si la fecha y hora del POST están disponibles y formatearlas
                        $fechaPostCompleta = '';
                        if ($fechaPost && $horaPost) {
                            $fechaDateTime = DateTime::createFromFormat('Y-m-d H:i', "$fechaPost $horaPost");
                            $fechaPostCompleta = $fechaDateTime ? $fechaDateTime->format('d/m/Y H:i') : ''; // Formatear a "d/m/Y H:i"
                        }
                    
                        // Extraer nombre del médico
                        if (preg_match('/Profesional:\s*([\p{L}\s]+)(?=\s+Especialidad)/', $texto, $medico_match)) {
                            $nomMedico = trim($medico_match[1]);
                        }
                    
                        // Extraer horario de inicio desde el PDF
                        if (preg_match('/Horario Atención:\s*([\d]{2}:[\d]{2})/', $texto, $hora_match)) {
                            $horaInicioPdf = $hora_match[1];
                        }
                    
                        // Extraer el día desde el PDF
                        if (preg_match('/Día:\s*([\d]{2}\/[\d]{2}\/[\d]{4})/', $texto, $dia_match)) {
                            $diaPdf = $dia_match[1];
                        }
                    
                        // Extraer pacientes usando la expresión regular
                        preg_match_all('/(\d+)\s*([\p{L}\s]+)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                        //preg_match_all('/(\d+)\s*([A-ZÁÉÍÓÚÑ\s]+(?:\s+[A-ZÁÉÍÓÚÑ\s]+)*)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                        //preg_match_all('/(\d+)\s*([\p{Lu}\s]+(?:\s+[\p{Lu}\s]+)*)\s+\d{1,2}\.\d{1,3}\.\d{1,3}-\d\s*[MF]\s*\d+\s*(?:a ?Tel|Tel):\s*(\d{8,9})\s*\/?\s*(\d{8,9})?/', $texto, $matches, PREG_SET_ORDER);
                    
                    
                    
                        foreach ($matches as $match) {
                            $nombre = trim($match[2]);
                            $numero = $match[1];
                    
                            // Verificar teléfonos
                            $telefonos = [];
                            if (preg_match('/^09\d{7,8}$/', trim($match[3]))) {
                                $telefonos[] = trim($match[3]);
                            }
                            if (isset($match[4]) && preg_match('/^09\d{7,8}$/', trim($match[4]))) {
                                $telefonos[] = trim($match[4]);
                            }
                    
                            // Solo agregar el paciente si hay teléfonos válidos y únicos
                            if (!empty($telefonos)) {
                                $telefonoUnico = array_unique($telefonos)[0]; // Tomar solo un número único
                    
                                $pacientes[] = [
                                    'Destino' => $telefonoUnico,
                                    'NombrePlantilla' => 'cancelacion_consulta',
                                    'Fecha' => $fechaPostCompleta, // Fecha y hora recibidas por POST en formato "d/m/Y H:i"
                                    'AdjuntoPlantilla' => '', // Dejar vacío como en el ejemplo
                                    'CampoPlantilla1' => $diaPdf, // Fecha extraída del PDF
                                    'CampoPlantilla2' => $nomMedico, // Nombre del médico
                                ];
                            }
                        }
                    
                        return $pacientes;
                    }

                    ob_start(); // Iniciar el buffer de salida
                
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
                        
                        // Obtener fecha y hora desde el formulario (si existen)
                        $fechaPost = $_POST['fecha'] ?? null;
                        $horaPost = $_POST['hora'] ?? null;
                    
                        // Llamar a procesarTexto con los argumentos correctos
                        $pacientes = procesarTexto($texto, $fechaPost, $horaPost);
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
                
                    // Rellenar las filas con los datos de los pacientes
                    $fila = 2;
                    foreach ($todosLosPacientes as $paciente) {
                        $nombrePlantilla = isset($paciente['cancelacion_consulta']) ? $paciente['cancelacion_consulta'] : 'Valor Predeterminado';

                        $hoja->setCellValue('A' . $fila, $paciente['Destino']);
                        $hoja->setCellValue('B' . $fila, $paciente['NombrePlantilla']);
                        $hoja->setCellValue('C' . $fila, $paciente['Fecha']);
                        $hoja->setCellValue('D' . $fila, $paciente['AdjuntoPlantilla']);
                        $hoja->setCellValue('E' . $fila, $paciente['CampoPlantilla1']);
                        $hoja->setCellValue('F' . $fila, $paciente['CampoPlantilla2']);

                        $fila++;
                    }
                
                    $nombreArchivoExcel = 'PlantillaancelWhatsApp.xls';
                    $rutaArchivoExcel = $directorioSubida . $nombreArchivoExcel;
                
                    $writer = new Xls($spreadsheet); // Generar archivo en formato .xls
                    $writer->save($rutaArchivoExcel);
                
                    ob_end_clean(); // Limpiar el buffer de salida
                
                    // Enviar el archivo Excel para descargar
                    header('Content-Type: application/vnd.ms-excel'); // Cambiado para .xls
                    header('Content-Disposition: attachment; filename="' . $nombreArchivoExcel . '"');
                    header('Content-Length: ' . filesize($rutaArchivoExcel));
                    readfile($rutaArchivoExcel);
                
                    // Función para eliminar todos los archivos en un directorio
                    function eliminarArchivosEnDirectorio($directorio) {
                        $archivos = glob($directorio . '/*'); // Obtener todos los archivos en el directorio
                        foreach ($archivos as $archivo) {
                            if (is_file($archivo)) {
                                unlink($archivo); // Eliminar el archivo
                            }
                        }
                    }
                
                    // Llamar a la función para eliminar los archivos después de la descarga
                    eliminarArchivosEnDirectorio($directorioSubida);
                
                    exit; // Salir del script después de la descarga
                }
                echo "Procesando cancelación de consulta.";
                break;


        }
    } else {
        echo "Método no permitido.";
    }
?>