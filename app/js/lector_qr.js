/*
 * Lector de QR por camara para las pantallas del operador.
 *
 * Usa dos lectores a la vez sobre cada cuadro del video:
 *   1. BarcodeDetector del navegador (rapido, pero en algunos celulares Android no llega a leer).
 *   2. jsQR (app/js/jsQR.js), una libreria propia que no depende del celular ni de Google Play.
 * Ademas ofrece linterna, zoom y leer una foto tomada con la camara normal.
 *
 * Uso:
 *   LectorQr.iniciar(video, { alTexto: async (texto) => true, alEstado: (mensaje, clase) => {}, ... });
 *   LectorQr.detener();
 * alTexto recibe el texto del QR; si devuelve true el lector se detiene, si devuelve false o falla
 * sigue buscando.
 */
const LectorQr = (() => {
    const MAX_LADO_VIDEO = 1280; // lado mayor con el que se analiza cada cuadro del video
    const MS_ENTRE_ANALISIS_JSQR = 200;
    const MS_AVISO_AYUDA = 6000;

    let stream = null;
    let activo = false;
    let procesando = false;
    let detectorNativo = null;
    let inicioBusqueda = 0;
    let ultimoJsQr = 0;
    let linternaEncendida = false;
    let opciones = null;
    let videoEl = null;
    let canvas = null;

    function estado(mensaje, clase) {
        if (opciones && opciones.alEstado) {
            opciones.alEstado(mensaje, clase || "text-muted");
        }
    }

    // Los QR de las remisiones traen los bytes tal cual: UTF-8 o, si hay una Ñ o acento sin
    // convertir, Latin-1 / Windows-1252. Se prueba UTF-8 estricto y si falla se usa Windows-1252.
    function bytesATexto(bytes) {
        try {
            return new TextDecoder("utf-8", { fatal: true }).decode(bytes);
        } catch (e) {
            return new TextDecoder("windows-1252").decode(bytes);
        }
    }

    function leerConJsQr(fuente, ancho, alto, maxLado) {
        if (typeof jsQR !== "function") {
            return null;
        }

        const escala = Math.min(1, maxLado / Math.max(ancho, alto));
        const w = Math.max(1, Math.round(ancho * escala));
        const h = Math.max(1, Math.round(alto * escala));

        canvas = canvas || document.createElement("canvas");
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext("2d", { willReadFrequently: true });
        ctx.drawImage(fuente, 0, 0, w, h);

        const imagen = ctx.getImageData(0, 0, w, h);
        const resultado = jsQR(imagen.data, w, h, { inversionAttempts: "attemptBoth" });
        if (!resultado) {
            return null;
        }

        const texto = resultado.binaryData && resultado.binaryData.length
            ? bytesATexto(new Uint8Array(resultado.binaryData))
            : resultado.data;
        return texto || null;
    }

    function nuevoDetectorNativo() {
        if (detectorNativo !== null) {
            return detectorNativo || null;
        }

        try {
            detectorNativo = "BarcodeDetector" in window
                ? new BarcodeDetector({ formats: ["qr_code"] })
                : false;
        } catch (e) {
            detectorNativo = false;
        }

        return detectorNativo || null;
    }

    // Devuelve el texto del primer QR que encuentre en la fuente (video o imagen) o null.
    async function buscarQr(fuente, ancho, alto, { usarJsQr, maxLado }) {
        const nativo = nuevoDetectorNativo();
        if (nativo) {
            try {
                const codigos = await nativo.detect(fuente);
                if (codigos.length > 0 && codigos[0].rawValue) {
                    return { texto: codigos[0].rawValue, lector: "nativo" };
                }
            } catch (e) {
                // Si el lector del navegador falla se sigue con jsQR.
            }
        }

        if (usarJsQr) {
            try {
                const texto = leerConJsQr(fuente, ancho, alto, maxLado);
                if (texto) {
                    return { texto, lector: "jsQR" };
                }
            } catch (e) {
                console.warn("jsQR fallo", e);
            }
        }

        return null;
    }

    // Entrega el texto a la pantalla; devuelve true si lo acepto.
    async function entregar(texto, lector) {
        procesando = true;
        try {
            console.info("QR leido con " + lector);
            return !!(await opciones.alTexto(texto, lector));
        } catch (error) {
            estado("Se leyo el QR pero no se pudo procesar: " + (error && error.message ? error.message : "intente de nuevo"), "text-danger");
            return false;
        } finally {
            procesando = false;
        }
    }

    function mensajeAyuda() {
        return "No se logra leer. Aleje el celular unos 20 a 30 cm, evite reflejos y sombras sobre el QR, " +
            "use la linterna o toque \"Tomar foto del QR\".";
    }

    async function ciclo() {
        if (!activo) {
            return;
        }

        if (!procesando && videoEl.readyState >= 2 && videoEl.videoWidth > 0) {
            const ahora = performance.now();
            const toca = ahora - ultimoJsQr >= MS_ENTRE_ANALISIS_JSQR;
            if (toca) {
                ultimoJsQr = ahora;
            }

            const hallazgo = await buscarQr(videoEl, videoEl.videoWidth, videoEl.videoHeight, {
                usarJsQr: toca,
                maxLado: MAX_LADO_VIDEO
            });

            if (hallazgo && activo) {
                const aceptado = await entregar(hallazgo.texto, hallazgo.lector);
                if (aceptado) {
                    return;
                }
                // Evita releer el mismo QR sin parar si la pantalla lo rechazo.
                await new Promise((r) => setTimeout(r, 1500));
                inicioBusqueda = performance.now();
            } else if (performance.now() - inicioBusqueda > MS_AVISO_AYUDA) {
                estado(mensajeAyuda(), "text-warning");
            }
        }

        if (activo) {
            requestAnimationFrame(ciclo);
        }
    }

    function prepararControles(track) {
        let capacidades = {};
        try {
            capacidades = track.getCapabilities ? track.getCapabilities() : {};
        } catch (e) {
            capacidades = {};
        }

        const { btnLinterna, rangoZoom, bloqueZoom } = opciones;
        linternaEncendida = false;

        if (btnLinterna) {
            btnLinterna.classList.toggle("d-none", !capacidades.torch);
            btnLinterna.classList.remove("active");
        }

        if (rangoZoom && bloqueZoom) {
            if (capacidades.zoom && capacidades.zoom.max > capacidades.zoom.min) {
                rangoZoom.min = capacidades.zoom.min;
                rangoZoom.max = capacidades.zoom.max;
                rangoZoom.step = capacidades.zoom.step || 0.1;
                rangoZoom.value = (track.getSettings && track.getSettings().zoom) || capacidades.zoom.min;
                bloqueZoom.classList.remove("d-none");
            } else {
                bloqueZoom.classList.add("d-none");
            }
        }
    }

    async function iniciar(video, config) {
        detener();
        opciones = config;
        videoEl = video;

        // Sin pedir resolucion el navegador suele dar 640x480, insuficiente para un QR denso.
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        });

        const track = stream.getVideoTracks()[0];
        try {
            await track.applyConstraints({ advanced: [{ focusMode: "continuous" }] });
        } catch (e) {
            // No todos los equipos permiten fijar el enfoque.
        }

        video.srcObject = stream;
        await video.play();

        prepararControles(track);
        activo = true;
        procesando = false;
        inicioBusqueda = performance.now();
        ultimoJsQr = 0;
        estado("Buscando QR...", "text-muted");
        requestAnimationFrame(ciclo);
    }

    function detener() {
        activo = false;
        procesando = false;
        linternaEncendida = false;

        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }

        if (videoEl) {
            videoEl.srcObject = null;
        }
    }

    async function alternarLinterna() {
        const track = stream && stream.getVideoTracks()[0];
        if (!track) {
            return false;
        }

        linternaEncendida = !linternaEncendida;
        try {
            await track.applyConstraints({ advanced: [{ torch: linternaEncendida }] });
        } catch (e) {
            linternaEncendida = false;
        }

        return linternaEncendida;
    }

    async function ponerZoom(valor) {
        const track = stream && stream.getVideoTracks()[0];
        if (!track) {
            return;
        }

        try {
            await track.applyConstraints({ advanced: [{ zoom: Number(valor) }] });
        } catch (e) {
            // Si el equipo rechaza el zoom se deja como estaba.
        }
    }

    // Lee un QR desde una foto tomada con la camara normal (alta resolucion).
    async function leerFoto(archivo, config) {
        opciones = config || opciones;
        estado("Leyendo la foto...", "text-muted");

        let bitmap;
        try {
            bitmap = await createImageBitmap(archivo);
        } catch (e) {
            const url = URL.createObjectURL(archivo);
            bitmap = new Image();
            bitmap.src = url;
            await bitmap.decode();
            URL.revokeObjectURL(url);
        }

        const ancho = bitmap.width;
        const alto = bitmap.height;
        let hallazgo = null;

        // Se prueba a varios tamanos: a veces una foto muy grande se lee peor que reducida.
        for (const maxLado of [2400, 1600, 1100]) {
            hallazgo = await buscarQr(bitmap, ancho, alto, { usarJsQr: true, maxLado });
            if (hallazgo) {
                break;
            }
        }

        if (bitmap.close) {
            bitmap.close();
        }

        if (!hallazgo) {
            estado("No se encontro un QR en la foto. Tome la foto de frente, bien enfocada y sin reflejos.", "text-danger");
            return false;
        }

        return entregar(hallazgo.texto, hallazgo.lector + " (foto)");
    }

    return { iniciar, detener, alternarLinterna, ponerZoom, leerFoto, _bytesATexto: bytesATexto };
})();
