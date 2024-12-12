// Función para cargar barra lateral y actualizar enlaces del menú
function mostrarFormulario(numFormulario) {
    // Cambiar los formularios
    document.querySelectorAll('.formulario').forEach((form, index) => {
        form.style.display = index + 1 === numFormulario ? 'block' : 'none';
    });

    // Actualizar la clase activa en el menú de navegación
    document.querySelectorAll('.nav-link').forEach((link, index) => {
        link.classList.toggle('active', index + 1 === numFormulario);
    });
}


function abrirModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function cerrarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmarEnvio(formId) {
    document.getElementById(formId).submit();
}

function abrirModalAviso() {
    // Formulario desde el cual se sube el archivo
    let formData = new FormData(document.getElementById('formAviso'));

    $.ajax({
        url: 'procesos/extraer_datos.php', // Archivo PHP que extrae datos del PDF
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (respuesta) {
            try {
                // Intentar analizar la respuesta como un array
                if (Array.isArray(respuesta)) {
                    // Limpiar la lista de pacientes antes de llenarla
                    $('#listaPacientes').empty();

                    if (respuesta.length > 0) {
                        respuesta.forEach(function (paciente) {
                            $('#listaPacientes').append(
                                `<li class="list-group-item">
                                    <strong>${paciente.nombre}</strong> - ${paciente.telefono}
                                </li>`
                            );
                        });

                        // Mostrar el modal
                        $('#modalAviso').fadeIn();
                    } else {
                        alert('No se encontraron datos en el PDF.');
                    }
                } else {
                    alert('La respuesta del servidor no es un array. Respuesta recibida: ' + JSON.stringify(respuesta));
                }
            } catch (e) {
                alert('Error al procesar la respuesta del servidor: ' + e.message);
            }
        },
        error: function () {
            alert('Error al procesar el archivo.');
        }
    });
}



// Cerrar el modal
function cerrarModal(idModal) {
    $(`#${idModal}`).fadeOut();
}


function confirmarEnvio() {
    // Seleccionar el formulario con id="formAviso"
    var form = $('#formAviso');

    // Enviar los datos del formulario por AJAX
    $.ajax({
        url: 'procesos/avisos.php', // Archivo PHP donde se procesan los datos
        type: 'POST',               // Método de envío POST
        data: form.serialize(),     // Serializar los datos del formulario
        success: function (respuesta, textStatus, xhr) {
            // Verificar si la respuesta es un archivo Excel
            var contentType = xhr.getResponseHeader('Content-Type');
            if (xhr.status === 200 && contentType === 'application/vnd.ms-excel') {
                // La respuesta es un Blob, así que lo asignamos a una variable
                var blob = xhr.response;
                
                // Verificar que el Blob no esté vacío
                if (blob) {
                    var url = window.URL.createObjectURL(blob); // Crear URL para el Blob
                    
                    // Crear un enlace para la descarga del archivo
                    var a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'PlantillaEnvioPersonalizadoWhatsApp.xls'; // Nombre del archivo
                    document.body.appendChild(a);
                    a.click(); // Iniciar la descarga
                    
                    // Limpiar
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Mostrar mensaje de éxito
                    alert('Envío confirmado: El archivo Excel fue descargado.');
                } else {
                    alert('Error: No se recibió un archivo válido.');
                }
            } else {
                alert('Error: El servidor no envió un archivo Excel.');
            }
        },
        error: function () {
            // Mostrar mensaje de error en caso de fallo
            alert('Error al confirmar el envío.');
        },
        xhrFields: {
            responseType: 'blob' // Esto es clave para recibir el archivo como Blob
        }
    });
}

function copiarTexto(elementId) {
    const texto = document.getElementById(elementId).textContent;

    navigator.clipboard.writeText(texto)
        .then(() => {
            mostrarNotificacion("Texto copiado al portapapeles.");
        })
        .catch(err => console.error("Error al copiar el texto:", err));
}

function mostrarNotificacion(mensaje) {
    const notificacion = document.getElementById("notificacion");
    notificacion.textContent = mensaje;
    notificacion.style.display = "block";

    // Ocultar la notificación después de 3 segundos
    setTimeout(() => {
        notificacion.style.display = "none";
    }, 3000);
}
