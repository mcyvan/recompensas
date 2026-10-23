<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
require_once('../app/functions/operador_qr.php');
verificarSesion();

// Pantalla en piloto: quien no este en la lista sigue con la de siempre.
if (!operadorUsaFlujoQr()) {
    header('Location: ingresar_tiempo_descarga.php');
    exit();
}

?>
<!doctype html>
<html lang="es">
<?php include("../app/layout/head.php"); ?>
<style>
    .btn-iniciar-descarga {
        min-height: 150px;
        font-size: 1.9rem;
        font-weight: 800;
        letter-spacing: .04em;
        border-radius: .5rem;
        line-height: 1.2;
    }

    .btn-iniciar-descarga i {
        display: block;
        font-size: 3.4rem;
        line-height: 1;
        margin-bottom: .35rem;
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

                                            <!-- QR DE LA REMISION -->
                                            <div class="m-2">
                                                <button type="button" class="btn btn-primary btn-lg w-100 py-3" id="btnEscanearQr">
                                                    <i class="bi bi-qr-code-scan"></i> Escanear QR de la remisión
                                                </button>
                                            </div>

                                            <!-- Lo leido del QR: solo informacion (nada que capturar), oculto hasta escanear -->
                                            <div id="bloqueDatosEscaneados" class="d-none">
                                                <div class="m-2">
                                                    <div class="text-muted small">Remisión</div>
                                                    <div class="fs-4 fw-bold" id="remisionTexto">-</div>
                                                    <small id="remisionInfo"></small>
                                                </div>

                                                <div class="m-2">
                                                    <div class="text-muted small">Metros</div>
                                                    <div class="fs-4 fw-bold"><span id="metrosTexto">-</span> m&sup3;</div>
                                                    <small id="metrosInfo"></small>
                                                </div>

                                                <!-- ALIADO: el telefono sale del QR de la remision, el operador no lo captura -->
                                                <div class="m-2">
                                                    <div class="text-muted small">Aliado</div>
                                                    <div id="clienteInfo" class="fw-semibold text-muted"></div>
                                                </div>

                                                <!-- Valores que se envian: los llena el QR, no se ven ni se editan -->
                                                <input type="hidden" id="remision" name="remision">
                                                <input type="hidden" id="metros" name="volumen" disabled>
                                                <input type="hidden" id="telefono" name="telefono">
                                                <input type="hidden" id="id_cliente_qr" name="id_cliente_qr">
                                                <input type="hidden" id="sin_cliente" name="sin_cliente" value="">

                                                <!-- Aparece cuando la remision, los metros y el aliado ya estan listos -->
                                                <div class="m-2 d-none" id="bloqueIniciarDescarga">
                                                    <button type="button" class="btn btn-success w-100 btn-iniciar-descarga" id="btnIniciarDescarga">
                                                        <i class="bi bi-play-circle-fill"></i>
                                                        INICIAR DESCARGA
                                                    </button>
                                                </div>
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
                                                        <span class="text-muted d-block">Aliado en obra</span>
                                                        <b id="resumenQrAliado">-</b>
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


        const telefonoInput = document.getElementById("telefono");
        const idClienteQrInput = document.getElementById("id_cliente_qr");
        const remisionInput = document.getElementById("remision");
        const metrosInput = document.getElementById("metros");
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

        const sinClienteInput = document.getElementById("sin_cliente");
        const bloqueIniciarDescarga = document.getElementById("bloqueIniciarDescarga");
        const btnIniciarDescarga = document.getElementById("btnIniciarDescarga");
        const formRemision = document.getElementById("formRemision");
        const bloqueDatosEscaneados = document.getElementById("bloqueDatosEscaneados");
        const remisionTexto = document.getElementById("remisionTexto");
        const metrosTexto = document.getElementById("metrosTexto");
        let consultandoCliente = false;
        let enviandoDescarga = false;

        // Lo que se ve de la remision son etiquetas, no campos: copian lo que dejo el QR.
        function actualizarTextosEscaneados() {
            remisionTexto.textContent = remisionInput.value || "-";
            metrosTexto.textContent = metrosInput.value || "-";
        }

        // Los metros se habilitan cuando la remision es valida.
        metrosInput.disabled = true;

        // Si el telefono del QR no es de un aliado registrado (o no vino), la remision se guarda
        // como "numero no encontrado" sola: el operador no marca nada.
        function actualizarEstadoCliente() {
            sinClienteInput.value = telefonoValido ? "" : "1";
            actualizarBotonIniciar();
        }

        // El boton grande de iniciar descarga solo aparece cuando la remision esta disponible,
        // los metros son validos y ya se termino de buscar al aliado.
        function actualizarBotonIniciar() {
            const listo = remisionValida && metrosValidos && !consultandoCliente && !enviandoDescarga;
            bloqueIniciarDescarga.classList.toggle("d-none", !listo);
        }

        function reiniciarDatosDeRemision() {
            // Cada QR escaneado empieza limpio: nada de la remision anterior (folio, metros, aliado).
            remisionValida = false;
            bloquearMetros();
            telefonoValido = false;
            consultandoCliente = false;
            telefonoInput.value = "";
            idClienteQrInput.value = "";
            sinClienteInput.value = "";
            clienteInfo.innerHTML = "Buscando aliado...";
            clienteInfo.className = "fw-semibold text-muted";
            bloqueIniciarDescarga.classList.add("d-none");
        }

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
            const etiquetas = ["REMISION", "FACTURA", "PEDIDO", "FECHA", "PLANTA", "VENDEDOR", "ARTICULO", "UNIDADES", "CANTIDAD", "VOLUMEN", "METROS", "M3", "M³", "PRECIO", "CLIENTE", "ALIADO EN OBRA", "TEL", "TELEFONO", "TELÉFONO"];
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
                cliente: extraerCampoQr(limpio, "CLIENTE", etiquetas),
                aliado: extraerCampoQr(limpio, "ALIADO EN OBRA", etiquetas)
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
            actualizarTextosEscaneados();
            actualizarBotonIniciar();
        }

        // Deja la pantalla como al inicio, lista para escanear otra remision.
        function reiniciarPantallaDescarga() {
            remisionInput.value = "";
            remisionInfo.innerHTML = "";
            remisionInfo.className = "";
            reiniciarDatosDeRemision();
            actualizarTextosEscaneados();
            bloqueDatosEscaneados.classList.add("d-none");
            ["qr_origen", "qr_texto", "qr_datos_json", "factura_crm", "pedido_crm", "fecha_crm", "planta_crm",
                "vendedor_crm", "articulo_crm", "cantidad_crm", "precio_crm", "cliente_crm"
            ].forEach((id) => asignarHidden(id, ""));
            document.getElementById("resumenQrRemision").classList.add("d-none");
        }

        // Los folios BLR son de block: esta pantalla es solo de descargas de concreto (folios RE).
        async function avisarRemisionDeBlock(folio) {
            await Swal.fire({
                icon: "warning",
                title: "No se puede ingresar",
                html: "La remisión <b>" + escaparHtmlOperador(folio) + "</b> es de block y no se puede ingresar.",
                confirmButtonText: "Entendido",
                confirmButtonColor: "#0d6efd",
                allowOutsideClick: false
            });

            reiniciarPantallaDescarga();
        }

        // La remision ya existe: se le dice al operador cuando se registro y se le regresa al inicio.
        async function avisarRemisionYaRegistrada(folio, datos) {
            const cuando = datos.registrada_fecha
                ? "el <b>" + escaparHtmlOperador(datos.registrada_fecha) + "</b>" +
                  (datos.registrada_hora ? " a las <b>" + escaparHtmlOperador(datos.registrada_hora) + "</b>" : "")
                : "anteriormente";
            const detalle = [
                datos.registrada_estatus ? "Estatus: " + escaparHtmlOperador(datos.registrada_estatus) : "",
                datos.registrada_camion ? "Camión: " + escaparHtmlOperador(datos.registrada_camion) : ""
            ].filter(Boolean).join(" &middot; ");

            await Swal.fire({
                icon: "warning",
                title: "Remisión ya registrada",
                html: "La remisión <b>" + escaparHtmlOperador(folio) + "</b> ya fue registrada " + cuando + "." +
                    (detalle ? "<br><span class='text-muted small'>" + detalle + "</span>" : ""),
                confirmButtonText: "Entendido",
                confirmButtonColor: "#0d6efd",
                allowOutsideClick: false
            });

            reiniciarPantallaDescarga();
        }

        function validarRemisionCapturada(valor) {
            valor = extraerFolioRemision(valor);
            remisionInput.value = valor;
            actualizarTextosEscaneados();

            if (!folioRemisionValido(valor)) {
                const esBlock = /^BLR/.test(valor);
                remisionInfo.innerHTML = esBlock ?
                    "Las remisiones de block (BLR) no se pueden ingresar" :
                    "Debe ser RE seguido de 6 dígitos (ej. RE123456)";
                remisionInfo.className = "text-danger";
                remisionValida = false;
                bloquearMetros();
                if (esBlock) {
                    avisarRemisionDeBlock(valor);
                }
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
                        avisarRemisionYaRegistrada(valor, data);
                        return false;
                    }

                    remisionValida = true;
                    remisionInfo.innerHTML = "Disponible";
                    remisionInfo.className = "text-success";
                    metrosInput.disabled = false;
                    actualizarBotonIniciar();
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

        // El numero del QR no es de un aliado registrado (no existe, esta mal o no vino): la
        // remision se registra igual, marcada como "numero no encontrado".
        function numeroNoEncontrado(motivo) {
            telefonoValido = false;
            consultandoCliente = false;
            idClienteQrInput.value = "";
            clienteInfo.innerHTML = motivo + ". Se registrará como número no encontrado.";
            clienteInfo.className = "fw-semibold text-warning";
            actualizarEstadoCliente();
        }

        function validarTelefonoCapturado(telefono, idCliente = "") {
            telefono = String(telefono || "").replace(/\D/g, "");
            idCliente = String(idCliente || "").replace(/\D/g, "");
            telefonoInput.value = telefono;
            idClienteQrInput.value = idCliente;

            if (!idCliente && telefono.length < 10) {
                numeroNoEncontrado(telefono ? "El número del QR no es válido" : "El QR no trae número");
                return Promise.resolve(false);
            }

            consultandoCliente = true;
            actualizarBotonIniciar();

            let formData = new FormData();
            formData.append("telefono", telefono);
            formData.append("id_cliente_qr", idCliente);

            return fetch("../ajax/ajax_validar_telefono.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    consultandoCliente = false;
                    if (data.existe) {
                        telefonoValido = true;
                        telefonoInput.value = data.telefono || telefono;
                        idClienteQrInput.value = data.id_cliente || idCliente;
                        clienteInfo.innerHTML = escaparHtmlOperador([data.nombre, data.apellido_p, data.apellido_m].filter(Boolean).join(" "));
                        clienteInfo.className = "fw-semibold text-success";
                        actualizarEstadoCliente();
                        return true;
                    }

                    numeroNoEncontrado("Número no encontrado en el sistema");
                    return false;
                })
                .catch(() => {
                    consultandoCliente = false;
                    numeroNoEncontrado("No se pudo validar el número");
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
            ponerResumen("resumenQrAliado", datos.aliado);
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
            reiniciarDatosDeRemision();
            // La informacion de la remision solo aparece cuando se escanea el QR.
            bloqueDatosEscaneados.classList.remove("d-none");
            llenarCamposCrm(datos);
            mostrarResumenQr(datos);

            // Solo se lee el numero del aliado que trae el QR de la remision; si no es de un
            // aliado registrado la remision queda como "numero no encontrado".
            const busquedaAliado = datos.telefono
                ? validarTelefonoCapturado(datos.telefono)
                : Promise.resolve(numeroNoEncontrado("El QR no trae número"));

            const volumenAutomatico = datos.volumen || datos.volumenDesdeConcreto;
            if (datos.remision) {
                await validarRemisionCapturada(datos.remision);
            }

            if (volumenAutomatico && !metrosInput.disabled) {
                metrosInput.value = volumenAutomatico;
                metrosInput.dispatchEvent(new Event("input"));
            } else if (!metrosInput.disabled) {
                // Sin cantidad en el QR no hay forma de capturarla a mano: no se puede iniciar.
                metrosInfo.innerHTML = "El QR no trae la cantidad de concreto. No se puede iniciar esta descarga.";
                metrosInfo.className = "text-danger";
            }

            await busquedaAliado;
            actualizarBotonIniciar();

            return datos;
        }

        /* ================= QR ================= */
        btnEscanearQr.addEventListener("click", async function() {
            abrirEscanerQr("remision");
        });

        async function abrirEscanerQr(modo) {
            qrModo = modo;
            modalQrTitulo.innerHTML = "Escanear remision";

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
                    text: "Este navegador no soporta la lectura de QR. Abra el sistema en un navegador o celular compatible (Chrome en Android)."
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

                // Sin pedir resolucion el navegador suele dar 640x480, insuficiente para un QR
                // denso impreso con tinta. Se pide 1080p y enfoque continuo (si el equipo lo soporta).
                qrStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: {
                            ideal: "environment"
                        },
                        width: {
                            ideal: 1920
                        },
                        height: {
                            ideal: 1080
                        },
                        advanced: [{
                            focusMode: "continuous"
                        }]
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
                            const datos = await aplicarDatosQr(codigos[0].rawValue);
                            qrInfo.innerHTML = "QR detectado: " + (datos.remision || "sin remision");
                            qrInfo.className = "d-block mt-2 text-success";
                            detenerEscanerQr();
                            qrModal.hide();
                            // Si el QR trajo todo (folio disponible y metros del QR validos) la descarga
                            // arranca sola; si falta algo se queda el boton para completarlo a mano.
                            if (datos.volumen || datos.volumenDesdeConcreto) {
                                iniciarDescargaAutomatica();
                            }
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
                    text: "Revise que el navegador tenga permiso para usar la camara y vuelva a intentar."
                });
            }
        }

        modalQrElement.addEventListener("hidden.bs.modal", detenerEscanerQr);

        /* ================= METROS ================= */
        metrosInput.addEventListener("input", function() {

            let valor = parseFloat(this.value);

            if (valor < 1 || valor > 8) {
                metrosInfo.innerHTML = "La cantidad del QR debe ser de 1 a 8 m³";
                metrosInfo.className = "text-danger";
                metrosValidos = false;
            } else if ((valor * 10) % 5 !== 0) {
                metrosInfo.innerHTML = "La cantidad del QR debe ser en .0 o .5";
                metrosInfo.className = "text-danger";
                metrosValidos = false;
            } else {
                metrosInfo.innerHTML = "OK";
                metrosInfo.className = "text-success";
                metrosValidos = true;
            }

            actualizarTextosEscaneados();
            actualizarBotonIniciar();
        });

        /* ================= INICIAR DESCARGA ================= */
        // Segundos de la cuenta regresiva con la que arranca sola la descarga despues de escanear
        // el QR (la descarga solo se cancela desde el sistema de administracion, por eso hay un
        // margen para detenerla). Con 0 se desactiva y siempre se usa el boton.
        const SEGUNDOS_AUTO_INICIO = 3;

        function puedeIniciarDescarga() {
            return remisionValida && metrosValidos && !consultandoCliente && !enviandoDescarga;
        }

        function textoResumenDescarga() {
            const aliado = telefonoValido ? clienteInfo.textContent.replace(/^Aliado:\s*/, "") : "Número no encontrado";
            return "<b>" + escaparHtmlOperador(remisionInput.value) + "</b> &middot; " +
                escaparHtmlOperador(metrosInput.value) + " m&sup3;<br>" +
                "<span class='text-muted'>Aliado:</span> " + escaparHtmlOperador(aliado);
        }

        function enviarInicioDescarga() {
            enviandoDescarga = true;
            btnIniciarDescarga.disabled = true;
            actualizarEstadoCliente();
            formRemision.submit();
        }

        // Boton INICIAR DESCARGA: pregunta antes de iniciar.
        async function iniciarDescargaConPregunta() {
            if (!puedeIniciarDescarga()) {
                return;
            }

            const confirmacion = await Swal.fire({
                icon: "question",
                title: "¿Iniciar la descarga?",
                html: textoResumenDescarga(),
                showCancelButton: true,
                confirmButtonText: "Sí, iniciar",
                cancelButtonText: "No",
                confirmButtonColor: "#198754",
                reverseButtons: true,
                focusCancel: true
            });

            if (confirmacion.isConfirmed) {
                enviarInicioDescarga();
            }
        }

        // Al escanear un QR completo (remision disponible, metros del QR validos, aliado ya buscado)
        // la descarga arranca sola despues de unos segundos, con opcion de cancelar.
        async function iniciarDescargaAutomatica() {
            if (SEGUNDOS_AUTO_INICIO <= 0 || !puedeIniciarDescarga()) {
                return;
            }

            const resultado = await Swal.fire({
                icon: "info",
                title: "Iniciando descarga...",
                html: textoResumenDescarga(),
                timer: SEGUNDOS_AUTO_INICIO * 1000,
                timerProgressBar: true,
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: "Iniciar ya",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#198754",
                reverseButtons: true,
                allowOutsideClick: false
            });

            const seCancelo = resultado.dismiss === Swal.DismissReason.cancel ||
                resultado.dismiss === Swal.DismissReason.backdrop ||
                resultado.dismiss === Swal.DismissReason.esc;
            if (!seCancelo && puedeIniciarDescarga()) {
                enviarInicioDescarga();
            }
        }

        btnIniciarDescarga.addEventListener("click", iniciarDescargaConPregunta);

        function escaparHtmlOperador(valor) {
            return String(valor || "").replace(/[&<>"']/g, (c) => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            })[c]);
        }

        /* ================= SUBMIT ================= */
        // Respaldo: el envio solo sale por el boton de iniciar descarga, pero si algo mas
        // dispara el formulario se valida igual y el aliado no encontrado se marca solo.
        formRemision.addEventListener("submit", function(e) {

            if (!remisionValida || !metrosValidos) {
                e.preventDefault();
                alert("Captura la remisión y los metros correctamente");
                return;
            }

            sinClienteInput.value = telefonoValido ? "" : "1";
        });
    </script>

</body>

</html>
