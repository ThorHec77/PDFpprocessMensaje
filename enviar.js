const puppeteer = require('puppeteer'); // Importar Puppeteer para automatización
const path = require('path'); // Librería nativa de Node.js para manejar rutas

(async () => {
    // Ruta al perfil de Chrome donde ya estás logueado
    const rutaPerfilChrome = 'C:\\Users\\hector\\AppData\\Local\\Google\\Chrome\\User Data\\Default\\Profile 4';

    // Ruta absoluta del archivo que deseas subir
    const rutaArchivo = path.resolve('C:\\Users\\hector\\Downloads\\PlantillaWhatsReagenda(10).xls'); // Asegúrate de usar '\\' o '/'

    // URL del sitio
    const urlSubida = 'https://portal.gruporyd.net/Notificaciones/Personalizado'; 

    // Abrir navegador usando el perfil del usuario
    const navegador = await puppeteer.launch({
        headless: false, // Cambiar a `true` si no deseas que el navegador sea visible
        args: [`--user-data-dir=${rutaPerfilChrome}`], // Usar el perfil de usuario
    });

    // Crear una nueva pestaña
    const pagina = await navegador.newPage();
    await new Promise(resolve => setTimeout(resolve, 15000)); //espera 15 segundos 
    // Navegar a la URL especificada
    await pagina.goto(urlSubida); 

    try {
        // 1. Tildar el input #opcionEnvioRespuestaAutomatica
        await pagina.waitForSelector('#opcionEnvioRespuestaAutomatica'); // Esperar a que el selector esté disponible
        await pagina.click('#opcionEnvioRespuestaAutomatica'); // Hacer clic en la opción
        console.log("Opción de envío automático seleccionada.");

        // 2. Ingresar texto en el textarea #mensajeRespuestaAutomatica
        await pagina.waitForSelector('#mensajeRespuestaAutomatica'); // Esperar el campo de texto
        await pagina.type('#mensajeRespuestaAutomatica', 
            'Por este medio no se recibirán respuestas. Si desea comunicarse, por favor hágalo llamando al teléfono 4332-3296. Gracias.'
        ); // Escribir mensaje
        console.log("Mensaje ingresado en el campo de texto.");

        // 3. Hacer clic en el botón siguiente #btnbtnAvanzarPaso1
        await pagina.waitForSelector('#btnbtnAvanzarPaso1'); // Esperar el botón
        await pagina.click('#btnbtnAvanzarPaso1'); // Hacer clic
        console.log("Avanzando al paso 1.");

        // 4. Esperar el botón o campo de carga de archivo
        await pagina.waitForSelector('#ArchivoEnvio'); // Esperar el campo de archivo
        const [seleccionadorDeArchivo] = await Promise.all([
            pagina.waitForFileChooser(), // Esperar a que se abra el selector
            pagina.click('#ArchivoEnvio'), // Hacer clic en el botón
        ]);
        await seleccionadorDeArchivo.accept([rutaArchivo]); // Seleccionar el archivo
        console.log("Archivo cargado exitosamente.");

        // 5. Hacer clic en el botón siguiente #btnAvanzarPaso2
        await pagina.waitForSelector('#btnAvanzarPaso2'); // Esperar el botón
        await pagina.click('#btnAvanzarPaso2'); // Hacer clic
        console.log("Avanzando al paso 2.");

        // 6. Hacer clic en el botón siguiente #btnAvanzarPaso3
        await pagina.waitForSelector('#btnAvanzarPaso3'); // Esperar el botón
        await pagina.click('#btnAvanzarPaso3'); // Hacer clic
        console.log("Avanzando al paso 3.");

        // 7. Confirmar en el modal con el botón class="swa12-confirm styled"
        await pagina.waitForSelector('.swa12-confirm.styled'); // Esperar el modal
        await pagina.click('.swa12-confirm.styled'); // Confirmar
        console.log("Modal de confirmación aceptado. Mensajes enviados.");

    } catch (error) {
        // Manejar errores en cualquier paso
        console.error("Error durante la ejecución del script:", error);
    } finally {
        // Cerrar el navegador para liberar recursos
        //await navegador.close();
    }
})();

