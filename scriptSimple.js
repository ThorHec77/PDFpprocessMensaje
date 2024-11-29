const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({
        product: 'firefox',
        headless: false,
        executablePath: 'C:\\Program Files\\Mozilla Firefox\\firefox.exe', // Ruta al ejecutable de Firefox
        args: [
            '--profile',
            'C:\\Users\\hector\\AppData\\Roaming\\Mozilla\\Firefox\\Profiles\\4ni9nojm.default' // Ruta al perfil `Default`
        ],
    });

    const page = await browser.newPage();

    // Modificar el `navigator.webdriver` y otras propiedades para evitar detección
    await page.evaluateOnNewDocument(() => {
        Object.defineProperty(navigator, 'webdriver', {
            get: () => false, // Configura `navigator.webdriver` como `false`
        });

        // Opcional: modifica otras propiedades para evitar detecciones avanzadas
        Object.defineProperty(navigator, 'languages', {
            get: () => ['es-ES', 'en-US'],
        });
        Object.defineProperty(navigator, 'platform', {
            get: () => 'Win32',
        });
        Object.defineProperty(navigator, 'plugins', {
            get: () => [1, 2, 3],
        });
    });

    await page.goto('https://google.com.uy');
    console.log('Firefox lanzado con el perfil Default sin indicar automatización');
    // await browser.close(); // Descomentar para cerrar el navegador al finalizar
})();
