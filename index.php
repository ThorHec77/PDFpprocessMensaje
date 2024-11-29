<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XLSX para SMS</title>
    <link rel="icon" href="/imagenes/mensajes.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=UnifrakturMaguntia&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d47a1;">
        <div class="container-fluid">
            <a class="navbar-brand" href="https://hospitaldecanelones.com/">
                <img src="/imagenes/logo-nuevo-hc.png" width="120" height="38" alt="Hospital de Canelones" class="img-fluid" style="border: 1px solid #fff; background-color: #fff; border-radius: 5px;">
            </a>
            <a class="navbar-brand" href="https://www.asse.com.uy/home">
                <img src="/imagenes/asse_capacita.png" width="150" height="38" alt="ASSE Capacita" class="img-fluid" style="border: 1px solid #fff; background-color: #fff; border-radius: 5px;">
            </a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Menú lateral izquierdo -->
            <div class="col-md-3 sidebar">
                <nav class="nav flex-column p-3">
                    <img src="imagenes/mensajes.png" width="150" height="auto" alt="">
                    <a class="nav-link active" href="#form1" onclick="mostrarFormulario(1)">Aviso de consulta</a>
                    <a class="nav-link" href="#form2" onclick="mostrarFormulario(2)">Re agenda de pacientes</a>
                    <a class="nav-link" href="#form3" onclick="mostrarFormulario(3)">Cancelación de consulta</a>
                </nav>
            </div>

            <!-- Contenido de los formularios -->
            <div class="col-md-9">
            <!-- Formulario 1: Aviso de consulta -->
            <div id="form1" class="formulario">
                <div class="container">
                    <h5 class="text-center titulo-formulario">Cargar para realizar avisos de consultas</h5>
                    <form id="formAviso" action="proceso.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                                <label for="pdfFiles" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="hidden" name="action" value="aviso_consulta">
                                <input type="file" class="form-control" id="pdfFiles" name="pdfFiles[]" accept="application/pdf" multiple required>
                            </div>
                            <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione la fecha para enviar el mensaje.</strong></p>
                                <input type="date" class="form-control" id="fecha" name="fecha">
                            </div>
                            <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione hora en la que desea enviar el mensaje.</strong></p>
                                <input type="time" class="form-control" id="hora" name="hora">
                            </div>
                        <button type="button submit" class="btn btn-primary" >Procesar</button>   <!--onclick="abrirModalAviso('modalAviso')"-->
                        
                    </form>
                    <div class="mensaje-ejemplo mt-3">
                        <h5 class="text-center">Mensaje: AVISO DE CONSULTA COORDINADA</h5>
                        <p class="text-center">
                            "Sr/a [<strong>NOMBRE</strong>] el día [<strong>FECHA</strong>] tiene consulta con el/la Dr/a [<strong>PROFESIONAL</strong>] a la hora [<strong>HORA</strong>] con el Nº [<strong>NUMERO</strong>]
                            En el caso de no poder concurrir deberá comunicarse al teléfono 4332-3296"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario 2: Reagenda de pacientes -->
            <div id="form2" class="formulario" style="display: none;">
                <div class="container">
                    <h5 class="text-center titulo-formulario">Re-agenda de pacientes</h5>
                    <form id="formReagenda" action="proceso.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="pdfFilesReagenda" class="form-label">Seleccionar Archivo PDF</label>
                            <input type="hidden" name="action" value="reagenda_pacientes">
                            <input type="file" class="form-control" id="pdfFilesReagenda" name="pdfFiles[]" accept="application/pdf" multiple required>
                        </div>
                        <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione la fecha para enviar el mensaje.</strong></p>
                                <input type="date" class="form-control" id="fecha" name="fecha">
                            </div>
                            <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione hora en la que desea enviar el mensaje.</strong></p>
                                <input type="time" class="form-control" id="hora" name="hora">
                            </div>
                            <div class="mb-3">
                                <label for="nombreMedico" class="form-label">Nombre del Médico</label>
                                <input type="text" class="form-control" id="nombreMedico" name="nombreMedico" required>
                            </div>
                            <div class="mb-3">
                                <label for="nuevaFecha" class="form-label">Fecha de reagenda</label>
                                <input type="date" class="form-control" id="nuevaFecha" name="nuevaFecha" required>
                            </div>
                            <div class="mb-3">
                                <label for="nuevoHorario" class="form-label">Horario de reagenda</label>
                                <input type="time" class="form-control" id="nuevoHorario" name="nuevoHorario" required>
                            </div>
                        <button type="button submit" class="btn btn-primary">Procesar</button>  <!--onclick="abrirModal('modalReagenda')"-->
                        
                    </form>
                    <div class="mensaje-ejemplo mt-3">
                        <h5 class="text-center">Mensaje: AVISO DE RE-AGENDA DE CONSULTA</h5>
                        <p class="text-center">
                            "Sr/a [<strong>NOMBRE</strong>] su consulta del día [<strong>FECHA</strong>] con el/la Dr./a [<strong>PROFESIONAL</strong>] ha sido cambiada para el día [<strong>NUEVA-FECHA</strong>] 
                            a la hora [<strong>NUEVA-HORA</strong>] con el Dr./a [<strong>NEVO-PROFESIONAL</strong>] En el caso de no poder concurrir deberá comunicarse al teléfono 4332-3296"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulario 3: Cancelación de consulta -->
            <div id="form3" class="formulario" style="display: none;">
                <div class="container">
                    <h5 class="text-center titulo-formulario">Cancelación de consulta</h5>
                    <form id="formCancelacion" action="proceso.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="pdfFilesCancelacion" class="form-label">Seleccionar Archivo PDF</label>
                            <input type="hidden" name="action" value="cancelacion_consulta">
                            <input type="file" class="form-control" id="pdfFilesCancelacion" name="pdfFiles[]" accept="application/pdf" multiple required>
                        </div>
                        <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione la fecha para enviar el mensaje.</strong></p>
                                <input type="date" class="form-control" id="fecha" name="fecha">
                            </div>
                            <div class="mb-3">
                                <p class="text-danger"><strong>Seleccione hora en la que desea enviar el mensaje.</strong></p>
                                <input type="time" class="form-control" id="hora" name="hora">
                            </div>
                        <button type="button submit" class="btn btn-primary">Procesar</button> <!--onclick="abrirModal('modalCancelacion')"-->
                    </form>
                    <div class="mensaje-ejemplo mt-3">
                        <h5 class="text-center">Mensaje: CANCELACIÓN DE CONSULTA</h5>
                        <p class="text-center">
                            "Sr./a [<strong>NOMBRE</strong>] la consulta del día [<strong>FECHA</strong>] con el Dr./a [<strong>PROFESIONAL</strong>] ha sido cancelada, se le notificará la nueva fecha asignada."
                        </p>
                    </div>
                </div>
            </div>



    <!-- Modal de confirmación -->
    <div id="modalAviso" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #0d47a1;">
                <img src="imagenes/mensajes.png" width="100" alt="Icono de mensaje" class="me-2">
                <h5 class="modal-title">Aviso de Consulta</h5>
            </div>
            <div class="modal-body text-center">
                <h5>Recordatorio</h5>
                <ul id="listaPacientes" class="list-group">
                    <!-- Aquí se llenarán los datos extraídos -->
                </ul>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button class="btn btn-danger" onclick="cerrarModal('modalAviso')">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarEnvio('formAviso')">Confirmar</button>
            </div>
        </div>
    </div>


    <div id="modalReagenda" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #0d47a1;">
                <img src="imagenes/mensajes.png" width="100" alt="Icono de mensaje" class="me-2">
                <h5 class="modal-title">Reagenda de Pacientes</h5>
            </div>
            <div class="modal-body text-center">
                <h5>Reagenda</h5>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button class="btn btn-danger" onclick="cerrarModal('modalReagenda')">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarEnvio('formReagenda')">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="modalCancelacion" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #0d47a1;">
                <img src="imagenes/mensajes.png" width="100" alt="Icono de mensaje" class="me-2">
                <h5 class="modal-title">Cancelación de Consulta</h5>
            </div>
            <div class="modal-body text-center">
                <h5>Cancelación</h5>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button class="btn btn-danger" onclick="cerrarModal('modalCancelacion')">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarEnvio('formCancelacion')">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Mensaje de ejemplo -->
        <div class="text-center mb-4">
            <p>Ejemplo de respuesta automatica</p>
        </div>

        <!-- Contenedor de los dos divs -->
        <div class="row">
            <!-- Div del lado izquierdo con el mensaje de respuesta automática -->
            <div class="col-md-6 d-flex align-items-center">
                <div>
                    <p class="text-center bg-light p-3 border rounded" style="background-color: #d4f1f9; border-radius: 5px;">
                        "Por este medio no se recibirán respuestas. Si desea comunicarse, por favor hágalo llamando al teléfono 4332-3296. Gracias."
                    </p>
                </div>
            </div>

            <!-- Div del lado derecho con la imagen centrada -->
            <div class="col-md-6 d-flex justify-content-center align-items-center">
                <a href="https://portal.gruporyd.net" target="_blank">
                    <img src="/imagenes/logo-ryd.png" alt="Portal Grupo RYD" class="img-fluid rounded">
                </a>
            </div>
        </div>
    </div>

    <!-- Cargar jQuery desde CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script>
    <script src="script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>