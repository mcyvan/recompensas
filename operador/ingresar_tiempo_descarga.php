<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();

?>
<!doctype html>
<html lang="es">
<?php include("../app/layout/head.php"); ?>
<style>
    .telefono-input::placeholder {
        color: #9aa4b2;
        opacity: 1;
    }
</style>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
    <div class="app-wrapper">

        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <main class="app-main">
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">

                        <div class="row justify-content-center">
                            <div class="col-sm-6">

                                <div class="card bg-light m-3">
                                    <form id="formRemision" action="../controller/controller_registrar_remision.php" method="POST">

                                        <div class="card-header">
                                            <b>MENU OPERADOR</b>
                                        </div>

                                        <div class="card-body">

                                            <!-- TELEFONO -->
                                            <div class="m-2">
                                                <label><b>1.</b> Teléfono:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="bi bi-telephone"></i>
                                                    </span>
                                                    <input type="text"
                                                        class="form-control telefono-input"
                                                        id="telefono"
                                                        name="telefono"
                                                        inputmode="numeric"
                                                        maxlength="10"
                                                        placeholder="Escribe los 10 digitos"
                                                        autocomplete="off"
                                                        required>
                                                    <button type="button" class="btn btn-outline-secondary" id="btnEscanearClienteQr">
                                                        Escanear
                                                    </button>
                                                </div>

                                                <input type="hidden" id="id_cliente_qr" name="id_cliente_qr">
                                                <small id="clienteInfo"></small>
                                            </div>

                                            <!-- REMISION -->
                                            <div class="m-2">
                                                <label><b>2.</b> Remisión:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="remision" name="remision"
                                                        maxlength="8" pattern="RE[0-9]{6}" inputmode="text"
                                                        placeholder="RE123456" autocomplete="off" disabled>
                                                    <button type="button" class="btn btn-outline-secondary" id="btnEscanearQr" disabled>
                                                        Escanear QR
                                                    </button>
                                                </div>
                                                <small id="remisionInfo"></small>
                                            </div>

                                            <!-- METROS -->
                                            <div class="m-2">
                                                <label><b>3.</b> Metros:</label>
                                                <div class="input-group">
                                                    <input type="number"
                                                        class="form-control"
                                                        id="metros"
                                                        name="volumen"
                                                        min="1"
                                                        max="8"
                                                        step="0.5"
                                                        placeholder="0.0"
                                                        disabled>
                                                    <span class="input-group-text">m&sup3;</span>
                                                </div>

                                                <small id="metrosInfo"></small>
                                            </div>

                                            <input type="hidden" id="qr_origen" name="qr_origen">
                                            <input type="hidden" id="qr_texto" name="qr_texto">
                                            <input type="hidden" id="qr_datos_json" name="qr_datos_json">
                                            <input type="hidden" id="factura_crm" name="factura_crm">
                                            <input type="hidden" id="pedido_crm" name="pedido_crm">
                                            <input type="hidden" id="fecha_crm" name="fecha_crm">
                                            <input type="hidden" id="planta_crm" name="planta_crm">
                                            <input type="hidden" id="vendedor_crm" name="vendedor_crm">
                                            <input type="hidden" id="articulo_crm" name="articulo_crm">
                                            <input type="hidden" id="cantidad_crm" name="cantidad_crm">
                                            <input type="hidden" id="precio_crm" name="precio_crm">
                                            <input type="hidden" id="cliente_crm" name="cliente_crm">

                                            <div id="resumenQrRemision" class="border rounded bg-white p-3 m-2 d-none">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <b>Datos de remision</b>
                                                    <span class="badge text-bg-primary">QR CRM</span>
                                                </div>
                                                <div class="row g-2 small">
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Remision</span>
                                                        <b id="resumenQrFolio">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Telefono</span>
                                                        <b id="resumenQrTelefono">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Factura</span>
                                                        <b id="resumenQrFactura">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Pedido</span>
                                                        <b id="resumenQrPedido">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Fecha</span>
                                                        <b id="resumenQrFecha">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Planta</span>
                                                        <b id="resumenQrPlanta">-</b>
                                                    </div>
                                                    <div class="col-12">
                                                        <span class="text-muted d-block">Cliente CRM</span>
                                                        <b id="resumenQrCliente">-</b>
                                                    </div>
                                                    <div class="col-12">
                                                        <span class="text-muted d-block">Articulo</span>
                                                        <b id="resumenQrArticulo">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Cantidad</span>
                                                        <b id="resumenQrCantidad">-</b>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted d-block">Precio</span>
                                                        <b id="resumenQrPrecio">-</b>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="card-footer d-flex justify-content-between">
                                            <a href="menu_operador.php" class="btn btn-secondary">Regresar</a>
                                            <button type="submit" class="btn btn-primary">Iniciar Descarga</button>
                                        </div>

                                    </form>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <div class="modal fade" id="modalQr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalQrTitulo">Escanear QR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <video id="qrVideo" class="w-100 rounded bg-dark" autoplay muted playsinline></video>
                    <small id="qrInfo" class="d-block mt-2 text-muted">
                        Apunte la camara al QR.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>

    <script>
        let telefonoValido = false;
        let remisionValida = false;
        let metrosValidos = false;

        let timeoutTelefono = null;
        let timeoutRemision = null;

        const telefonoInput = document.getElementById("telefono");
        const idClienteQrInput = document.getElementById("id_cliente_qr");
        const remisionInput = document.getElementById("remision");
        const metrosInput = document.getElementById("metros");
        const btnEscanearClienteQr = document.getElementById("btnEscanearClienteQr");
        const btnEscanearQr = document.getElementById("btnEscanearQr");
        const modalQrElement = document.getElementById("modalQr");
        const modalQrTitulo = document.getElementById("modalQrTitulo");
        const qrVideo = document.getElementById("qrVideo");
        const qrInfo = document.getElementById("qrInfo");

        const clienteInfo = document.getElementById("clienteInfo");
        const remisionInfo = document.getElementById("remisionInfo");
        const metrosInfo = document.getElementById("metrosInfo");

        let qrModal = null;
        let qrStream = null;
        let qrDetector = null;
        let qrEscaneando = false;
        let qrModo = "remision";

        // bloquear
        remisionInput.disabled = true;
        metrosInput.disabled = true;
        btnEscanearQr.disabled = true;

        function extraerFolioRemision(texto) {
            const limpio = String(texto || "").trim().toUpperCase();
            const normalizarFolio = (folio) => {
                const valor = String(folio || "").trim().toUpperCase();
                return /^\d{6}$/.test(valor) ? "RE" + valor : valor;
            };
            const porEtiqueta = limpio.match(/REMISION\s*:?\s*([A-Z0-9-]+?)(?=FACTURA|PEDIDO|FECHA|PLANTA|VENDEDOR|ARTICULO|UNIDADES|PRECIO|CLIENTE|TEL|$)/);
            if (porEtiqueta) {
                return normalizarFolio(porEtiqueta[1]);
            }

            const porFormatoAnterior = limpio.match(/\bRE\d+\b/);
            if (porFormatoAnterior) {
                return normalizarFolio(porFormatoAnterior[0]);
            }

            const porFormatoCrm = limpio.match(/\b[A-Z]{2,5}\d{3,}[A-Z0-9-]*\b/);
            return normalizarFolio(porFormatoCrm ? porFormatoCrm[0] : limpio);
        }

        function extraerCampoQr(texto, etiqueta, siguientes) {
            const indice = texto.indexOf(etiqueta);
            if (indice === -1) {
                return "";
            }

            let inicio = indice + etiqueta.length;
            if (texto[inicio] === ":") {
                inicio++;
            }

            let fin = texto.length;
            siguientes.forEach((siguiente) => {
                const posicion = texto.indexOf(siguiente, inicio);
                if (posicion !== -1 && posicion < fin) {
                    fin = posicion;
                }
            });

            return texto.slice(inicio, fin).replace(/^[:\s]+|[:\s]+$/g, "").trim();
        }

        function normalizarDecimal(valor) {
            const coincidencia = String(valor || "").match(/[0-9]+(?:[.,][0-9]+)?/);
            return coincidencia ? coincidencia[0].replace(",", ".") : "";
        }

        function normalizarFechaCrm(valor) {
            const coincidencia = String(valor || "").match(/(\d{2})\/(\d{2})\/(\d{4})/);
            return coincidencia ? coincidencia[3] + "-" + coincidencia[2] + "-" + coincidencia[1] : "";
        }

        function extraerConceptosQr(texto, etiquetas) {
            const conceptos = [];
            let posicion = 0;

            while (true) {
                const indiceArticulo = texto.indexOf("ARTICULO", posicion);
                if (indiceArticulo === -1) {
                    break;
                }

                let finConcepto = texto.length;
                const siguienteArticulo = texto.indexOf("ARTICULO", indiceArticulo + 8);
                const indiceCliente = texto.indexOf("CLIENTE", indiceArticulo + 8);
                const indiceTel = texto.indexOf("TEL", indiceArticulo + 8);

                [siguienteArticulo, indiceCliente, indiceTel].forEach((indice) => {
                    if (indice !== -1 && indice < finConcepto) {
                        finConcepto = indice;
                    }
                });

                const bloque = texto.slice(indiceArticulo, finConcepto);
                const articulo = extraerCampoQr(bloque, "ARTICULO", etiquetas);
                const cantidadTexto = extraerCampoQr(bloque, "CANTIDAD", etiquetas) ||
                    extraerCampoQr(bloque, "UNIDADES", etiquetas);

                conceptos.push({
                    articulo,
                    cantidad: normalizarDecimal(cantidadTexto),
                    esConcreto: /\bCONCRETO\b/.test(articulo)
                });

                posicion = finConcepto;
            }

            return conceptos;
        }

        function extraerDatosQr(texto) {
            const original = String(texto || "").trim();
            const limpio = original.toUpperCase();
            const etiquetas = ["REMISION", "FACTURA", "PEDIDO", "FECHA", "PLANTA", "VENDEDOR", "ARTICULO", "UNIDADES", "CANTIDAD", "VOLUMEN", "METROS", "M3", "M³", "PRECIO", "CLIENTE", "TEL", "TELEFONO", "TELÉFONO"];
            const telefono = limpio.match(/(?:TEL|TELEFONO|TELÉFONO)\s*:?\s*([0-9]{10})/);
            const idCliente = limpio.match(/(?:CLIENTE_ID|ID_CLIENTE|ID)\s*:?\s*([0-9]+)/);
            const volumen = extraerCampoQr(limpio, "VOLUMEN", etiquetas) ||
                extraerCampoQr(limpio, "METROS", etiquetas) ||
                extraerCampoQr(limpio, "M3", etiquetas) ||
                extraerCampoQr(limpio, "M³", etiquetas);
            const cantidad = extraerCampoQr(limpio, "CANTIDAD", etiquetas) ||
                extraerCampoQr(limpio, "UNIDADES", etiquetas);
            const conceptos = extraerConceptosQr(limpio, etiquetas);
            const conceptoConcreto = conceptos.find((concepto) => concepto.esConcreto);
            const articuloPrincipal = conceptoConcreto?.articulo || extraerCampoQr(limpio, "ARTICULO", etiquetas);
            const cantidadPrincipal = conceptoConcreto?.cantidad || normalizarDecimal(cantidad);

            return {
                raw: original,
                idCliente: idCliente ? idCliente[1] : "",
                telefono: telefono ? telefono[1] : "",
                remision: extraerFolioRemision(limpio),
                volumen: normalizarDecimal(volumen),
                volumenDesdeConcreto: conceptoConcreto ? conceptoConcreto.cantidad : "",
                factura: extraerCampoQr(limpio, "FACTURA", etiquetas),
                pedido: extraerCampoQr(limpio, "PEDIDO", etiquetas),
                fecha: normalizarFechaCrm(extraerCampoQr(limpio, "FECHA", etiquetas)),
                planta: extraerCampoQr(limpio, "PLANTA", etiquetas),
                vendedor: extraerCampoQr(limpio, "VENDEDOR", etiquetas),
                articulo: articuloPrincipal,
                cantidad: cantidadPrincipal,
                precio: normalizarDecimal(extraerCampoQr(limpio, "PRECIO", etiquetas)),
                cliente: extraerCampoQr(limpio, "CLIENTE", etiquetas)
            };
        }

        function folioRemisionValido(valor) {
            return /^RE\d{6}$/.test(valor);
        }

        function detenerEscanerQr() {
            qrEscaneando = false;

            if (qrStream) {
                qrStream.getTracks().forEach(track => track.stop());
                qrStream = null;
            }

            qrVideo.srcObject = null;
        }

        function bloquearMetros() {
            metrosInput.value = "";
            metrosInput.disabled = true;
            metrosValidos = false;
            metrosInfo.innerHTML = "";
            metrosInfo.className = "";
        }

        function validarRemisionCapturada(valor, opciones = {}) {
            const enfocarMetros = opciones.enfocarMetros === true;
            valor = extraerFolioRemision(valor);
            remisionInput.value = valor;
            clearTimeout(timeoutRemision);

            if (!folioRemisionValido(valor)) {
                remisionInfo.innerHTML = "Debe ser RE seguido de 6 dígitos (ej. RE123456)";
                remisionInfo.className = "text-danger";
                remisionValida = false;
                bloquearMetros();
                return Promise.resolve(false);
            }

            remisionInfo.innerHTML = "Validando...";
            remisionInfo.className = "text-muted";

            let formData = new FormData();
            formData.append("remision", valor);

            return fetch("../ajax/ajax_validar_remision.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.existe) {
                        remisionValida = false;
                        remisionInfo.innerHTML = "Ya registrada";
                        remisionInfo.className = "text-danger";
                        bloquearMetros();
                        return false;
                    }

                    remisionValida = true;
                    remisionInfo.innerHTML = "Disponible";
                    remisionInfo.className = "text-success";
                    metrosInput.disabled = false;
                    if (enfocarMetros) {
                        metrosInput.focus();
                    }
                    return true;
                })
                .catch(() => {
                    remisionValida = false;
                    remisionInfo.innerHTML = "Error al validar remision";
                    remisionInfo.className = "text-danger";
                    bloquearMetros();
                    return false;
                });
        }

        function validarTelefonoCapturado(telefono, idCliente = "") {
            telefono = String(telefono || "").replace(/\D/g, "");
            idCliente = String(idCliente || "").replace(/\D/g, "");
            telefonoInput.value = telefono;
            idClienteQrInput.value = idCliente;
            clearTimeout(timeoutTelefono);

            if (!idCliente && telefono.length < 10) {
                clienteInfo.innerHTML = "Debe tener 10 dígitos";
                clienteInfo.className = "text-danger";
                telefonoValido = false;
                remisionInput.disabled = true;
                bloquearMetros();
                return Promise.resolve(false);
            }

            let formData = new FormData();
            formData.append("telefono", telefono);
            formData.append("id_cliente_qr", idCliente);

            return fetch("../ajax/ajax_validar_telefono.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.existe) {
                        telefonoValido = true;
                        telefonoInput.value = data.telefono || telefono;
                        idClienteQrInput.value = data.id_cliente || idCliente;
                        clienteInfo.innerHTML = "Cliente: " + data.nombre + " " + data.apellido_p + " " + data.apellido_m;
                        clienteInfo.className = "text-success";
                        remisionInput.disabled = false;
                        btnEscanearQr.disabled = false;
                        return true;
                    }

                    telefonoValido = false;
                    clienteInfo.innerHTML = "Cliente no encontrado";
                    clienteInfo.className = "text-danger";
                    remisionInput.disabled = true;
                    btnEscanearQr.disabled = true;
                    bloquearMetros();
                    return false;
                })
                .catch(() => {
                    telefonoValido = false;
                    clienteInfo.innerHTML = "Error al validar";
                    clienteInfo.className = "text-danger";
                    remisionInput.disabled = true;
                    btnEscanearQr.disabled = true;
                    bloquearMetros();
                    return false;
                });
        }

        function asignarHidden(id, valor) {
            const input = document.getElementById(id);
            if (input) {
                input.value = valor || "";
            }
        }

        function ponerResumen(id, valor) {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.textContent = valor || "-";
            }
        }

        function mostrarResumenQr(datos) {
            document.getElementById("resumenQrRemision").classList.remove("d-none");
            ponerResumen("resumenQrFolio", datos.remision);
            ponerResumen("resumenQrTelefono", datos.telefono);
            ponerResumen("resumenQrFactura", datos.factura);
            ponerResumen("resumenQrPedido", datos.pedido);
            ponerResumen("resumenQrFecha", datos.fecha);
            ponerResumen("resumenQrPlanta", datos.planta);
            ponerResumen("resumenQrCliente", datos.cliente);
            ponerResumen("resumenQrArticulo", datos.articulo);
            ponerResumen("resumenQrCantidad", datos.cantidad);
            ponerResumen("resumenQrPrecio", datos.precio);
        }

        function llenarCamposCrm(datos) {
            asignarHidden("qr_origen", "CRM");
            asignarHidden("qr_texto", datos.raw);
            asignarHidden("qr_datos_json", JSON.stringify(datos));
            asignarHidden("factura_crm", datos.factura);
            asignarHidden("pedido_crm", datos.pedido);
            asignarHidden("fecha_crm", datos.fecha);
            asignarHidden("planta_crm", datos.planta);
            asignarHidden("vendedor_crm", datos.vendedor);
            asignarHidden("articulo_crm", datos.articulo);
            asignarHidden("cantidad_crm", datos.cantidad);
            asignarHidden("precio_crm", datos.precio);
            asignarHidden("cliente_crm", datos.cliente);
        }

        async function aplicarDatosQr(textoQr) {
            const datos = extraerDatosQr(textoQr);
            llenarCamposCrm(datos);
            mostrarResumenQr(datos);

            if (datos.telefono) {
                await validarTelefonoCapturado(datos.telefono);
            }

            if (datos.remision) {
                remisionInput.disabled = false;
                await validarRemisionCapturada(datos.remision, {
                    enfocarMetros: true
                });
            }

            const volumenAutomatico = datos.volumen || datos.volumenDesdeConcreto;
            if (volumenAutomatico && !metrosInput.disabled) {
                metrosInput.value = volumenAutomatico;
                metrosInput.dispatchEvent(new Event("input"));
            } else if (datos.cantidad && !metrosInput.disabled) {
                metrosInfo.innerHTML = "No se encontro cantidad de concreto. Capture m³.";
                metrosInfo.className = "text-warning";
            }

            if (!datos.telefono && !telefonoValido) {
                clienteInfo.innerHTML = "El QR no trae telefono. Capturelo manualmente.";
                clienteInfo.className = "text-warning";
            }

            return datos;
        }

        /* ================= TELEFONO ================= */
        telefonoInput.addEventListener("input", function() {

            // 🔥 solo números
            this.value = this.value.replace(/\D/g, '');
            let telefono = this.value;
            idClienteQrInput.value = "";

            clearTimeout(timeoutTelefono);

            if (telefono.length < 10) {
                clienteInfo.innerHTML = "Debe tener 10 dígitos";
                clienteInfo.className = "text-danger";

                telefonoValido = false;
                remisionInput.disabled = true;
                btnEscanearQr.disabled = true;
                bloquearMetros();
                return;
            }

            timeoutTelefono = setTimeout(() => {

                let formData = new FormData();
                formData.append("telefono", telefono);

                fetch("../ajax/ajax_validar_telefono.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {

                        if (data.existe) {
                        telefonoValido = true;
                        telefonoInput.value = data.telefono || telefono;
                        idClienteQrInput.value = data.id_cliente || "";

                            clienteInfo.innerHTML = "Cliente: " + data.nombre + " " + data.apellido_p + " " + data.apellido_m;
                            clienteInfo.className = "text-success";

                            remisionInput.disabled = false;
                            btnEscanearQr.disabled = false;
                        } else {
                            telefonoValido = false;

                            clienteInfo.innerHTML = "Cliente no encontrado";
                            clienteInfo.className = "text-danger";

                            remisionInput.disabled = true;
                            btnEscanearQr.disabled = true;
                            bloquearMetros();
                        }

                    })
                    .catch(() => {
                        clienteInfo.innerHTML = "Error al validar";
                        clienteInfo.className = "text-danger";
                        btnEscanearQr.disabled = true;
                    });

            }, 400);
        });

        /* ================= REMISION ================= */
        remisionInput.addEventListener("input", function() {

            let valor = extraerFolioRemision(this.value);
            this.value = valor;

            clearTimeout(timeoutRemision);

            timeoutRemision = setTimeout(() => {
                validarRemisionCapturada(valor);
            }, 900);
        });

        /* ================= QR ================= */
        btnEscanearClienteQr.addEventListener("click", function() {
            abrirEscanerQr("cliente");
        });

        btnEscanearQr.addEventListener("click", async function() {
            abrirEscanerQr("remision");
        });

        async function abrirEscanerQr(modo) {
            qrModo = modo;
            modalQrTitulo.innerHTML = modo === "cliente" ? "Escanear cliente" : "Escanear remision";

            if (!window.isSecureContext) {
                Swal.fire({
                    icon: "warning",
                    title: "Camara bloqueada",
                    text: "Para escanear desde celular, abra el sistema con HTTPS. En HTTP el navegador bloquea la camara."
                });
                return;
            }

            if (!("BarcodeDetector" in window)) {
                Swal.fire({
                    icon: "warning",
                    title: "Camara QR no disponible",
                    text: "Este navegador no soporta lectura QR directa. Puede capturar el folio manualmente."
                });
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                Swal.fire({
                    icon: "warning",
                    title: "Camara no disponible",
                    text: "El navegador no permite usar la camara en esta pagina."
                });
                return;
            }

            try {
                qrModal = qrModal || new bootstrap.Modal(modalQrElement);
                qrModal.show();

                qrDetector = qrDetector || new BarcodeDetector({
                    formats: ["qr_code"]
                });

                qrStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: {
                            ideal: "environment"
                        }
                    },
                    audio: false
                });

                qrVideo.srcObject = qrStream;
                await qrVideo.play();

                qrEscaneando = true;
                qrInfo.innerHTML = "Buscando QR...";
                qrInfo.className = "d-block mt-2 text-muted";

                const escanear = async () => {
                    if (!qrEscaneando) {
                        return;
                    }

                    try {
                        const codigos = await qrDetector.detect(qrVideo);

                        if (codigos.length > 0) {
                            if (qrModo === "cliente") {
                                const datosCliente = extraerDatosQr(codigos[0].rawValue);
                                const telefonoQr = datosCliente.telefono || (String(codigos[0].rawValue).match(/[0-9]{10}/) || [""])[0];
                                const clienteDetectado = await validarTelefonoCapturado(telefonoQr, datosCliente.idCliente);
                                qrInfo.innerHTML = clienteDetectado ? "Cliente detectado" : "No se encontro telefono valido";
                                qrInfo.className = clienteDetectado ? "d-block mt-2 text-success" : "d-block mt-2 text-danger";
                            } else {
                                const datos = await aplicarDatosQr(codigos[0].rawValue);
                                qrInfo.innerHTML = "QR detectado: " + (datos.remision || "sin remision");
                                qrInfo.className = "d-block mt-2 text-success";
                            }
                            detenerEscanerQr();
                            qrModal.hide();
                            return;
                        }
                    } catch (error) {
                        qrInfo.innerHTML = "No fue posible leer el QR. Intente acercar la camara.";
                        qrInfo.className = "d-block mt-2 text-danger";
                    }

                    requestAnimationFrame(escanear);
                };

                requestAnimationFrame(escanear);
            } catch (error) {
                detenerEscanerQr();
                Swal.fire({
                    icon: "error",
                    title: "No se pudo abrir la camara",
                    text: "Revise permisos del navegador o capture el folio manualmente."
                });
            }
        }

        modalQrElement.addEventListener("hidden.bs.modal", detenerEscanerQr);

        /* ================= METROS ================= */
        metrosInput.addEventListener("input", function() {

            let valor = parseFloat(this.value);

            if (valor < 1 || valor > 8) {
                metrosInfo.innerHTML = "1 a 8 m³";
                metrosInfo.className = "text-danger";
                metrosValidos = false;
                return;
            }

            if ((valor * 10) % 5 !== 0) {
                metrosInfo.innerHTML = "Solo .0 o .5";
                metrosInfo.className = "text-danger";
                metrosValidos = false;
                return;
            }

            metrosInfo.innerHTML = "OK";
            metrosInfo.className = "text-success";
            metrosValidos = true;
        });

        /* ================= SUBMIT ================= */
        document.getElementById("formRemision").addEventListener("submit", function(e) {

            if (!telefonoValido || !remisionValida || !metrosValidos) {
                e.preventDefault();
                alert("Completa correctamente los datos");
            }
        });
    </script>

</body>

</html>
