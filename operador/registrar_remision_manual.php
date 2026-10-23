<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/remisiones.php");
verificarSesion();
verificarPermisoRegistrarRemisionManual($pdo);

$ahora = date('Y-m-d\TH:i');
$mensaje_error = $_SESSION['mensaje_registro_remision_error'] ?? null;
$mensaje_correcto = $_SESSION['mensaje_registro_remision_correcto'] ?? null;
unset($_SESSION['mensaje_registro_remision_error'], $_SESSION['mensaje_registro_remision_correcto']);
$operadores = obtenerOperadores($pdo);
?>
<!doctype html>
<html lang="es">
<?php include("../app/layout/head.php"); ?>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
    <?php if ($mensaje_error): ?>
        <script>
            Swal.fire({
                title: "",
                text: <?= json_encode($mensaje_error, JSON_UNESCAPED_UNICODE) ?>,
                icon: "error"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: <?= json_encode($mensaje_correcto, JSON_UNESCAPED_UNICODE) ?>,
                icon: "success"
            });
        </script>
    <?php endif; ?>

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <?php include("../app/layout/menu.php"); ?>

        <main class="app-main">
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row justify-content-center">
                            <div class="col-sm-7">
                                <div class="card bg-light m-3">
                                    <form id="formRemisionManual" action="../controller/controller_registrar_remision_manual.php" method="POST">
                                        <div class="card-header">
                                            <b>REGISTRO MANUAL DE REMISION</b>
                                        </div>

                                        <div class="card-body">
                                            <div class="alert alert-warning mb-3">
                                                Use esta opcion solo cuando no se pudo iniciar o finalizar el tiempo en la app.
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label"><b>Telefono</b></label>
                                                    <input type="text"
                                                        class="form-control"
                                                        id="telefonoManual"
                                                        name="telefono"
                                                        inputmode="numeric"
                                                        maxlength="10"
                                                        pattern="[0-9]{10}"
                                                        placeholder="6141234567"
                                                        required>
                                                    <small id="clienteManualInfo"></small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label"><b>Remision</b></label>
                                                    <input type="text"
                                                        class="form-control"
                                                        id="remisionManual"
                                                        name="remision"
                                                        maxlength="8"
                                                        pattern="RE[0-9]{6}"
                                                        placeholder="RE123456"
                                                        autocomplete="off"
                                                        required>
                                                    <small id="remisionManualInfo"></small>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label"><b>Metros</b></label>
                                                    <input type="number"
                                                        class="form-control"
                                                        name="volumen"
                                                        min="1"
                                                        max="8"
                                                        step="0.5"
                                                        required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label"><b>Chofer</b></label>
                                                    <select name="id_operador" class="form-control" required>
                                                        <option value="">Seleccione chofer</option>
                                                        <?php foreach ($operadores as $operador): ?>
                                                            <option value="<?= (int) $operador['id_usuario'] ?>">
                                                                <?= htmlspecialchars($operador['usuario'], ENT_QUOTES, 'UTF-8') ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label"><b>Inicio descarga</b></label>
                                                    <input type="datetime-local"
                                                        class="form-control"
                                                        id="horaInicioManual"
                                                        name="hora_inicio"
                                                        max="<?= $ahora ?>"
                                                        required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label"><b>Fin descarga</b></label>
                                                    <input type="datetime-local"
                                                        class="form-control"
                                                        id="horaFinManual"
                                                        name="hora_fin"
                                                        max="<?= $ahora ?>"
                                                        required>
                                                    <small id="tiempoManualInfo"></small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-footer d-flex flex-column align-items-stretch">
                                            <button type="submit" class="btn btn-primary m-2">GUARDAR</button>
                                            <a href="../remisiones/index.php" class="btn btn-secondary m-2">CANCELAR</a>
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

    <?php include("../app/layout/footer_links.php"); ?>

    <script>
        const telefonoManual = document.getElementById("telefonoManual");
        const remisionManual = document.getElementById("remisionManual");
        const formRemisionManual = document.getElementById("formRemisionManual");
        const horaInicioManual = document.getElementById("horaInicioManual");
        const horaFinManual = document.getElementById("horaFinManual");
        const clienteManualInfo = document.getElementById("clienteManualInfo");
        const remisionManualInfo = document.getElementById("remisionManualInfo");
        const tiempoManualInfo = document.getElementById("tiempoManualInfo");
        let timeoutTelefonoManual = null;
        let timeoutRemisionManual = null;
        let envioConfirmado = false;

        function extraerFolioRemision(texto) {
            const limpio = String(texto || "").trim().toUpperCase();
            const porEtiqueta = limpio.match(/REMISION\s*:?\s*([A-Z0-9-]+?)(?=FACTURA|PEDIDO|FECHA|PLANTA|VENDEDOR|ARTICULO|UNIDADES|PRECIO|CLIENTE|TEL|$)/);
            if (porEtiqueta) {
                return normalizarFolioRemision(porEtiqueta[1]);
            }

            const porFormatoAnterior = limpio.match(/\bRE\d+\b/);
            if (porFormatoAnterior) {
                return normalizarFolioRemision(porFormatoAnterior[0]);
            }

            const porFormatoCrm = limpio.match(/\b[A-Z]{2,5}\d{3,}[A-Z0-9-]*\b/);
            return normalizarFolioRemision(porFormatoCrm ? porFormatoCrm[0] : limpio);
        }

        function normalizarFolioRemision(valor) {
            const limpio = String(valor || "").trim().toUpperCase();
            return /^\d{6}$/.test(limpio) ? "RE" + limpio : limpio;
        }

        function folioRemisionValido(valor) {
            return /^RE\d{6}$/.test(valor);
        }

        telefonoManual.addEventListener("input", function() {
            this.value = this.value.replace(/\D/g, "");
            clearTimeout(timeoutTelefonoManual);

            if (this.value.length < 10) {
                clienteManualInfo.innerHTML = "Debe tener 10 digitos";
                clienteManualInfo.className = "text-danger";
                return;
            }

            timeoutTelefonoManual = setTimeout(() => {
                const formData = new FormData();
                formData.append("telefono", this.value);

                fetch("../ajax/ajax_validar_telefono.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.existe) {
                            clienteManualInfo.innerHTML = "Cliente: " + data.nombre + " " + data.apellido_p + " " + data.apellido_m;
                            clienteManualInfo.className = "text-success";
                        } else {
                            clienteManualInfo.innerHTML = "Cliente no encontrado";
                            clienteManualInfo.className = "text-danger";
                        }
                    })
                    .catch(() => {
                        clienteManualInfo.innerHTML = "No se pudo validar en pantalla. Al guardar se validara en servidor.";
                        clienteManualInfo.className = "text-muted";
                    });
            }, 400);
        });

        remisionManual.addEventListener("input", function() {
            this.value = extraerFolioRemision(this.value);
            clearTimeout(timeoutRemisionManual);

            if (!folioRemisionValido(this.value)) {
                remisionManualInfo.innerHTML = "Debe ser RE seguido de 6 digitos (ej. RE123456)";
                remisionManualInfo.className = "text-danger";
                return;
            }

            timeoutRemisionManual = setTimeout(() => {
                const formData = new FormData();
                formData.append("remision", this.value);

                fetch("../ajax/ajax_validar_remision.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.existe) {
                            remisionManualInfo.innerHTML = "Ya registrada";
                            remisionManualInfo.className = "text-danger";
                        } else {
                            remisionManualInfo.innerHTML = "Disponible";
                            remisionManualInfo.className = "text-success";
                        }
                    })
                    .catch(() => {
                        remisionManualInfo.innerHTML = "No se pudo validar en pantalla. Al guardar se validara en servidor.";
                        remisionManualInfo.className = "text-muted";
                    });
            }, 400);
        });

        function validarTiempoManual() {
            if (!horaInicioManual.value || !horaFinManual.value) {
                tiempoManualInfo.innerHTML = "";
                return true;
            }

            const inicio = new Date(horaInicioManual.value);
            const fin = new Date(horaFinManual.value);

            if (fin < inicio) {
                tiempoManualInfo.innerHTML = "El fin no puede ser menor al inicio";
                tiempoManualInfo.className = "text-danger";
                return false;
            }

            const minutos = Math.floor((fin - inicio) / 60000);
            tiempoManualInfo.innerHTML = "Tiempo: " + minutos + " min";
            tiempoManualInfo.className = minutos <= 45 ? "text-success" : "text-warning";
            return true;
        }

        horaInicioManual.addEventListener("change", validarTiempoManual);
        horaFinManual.addEventListener("change", validarTiempoManual);

        function obtenerMinutosManual() {
            if (!horaInicioManual.value || !horaFinManual.value) {
                return null;
            }

            const inicio = new Date(horaInicioManual.value);
            const fin = new Date(horaFinManual.value);
            return Math.floor((fin - inicio) / 60000);
        }

        formRemisionManual.addEventListener("submit", function(event) {
            if (envioConfirmado) {
                return;
            }

            event.preventDefault();

            if (!validarTiempoManual()) {
                return;
            }

            remisionManual.value = extraerFolioRemision(remisionManual.value);
            if (!folioRemisionValido(remisionManual.value)) {
                remisionManualInfo.innerHTML = "Debe ser RE seguido de 6 digitos (ej. RE123456)";
                remisionManualInfo.className = "text-danger";
                remisionManual.focus();
                return;
            }

            const minutos = obtenerMinutosManual();
            const folio = remisionManual.value || "Sin folio";
            const telefono = telefonoManual.value || "Sin telefono";

            Swal.fire({
                title: "Confirmar registro manual",
                html: "Se registrara la remision <b>" + folio + "</b><br>" +
                    "Telefono: <b>" + telefono + "</b><br>" +
                    "Tiempo capturado: <b>" + minutos + " min</b>",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Si, guardar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#0d6efd"
            }).then((resultado) => {
                if (resultado.isConfirmed) {
                    envioConfirmado = true;
                    formRemisionManual.submit();
                }
            });
        });
    </script>
</body>

</html>
